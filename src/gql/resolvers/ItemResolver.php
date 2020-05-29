<?php
namespace verbb\wishlist\gql\resolvers;

use verbb\wishlist\elements\Item;

use craft\gql\base\ElementResolver;

use GraphQL\Type\Definition\ResolveInfo;

class ItemResolver extends ElementResolver
{
    // Public Methods
    // =========================================================================

    public static function prepareQuery($source, array $arguments, $fieldName = null)
    {
        if ($source === null) {
            $query = Item::find();
        } else {
            $query = $source->$fieldName;
        }

        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        return $query;
    }
}
