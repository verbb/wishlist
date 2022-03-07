<?php
namespace verbb\wishlist\events;

use yii\base\Event;
use verbb\wishlist\models\ListType;

class ListTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ListType $listType = null;
    public bool $isNew = false;

}