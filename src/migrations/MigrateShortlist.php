<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\records\ListRecord;
use verbb\wishlist\records\Item;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

use Throwable;

class MigrateShortlist extends Migration
{
    // Properties
    // =========================================================================

    public $shortlistId;

    private $_list;


    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        if ($this->_list = $this->_migrateList()) {
            $this->_migrateItems();
        }
    }

    public function safeDown()
    {
        return false;
    }


    // Private Methods
    // =========================================================================

    private function _migrateList()
    {
        $shortlist = (new Query())->from('{{%shortlist_list}}')->one();

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

    private function _migrateItems()
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
                $elementClass = 'craft\\elements\\Entry';
            } else if ($shortlistItem['elementType'] === 'Category') {
                $elementClass = 'craft\\elements\\Category';
            } else if ($shortlistItem['elementType'] === 'User') {
                $elementClass = 'craft\\elements\\User';
            } else if ($shortlistItem['elementType'] === 'Tag') {
                $elementClass = 'craft\\elements\\Tag';
            } else if ($shortlistItem['elementType'] === 'Asset') {
                $elementClass = 'craft\\elements\\Asset';
            } else if ($shortlistItem['elementType'] === 'Commerce_Product') {
                $elementClass = 'craft\\commerce\\elements\\Product';
            } else if ($shortlistItem['elementType'] === 'Commerce_Variant') {
                $elementClass = 'craft\\commerce\\elements\\Variant';
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