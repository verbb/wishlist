<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\models\Settings;

use Craft;
use craft\db\Query;
use craft\helpers\StringHelper;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

use Throwable;

class BaseController extends Controller
{
    // Constants
    // =========================================================================

    private const GUEST_LIST_RATE_LIMIT = 10;
    private const GUEST_ITEM_RATE_LIMIT = 100;
    private const GUEST_CREATION_RATE_WINDOW = 900;
    private const GUEST_LIST_LIMIT = 20;
    private const GUEST_ITEM_LIMIT = 500;
    private const GUEST_CREATION_CACHE_PREFIX = 'wishlist.guest-creation.';
    private const GUEST_CREATION_MUTEX_PREFIX = 'wishlist.guest-creation-lock.';
    private const GUEST_STORAGE_MUTEX_PREFIX = 'wishlist.guest-storage-lock.';


    // Protected Methods
    // =========================================================================

    protected function runGuestStorageAction(callable $callback, ?string $sessionId = null): mixed
    {
        if (Craft::$app->getUser()->getIdentity()) {
            return $callback();
        }

        $sessionId ??= Wishlist::$plugin->getLists()->createList()->sessionId;

        if (!is_string($sessionId) || $sessionId === '') {
            $this->_rejectGuestCreation(1);
        }

        $mutex = Craft::$app->getMutex();
        $mutexKey = self::GUEST_STORAGE_MUTEX_PREFIX . hash('sha256', $sessionId);

        try {
            if (!($mutex?->acquire($mutexKey, 3) ?? false)) {
                $this->_rejectGuestCreation(1);
            }
        } catch (Throwable) {
            $this->_rejectGuestCreation(1);
        }

        try {
            // A limit reached part-way through a batch must not leave earlier rows behind.
            return Craft::$app->getDb()->transaction($callback);
        } finally {
            try {
                $mutex?->release($mutexKey);
            } catch (Throwable) {
                // The completed request must retain its result if lock cleanup is unavailable.
            }
        }
    }

    protected function saveListWithGuestLimits(ListElement $list): bool
    {
        if ($list->id || Craft::$app->getUser()->getIdentity()) {
            return Wishlist::$plugin->getLists()->saveElement($list);
        }

        return $this->_saveWithGuestLimits(
            'list',
            $list->sessionId,
            self::GUEST_LIST_RATE_LIMIT,
            self::GUEST_LIST_LIMIT,
            fn(): bool => Wishlist::$plugin->getLists()->saveElement($list),
        );
    }

    protected function saveItemWithGuestLimits(Item $item, ListElement $list): bool
    {
        if ($item->id || Craft::$app->getUser()->getIdentity()) {
            return Wishlist::$plugin->getItems()->saveElement($item);
        }

        return $this->_saveWithGuestLimits(
            'item',
            $list->sessionId,
            self::GUEST_ITEM_RATE_LIMIT,
            self::GUEST_ITEM_LIMIT,
            fn(): bool => Wishlist::$plugin->getItems()->saveElement($item),
        );
    }

    protected function enforceItemRequestLimit(int $itemCount, int $limit): void
    {
        if ($itemCount > $limit) {
            $this->_rejectGuestCreation(self::GUEST_CREATION_RATE_WINDOW, Craft::t('wishlist', 'A maximum of {limit} items can be submitted at once.', [
                'limit' => $limit,
            ]));
        }
    }

    protected function enforceEnabledList(?ListElement $list): void
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

        // If it's disabled, and should we check?
        if ($list && !$list->enabled && !$settings->manageDisabledLists) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    protected function enforceListPermissions(ListElement $list, bool $enforceOwner = true): void
    {
        if (!$list->getType()) {
            Craft::error('Attempting to access a list that doesn’t have a type', __METHOD__);
            throw new HttpException(404);
        }

        // If this is a front-end request, ensure that it's the owner of the list making changes
        if ($enforceOwner) {
            if (Craft::$app->getRequest()->getIsSiteRequest()) {
                $currentUser = Craft::$app->getUser()->getIdentity();

                // If an admin, assume they have permission to edit another list
                if (Craft::$app->getUser()->getIsAdmin()) {
                    return;
                }

                // If logged in, must be the list owner or have delegated permission
                if ($currentUser) {
                    if ($currentUser->id === $list->userId) {
                        return;
                    }

                    if (Wishlist::$plugin->getLists()->canManageOthersList($list)) {
                        return;
                    }

                    throw new HttpException(403);
                }

                if (Wishlist::$plugin->getLists()->isListOwner($list)) {
                    return;
                }

                throw new HttpException(403);
            }

            $this->requirePermission('wishlist-manageListType:' . $list->getType()->uid);
        }
    }

    protected function returnSuccess(string $message, array $params = [], ?object $object = null): Response
    {
        // Try and determine the action automatically
        $action = debug_backtrace()[1]['function'] ?? '';
        $action = str_replace('action', '', $action);
        $action = StringHelper::toKebabCase($action);

        if ($action) {
            $params['action'] = $action;
        }

        if ($this->request->getAcceptsJson()) {
            $params['success'] = true;

            return $this->asJson($params);
        }

        $this->setSuccessFlash(Craft::t('wishlist', $message));

        if ($this->request->getIsPost()) {

            // Pass object to redirect for URL variables
            return $this->redirectToPostedUrl($object);
        }

        return $this->redirect($this->request->referrer);
    }

    protected function returnError(string $message, array $params = []): ?Response
    {
        $error = Craft::t('wishlist', $message);

        // Try and determine the action automatically
        $action = debug_backtrace()[1]['function'] ?? '';
        $action = str_replace('action', '', $action);
        $action = StringHelper::toKebabCase($action);

        if ($action) {
            $params['action'] = $action;
        }

        if ($this->request->getAcceptsJson()) {
            $params['error'] = $error;

            return $this->asJson($params);
        }

        $this->setFailFlash($error);

        if ($this->request->getIsPost()) {
            if ($params) {
                Craft::$app->getUrlManager()->setRouteParams($params);
            }

            return null;
        }

        return $this->redirect($this->request->referrer);
    }


    // Private Methods
    // =========================================================================

    private function _saveWithGuestLimits(string $resource, ?string $sessionId, int $rateLimit, int $activeLimit, callable $save): bool
    {
        if (!is_string($sessionId) || $sessionId === '') {
            $this->_rejectGuestCreation(1);
        }

        $clientIdentity = 'ip:' . ($this->request->getUserIP() ?: 'unknown');
        $identities = [
            'client' => $clientIdentity,
            'guest' => 'guest:' . $sessionId,
        ];
        $budgets = [];

        foreach ($identities as $name => $identity) {
            $identityHash = hash('sha256', $identity);
            $budgets[$name] = [
                'cacheKey' => self::GUEST_CREATION_CACHE_PREFIX . $resource . '.' . $name . '.' . $identityHash,
                'mutexKey' => self::GUEST_CREATION_MUTEX_PREFIX . $resource . '.' . $name . '.' . $identityHash,
            ];
        }

        ksort($budgets);

        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $acquiredLocks = [];
        $previousEntries = [];
        $writtenBudgets = [];

        try {
            try {
                foreach ($budgets as $budget) {
                    if (!($mutex?->acquire($budget['mutexKey'], 3) ?? false)) {
                        $this->_rejectGuestCreation(1);
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
                        'resetAt' => $now + self::GUEST_CREATION_RATE_WINDOW,
                    ];

                    if ((int)$entry['count'] >= $rateLimit) {
                        $retryAfter = max($retryAfter ?? 1, (int)$entry['resetAt'] - $now);
                    }

                    $previousEntries[$name] = $isCurrentEntry ? $storedEntry : false;
                    $entry['count'] = (int)$entry['count'] + 1;
                    $entries[$name] = $entry;
                }

                if ($retryAfter !== null) {
                    $this->_rejectGuestCreation(max(1, $retryAfter));
                }

                if ($this->_guestActiveCount($resource, $sessionId) >= $activeLimit) {
                    $this->_rejectGuestCreation(self::GUEST_CREATION_RATE_WINDOW);
                }

                foreach ($budgets as $name => $budget) {
                    $resetAt = max($now + 1, (int)$entries[$name]['resetAt']);
                    $writtenBudgets[] = $name;

                    if (!$cache->set($budget['cacheKey'], $entries[$name], max(1, $resetAt - $now))) {
                        $this->_restoreGuestCreationBudgets($budgets, $previousEntries, $writtenBudgets, $now);
                        $this->_rejectGuestCreation(1);
                    }
                }

                foreach ($budgets as $name => $budget) {
                    if ($cache->get($budget['cacheKey']) !== $entries[$name]) {
                        $this->_restoreGuestCreationBudgets($budgets, $previousEntries, $writtenBudgets, $now);
                        $this->_rejectGuestCreation(1);
                    }
                }
            } catch (TooManyRequestsHttpException $e) {
                throw $e;
            } catch (Throwable) {
                try {
                    $this->_restoreGuestCreationBudgets($budgets, $previousEntries, $writtenBudgets, $now);
                } catch (Throwable) {
                    // The request remains blocked if cache recovery is unavailable.
                }

                $this->_rejectGuestCreation(1);
            }

            return $save();
        } finally {
            foreach (array_reverse($acquiredLocks) as $mutexKey) {
                try {
                    $mutex?->release($mutexKey);
                } catch (Throwable) {
                    // The completed write must retain its result if lock cleanup is unavailable.
                }
            }
        }
    }

    private function _guestActiveCount(string $resource, string $sessionId): int
    {
        $query = (new Query())
            ->from(['lists' => '{{%wishlist_lists}}'])
            ->innerJoin(['listElements' => '{{%elements}}'], '[[listElements.id]] = [[lists.id]]')
            ->where([
                'lists.sessionId' => $sessionId,
                'lists.userId' => null,
                'listElements.dateDeleted' => null,
            ]);

        if ($resource === 'item') {
            $query
                ->innerJoin(['items' => '{{%wishlist_items}}'], '[[items.listId]] = [[lists.id]]')
                ->innerJoin(['itemElements' => '{{%elements}}'], '[[itemElements.id]] = [[items.id]]')
                ->andWhere(['itemElements.dateDeleted' => null]);
        }

        return (int)$query->count();
    }

    private function _restoreGuestCreationBudgets(array $budgets, array $previousEntries, array $writtenBudgets, int $now): void
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

    private function _rejectGuestCreation(int $retryAfter, ?string $message = null): never
    {
        Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, $retryAfter));

        throw new TooManyRequestsHttpException($message ?? Craft::t('wishlist', 'Too many guest wishlist changes. Please try again later.'));
    }
}
