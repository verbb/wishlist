<?php
namespace verbb\wishlist\records;

use craft\db\ActiveRecord;
use craft\records\FieldLayout;

use yii\db\ActiveQueryInterface;

class ListType extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%wishlist_listtypes}}';
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

    public function getItemFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'itemFieldLayoutId']);
    }
}
