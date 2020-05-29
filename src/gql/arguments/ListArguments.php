<?php
namespace verbb\wishlist\gql\arguments;

use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class ListArguments extends ElementArguments
{
    // Public Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), [
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the list type ID.'
            ],
            'userId' => [
                'name' => 'userId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the list’s owner.'
            ],
            'sessionId' => [
                'name' => 'sessionId',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the list’s session ID.'
            ],
            'reference' => [
                'name' => 'reference',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the list’s reference.'
            ],
            'default' => [
                'name' => 'default',
                'type' => Type::boolean(),
                'description' => 'Narrows the query results based on whether it is the default list.'
            ],
        ]);
    }
}
