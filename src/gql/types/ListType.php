<?php
namespace verbb\wishlist\gql\types;

use verbb\wishlist\gql\interfaces\ListInterface;

use craft\gql\base\ObjectType;
use craft\gql\interfaces\Element as ElementInterface;
use craft\gql\types\elements\Element;

use GraphQL\Type\Definition\ResolveInfo;

class ListType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            ListInterface::getType(),
        ];

        parent::__construct($config);
    }
}
