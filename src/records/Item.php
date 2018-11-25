<?php
namespace verbb\wishlist\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Site;

use yii\db\ActiveQueryInterface;

class Item extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%wishlist_items}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getElementSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'elementSiteId']);
    }

    public function getList(): ActiveQueryInterface
    {
        return $this->hasOne(ListElement::class, ['id' => 'listId']);
    }
}
