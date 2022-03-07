<?php
namespace verbb\wishlist\variables;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\db\ListQuery;

use verbb\wishlist\elements\db\ItemQuery;

class WishlistVariable
{
    // Public Methods
    // =========================================================================

    public function getPlugin(): Wishlist
    {
        return Wishlist::$plugin;
    }

    public function getPluginName(): string
    {
        return Wishlist::$plugin->getPluginName();
    }

    public function lists($forUser = true, $forceSave = false): ListQuery
    {
        if ($forUser) {
            if ($forceSave) {
                Wishlist::$plugin->getLists()->getList(null, true);
            }

            return Wishlist::$plugin->getLists()->getListQueryForOwner();
        }

        return ListElement::find();
    }

    public function items(): ItemQuery
    {
        return Item::find();
    }

    public function item($elementId, $listId = null, $listType = null): ?Item
    {
        $item = null;
        $listTypeId = null;

        if (!$elementId) {
            return null;
        }

        if ($listType && $listType = Wishlist::$plugin->getListTypes()->getListTypeByHandle($listType)) {
            $listTypeId = $listType->id;
        }

        // Get the list, don't force it to be created yet
        $list = Wishlist::$plugin->getLists()->getList($listId, false, $listTypeId);

        if ($list->id) {
            $item = Item::find()->elementId($elementId)->listId($list->id)->listTypeId($listTypeId)->one();
        }

        if (!$item) {
            $item = WishList::$plugin->getItems()->createItem($elementId, $listId, $listTypeId);
        }

        return $item;
    }

    public function getInUserLists($elementId): bool
    {
        // Get all lists for the current user (session).
        $userListIds = Wishlist::$plugin->getLists()->getListQueryForOwner()->ids();

        if (!$userListIds) {
            return false;
        }

        return Item::find()
            ->elementId($elementId)
            ->listId($userListIds)
            ->exists();
    }

}
