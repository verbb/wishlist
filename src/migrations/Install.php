<?php
namespace verbb\wishlist\migrations;

use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\Item;
use verbb\wishlist\records\ListType;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;
use craft\records\FieldLayout;

class Install extends Migration
{
    // Private properties
    // =========================================================================

    private $_itemFieldLayoutId;
    private $_listFieldLayoutId;


    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        return true;
    }

    public function safeDown()
    {
        $this->dropForeignKeys();
        $this->dropTables();

        return true;
    }

    public function createTables()
    {
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

        $this->createTable('{{%wishlist_items}}', [
            'id' => $this->primaryKey(),
            'listId' => $this->integer()->notNull(),
            'elementId' => $this->integer(),
            'elementSiteId' => $this->integer(),
            'elementClass' => $this->string(255),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes()
    {
        $this->createIndex(null, '{{%wishlist_lists}}', 'typeId', false);

        $this->createIndex(null, '{{%wishlist_listtypes}}', 'handle', true);
        $this->createIndex(null, '{{%wishlist_listtypes}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%wishlist_listtypes}}', 'itemFieldLayoutId', false);

        $this->createIndex(null, '{{%wishlist_items}}', ['elementSiteId'], false);
        $this->createIndex(null, '{{%wishlist_items}}', ['listId'], false);
    }

    public function addForeignKeys()
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

    public function insertDefaultData()
    {
        $this->insert(FieldLayout::tableName(), ['type' => ListElement::class]);
        $this->_listFieldLayoutId = $this->db->getLastInsertID(FieldLayout::tableName());
        $this->insert(FieldLayout::tableName(), ['type' => Item::class]);
        $this->_itemFieldLayoutId = $this->db->getLastInsertID(FieldLayout::tableName());

        $data = [
            'name' => 'Wishlist',
            'handle' => 'wishlist',
            'default' => true,
            'fieldLayoutId' => $this->_listFieldLayoutId,
            'itemFieldLayoutId' => $this->_itemFieldLayoutId,
        ];

        $this->insert(ListType::tableName(), $data);
        $listTypeId = $this->db->getLastInsertID(ListType::tableName());
    }

    public function dropForeignKeys()
    {
        MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_lists}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_listtypes}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_items}}', $this);
    }

    public function dropTables()
    {
        $this->dropTable('{{%wishlist_lists}}');
        $this->dropTable('{{%wishlist_listtypes}}');
        $this->dropTable('{{%wishlist_items}}');
    }
}
