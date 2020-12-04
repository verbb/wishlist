<?php
namespace verbb\wishlist\gql\resolvers;

use verbb\wishlist\elements\ListElement;
use verbb\wishlist\helpers\Gql as GqlHelper;

use craft\gql\base\ElementResolver;
use craft\helpers\Db;

use GraphQL\Type\Definition\ResolveInfo;

class ListResolver extends ElementResolver
{
    // Public Methods
    // =========================================================================

    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        if ($source === null) {
            $query = ListElement::find();
        } else {
            $query = $source->$fieldName;
        }

        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $pairs = GqlHelper::extractAllowedEntitiesFromSchema('read');

        if (!GqlHelper::canQueryWishlist()) {
            return [];
        }

        $query->andWhere(['in', 'typeId', array_values(Db::idsByUids('{{%wishlist_listtypes}}', $pairs['wishlistListTypes']))]);

        return $query;
    }
}
