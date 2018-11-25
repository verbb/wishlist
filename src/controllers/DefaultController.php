<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;

use Craft;
use craft\web\Controller;

class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = Wishlist::$plugin->getSettings();

        return $this->renderTemplate('wishlist/settings', [
            'settings' => $settings,
        ]);
    }

}