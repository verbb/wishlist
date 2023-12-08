<?php
namespace verbb\wishlist\events;

use yii\base\Event;

class ModifySupportedElementTypesEvent extends Event
{
    // Properties
    // =========================================================================

    public array $types = [];

}