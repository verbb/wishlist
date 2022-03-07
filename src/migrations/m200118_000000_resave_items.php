<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\elements\Item;

use Craft;
use craft\db\Migration;
use craft\queue\jobs\ResaveElements;

class m200118_000000_resave_items extends Migration
{
    public function safeUp(): bool
    {
        Craft::$app->getQueue()->push(new ResaveElements([
            'elementType' => Item::class,
        ]));

        return true;
    }

    public function safeDown(): bool
    {
        echo "m200118_000000_resave_items cannot be reverted.\n";
        return false;
    }
}
