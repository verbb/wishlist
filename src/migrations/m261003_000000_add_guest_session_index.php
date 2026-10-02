<?php
namespace verbb\wishlist\migrations;

use craft\db\Migration;

class m261003_000000_add_guest_session_index extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createIndex(null, '{{%wishlist_lists}}', ['sessionId', 'userId'], false);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m261003_000000_add_guest_session_index cannot be reverted.\n";

        return false;
    }
}
