<?php
namespace verbb\wishlist\console\controllers;

use verbb\wishlist\Wishlist;

use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

/**
 * Manages Wishlist Lists.
 */
class ListsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Removes any lists that are deemed inactive (according to your plugin settings).
     */
    public function actionPurgeInactiveLists(): int
    {
        $count = Wishlist::$plugin->getLists()->purgeInactiveLists();

        $this->stderr('Purged ' . $count . ' lists.' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
