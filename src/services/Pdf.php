<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\events\PdfEvent;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\web\View;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
use yii\base\Exception;

class Pdf extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';

    // Public Methods
    // =========================================================================

    public function renderPdf($list): string
    {
        $settings = Wishlist::$plugin->getSettings();

        $request = Craft::$app->getRequest();
        $format = $request->getParam('format');

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

        try {
            $html = $view->renderTemplate($templatePath, compact('list'));
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

        // Should throw an error if not writable
        FileHelper::isWritable($dompdfTempDir);
        FileHelper::isWritable($dompdfLogFile);

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
        } else {
            $dompdf->render();
        }

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