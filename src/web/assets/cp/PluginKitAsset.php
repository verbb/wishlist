<?php
namespace verbb\wishlist\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class PluginKitAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Plugin Kit's state panel and Wishlist's styles for it, kept apart from wishlist.js so only those pages load it.
        $this->sourcePath = '@verbb/wishlist/web/assets/cp/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'plugin-kit.css',
        ];

        $this->js = [
            'plugin-kit.js',
        ];

        parent::init();
    }
}
