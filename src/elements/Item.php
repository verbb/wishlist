<?php
namespace verbb\wishlist\elements;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\db\ItemQuery;
use verbb\wishlist\records\Item as ItemRecord;

use Craft;
use craft\base\Element;
use craft\controllers\ElementIndexesController;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\actions\Edit;
use craft\elements\actions\NewChild;
use craft\elements\actions\SetStatus;
use craft\elements\actions\View;
use craft\elements\db\ElementQueryInterface;
use Craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use yii\base\Exception;
use yii\base\InvalidConfigException;

class Item extends Element
{
    // Static
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('wishlist', 'Wishlist Item');
    }

    public static function refHandle()
    {
        return 'wishlistItem';
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new ItemQuery(static::class);
    }

    // Properties
    // =========================================================================

    public $id;
    public $elementId;
    public $elementSiteId;
    public $elementClass;
    public $listId;

    private $_element;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $element = $this->getElement();

        if ($element) {
            $this->title = $element->title;
        }
    }

    public function getElement()
    {
        if ($this->_element !== null) {
            return $this->_element;
        }

        if ($this->elementId === null) {
            return null;
        }

        return $this->_element = Craft::$app->getElements()->getElementById($this->elementId, $this->elementClass, $this->elementSiteId);
    }

    public function setElement($element = null)
    {
        $this->_element = $element;
    }

    public function getList()
    {
        if ($this->listId === null) {
            throw new InvalidConfigException('Item is missing its list ID');
        }

        $list = Wishlist::$plugin->getLists()->getListById($this->listId);

        if (!$list) {
            throw new InvalidConfigException('Invalid list ID: ' . $this->listId);
        }

        return $list;
    }

    public function getElementDisplay()
    {
        return $this->elementClass ? $this->elementClass::displayName() : null;
    }

    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('wishlist/lists/' . $this->list->type->handle . '/' . $this->listId . '/items/' . $this->id);
    }
    
    public function getFieldLayout()
    {
        if ($this->listId) {
            return $this->getList()->getType()->getItemFieldLayout();
        }

        return parent::getFieldLayout();
    }

    public function getInList()
    {
        if ($this->id && $this->listId) {
            return in_array($this->id, $this->getList()->getItems()->ids());
        }

        return false;
    }


    // URLs
    // -------------------------------------------------------------------------

    public function getAddUrl()
    {
        return UrlHelper::actionUrl('wishlist/items/add', [ 'elementId' => $this->elementId, 'listId' => $this->listId ]);
    }

    public function getRemoveUrl()
    {
        return UrlHelper::actionUrl('wishlist/items/remove', [ 'elementId' => $this->elementId, 'listId' => $this->listId ]);
    }

    public function getToggleUrl()
    {
        return UrlHelper::actionUrl('wishlist/items/toggle', [ 'elementId' => $this->elementId, 'listId' => $this->listId ]);
    }


    // Events
    // -------------------------------------------------------------------------

    public function afterSave(bool $isNew)
    {
        // Get the node record
        if (!$isNew) {
            $record = ItemRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid node ID: ' . $this->id);
            }
        } else {
            $record = new ItemRecord();
            $record->id = $this->id;
        }

        $record->elementId = $this->elementId;
        $record->elementSiteId = $this->elementSiteId;
        $record->elementClass = $this->elementClass;
        $record->listId = $this->listId;

        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }
}
