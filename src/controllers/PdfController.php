<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\helpers\Locale;

use Craft;
use craft\web\Response;

use yii\web\HttpException;

class PdfController extends BaseController
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionIndex(): Response|string
    {
        $siteHandle = $this->request->getParam('site');
        $site = Craft::$app->getSites()->getPrimarySite();

        if ($siteHandle) {
            if ($requestedSite = Craft::$app->getSites()->getSiteByHandle($siteHandle)) {
                $site = $requestedSite;
            }
        }

        $listId = (int)$this->request->getRequiredParam('listId');
        $list = Wishlist::$plugin->getLists()->getListById($listId);

        if (!$list) {
            throw new HttpException(404, Craft::t('wishlist', 'Unable to find the requested list.'));
        }

        $this->enforceEnabledList($list);

        $reference = $this->request->getParam('reference');
        $canModifyList = Wishlist::$plugin->getLists()->canModifyListContent($list);

        if (!$canModifyList && !Wishlist::$plugin->getLists()->hasMatchingReference($list, $reference)) {
            throw new HttpException(403, Craft::t('wishlist', 'A valid shared-list reference is required.'));
        }

        // Switch to use the correct site/language
        $originalLanguage = Craft::$app->language;
        $originalFormattingLocale = Craft::$app->formattingLocale;

        Locale::switchAppLanguage($site->language);

        $format = $this->request->getParam('format');

        try {
            $pdf = Wishlist::$plugin->getPdf()->renderPdf($list, $site, is_string($format) ? $format : null);
        } finally {
            // Rendering failures must not leak the requested site's locale into error handling.
            Locale::switchAppLanguage($originalLanguage, $originalFormattingLocale);
        }

        $filenameFormat = Wishlist::$plugin->getSettings()->pdfFilenameFormat;
        $filename = $this->getView()->renderObjectTemplate($filenameFormat, $list);

        if (!$filename) {
            $filename = 'Wishlist';
        }

        $options = [
            'mimeType' => 'application/pdf',
        ];

        $attach = $this->request->getParam('attach');

        if ($attach) {
            $options['inline'] = true;
        }

        if ($format === 'plain') {
            return $pdf;
        }

        return Craft::$app->getResponse()->sendContentAsFile($pdf, $filename . '.pdf', $options);
    }
}
