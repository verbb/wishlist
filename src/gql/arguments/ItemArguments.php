<?php
namespace verbb\wishlist\gql\arguments;

use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class ItemArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'listId' => [
                'name' => 'listId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the list ID.'
            ],
            'elementId' => [
                'name' => 'elementId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the owner element ID.'
            ],
            'elementClass' => [
                'name' => 'elementClass',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the owner element class.'
            ],
        ]);
    }
}
