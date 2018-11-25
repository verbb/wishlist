<?php
namespace verbb\wishlist\records;

use craft\db\ActiveRecord;
use craft\records\Structure;

use yii\db\ActiveQueryInterface;

class ListRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%wishlist_lists}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getItems(): ActiveQueryInterface
    {
        return $this->hasMany(Item::class, ['listId' => 'id']);
    }
}
