<?php
namespace verbb\wishlist\events;

use verbb\wishlist\elements\ListElement;

use yii\base\Event;

class PdfEvent extends Event
{
    // Properties
    // =========================================================================

    public ?ListElement $list = null;
    public ?string $template = null;
    public mixed $pdf = null;

}