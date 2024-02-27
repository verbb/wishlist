<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\helpers\Locale;

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

        $siteHandle = $request->getParam('site');
        $site = Craft::$app->getSites()->getPrimarySite();

        if ($siteHandle) {
            if ($requestedSite = Craft::$app->getSites()->getSiteByHandle($siteHandle)) {
                $site = $requestedSite;
            }
        }

        $listId = $request->getRequiredParam('listId');
        $list = Wishlist::$plugin->getLists()->getListById($listId);

        // Switch to use the correct site/language
        $originalLanguage = Craft::$app->language;
        $originalFormattingLocale = Craft::$app->formattingLocale;

        Locale::switchAppLanguage($site->language);

        $pdf = Wishlist::$plugin->getPdf()->renderPdf($list, $site);

        // Set previous language back
        Locale::switchAppLanguage($originalLanguage, $originalFormattingLocale);

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
