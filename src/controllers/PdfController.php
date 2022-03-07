<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;

use Craft;
use craft\web\Controller;

use yii\console\Response;

class PdfController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionIndex(): \craft\web\Response|string|Response
    {
        $request = Craft::$app->getRequest();

        $listId = $request->getRequiredParam('listId');
        $list = Wishlist::$plugin->getLists()->getListById($listId);

        $pdf = Wishlist::$plugin->getPdf()->renderPdf($list);

        $filenameFormat = Wishlist::$plugin->getSettings()->pdfFilenameFormat;
        $filename = $this->getView()->renderObjectTemplate($filenameFormat, $list);

        if (!$filename) {
            $filename = 'Wishlist';
        }

        $options = [
            'mimeType' => 'application/pdf',
        ];

        $format = $request->getParam('format');
        $attach = $request->getParam('attach');

        if ($attach) {
            $options['inline'] = true;
        }

        if ($format === 'plain') {
            return $pdf;
        }

        return Craft::$app->getResponse()->sendContentAsFile($pdf, $filename . '.pdf', $options);
    }
}
