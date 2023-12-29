<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;

use craft\db\Query;
use craft\migrations\BaseContentRefactorMigration;

class m231229_000000_content_refactor extends BaseContentRefactorMigration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        foreach (Wishlist::$plugin->getListTypes()->getAllListTypes() as $type) {
            $listIds = (new Query())->select('id')->from('{{%wishlist_lists}}')->where(['typeId' => $type->id])->column();

            $this->updateElements(
                $listIds,
                $type->getFieldLayout(),
            );

            foreach ($listIds as $listId) {
                $this->updateElements(
                    (new Query())->from('{{%wishlist_items}}')->where(['listId' => $listId]),
                    $type->getFieldLayout(),
                );
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m231229_000000_content_refactor cannot be reverted.\n";

        return false;
    }
}
