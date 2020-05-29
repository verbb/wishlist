<?php
namespace verbb\wishlist\gql\types\generators;

use verbb\wishlist\elements\ListElement;
use verbb\wishlist\gql\arguments\ListArguments;
use verbb\wishlist\gql\interfaces\ListInterface;
use verbb\wishlist\gql\types\ListType;

use Craft;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use craft\gql\TypeManager;
use craft\helpers\Gql as GqlHelper;

class ListGenerator implements GeneratorInterface
{
    // Public Methods
    // =========================================================================

    public static function generateTypes($context = null): array
    {
        $gqlTypes = [];
        $typeName = ListElement::gqlTypeNameByContext(null);

        $contentFields = Craft::$app->getFields()->getLayoutByType(ListElement::class)->getFields();
        $contentFieldGqlTypes = [];

        /** @var Field $contentField */
        foreach ($contentFields as $contentField) {
            $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
        }

        $listFields = TypeManager::prepareFieldDefinitions(array_merge(ListInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        // Generate a type for each entry type
        $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ListType([
            'name' => $typeName,
            'fields' => function() use ($listFields) {
                return $listFields;
            }
        ]));

        return $gqlTypes;
    }
}
