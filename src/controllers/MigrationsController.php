<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\migrations\MigrateShortlist;
use verbb\wishlist\migrations\MigrateUpvote;

use Craft;
use craft\db\Query;
use craft\helpers\App;
use craft\web\Controller;

use Throwable;

class MigrationsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionShortlist(): void
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $outputs = [];
        $shortlists = (new Query())->from('{{%shortlist_list}}')->all();

        foreach ($shortlists as $shortlist) {
            $migration = new MigrateShortlist(['shortlistId' => $shortlist['id']]);

            try {
                ob_start();
                $migration->up();
                $output = ob_get_clean();

                $outputs[$shortlist['id']] = nl2br($output);
            } catch (Throwable $e) {
                $outputs[$shortlist['id']] = 'Failed to migrate: ' . $e->getMessage();
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        Craft::$app->getSession()->setNotice(Craft::t('wishlist', 'Shortlist lists migrated.'));
    }

    public function actionUpvote(): void
    {
        App::maxPowerCaptain();

        // Backup!
        Craft::$app->getDb()->backup();

        $outputs = [];
        $upvotes = (new Query())->from('{{%upvote_userhistories}}')->all();

        foreach ($upvotes as $upvote) {
            $migration = new MigrateUpvote(['id' => $upvote['id']]);

            try {
                ob_start();
                $migration->up();
                $output = ob_get_clean();

                $outputs[$upvote['id']] = nl2br($output);
            } catch (Throwable $e) {
                $outputs[$upvote['id']] = 'Failed to migrate: ' . $e->getMessage();
            }
        }

        Craft::$app->getUrlManager()->setRouteParams([
            'outputs' => $outputs,
        ]);

        Craft::$app->getSession()->setNotice(Craft::t('wishlist', 'Upvote lists migrated.'));
    }
}
