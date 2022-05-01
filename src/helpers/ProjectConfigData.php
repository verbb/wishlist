<?php
namespace verbb\wishlist\helpers;

use verbb\wishlist\Wishlist;

use Craft;
use craft\db\Query;

class ProjectConfigData
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $configData = [];

        $configData['listTypes'] = self::_getListTypeData();

        return array_filter($configData);
    }

    
    // Private Methods
    // =========================================================================

    private static function _getListTypeData(): array
    {
        $data = [];

        foreach (Wishlist::$plugin->getListTypes()->getAllListTypes() as $listType) {
            $data[$listType->uid] = $listType->getConfig();
        }

        return $data;
    }
}
