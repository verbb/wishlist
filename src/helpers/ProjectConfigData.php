<?php
namespace verbb\wishlist\helpers;

use Craft;
use craft\db\Query;

class ProjectConfigData
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $output = [];

        $output['listTypes'] = self::_getListTypeData();

        return $output;
    }

    private static function _getListTypeData(): array
    {
        $listTypeRows = (new Query())
            ->select([
                'fieldLayoutId',
                'itemFieldLayoutId',
                'name',
                'handle',
                'default',
                'uid',
            ])
            ->from(['{{%wishlist_listtypes}} listTypes'])
            ->all();

        $typeData = [];

        foreach ($listTypeRows as $listTypeRow) {
            $rowUid = $listTypeRow['uid'];

            if (!empty($listTypeRow['fieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($listTypeRow['fieldLayoutId']);

                if ($layout) {
                    $listTypeRow['listFieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            if (!empty($listTypeRow['itemFieldLayoutId'])) {
                $layout = Craft::$app->getFields()->getLayoutById($listTypeRow['itemFieldLayoutId']);

                if ($layout) {
                    $listTypeRow['itemFieldLayouts'] = [$layout->uid => $layout->getConfig()];
                }
            }

            unset($listTypeRow['uid'], $listTypeRow['fieldLayoutId'], $listTypeRow['itemFieldLayoutId']);

            $typeData[$rowUid] = $listTypeRow;
        }

        return $typeData;
    }
}
