<?php
namespace verbb\wishlist\errors;

class ListError
{
    // Public Methods
    // =========================================================================

    public function __construct(public $message = '', public $params = [])
    {
    }

}