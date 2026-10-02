<?php
namespace verbb\wishlist\models;

use Craft;
use craft\base\Model;
use verbb\wishlist\Wishlist;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Wishlist';
    public bool $showListInfoTab = true;

    // Lists
    public bool $allowDuplicates = false;
    // TODO: Default to false in the next major version.
    public bool $allowGetListActions = true;
    public bool $manageDisabledLists = true;
    public bool $mergeLastListOnLogin = false;
    public bool $purgeInactiveLists = true;
    public string $purgeInactiveListsDuration = 'P3M';
    public string $purgeInactiveGuestListsDuration = 'P1D';
    public bool $purgeEmptyListsOnly = true;
    public bool $purgeEmptyGuestListsOnly = false;
    public mixed $cookieExpiry = 0;
    public bool $updateListSearchIndexes = true;
    public bool $updateItemSearchIndexes = true;
    public ?string $defaultCpItemElementType = null;
    public bool $allowDraftElements = false;
    public bool $allowRevisionElements = false;
    public bool $allowInactiveElements = false;
    public bool $allowCrossSiteElements = false;
    public bool $allowNonPublicElements = false;

    // PDF
    public string $pdfFilenameFormat = 'Wishlist-{id}';
    public string $pdfPath = '_pdf/wishlist';
    public bool $pdfAllowRemoteImages = true;
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';

    // Email
    public ?string $templateEmail = null;
    public bool $attachPdfToEmail = false;


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName'], 'trim'];
        $rules[] = [['pluginName', 'pdfPath', 'pdfFilenameFormat'], 'required'];
        $rules[] = [['defaultCpItemElementType'], 'validateDefaultCpItemElementType'];

        return $rules;
    }

    public function validateDefaultCpItemElementType(string $attribute): void
    {
        if ($this->defaultCpItemElementType === null || $this->defaultCpItemElementType === '') {
            $this->defaultCpItemElementType = null;

            return;
        }

        $supported = Wishlist::$plugin->getItems()->getSupportedElementTypes();

        if (!in_array($this->defaultCpItemElementType, $supported, true)) {
            $this->addError($attribute, Craft::t('wishlist', '“{type}” is not a supported element type.', [
                'type' => $this->defaultCpItemElementType,
            ]));
        }
    }

}
