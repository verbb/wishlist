<?php
namespace verbb\wishlist\console\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\helpers\Console;
use craft\helpers\Db;

use yii\console\ExitCode;
use yii\web\Response;

class ItemsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionCleanupOrphanedItems()
    {
        // Allow batch processing for large items/lists
        $limit = 200;
        $offset = 0;

        do {
            $itemElements = (new Query())
                ->select(['id'])
                ->from(['{{%elements}}'])
                ->where(['type' => Item::class])
                ->limit($limit)
                ->offset($offset)
                ->column();

            foreach ($itemElements as $itemElement) {
                $item = (new Query())
                    ->from(['{{%wishlist_items}}'])
                    ->where(['id' => $itemElement])
                    ->exists();

                if (!$item) {
                    $this->stderr('Removed item element ' . $itemElement . '.' . PHP_EOL, Console::FG_GREEN);

                    Db::delete('{{%elements}}', ['id' => $itemElement]);
                }
            }

            $offset = $offset + $limit;
        } while ($itemElements);

        $limit = 200;
        $offset = 0;

        // Clear any items that no longer reference an element
        do {
            $itemElements = (new Query())
                ->select(['id'])
                ->from(['{{%wishlist_items}}'])
                ->where(['elementId' => null])
                ->limit($limit)
                ->offset($offset)
                ->column();

            foreach ($itemElements as $itemElement) {
                $this->stderr('Removed item element ' . $itemElement . ' due to deleted linked element.' . PHP_EOL, Console::FG_GREEN);

                Db::delete('{{%wishlist_items}}', ['id' => $itemElement]);
            }

            $offset = $offset + $limit;
        } while ($itemElements);

        return ExitCode::OK;
    }
}
