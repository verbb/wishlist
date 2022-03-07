<?php
namespace verbb\wishlist\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class ItemQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $id = null;
    public mixed $elementId = null;
    public mixed $elementSiteId = null;
    public mixed $elementClass = null;
    public mixed $listId = null;
    public mixed $listTypeId = null;
    public mixed $options = null;
    public mixed $optionsSignature = null;
    public bool $enabled = true;

    protected array $defaultOrderBy = ['wishlist_items.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    public function elementId($value): static
    {
        $this->elementId = $value;
        return $this;
    }

    public function elementSiteId($value): static
    {
        $this->elementSiteId = $value;
        return $this;
    }

    public function elementClass($value): static
    {
        $this->elementClass = $value;
        return $this;
    }

    public function listId($value): static
    {
        $this->listId = $value;
        return $this;
    }

    public function listTypeId($value): static
    {
        $this->listTypeId = $value;
        return $this;
    }

    public function options($value): static
    {
        $this->options = $value;
        return $this;
    }

    public function optionsSignature($value): static
    {
        $this->optionsSignature = $value;
        return $this;
    }

    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('wishlist_items');
            
        $this->subQuery->innerJoin('{{%wishlist_lists}} wishlist_lists', '[[wishlist_items.listId]] = [[wishlist_lists.id]]');

        $this->query->select([
            'wishlist_items.id',
            'wishlist_items.elementId',
            'wishlist_items.elementSiteId',
            'wishlist_items.elementClass',
            'wishlist_items.listId',
            'wishlist_items.options',
            'wishlist_items.optionsSignature',
            'wishlist_items.dateCreated',
        ]);

        if ($this->id) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.id', $this->id));
        }

        if ($this->elementId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.elementId', $this->elementId));
        }

        if ($this->elementSiteId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.elementSiteId', $this->elementSiteId));
        }

        if ($this->elementClass) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.elementClass', $this->elementClass));
        }

        if ($this->listId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.listId', $this->listId));
        }

        if ($this->options) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.options', $this->options));
        }

        if ($this->optionsSignature) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.optionsSignature', $this->optionsSignature));
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('wishlist_items.dateCreated', $this->dateCreated));
        }

        if ($this->listTypeId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_lists.typeId', $this->listTypeId));
        }

        return parent::beforePrepare();
    }
}
