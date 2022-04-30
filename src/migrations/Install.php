<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\Item;
use verbb\wishlist\models\ListType;

use Craft;
use craft\db\Migration;
use craft\helpers\Db;
use craft\helpers\MigrationHelper;
use craft\records\FieldLayout;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->dropTables();
        $this->dropProjectConfig();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%wishlist_lists}}');
        $this->createTable('{{%wishlist_lists}}', [
            'id' => $this->primaryKey(),
            'typeId' => $this->integer(),
            'reference' => $this->string(10),
            'lastIp' => $this->string(),
            'userId' => $this->integer(),
            'sessionId' => $this->string(32),
            'default' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%wishlist_listtypes}}');
        $this->createTable('{{%wishlist_listtypes}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'itemFieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'default' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%wishlist_items}}');
        $this->createTable('{{%wishlist_items}}', [
            'id' => $this->primaryKey(),
            'listId' => $this->integer()->notNull(),
            'elementId' => $this->integer(),
            'elementSiteId' => $this->integer(),
            'elementClass' => $this->string(255),
            'options' => $this->text(),
            'optionsSignature' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%wishlist_lists}}', 'typeId', false);

        $this->createIndex(null, '{{%wishlist_listtypes}}', 'handle', true);
        $this->createIndex(null, '{{%wishlist_listtypes}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%wishlist_listtypes}}', 'itemFieldLayoutId', false);

        $this->createIndex(null, '{{%wishlist_items}}', ['elementSiteId'], false);
        $this->createIndex(null, '{{%wishlist_items}}', ['listId'], false);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%wishlist_lists}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%wishlist_lists}}', ['typeId'], '{{%wishlist_listtypes}}', ['id'], 'CASCADE');

        $this->addForeignKey(null, '{{%wishlist_listtypes}}', ['fieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL');
        $this->addForeignKey(null, '{{%wishlist_listtypes}}', ['itemFieldLayoutId'], '{{%fieldlayouts}}', ['id'], 'SET NULL');

        $this->addForeignKey(null, '{{%wishlist_items}}', ['id'], '{{%elements}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%wishlist_items}}', ['listId'], '{{%wishlist_lists}}', ['id'], 'CASCADE', null);
        $this->addForeignKey(null, '{{%wishlist_items}}', ['elementId'], '{{%elements}}', ['id'], 'SET NULL', null);
        $this->addForeignKey(null, '{{%wishlist_items}}', ['elementSiteId'], '{{%sites}}', ['id'], 'CASCADE', 'CASCADE');
    }

    public function insertDefaultData(): void
    {
        // Don't make the same config changes twice
        $installed = (Craft::$app->projectConfig->get('plugins.wishlist', true) !== null);
        $configExists = (Craft::$app->projectConfig->get('wishlist', true) !== null);

        if (!$installed && !$configExists) {
            $this->insert(FieldLayout::tableName(), ['type' => ListElement::class]);
            $listFieldLayoutId = $this->db->getLastInsertID(FieldLayout::tableName());

            $this->insert(FieldLayout::tableName(), ['type' => Item::class]);
            $itemFieldLayoutId = $this->db->getLastInsertID(FieldLayout::tableName());

            $data = [
                'name' => 'Wishlist',
                'handle' => 'wishlist',
                'default' => true,
                'fieldLayoutId' => $listFieldLayoutId,
                'itemFieldLayoutId' => $itemFieldLayoutId,
            ];

            $listType = new ListType($data);
            Wishlist::$plugin->getListTypes()->saveListType($listType);
        }
    }

    public function dropForeignKeys(): void
    {
        MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_lists}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_listtypes}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_items}}', $this);
    }

    public function dropTables(): void
    {
        $this->dropTable('{{%wishlist_lists}}');
        $this->dropTable('{{%wishlist_listtypes}}');
        $this->dropTable('{{%wishlist_items}}');
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->projectConfig->remove('wishlist');
    }
}
