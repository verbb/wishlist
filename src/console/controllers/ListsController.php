<?php
namespace verbb\wishlist\console\controllers;

use verbb\wishlist\Wishlist;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;
use yii\web\Response;

class ListsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPurgeInactiveLists()
    {
        $count = Wishlist::getInstance()->getLists()->purgeInactiveLists();

        $this->stderr('Purged ' . $count . ' lists.' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
