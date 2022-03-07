<?php
namespace verbb\wishlist\console\controllers;

use verbb\wishlist\Wishlist;

use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

class ListsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPurgeInactiveLists(): int
    {
        $count = Wishlist::$plugin->getLists()->purgeInactiveLists();

        $this->stderr('Purged ' . $count . ' lists.' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
