<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\elements\Item;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\queue\jobs\ResaveElements;

class m201004_000000_generate_options extends Migration
{
    public function safeUp()
    {
        $items = (new Query())
            ->select(['*'])
            ->from(['{{%wishlist_items}} items'])
            ->all();

        foreach ($items as $key => $item) {
            $this->update('{{%wishlist_items}}', [
                'options' => Json::encode([]),
                'optionsSignature' => md5(Json::encode([]))
            ], ['id' => $item['id']]);
        }

        return true;
    }

    public function safeDown()
    {
        echo "m201004_000000_generate_options cannot be reverted.\n";
        return false;
    }
}
