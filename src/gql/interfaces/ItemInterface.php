<?php
namespace verbb\wishlist\gql\interfaces;

use verbb\wishlist\elements\Item;
use verbb\wishlist\gql\types\generators\ItemGenerator;
use verbb\wishlist\gql\arguments\ItemArguments;
use verbb\wishlist\gql\interfaces\ItemInterface as ItemInterfaceLocal;

use craft\gql\base\InterfaceType as BaseInterfaceType;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class ItemInterface extends Element
{
    // Public Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return ItemGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all items.',
            'resolveType' => function(Item $value) {
                return $value->getGqlTypeName();
            },
        ]));

        ItemGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'ItemInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return TypeManager::prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'listId' => [
                'name' => 'listId',
                'type' => Type::int(),
                'description' => 'The ID of the list the item belongs to.'
            ],
            'elementId' => [
                'name' => 'elementId',
                'type' => Type::int(),
                'description' => 'The ID of the element that the item relates to.'
            ],
            'elementClass' => [
                'name' => 'elementClass',
                'type' => Type::string(),
                'description' => 'The class of the element that the item relates to.'
            ],
            'element' => [
                'name' => 'element',
                'type' => Element::getType(),
                'description' => 'The element the node links to.'
            ],
        ]), self::getName());
    }
}
