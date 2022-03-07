<?php
namespace verbb\wishlist\events;

use yii\base\Event;
use verbb\wishlist\elements\ListElement;

class PdfEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ListElement $list = null;
    public ?string $template = null;
    public mixed $pdf = null;

}