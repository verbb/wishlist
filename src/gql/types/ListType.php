<?php
namespace verbb\wishlist\gql\types;

use verbb\wishlist\gql\interfaces\ListInterface;

use craft\gql\types\elements\Element;

class ListType extends Element
{
    // Static Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            ListInterface::getType(),
        ];

        parent::__construct($config);
    }
}
