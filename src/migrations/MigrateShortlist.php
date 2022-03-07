<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\records\ListRecord;
use verbb\wishlist\records\Item;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Tag;
use craft\elements\User;
use craft\elements\Category;
use craft\elements\Entry;
use craft\helpers\Json;

use craft\commerce\elements\Variant;
use craft\commerce\elements\Product;

class MigrateShortlist extends Migration
{
    // Properties
    // =========================================================================

    public mixed $shortlistId = null;

    private mixed $_list = null;


    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if ($this->_list = $this->_migrateList()) {
            $this->_migrateItems();
        }

        return true;
    }

    public function safeDown(): bool
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateList(): bool|ListRecord
    {
        $shortlist = (new Query())
            ->from('{{%shortlist_list}}')
            ->where(['id' => $this->shortlistId])
            ->one();

        if (!$shortlist) {
            return false;
        }

        // Rename the element type, easy
        $element = (new Query())
            ->from(Table::ELEMENTS)
            ->where(['id' => $shortlist['id'], 'type' => 'Shortlist_List'])
            ->one();

        if ($element) {
            $this->update(Table::ELEMENTS, ['type' => ListElement::class], ['id' => $shortlist['id']]);
        }

        // Create a new record to link to the element that's been converted
        $list = ListRecord::findOne($shortlist['id']);

        if (!$list) {
            $list = new ListRecord();
        }

        $list->id = $shortlist['id'];
        $list->default = $shortlist['default'];
        $list->typeId = Wishlist::$plugin->getListTypes()->getDefaultListType()->id;

        if ($shortlist['ownerType'] === 'member') {
            $list->userId = $shortlist['ownerId'];
        } else {
            $list->sessionId = $shortlist['ownerId'];
        }

        $shareSlug = $shortlist['shareSlug'] ?? '';

        if ($shareSlug) {
            $list->reference = substr($shareSlug, 0, 10);
        } else {
            $list->reference = Wishlist::$plugin->getLists()->generateReferenceNumber();
        }

        $list->save(false);

        return $list;

    }

    private function _migrateItems(): void
    {
        $shortlistItems = (new Query())
            ->from('{{%shortlist_item}}')
            ->where(['listId' => $this->_list->id])
            ->all();

        foreach ($shortlistItems as $shortlistItem) {
            // Rename the element type - there's too many cases to cover them all, but try a few...
            $element = (new Query())
                ->from(Table::ELEMENTS)
                ->where(['id' => $shortlistItem['id'], 'type' => 'Shortlist_Item'])
                ->one();

            $elementClass = '';

            if ($shortlistItem['elementType'] === 'Entry') {
                $elementClass = Entry::class;
            } else if ($shortlistItem['elementType'] === 'Category') {
                $elementClass = Category::class;
            } else if ($shortlistItem['elementType'] === 'User') {
                $elementClass = User::class;
            } else if ($shortlistItem['elementType'] === 'Tag') {
                $elementClass = Tag::class;
            } else if ($shortlistItem['elementType'] === 'Asset') {
                $elementClass = Asset::class;
            } else if ($shortlistItem['elementType'] === 'Commerce_Product') {
                $elementClass = Product::class;
            } else if ($shortlistItem['elementType'] === 'Commerce_Variant') {
                $elementClass = Variant::class;
            }

            if ($element && $elementClass) {
                $this->update(Table::ELEMENTS, ['type' => $elementClass], ['id' => $shortlistItem['id']]);
            }

            // Create a new record to link to the element that's been converted
            $item = Item::findOne($shortlistItem['id']);

            if (!$item) {
                $item = new Item();
            }

            $item->id = $shortlistItem['id'];
            $item->listId = $shortlistItem['listId'];
            $item->elementId = $shortlistItem['elementId'];
            $item->elementClass = $elementClass;
            $item->options = Json::encode([]);
            $item->optionsSignature = md5(Json::encode([]));

            $item->save(false);
        }

    }

}