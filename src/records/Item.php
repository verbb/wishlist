<?php
namespace verbb\wishlist\records;

use verbb\wishlist\elements\ListElement;

use craft\db\ActiveQuery;
use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\Site;

class Item extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%wishlist_items}}';
    }

    public function getElement(): ActiveQuery
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getElementSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'elementSiteId']);
    }

    public function getList(): ActiveQuery
    {
        return $this->hasOne(ListElement::class, ['id' => 'listId']);
    }
}
