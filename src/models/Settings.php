<?php
namespace verbb\wishlist\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public $pluginName = 'Wishlist';
    public $allowDuplicates = false;
    public $purgeInactiveLists = false;
    public $purgeInactiveListsDuration = 'P3M';
    public $purgeInactiveGuestListsDuration = 'P1D';

}