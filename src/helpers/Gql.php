<?php
namespace verbb\wishlist\helpers;

use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\AuthorizeGqlElementEvent;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Event;
use craft\base\NestedElementInterface;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\helpers\Gql as GqlHelper;
use craft\models\GqlSchema;

class Gql extends GqlHelper
{
    // Constants
    // =========================================================================

    public const EVENT_AUTHORIZE_LINKED_ELEMENT = 'authorizeLinkedElement';


    // Static Methods
    // =========================================================================

    public static function canQueryWishlist(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['wishlistListTypes']);
    }

    public static function canQueryWishlistItems(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['wishlistListTypes']);
    }

    public static function resolveItemElement(Item $item): ?ElementInterface
    {
        $element = $item->getElement();

        return self::canQueryLinkedElement($element) ? $element : null;
    }

    /**
     * Whether the active GraphQL schema may read the exact linked element.
     *
     * Shared GraphQL types such as Entry, User, Address and Wishlist elements
     * do not prove access to a particular section, group or list type.
     */
    public static function canQueryLinkedElement(?ElementInterface $element, ?GqlSchema $schema = null): bool
    {
        if ($element === null || !self::_canQuerySite($element, $schema) || !self::_canQueryLifecycle($element, $schema)) {
            return false;
        }

        if ($element instanceof Entry) {
            if ($element->sectionId) {
                $section = $element->getSection();

                return $section !== null && self::canSchema('sections.' . $section->uid, 'read', $schema);
            }

            if ($element->fieldId) {
                $field = Craft::$app->getFields()->getFieldById($element->fieldId);

                return $field !== null && self::canSchema('nestedentryfields.' . $field->uid, 'read', $schema);
            }

            return false;
        }

        if ($element instanceof User) {
            return self::_canQueryUser($element, $schema);
        }

        if ($element instanceof Address) {
            $owner = $element->getPrimaryOwner();

            return $owner !== null && $owner !== $element && self::canQueryLinkedElement($owner, $schema);
        }

        if ($element instanceof ListElement) {
            return self::_canQueryList($element, $schema);
        }

        if ($element instanceof Item) {
            $list = $element->getList();

            return $list !== null && self::_canQueryList($list, $schema);
        }

        if ($element instanceof Asset) {
            $volume = $element->getVolume();

            return self::canSchema('volumes.' . $volume->uid, 'read', $schema);
        }

        if ($element instanceof Category) {
            $group = $element->getGroup();

            return self::canSchema('categorygroups.' . $group->uid, 'read', $schema);
        }

        if ($element instanceof Tag) {
            $group = $element->getGroup();

            return self::canSchema('taggroups.' . $group->uid, 'read', $schema);
        }

        if ($element instanceof GlobalSet) {
            return self::canSchema('globalsets.' . $element->uid, 'read', $schema);
        }

        if (is_a($element, 'craft\\commerce\\elements\\Product', false)) {
            $type = $element->getType();

            return self::canSchema('productTypes.' . $type->uid, 'read', $schema);
        }

        if (is_a($element, 'craft\\commerce\\elements\\Variant', false)) {
            $product = $element->getProduct();

            return $product !== null && self::canQueryLinkedElement($product, $schema);
        }

        // Other nested elements inherit the visibility of their root owner.
        if ($element instanceof NestedElementInterface) {
            $owner = $element->getPrimaryOwner();

            return $owner !== null && $owner !== $element && self::canQueryLinkedElement($owner, $schema);
        }

        // Custom element types must provide an explicit scope decision.
        if (Event::hasHandlers(self::class, self::EVENT_AUTHORIZE_LINKED_ELEMENT)) {
            $event = new AuthorizeGqlElementEvent([
                'element' => $element,
                'schema' => $schema,
            ]);
            Event::trigger(self::class, self::EVENT_AUTHORIZE_LINKED_ELEMENT, $event);

            return $event->authorized === true;
        }

        return false;
    }

    private static function _canQuerySite(ElementInterface $element, ?GqlSchema $schema): bool
    {
        if (!$element::isLocalized()) {
            return true;
        }

        $site = Craft::$app->getSites()->getSiteById($element->siteId);

        return $site !== null && self::canSchema('sites.' . $site->uid, 'read', $schema);
    }

    private static function _canQueryLifecycle(ElementInterface $element, ?GqlSchema $schema): bool
    {
        if ($element->getIsDraft()) {
            return self::canQueryDrafts($schema);
        }

        if ($element->getIsRevision()) {
            return self::canQueryRevisions($schema);
        }

        $activeStatuses = [Element::STATUS_ENABLED];

        foreach (['STATUS_LIVE', 'STATUS_ACTIVE'] as $constant) {
            $constantName = $element::class . '::' . $constant;

            if (defined($constantName)) {
                $activeStatuses[] = constant($constantName);
            }
        }

        return in_array($element->getStatus(), $activeStatuses, true) || self::canQueryInactiveElements($schema);
    }

    private static function _canQueryUser(User $user, ?GqlSchema $schema): bool
    {
        if (self::canSchema('usergroups.everyone', 'read', $schema) || self::canSchema('usergroups.solo', 'read', $schema)) {
            return true;
        }

        foreach ($user->getGroups() as $group) {
            if (self::canSchema('usergroups.' . $group->uid, 'read', $schema)) {
                return true;
            }
        }

        return false;
    }

    private static function _canQueryList(ListElement $list, ?GqlSchema $schema): bool
    {
        if (self::canSchema('wishlistListTypes.all', 'read', $schema)) {
            return true;
        }

        $type = $list->getType();

        return self::canSchema('wishlistListTypes.' . $type->uid, 'read', $schema);
    }
}
