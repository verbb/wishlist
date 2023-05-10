<?php
namespace verbb\wishlist\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m230510_000000_permissions_to_uid extends Migration
{
    public function safeUp()
    {
        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from([Table::USERPERMISSIONS])
            ->pairs();

        $listTypeMap = (new Query())
            ->select(['id', 'uid'])
            ->from('{{%wishlist_listtypes}}')
            ->pairs();

        $relations = [
            // Must be lowercase to match permissions database table
            'wishlist-managelisttype' => $listTypeMap,
        ];

        foreach ($permissions as $id => $permission) {
            if (
                preg_match('/([\w-]+)(:|-)([\d]+)/i', $permission, $matches) &&
                array_key_exists(strtolower($matches[1]), $relations) &&
                !empty($relations[strtolower($matches[1])][$matches[3]])
            ) {
                $permission = $matches[1] . $matches[2] . $relations[strtolower($matches[1])][$matches[3]];
                $this->update(Table::USERPERMISSIONS, ['name' => $permission], ['id' => $id]);
            }
        }
    }

    public function safeDown()
    {
        echo "m230510_000000_permissions_to_uid cannot be reverted.\n";

        return false;
    }
}
