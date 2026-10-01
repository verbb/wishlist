<?php
namespace verbb\wishlist\events;

use craft\base\ElementInterface;
use craft\base\Event;
use craft\models\GqlSchema;

class AuthorizeGqlElementEvent extends Event
{
    // Properties
    // =========================================================================

    public ElementInterface $element;
    public ?GqlSchema $schema = null;
    public ?bool $authorized = null;
}
