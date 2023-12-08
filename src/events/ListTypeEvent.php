<?php
namespace verbb\wishlist\events;

use verbb\wishlist\models\ListType;

use yii\base\Event;

class ListTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ListType $listType = null;
    public bool $isNew = false;

}