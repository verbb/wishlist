<?php
namespace verbb\wishlist\migrations;

use craft\db\Migration;

class m260928_000000_clear_user_session_ids extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Account ownership supersedes the temporary bearer credential used by guests.
        $this->update('{{%wishlist_lists}}', ['sessionId' => null], ['not', ['userId' => null]]);

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260928_000000_clear_user_session_ids cannot be reverted.\n";

        return false;
    }
}
