<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\ModifySupportedElementTypesEvent;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;

class Items extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_MODIFY_SUPPORTED_ELEMENT_TYPES = 'modifySupportedElementTypes';
    

    // Public Methods
    // =========================================================================

    public function getItemById(int $id, ?int $siteId = null): ?Item
    {
        return Craft::$app->getElements()->getElementById($id, Item::class, $siteId);
    }

    public function saveElement(ElementInterface $element, bool $runValidation = true, bool $propagate = true): bool
    {
        $updateItemSearchIndexes = Wishlist::$plugin->getSettings()->updateItemSearchIndexes;

        return Craft::$app->getElements()->saveElement($element, $runValidation, $propagate, $updateItemSearchIndexes);
    }

    public function deleteItemsForList(int $listId, ?int $siteId = null): bool
    {
        $items = Item::find()
            ->listId($listId)
            ->status(null)
            ->ids();

        foreach ($items as $itemId) {
            Craft::$app->getElements()->deleteElementById($itemId);
        }

        return true;
    }

    public function createItem(ListElement $list, ElementInterface $element, array $params = []): Item
    {
        $item = new Item();
        $item->listId = $list->id;
        $item->elementId = $element->id;
        $item->elementSiteId = $element->siteId;
        $item->elementClass = $element::class;

        $fields = ArrayHelper::remove($params, 'fields');

        if ($fields) {
            $item->setFieldValues($fields);
        }

        Craft::configure($item, $params);

        return $item;
    }

    public function getOptionsSignature(array $options = []): string
    {
        ksort($options);

        return md5(Json::encode($options));
    }

    public function getSupportedElementTypes(): array
    {
        $elementTypes = Craft::$app->getElements()->getAllElementTypes();

        ArrayHelper::removeValue($elementTypes, Item::class);
        ArrayHelper::removeValue($elementTypes, ListElement::class);

        $event = new ModifySupportedElementTypesEvent([
            'types' => $elementTypes,
        ]);
        $this->trigger(self::EVENT_MODIFY_SUPPORTED_ELEMENT_TYPES, $event);

        return $event->types;
    }
}