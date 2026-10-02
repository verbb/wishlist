<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\PdfEvent;
use verbb\wishlist\models\Settings;

use Craft;
use craft\db\Query;
use craft\helpers\FileHelper;
use craft\models\Site;
use craft\web\Request as WebRequest;
use craft\web\View;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\caching\TagDependency;
use yii\web\HttpException;
use yii\web\TooManyRequestsHttpException;

use Throwable;

class Pdf extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    public const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';

    private const CLIENT_RENDER_RATE_LIMIT = 5;
    private const LIST_RENDER_RATE_LIMIT = 20;
    private const RENDER_RATE_WINDOW = 900;
    private const RENDER_CACHE_DURATION = 300;
    private const MAX_CONCURRENT_RENDERS = 4;
    private const MAX_HTML_BYTES = 2097152;
    private const RENDER_CACHE_PREFIX = 'wishlist.pdf-render.';
    private const RENDER_CACHE_TAG_PREFIX = 'wishlist.pdf-list.';
    private const RENDER_MUTEX_PREFIX = 'wishlist.pdf-render-lock.';
    private const RENDER_RATE_CACHE_PREFIX = 'wishlist.pdf-rate.';
    private const RENDER_RATE_MUTEX_PREFIX = 'wishlist.pdf-rate-lock.';
    private const RENDER_SLOT_MUTEX_PREFIX = 'wishlist.pdf-render-slot.';

    // Public Methods
    // =========================================================================

    public function renderPdf(ListElement $list, ?Site $site = null, ?string $format = null): string
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();
        $currentSite = $site ?? Craft::$app->getSites()->getCurrentSite();
        $format ??= Craft::$app->getRequest()->getParam('format');
        $format = $format === 'plain' ? 'plain' : 'pdf';

        // Before-render handlers may return request-specific output, so only the default pipeline is cacheable.
        $useCache = $list->id !== null && empty($list->getDirtyAttributes()) && empty($list->getDirtyFields()) && !$this->hasEventHandlers(self::EVENT_BEFORE_RENDER_PDF);
        $cacheKey = $this->_renderCacheKey($list, $currentSite, $format, $settings);

        if ($useCache && ($cachedOutput = $this->_cachedOutput($cacheKey)) !== null) {
            return $this->_returnCachedOutput($list, $settings->pdfPath, $format, $cachedOutput);
        }

        $mutex = null;
        $renderMutexKey = self::RENDER_MUTEX_PREFIX . hash('sha256', $cacheKey);

        try {
            $mutex = Craft::$app->getMutex();

            if (!($mutex?->acquire($renderMutexKey, 3) ?? false)) {
                $this->_rejectRender(1);
            }
        } catch (Throwable) {
            $this->_rejectRender(1);
        }

        try {
            // Recheck after serialization so followers reuse the completed render without consuming a budget.
            if ($useCache && ($cachedOutput = $this->_cachedOutput($cacheKey)) !== null) {
                return $this->_returnCachedOutput($list, $settings->pdfPath, $format, $cachedOutput);
            }

            if ($retryAfter = $this->_consumeRenderRateLimits($list)) {
                $this->_rejectRender($retryAfter);
            }

            $renderSlot = $this->_acquireRenderSlot();

            if ($renderSlot === null) {
                $this->_rejectRender(1);
            }

            try {
                $result = $this->_renderUncached($list, $currentSite, $format, $settings);

                if ($useCache && $result['cacheable'] && $list->id !== null) {
                    $this->_cacheOutput($cacheKey, $list->id, $result['output']);
                }

                return $result['triggerAfter'] ? $this->_triggerAfterRenderPdf($list, $settings->pdfPath, $result['output']) : $result['output'];
            } finally {
                $this->_releaseRenderSlot($renderSlot);
            }
        } finally {
            try {
                $mutex?->release($renderMutexKey);
            } catch (Throwable) {
                // A completed render must retain its result if lock cleanup is unavailable.
            }
        }
    }

    public function invalidateListCache(int $listId): void
    {
        try {
            TagDependency::invalidate(Craft::$app->getCache(), self::RENDER_CACHE_TAG_PREFIX . $listId);
        } catch (Throwable $e) {
            Craft::warning('Unable to invalidate the Wishlist PDF cache for list ' . $listId . ': ' . $e->getMessage(), __METHOD__);
        }
    }


    // Private Methods
    // =========================================================================

    private function _renderUncached(ListElement $list, Site $currentSite, string $format, Settings $settings): array
    {
        $templatePath = $settings->pdfPath;

        // Preserve the public extension point before any template or Dompdf work begins.
        $event = new PdfEvent([
            'list' => $list,
            'template' => $templatePath,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PDF, $event);

        if ($event->pdf !== null) {
            return [
                'output' => $event->pdf,
                'cacheable' => false,
                'triggerAfter' => false,
            ];
        }

        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        try {
            if (!$templatePath || !$view->doesTemplateExist($templatePath)) {
                throw new Exception('PDF template file does not exist.');
            }

            $variables = [
                'list' => $list,
                'currentSite' => $currentSite,
            ];

            try {
                $html = $view->renderTemplate($templatePath, $variables);
            } catch (\Exception $e) {
                // Preserve the existing friendly PDF body while retaining the underlying error in logs.
                Craft::error('List PDF render error. List ID: ' . $list->id . '. ' . $e->getMessage());
                Craft::$app->getErrorHandler()->logException($e);
                $html = Craft::t('wishlist', 'An error occurred while generating this PDF.');
            }
        } finally {
            $view->setTemplateMode($oldTemplateMode);
        }

        if (strlen($html) > self::MAX_HTML_BYTES) {
            throw new HttpException(413, Craft::t('wishlist', 'The rendered PDF template exceeds the allowed size.'));
        }

        if ($format === 'plain') {
            return [
                'output' => $html,
                'cacheable' => true,
                'triggerAfter' => false,
            ];
        }

        $dompdf = new Dompdf();

        // Set the config options
        $tempPath = Craft::$app->getPath()->getTempPath();
        $dompdfTempDir = $tempPath . DIRECTORY_SEPARATOR . 'wishlist_dompdf';
        $dompdfFontCache = $tempPath . DIRECTORY_SEPARATOR . 'wishlist_dompdf';
        $dompdfLogFile = $tempPath . DIRECTORY_SEPARATOR . 'wishlist_dompdf.htm';

        // Ensure directories are created
        FileHelper::createDirectory($dompdfTempDir);
        FileHelper::createDirectory($dompdfFontCache);

        if (!FileHelper::isWritable($dompdfLogFile)) {
            throw new ErrorException("Unable to write to file: $dompdfLogFile");
        }

        if (!FileHelper::isWritable($dompdfFontCache)) {
            throw new ErrorException("Unable to write to folder: $dompdfFontCache");
        }

        if (!FileHelper::isWritable($dompdfTempDir)) {
            throw new ErrorException("Unable to write to folder: $dompdfTempDir");
        }

        $options = new Options();
        $options->setTempDir($dompdfTempDir);
        $options->setFontCache($dompdfFontCache);
        $options->setFontDir($dompdfFontCache);
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled($settings->pdfAllowRemoteImages);
        $options->setDefaultFont('sans-serif');

        // Paper Size and Orientation
        $pdfPaperSize = $settings->pdfPaperSize;
        $pdfPaperOrientation = $settings->pdfPaperOrientation;
        $dompdf->setPaper($pdfPaperSize, $pdfPaperOrientation);

        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);
        $dompdf->render();

        return [
            'output' => $dompdf->output(),
            'cacheable' => true,
            'triggerAfter' => true,
        ];
    }

    private function _triggerAfterRenderPdf(ListElement $list, string $templatePath, string $pdf): string
    {
        $event = new PdfEvent([
            'list' => $list,
            'template' => $templatePath,
            'pdf' => $pdf,
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER_PDF, $event);

        return $event->pdf;
    }

    private function _returnCachedOutput(ListElement $list, string $templatePath, string $format, string $output): string
    {
        if ($format === 'plain') {
            return $output;
        }

        $renderSlot = $this->_acquireRenderSlot();

        if ($renderSlot === null) {
            $this->_rejectRender(1);
        }

        try {
            return $this->_triggerAfterRenderPdf($list, $templatePath, $output);
        } finally {
            $this->_releaseRenderSlot($renderSlot);
        }
    }

    private function _renderCacheKey(ListElement $list, Site $site, string $format, Settings $settings): string
    {
        $request = Craft::$app->getRequest();
        $currentUser = Craft::$app->getUser()->getIdentity();
        $view = Craft::$app->getView();
        $templateFile = $view->resolveTemplate($settings->pdfPath, View::TEMPLATE_MODE_SITE);
        $listState = $list->id === null ? null : (new Query())
            ->select([
                'listUpdated' => 'lists.dateUpdated',
                'elementUpdated' => 'elements.dateUpdated',
            ])
            ->from(['lists' => '{{%wishlist_lists}}'])
            ->innerJoin(['elements' => '{{%elements}}'], '[[elements.id]] = [[lists.id]]')
            ->where(['lists.id' => $list->id])
            ->one();
        $templateState = $templateFile && is_file($templateFile) ? [
            'path' => $templateFile,
            'modified' => filemtime($templateFile),
            'size' => filesize($templateFile),
        ] : $templateFile;
        $requestState = [
            'query' => method_exists($request, 'getQueryParams') ? $request->getQueryParams() : [],
            'body' => method_exists($request, 'getBodyParams') ? $request->getBodyParams() : [],
        ];

        try {
            $sessionId = Craft::$app->getSession()->getId();
        } catch (Throwable) {
            $sessionId = null;
        }
        $context = [
            'version' => 1,
            'listId' => $list->id,
            'listState' => $listState,
            'siteId' => $site->id,
            'siteLanguage' => $site->language,
            'currentSiteId' => Craft::$app->getSites()->getCurrentSite()->id,
            'language' => Craft::$app->language,
            'formattingLocale' => Craft::$app->formattingLocale,
            'format' => $format,
            'userId' => $currentUser?->id,
            'sessionId' => $sessionId,
            'clientIp' => method_exists($request, 'getUserIP') ? $request->getUserIP() : null,
            'request' => $requestState,
            'template' => $templateState,
            'settings' => [
                'path' => $settings->pdfPath,
                'remoteImages' => $settings->pdfAllowRemoteImages,
                'paperSize' => $settings->pdfPaperSize,
                'paperOrientation' => $settings->pdfPaperOrientation,
            ],
        ];

        return self::RENDER_CACHE_PREFIX . hash('sha256', serialize($context));
    }

    private function _cachedOutput(string $cacheKey): ?string
    {
        try {
            $cached = Craft::$app->getCache()->get($cacheKey);
        } catch (Throwable) {
            $this->_rejectRender(1);
        }

        return is_array($cached) && is_string($cached['output'] ?? null) ? $cached['output'] : null;
    }

    private function _cacheOutput(string $cacheKey, int $listId, string $output): void
    {
        $cached = ['output' => $output];

        try {
            $cache = Craft::$app->getCache();
            $saved = $cache->set($cacheKey, $cached, self::RENDER_CACHE_DURATION, new TagDependency([
                'tags' => self::RENDER_CACHE_TAG_PREFIX . $listId,
            ]));

            if (!$saved || $cache->get($cacheKey) !== $cached) {
                $this->_rejectRender(1);
            }
        } catch (TooManyRequestsHttpException $e) {
            throw $e;
        } catch (Throwable) {
            $this->_rejectRender(1);
        }
    }

    private function _consumeRenderRateLimits(ListElement $list): ?int
    {
        $request = Craft::$app->getRequest();
        $identities = [
            'list' => [
                'identity' => 'list:' . ($list->id ?? $list->reference ?? 'unsaved'),
                'limit' => self::LIST_RENDER_RATE_LIMIT,
            ],
        ];

        // Console and queue callers have no client identity, but remain subject to list and concurrency limits.
        if ($request instanceof WebRequest) {
            $currentUser = Craft::$app->getUser()->getIdentity();
            $clientIdentity = $currentUser ? 'user:' . $currentUser->id : 'ip:' . ($request->getUserIP() ?: 'unknown');
            $identities['client'] = [
                'identity' => $clientIdentity,
                'limit' => self::CLIENT_RENDER_RATE_LIMIT,
            ];
        }

        $budgets = [];

        foreach ($identities as $name => $identity) {
            $identityHash = hash('sha256', $identity['identity']);
            $budgets[$name] = [
                'cacheKey' => self::RENDER_RATE_CACHE_PREFIX . $name . '.' . $identityHash,
                'mutexKey' => self::RENDER_RATE_MUTEX_PREFIX . $name . '.' . $identityHash,
                'limit' => $identity['limit'],
            ];
        }

        ksort($budgets);

        $cache = null;
        $mutex = null;
        $now = time();
        $acquiredLocks = [];
        $previousEntries = [];
        $writtenBudgets = [];

        try {
            $cache = Craft::$app->getCache();
            $mutex = Craft::$app->getMutex();

            foreach ($budgets as $budget) {
                if (!($mutex?->acquire($budget['mutexKey'], 3) ?? false)) {
                    return 1;
                }

                $acquiredLocks[] = $budget['mutexKey'];
            }

            $entries = [];
            $retryAfter = null;

            foreach ($budgets as $name => $budget) {
                $storedEntry = $cache->get($budget['cacheKey']);
                $isCurrentEntry = is_array($storedEntry) && isset($storedEntry['count'], $storedEntry['resetAt']) && (int)$storedEntry['resetAt'] > $now;
                $entry = $isCurrentEntry ? $storedEntry : [
                    'count' => 0,
                    'resetAt' => $now + self::RENDER_RATE_WINDOW,
                ];

                if ((int)$entry['count'] >= $budget['limit']) {
                    $retryAfter = max($retryAfter ?? 1, (int)$entry['resetAt'] - $now);
                }

                $previousEntries[$name] = $isCurrentEntry ? $storedEntry : false;
                $entry['count'] = (int)$entry['count'] + 1;
                $entries[$name] = $entry;
            }

            if ($retryAfter !== null) {
                return max(1, $retryAfter);
            }

            foreach ($budgets as $name => $budget) {
                $resetAt = max($now + 1, (int)$entries[$name]['resetAt']);

                if (!$cache->set($budget['cacheKey'], $entries[$name], max(1, $resetAt - $now))) {
                    $this->_restoreRenderRateLimits($budgets, $previousEntries, $writtenBudgets, $now);

                    return 1;
                }

                $writtenBudgets[] = $name;
            }

            foreach ($budgets as $name => $budget) {
                if ($cache->get($budget['cacheKey']) !== $entries[$name]) {
                    $this->_restoreRenderRateLimits($budgets, $previousEntries, $writtenBudgets, $now);

                    return 1;
                }
            }

            return null;
        } catch (Throwable) {
            try {
                $this->_restoreRenderRateLimits($budgets, $previousEntries, $writtenBudgets, $now);
            } catch (Throwable) {
                // The request remains blocked if cache recovery is unavailable.
            }

            return 1;
        } finally {
            foreach (array_reverse($acquiredLocks) as $mutexKey) {
                try {
                    $mutex?->release($mutexKey);
                } catch (Throwable) {
                    // The request remains bounded even if lock cleanup is unavailable.
                }
            }
        }
    }

    private function _restoreRenderRateLimits(array $budgets, array $previousEntries, array $writtenBudgets, int $now): void
    {
        $cache = Craft::$app->getCache();

        foreach (array_reverse($writtenBudgets) as $name) {
            $cacheKey = $budgets[$name]['cacheKey'];
            $previousEntry = $previousEntries[$name];

            if ($previousEntry === false) {
                $cache->delete($cacheKey);

                continue;
            }

            $resetAt = max($now + 1, (int)$previousEntry['resetAt']);
            $cache->set($cacheKey, $previousEntry, max(1, $resetAt - $now));
        }
    }

    private function _acquireRenderSlot(): ?string
    {
        try {
            $mutex = Craft::$app->getMutex();

            for ($slot = 0; $slot < self::MAX_CONCURRENT_RENDERS; $slot++) {
                $mutexKey = self::RENDER_SLOT_MUTEX_PREFIX . $slot;

                if ($mutex?->acquire($mutexKey, 0) ?? false) {
                    return $mutexKey;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function _releaseRenderSlot(string $mutexKey): void
    {
        try {
            Craft::$app->getMutex()?->release($mutexKey);
        } catch (Throwable) {
            // Yii auto-releases process-held locks at shutdown if explicit cleanup is unavailable.
        }
    }

    private function _rejectRender(int $retryAfter): never
    {
        $response = Craft::$app->getResponse();

        if (method_exists($response, 'getHeaders')) {
            $response->getHeaders()->set('Retry-After', (string)max(1, $retryAfter));
        }

        throw new TooManyRequestsHttpException(Craft::t('wishlist', 'Too many PDF generation requests. Please try again later.'));
    }
}
