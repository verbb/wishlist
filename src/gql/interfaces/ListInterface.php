<?php
namespace verbb\wishlist\gql\interfaces;

use verbb\wishlist\elements\ListElement;
use verbb\wishlist\gql\types\generators\ListGenerator;
use verbb\wishlist\gql\arguments\ItemArguments;
use verbb\wishlist\gql\arguments\ListArguments;
use verbb\wishlist\gql\interfaces\ItemInterface;
use verbb\wishlist\gql\interfaces\ListInterface as ListInterfaceLocal;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class ListInterface extends Element
{
    // Public Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return ListGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all lists.',
            'resolveType' => function(ListElement $value) {
                return $value->getGqlTypeName();
            },
        ]));

        ListGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'ListInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'items' => [
                'name' => 'items',
                'args' => ItemArguments::getArguments(),
                'type' => Type::listOf(ItemInterface::getType()),
                'description' => 'The lists items. Accepts the same arguments as the `items` query.',
            ],
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::int(),
                'description' => 'The list type ID.',
            ],
            'userId' => [
                'name' => 'userId',
                'type' => Type::int(),
                'description' => 'The user ID that owns the list.',
            ],
            'sessionId' => [
                'name' => 'sessionId',
                'type' => Type::string(),
                'description' => 'The list’s session ID.',
            ],
            'reference' => [
                'name' => 'reference',
                'type' => Type::string(),
                'description' => 'The list’s reference.',
            ],
            'default' => [
                'name' => 'default',
                'type' => Type::boolean(),
                'description' => 'Whether it is the default list.',
            ],
        ]), self::getName());
    }
}
