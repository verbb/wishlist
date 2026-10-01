<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\ModifySupportedElementTypesEvent;

use Craft;
use craft\base\Element;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\Site;

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

    /**
     * Whether a linked element may be added from a front-end request.
     *
     * Nested elements inherit the lifecycle and public visibility of their
     * root owner. An explicitly allowed non-live root owner does not also
     * require the non-public opt-out, because that state cannot have a public
     * URL.
     */
    public function canAddElementFromSite(ElementInterface $element, ?Site $currentSite = null): bool
    {
        if (!in_array($element::class, $this->getSupportedElementTypes(), true)) {
            return false;
        }

        $settings = Wishlist::$plugin->getSettings();
        $currentSite ??= Craft::$app->getSites()->getCurrentSite();
        $elements = [$element];
        $rootOwner = $this->_getRootOwner($element);

        if ($rootOwner !== $element) {
            $elements[] = $rootOwner;
        }

        $rootOwnerHasExplicitlyAllowedState = false;

        foreach ($elements as $candidate) {
            if ($candidate::isLocalized() && $candidate->siteId !== $currentSite->id && !$settings->allowCrossSiteElements) {
                return false;
            }

            if ($candidate->getIsDraft()) {
                if (!$settings->allowDraftElements) {
                    return false;
                }

                $rootOwnerHasExplicitlyAllowedState = $rootOwnerHasExplicitlyAllowedState || $candidate === $rootOwner;
            }

            if ($candidate->getIsRevision()) {
                if (!$settings->allowRevisionElements) {
                    return false;
                }

                $rootOwnerHasExplicitlyAllowedState = $rootOwnerHasExplicitlyAllowedState || $candidate === $rootOwner;
            }

            if ($this->_isInactiveElement($candidate)) {
                if (!$settings->allowInactiveElements) {
                    return false;
                }

                $rootOwnerHasExplicitlyAllowedState = $rootOwnerHasExplicitlyAllowedState || $candidate === $rootOwner;
            }
        }

        if ($rootOwnerHasExplicitlyAllowedState || $settings->allowNonPublicElements) {
            return true;
        }

        return $rootOwner->getUrl() !== null;
    }


    // Private Methods
    // =========================================================================

    private function _getRootOwner(ElementInterface $element): ElementInterface
    {
        $rootOwner = $element;
        $visitedOwners = [];

        while ($rootOwner instanceof NestedElementInterface) {
            $objectId = spl_object_id($rootOwner);

            if (isset($visitedOwners[$objectId])) {
                break;
            }

            $visitedOwners[$objectId] = true;
            $primaryOwner = $rootOwner->getPrimaryOwner();

            if (!$primaryOwner || $primaryOwner === $rootOwner) {
                break;
            }

            $rootOwner = $primaryOwner;
        }

        return $rootOwner;
    }

    private function _isInactiveElement(ElementInterface $element): bool
    {
        if (!$element->enabled || $element->archived || $element->getEnabledForSite($element->siteId) === false) {
            return true;
        }

        $inactiveStatuses = [Element::STATUS_DISABLED, Element::STATUS_ARCHIVED];

        foreach (['STATUS_INACTIVE', 'STATUS_PENDING', 'STATUS_EXPIRED', 'STATUS_SUSPENDED', 'STATUS_LOCKED'] as $constant) {
            $constantName = $element::class . '::' . $constant;

            if (defined($constantName)) {
                $inactiveStatuses[] = constant($constantName);
            }
        }

        return in_array($element->getStatus(), $inactiveStatuses, true);
    }
}
