<?php
namespace verbb\wishlist\errors;

use yii\base\Event;

class ListError
{
    // Properties
    // =========================================================================

    public $message = '';
    public $params = [];


    // Public Methods
    // =========================================================================

    public function __construct($message = '', $params = [])
    {
        $this->message = $message;
        $this->params = $params;
    }

}