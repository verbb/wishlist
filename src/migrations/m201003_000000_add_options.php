<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\elements\Item;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;
use craft\queue\jobs\ResaveElements;

class m201003_000000_add_options extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%wishlist_items}}', 'options')) {
            $this->addColumn('{{%wishlist_items}}', 'options', $this->text()->after('elementClass'));
        }

        if (!$this->db->columnExists('{{%wishlist_items}}', 'optionsSignature')) {
            $this->addColumn('{{%wishlist_items}}', 'optionsSignature', $this->string()->after('options'));

            // Populate the values
            $items = (new Query())
                ->select(['*'])
                ->from(['{{%wishlist_items}} items'])
                ->all();

            foreach ($items as $key => $item) {
                $this->update('{{%wishlist_items}}', [
                    'options' => Json::encode([]),
                    'optionsSignature' => md5(Json::encode([])),
                ], ['id' => $item['id']]);
            }

            // Mark the column as not null, now values have been imported
            $this->alterColumn('{{%wishlist_items}}', 'optionsSignature', $this->string()->notNull());
        }

        return true;
    }

    public function safeDown()
    {
        echo "m201003_000000_add_options cannot be reverted.\n";
        return false;
    }
}
