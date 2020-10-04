<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;

use Craft;
use craft\web\Controller;

use yii\web\HttpException;
use yii\web\Response;

class PdfController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $request = Craft::$app->getRequest();

        $listId = $request->getRequiredParam('listId');
        $list = Wishlist::$plugin->getLists()->getListById($listId);

        $pdf = Wishlist::$plugin->getPdf()->renderPdf($list);

        $filenameFormat = Wishlist::$plugin->getSettings()->pdfFilenameFormat;
        $filename = $this->getView()->renderObjectTemplate($filenameFormat, $list);

        if (!$filename) {
            $filename = 'Wishist';
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
        } else {
            return Craft::$app->getResponse()->sendContentAsFile($pdf, $filename . '.pdf', $options);
        }
    }
}
