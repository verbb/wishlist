<?php
namespace verbb\wishlist\gql\queries;

use verbb\wishlist\gql\arguments\ListArguments;
use verbb\wishlist\gql\interfaces\ListInterface;
use verbb\wishlist\gql\resolvers\ListResolver;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class ListQuery extends Query
{
    // Public Methods
    // =========================================================================

    public static function getQueries($checkToken = true): array
    {
        return [
            'wishlists' => [
                'type' => Type::listOf(ListInterface::getType()),
                'args' => ListArguments::getArguments(),
                'resolve' => ListResolver::class . '::resolve',
                'description' => 'This query is used to query for lists.',
            ],
        ];
    }
}
