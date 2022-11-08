<?php
namespace verbb\wishlist\gql\resolvers;

use verbb\wishlist\elements\ListElement;
use verbb\wishlist\helpers\Gql as GqlHelper;

use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

use Illuminate\Support\Collection;

class ListResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source === null) {
            $query = ListElement::find();
        } else {
            $query = $source->$fieldName;
        }

        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryWishlist()) {
            return Collection::empty();
        }

        if (!GqlHelper::canSchema('wishlistListTypes.all')) {
            $query->andWhere(['in', 'typeId', array_values(Db::idsByUids('{{%wishlist_listtypes}}', $pairs['wishlistListTypes']))]);
        }

        return $query;
    }
}
