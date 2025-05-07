<?php
namespace verbb\wishlist\events;

use verbb\wishlist\elements\ListElement;

use yii\base\Event;

use craft\commerce\elements\Order;

class AddToCartEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Order $cart = null;
    public ?ListElement $list = null;

}