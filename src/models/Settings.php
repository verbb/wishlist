<?php
namespace verbb\wishlist\models;

use craft\base\Model;

class Settings extends Model
{
    // Properties
    // =========================================================================

    public string $pluginName = 'Wishlist';
    public bool $showListInfoTab = true;

    // Lists
    public bool $allowDuplicates = false;
    public bool $manageDisabledLists = true;
    public bool $mergeLastListOnLogin = false;
    public bool $purgeInactiveLists = true;
    public string $purgeInactiveListsDuration = 'P3M';
    public string $purgeInactiveGuestListsDuration = 'P1D';
    public bool $purgeEmptyListsOnly = true;
    public bool $purgeEmptyGuestListsOnly = true;
    public mixed $cookieExpiry = 0;
    public bool $updateListSearchIndexes = true;
    public bool $updateItemSearchIndexes = true;

    // PDF
    public string $pdfFilenameFormat = 'Wishlist-{id}';
    public string $pdfPath = '_pdf/wishlist';
    public bool $pdfAllowRemoteImages = false;
    public string $pdfPaperSize = 'letter';
    public string $pdfPaperOrientation = 'portrait';

}