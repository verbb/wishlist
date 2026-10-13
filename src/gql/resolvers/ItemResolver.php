<?php
namespace verbb\wishlist\gql\resolvers;

use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\helpers\Gql as GqlHelper;

use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

use Traversable;

class ItemResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source instanceof ListElement) {
            // Parent membership cannot be replaced by a nested listId argument or a preloaded result.
            $query = Item::find()->andWhere(['wishlist_items.listId' => $source->id]);
        } elseif ($source === null) {
            $query = Item::find();
        } else {
            $query = $source->$fieldName;

            if (!$query instanceof ElementQuery && (is_array($query) || $query instanceof Traversable)) {
                $ids = [];

                foreach ($query as $item) {
                    if ($item instanceof Item && $item->id) {
                        $ids[] = $item->id;
                    }
                }

                $query = Item::find()->andWhere(['wishlist_items.id' => $ids]);
            }
        }

        if (!$query instanceof ElementQuery) {
            return $query;
        }

        $query = clone $query;
        $query->clearCachedResult();

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryWishlistItems()) {
            return ElementCollection::empty();
        }

        if (!GqlHelper::canSchema('wishlistListTypes.all')) {
            $query->andWhere(['in', 'typeId', array_values(Db::idsByUids('{{%wishlist_listtypes}}', $pairs['wishlistListTypes']))]);
        }

        if (self::_usesLinkedMetadata($query)) {
            self::_restrictLinkedMetadata($query);
        }

        return $query;
    }

    private static function _usesLinkedMetadata(ElementQuery $query): bool
    {
        foreach (['title', 'search', 'elementId', 'elementClass'] as $attribute) {
            if ($query->$attribute !== null) {
                return true;
            }
        }

        $orderBy = $query->orderBy;

        if (is_array($orderBy)) {
            $orderBy = implode(' ', array_keys($orderBy));
        }

        return $orderBy !== null && (!is_string($orderBy) || preg_match('/title|elementId|elementClass/i', $orderBy) === 1);
    }

    private static function _restrictLinkedMetadata(ElementQuery $query): void
    {
        // Masked fields alone would still allow title guesses through filters or sorting.
        // Check matching candidates before pagination, retaining placeholders in ordinary queries.
        $candidates = clone $query;
        $candidates->clearCachedResult();
        $candidates->limit(null)->offset(null)->asArray(false)->indexBy(null);
        $ids = [];

        try {
            foreach ($candidates->each(100) as $item) {
                if ($item instanceof Item && GqlHelper::resolveItemElement($item) !== null) {
                    $ids[] = $item->id;
                }
            }
        } catch (QueryAbortedException) {
            // Craft uses this for empty searches; each() does not normalize it like all().
            $ids = [];
        }

        $query->andWhere(['wishlist_items.id' => $ids]);
    }
}
