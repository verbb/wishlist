<?php
namespace verbb\wishlist\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class WishlistAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/wishlist/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/wishlist.css',
        ];

        $this->js = [
            'js/wishlist.js',
        ];

        parent::init();
    }
}
