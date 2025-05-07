<?php
namespace verbb\wishlist\events;

use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;

use yii\base\Event;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

class AddLineItemEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Order $cart = null;
    public ?ListElement $list = null;
    public ?Item $item = null;
    public ?LineItem $lineItem = null;

}