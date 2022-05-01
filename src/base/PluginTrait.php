<?php
namespace verbb\wishlist\base;

use verbb\wishlist\Wishlist;
use verbb\wishlist\services\Lists;
use verbb\wishlist\services\ListTypes;
use verbb\wishlist\services\Items;
use verbb\wishlist\services\Pdf;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static Wishlist $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('wishlist', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'wishlist');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('wishlist', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'wishlist');
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


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        $this->setComponents([
            'lists' => Lists::class,
            'listTypes' => ListTypes::class,
            'items' => Items::class,
            'pdf' => Pdf::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('wishlist');
    }

}