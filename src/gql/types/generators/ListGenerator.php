<?php
namespace verbb\wishlist\gql\types\generators;

use verbb\wishlist\elements\ListElement;
use verbb\wishlist\gql\interfaces\ListInterface;
use verbb\wishlist\gql\types\ListType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeManager;

class ListGenerator extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $type = static::generateType($context);
        return [$type->name => $type];
    }

    public static function generateType(mixed $context): mixed
    {
        $context = $context ?: Craft::$app->getFields()->getLayoutByType(ListElement::class);

        $typeName = ListElement::gqlTypeNameByContext(null);
        $contentFieldGqlTypes = self::getContentFields($context);
        $listFields = TypeManager::prepareFieldDefinitions(array_merge(ListInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new ListType([
            'name' => $typeName,
            'fields' => function() use ($listFields) {
                return $listFields;
            },
        ]));
    }
}
