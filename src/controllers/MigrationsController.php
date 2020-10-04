<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\migrations\MigrateShortlist;
use verbb\wishlist\migrations\MigrateUpvote;

use Craft;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\errors\ShellCommandException;
use craft\helpers\App;
use craft\web\Controller;

use yii\base\Exception;

class MigrationsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionShortlist()
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $shortlists = (new Query())->from('{{%shortlist_list}}')->all();

        foreach ($shortlists as $shortlist) {
            $migration = new MigrateShortlist(['shortlistId' => $shortlist['id']]);

            try {
                ob_start();
                $migration->up();
                $output = ob_get_contents();
                ob_end_clean();

                $outputs[$shortlist['id']] = nl2br($output);
            } catch (\Throwable $e) {
                $outputs[$shortlist['id']] = 'Failed to migrate: ' . $e->getMessage();
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        Craft::$app->getSession()->setNotice(Craft::t('wishlist', 'Shortlist lists migrated.'));

        return null;
    }

    public function actionUpvote()
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $upvotes = (new Query())->from('{{%upvote_userhistories}}')->all();

        foreach ($upvotes as $upvote) {
            $migration = new MigrateUpvote(['id' => $upvote['id']]);

            try {
                ob_start();
                $migration->up();
                $output = ob_get_contents();
                ob_end_clean();

                $outputs[$upvote['id']] = nl2br($output);
            } catch (\Throwable $e) {
                $outputs[$upvote['id']] = 'Failed to migrate: ' . $e->getMessage();
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        Craft::$app->getSession()->setNotice(Craft::t('wishlist', 'Upvote lists migrated.'));

        return null;
    }
}
