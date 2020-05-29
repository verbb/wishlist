<?php
namespace verbb\wishlist\gql\resolvers;

use verbb\wishlist\elements\ListElement;

use craft\gql\base\ElementResolver;

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

        return $query;
    }
}
