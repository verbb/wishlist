<?php
namespace verbb\wishlist\helpers;

use craft\helpers\Gql as GqlHelper;

class Gql extends GqlHelper
{
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
}