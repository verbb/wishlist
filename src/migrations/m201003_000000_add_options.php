<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\elements\Item;

use Craft;
use craft\db\Migration;
use craft\queue\jobs\ResaveElements;

class m201003_000000_add_options extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%wishlist_items}}', 'options')) {
            $this->addColumn('{{%wishlist_items}}', 'options', $this->text()->after('elementClass'));
        }

        if (!$this->db->columnExists('{{%wishlist_items}}', 'optionsSignature')) {
            $this->addColumn('{{%wishlist_items}}', 'optionsSignature', $this->string()->notNull()->after('options'));
        }

        return true;
    }

    public function safeDown()
    {
        echo "m201003_000000_add_options cannot be reverted.\n";
        return false;
    }
}
