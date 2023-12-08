<?php
namespace verbb\wishlist\variables;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\db\ListQuery;
use verbb\wishlist\elements\db\ItemQuery;
use verbb\wishlist\helpers\UrlHelper;

use Craft;
use craft\base\ElementInterface;

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

    public function getUserList(array $params = []): ListElement
    {
        return Wishlist::$plugin->getLists()->getUserList($params);
    }

    public function lists(bool $forUser = true): ListQuery
    {
        if ($forUser) {
            return Wishlist::$plugin->getLists()->getListQueryForUser();
        }

        return ListElement::find();
    }

    public function items(): ItemQuery
    {
        return Item::find();
    }

    public function getAddItemUrl(ElementInterface $element, array $params = []): string
    {
        return UrlHelper::addUrl($element, $params);
    }

    public function getToggleItemUrl(ElementInterface $element, array $params = []): string
    {
        return UrlHelper::toggleUrl($element, $params);
    }

    public function getRemoveItemUrl(ElementInterface $element, array $params = []): string
    {
        return UrlHelper::removeUrl($element, $params);
    }

    public function getInUserLists(ElementInterface $element): bool
    {
        return Wishlist::$plugin->getLists()->getInUserLists($element);
    }


    // Deprecated Methods
    // =========================================================================

    public function item(?int $elementId, ?int $listId = null, ?string $listTypeHandle = null, ?int $elementSiteId = null): ?Item
    {
        Craft::$app->getDeprecator()->log(__METHOD__, '`craft.wishlist.item()` has been deprecated. Use `craft.wishlist.items(params)` to find items, or `craft.wishlist.addItemUrl/toggleItemUrl/removeItemUrl` to manage items.');

        if (!$elementId) {
            return null;
        }

        $params = array_filter([
            'elementId' => $elementId,
            'elementSiteId' => $elementSiteId,
            'listId' => $listId,
        ]);

        if ($listTypeHandle && $listType = Wishlist::$plugin->getListTypes()->getListTypeByHandle($listTypeHandle)) {
            $params['typeId'] = $listType->id;
        }

        $query = Item::find();
        Craft::configure($query, $params);

        if ($item = $query->one()) {
            return $item;
        }

        if ($listId) {
            $list = Wishlist::$plugin->getLists()->getListById($listId);
        } 

        if (!isset($list)) {
            $list = Wishlist::$plugin->getLists()->getUserList();
        }

        $element = Craft::$app->getElements()->getElementById($elementId, null, $elementSiteId);

        if (!$element || !$list) {
            return null;
        }

        return WishList::$plugin->getItems()->createItem($list, $element, $params);
    }

}
