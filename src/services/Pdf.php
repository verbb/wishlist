<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\PdfEvent;
use verbb\wishlist\models\Settings;

use Craft;
use craft\helpers\FileHelper;
use craft\models\Site;
use craft\web\View;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;

class Pdf extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    public const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';

    // Public Methods
    // =========================================================================

    public function renderPdf(ListElement $list, ?Site $site = null): string
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

        $request = Craft::$app->getRequest();
        $format = $request->getParam('format');

        $currentSite = $site ?? Craft::$app->getSites()->getCurrentSite();

        $templatePath = $settings->pdfPath;

        // Trigger a 'beforeRenderPdf' event
        $event = new PdfEvent([
            'list' => $list,
            'template' => $templatePath,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PDF, $event);

        if ($event->pdf !== null) {
            return $event->pdf;
        }

        // Set Craft to the site template mode
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        if (!$templatePath || !$view->doesTemplateExist($templatePath)) {
            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            throw new Exception('PDF template file does not exist.');
        }

        $variables = [
            'list' => $list,
            'currentSite' => $currentSite,
        ];

        try {
            $html = $view->renderTemplate($templatePath, $variables);
        } catch (\Exception $e) {
            // Set the pdf html to the render error.
            Craft::error('List PDF render error. List ID: ' . $list->id . '. ' . $e->getMessage());

            Craft::$app->getErrorHandler()->logException($e);
            $html = Craft::t('events', 'An error occurred while generating this PDF.');
        }

        // Restore the original template mode
        $view->setTemplateMode($oldTemplateMode);

        $dompdf = new Dompdf();

        // Set the config options
        $pathService = Craft::$app->getPath();
        $dompdfTempDir = $pathService->getTempPath() . DIRECTORY_SEPARATOR . 'wishlist_dompdf';
        $dompdfFontCache = $pathService->getCachePath() . DIRECTORY_SEPARATOR . 'wishlist_dompdf';
        $dompdfLogFile = $pathService->getLogPath() . DIRECTORY_SEPARATOR . 'wishlist_dompdf.htm';

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

        $isRemoteEnabled = $settings->pdfAllowRemoteImages;

        $options = new Options();
        $options->setTempDir($dompdfTempDir);
        $options->setFontCache($dompdfFontCache);
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled($isRemoteEnabled);

        // Paper Size and Orientation
        $pdfPaperSize = $settings->pdfPaperSize;
        $pdfPaperOrientation = $settings->pdfPaperOrientation;
        $dompdf->setPaper($pdfPaperSize, $pdfPaperOrientation);

        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);

        if ($format === 'plain') {
            return $html;
        }

        $dompdf->render();

        // Trigger an 'afterRenderPdf' event
        $event = new PdfEvent([
            'list' => $list,
            'template' => $templatePath,
            'pdf' => $dompdf->output(),
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER_PDF, $event);

        return $event->pdf;
    }
}