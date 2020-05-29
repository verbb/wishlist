<?php
namespace verbb\wishlist\gql\queries;

use verbb\wishlist\gql\arguments\ItemArguments;
use verbb\wishlist\gql\interfaces\ItemInterface;
use verbb\wishlist\gql\resolvers\ItemResolver;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class ItemQuery extends Query
{
    // Public Methods
    // =========================================================================

    public static function getQueries($checkToken = true): array
    {
        return [
            'wishlistItems' => [
                'type' => Type::listOf(ItemInterface::getType()),
                'args' => ItemArguments::getArguments(),
                'resolve' => ItemResolver::class . '::resolve',
                'description' => 'This query is used to query for items.',
            ],
        ];
    }
}
