<?php
namespace verbb\wishlist\records;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\records\FieldLayout;

class ListType extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%wishlist_listtypes}}';
    }

    public function getFieldLayout(): ActiveQuery
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

    public function getItemFieldLayout(): ActiveQuery
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'itemFieldLayoutId']);
    }
}
