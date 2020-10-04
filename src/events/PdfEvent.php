<?php
namespace verbb\wishlist\events;

use yii\base\Event;

class PdfEvent extends Event
{
    // Properties
    // =========================================================================

    public $list;
    public $template;
    public $pdf;

}