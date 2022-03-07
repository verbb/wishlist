<?php
namespace verbb\wishlist\records;

use craft\db\ActiveQuery;
use craft\base\Element;
use craft\db\ActiveRecord;

class ListRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%wishlist_lists}}';
    }

    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getItems(): ActiveQuery
    {
        return $this->hasMany(Item::class, ['listId' => 'id']);
    }
}
