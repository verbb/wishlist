<?php
namespace verbb\wishlist\errors;

class ItemError
{
    // Public Methods
    // =========================================================================

    public function __construct(public $message = '', public $params = [])
    {
    }

}