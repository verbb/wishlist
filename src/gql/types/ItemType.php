<?php
namespace verbb\wishlist\gql\types;

use verbb\wishlist\gql\interfaces\ItemInterface;

use craft\gql\types\elements\Element;

class ItemType extends Element
{
    // Static Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            ItemInterface::getType(),
        ];

        parent::__construct($config);
    }
}
