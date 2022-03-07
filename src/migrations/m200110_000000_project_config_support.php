<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\services\ListTypes;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m200110_000000_project_config_support extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.wishlist.schemaVersion', true);
        if (version_compare($schemaVersion, '1.0.1', '>')) {
            return true;
        }

        $listTypeData = $this->_getListTypeData();
        $projectConfig->set(ListTypes::CONFIG_LISTTYPES_KEY, $listTypeData);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m200110_000000_project_config_support cannot be reverted.\n";
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _getListTypeData(): array
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