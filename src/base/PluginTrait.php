<?php
namespace verbb\wishlist\base;

use verbb\wishlist\Wishlist;
use verbb\wishlist\services\Lists;
use verbb\wishlist\services\ListTypes;
use verbb\wishlist\services\Items;
use verbb\wishlist\services\Pdf;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Wishlist $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('wishlist');

        return [
            'components' => [
                'lists' => Lists::class,
                'listTypes' => ListTypes::class,
                'items' => Items::class,
                'pdf' => Pdf::class,
            ],
        ];
    }


    // Public Methods
    // =========================================================================

    public function getLists(): Lists
    {
        return $this->get('lists');
    }

    public function getListTypes(): ListTypes
    {
        return $this->get('listTypes');
    }

    public function getItems(): Items
    {
        return $this->get('items');
    }

    public function getPdf(): Pdf
    {
        return $this->get('pdf');
    }

}