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
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

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
        $this->dropProjectConfig();
        $this->dropForeignKeys();
        $this->dropTables();

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
        $installed = (Craft::$app->getProjectConfig()->get('plugins.wishlist', true) !== null);
        $configExists = (Craft::$app->getProjectConfig()->get('wishlist', true) !== null);

        if (!$installed && !$configExists) {
            $listFieldLayout = $this->_saveFieldLayout(ListElement::class);
            $itemFieldLayout = $this->_saveFieldLayout(Item::class);

            $listType = new ListType([
                'name' => 'Wishlist',
                'handle' => 'wishlist',
                'default' => true,
                'fieldLayoutId' => $listFieldLayout->id,
                'itemFieldLayoutId' => $itemFieldLayout->id,
            ]);

            Wishlist::$plugin->getListTypes()->saveListType($listType);
        }
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%wishlist_lists}}');
        $this->dropTableIfExists('{{%wishlist_listtypes}}');
        $this->dropTableIfExists('{{%wishlist_items}}');
    }

    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%wishlist_lists}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_lists}}', $this);
        }

        if ($this->db->tableExists('{{%wishlist_listtypes}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_listtypes}}', $this);
        }

        if ($this->db->tableExists('{{%wishlist_items}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%wishlist_items}}', $this);
        }
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('wishlist');
    }

    private function _saveFieldLayout(string $type): FieldLayout
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType($type) ?? new FieldLayout();
        $tab1 = new FieldLayoutTab(['name' => Craft::t('app', 'Content')]);
        $tab1->setLayout($fieldLayout);
        $fieldLayout->setTabs([$tab1]);

        Craft::$app->getFields()->saveLayout($fieldLayout);

        return $fieldLayout;
    }
}
