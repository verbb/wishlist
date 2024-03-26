<?php
namespace verbb\wishlist\elements\db;

use verbb\wishlist\Wishlist;

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
    public mixed $optionsSignature = null;
    public bool $enabled = true;
    public bool $trashedElement = false;

    protected array $defaultOrderBy = ['wishlist_items.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    public function __set($name, $value)
    {
        switch ($name) {
            case 'options':
                $this->optionsSignature($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

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
        $this->optionsSignature($value);
        return $this;
    }

    public function optionsSignature($value): static
    {
        if (is_array($value)) {
            $value = Wishlist::$plugin->getItems()->getOptionsSignature($value);
        }

        $this->optionsSignature = $value;
        return $this;
    }

    public function trashedElement($value): static
    {
        $this->trashedElement($value);
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('wishlist_items');

        $this->subQuery->innerJoin('{{%wishlist_lists}} wishlist_lists', '[[wishlist_items.listId]] = [[wishlist_lists.id]]');

        // And join the element table for the linked element, in order to fetch non-deleted linked elements
        $this->query->leftJoin('{{%elements}} element_item', '[[wishlist_items.elementId]] = [[element_item.id]]');

        // Join the element sites table (again) for the linked element
        $this->query->leftJoin('{{%elements_sites}} element_item_sites', '[[wishlist_items.elementId]] = [[element_item_sites.elementId]] AND [[wishlist_items.elementSiteId]] = [[element_item_sites.siteId]]');

        $this->query->select([
            'wishlist_items.id',
            'wishlist_items.elementId',
            'wishlist_items.elementSiteId',
            'wishlist_items.elementClass',
            'wishlist_items.listId',
            'wishlist_items.options',
            'wishlist_items.optionsSignature',
            'wishlist_items.dateCreated',

            // Join the element's title onto the same query
            'element_item_sites.title AS elementTitle',
        ]);

        // Join the linked-to element's content
        $this->subQuery->innerJoin('{{%elements_sites}} element_content', '[[wishlist_items.elementId]] = [[element_content.elementId]] AND [[wishlist_items.elementSiteId]] = [[element_content.siteId]]');
        $this->subQuery->addSelect('element_content.title AS elementTitle');

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

        if ($this->optionsSignature) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_items.optionsSignature', $this->optionsSignature));
        }

        if ($this->dateCreated) {
            $this->subQuery->andWhere(Db::parseDateParam('wishlist_items.dateCreated', $this->dateCreated));
        }

        if ($this->listTypeId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_lists.typeId', $this->listTypeId));
        }

        if (!$this->trashedElement) {
            $this->query->andWhere(['element_item.dateDeleted' => null]);
        }

        return parent::beforePrepare();
    }
}
