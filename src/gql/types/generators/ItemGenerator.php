<?php
namespace verbb\wishlist\gql\types\generators;

use verbb\wishlist\elements\Item;
use verbb\wishlist\gql\arguments\ItemArguments;
use verbb\wishlist\gql\interfaces\ItemInterface;
use verbb\wishlist\gql\types\ItemType;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\helpers\Gql as GqlHelper;

class ItemGenerator implements GeneratorInterface
{
    // Public Methods
    // =========================================================================

    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];
        $typeName = Item::gqlTypeNameByContext(null);

        $contentFields = Craft::$app->getFields()->getLayoutByType(Item::class)->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
        }

        $itemFields = TypeManager::prepareFieldDefinitions(array_merge(ItemInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        // Generate a type for each entry type
        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ItemType([
            'name' => $typeName,
            'fields' => function() use ($itemFields) {
                return $itemFields;
            }
        ]));

        return $gqlTypes;
    }
}
