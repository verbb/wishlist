<?php
namespace verbb\wishlist\events;

use yii\base\Event;

class ListTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public $listType;
    public $isNew = false;

}