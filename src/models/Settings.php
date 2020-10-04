<?php
namespace verbb\wishlist\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $pluginName = 'Wishlist';
    public $showListInfoTab = true;

    // Lists
    public $allowDuplicates = false;
    public $manageDisabledLists = true;
    public $mergeLastListOnLogin = false;
    public $purgeInactiveLists = true;
    public $purgeInactiveListsDuration = 'P3M';
    public $purgeInactiveGuestListsDuration = 'P1D';
    public $purgeEmptyListsOnly = true;
    public $purgeEmptyGuestListsOnly = true;

    // PDF
    public $pdfFilenameFormat = 'Wishlist-{id}';
    public $pdfPath = '_pdf/wishlist';
    public $pdfAllowRemoteImages = false;
    public $pdfPaperSize = 'letter';
    public $pdfPaperOrientation = 'portrait';

}