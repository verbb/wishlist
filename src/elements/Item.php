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
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;

use yii\base\Exception;
use yii\base\InvalidConfigException;

use LitEmoji\LitEmoji;

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

    public static function defineSources(string $context = null): array
    {
        $sources = [[
            'key' => '*',
            'label' => Craft::t('wishlist', 'All items'),
            'defaultSort' => ['dateCreated', 'desc'],
        ]];

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('wishlist', 'Are you sure you want to delete the selected items?'),
            'successMessage' => Craft::t('wishlist', 'Items deleted.'),
        ]);

        return $actions;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'elementDisplay'];
    }


    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Item'),
            'elementDisplay' => Craft::t('app', 'Type'),
            'dateCreated' => Craft::t('app', 'Date Created'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Item')],
            'elementDisplay' => ['label' => Craft::t('app', 'Type')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        $attributes[] = 'title';
        $attributes[] = 'elementDisplay';
        $attributes[] = 'dateCreated';

        return $attributes;
    }


    // Properties
    // =========================================================================

    public $id;
    public $elementId;
    public $elementSiteId;
    public $elementClass;
    public $listId;

    private $_element;
    private $_list;
    private $_fieldLayout;
    private $_listItemIds = [];
    private $_options = [];


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        if ($element = $this->getElement()) {
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
        if ($this->_list !== null) {
            return $this->_list;
        }

        if ($this->listId === null) {
            return null;
        }

        $list = Wishlist::$plugin->getLists()->getListById($this->listId);

        if (!$list) {
            return null;
        }

        return $this->_list = $list;
    }

    public function getElementDisplay()
    {
        return $this->elementClass ? $this->elementClass::displayName() : null;
    }

    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('wishlist/lists/' . $this->getList()->type->handle . '/' . $this->listId . '/items/' . $this->id);
    }
    
    public function getFieldLayout()
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        if ($list = $this->getList()) {
            return $this->_fieldLayout = $list->getType()->getItemFieldLayout();
        }

        return $this->_fieldLayout = parent::getFieldLayout();
    }

    public function getInList()
    {
        if ($this->id && $list = $this->getList()) {
            if (!$this->_listItemIds) {
                $this->_listItemIds = $list->getItems()->ids();
            }

            return in_array($this->id, $this->_listItemIds);
        }

        return false;
    }

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function setOptions($options)
    {
        $options = Json::decodeIfJson($options);

        if (!is_array($options)) {
            $options = [];
        }

        $cleanEmojiValues = static function(&$options) use (&$cleanEmojiValues) {
            foreach ($options as $key => $value) {
                if (is_array($value)) {
                    $cleanEmojiValues($options[$key]);
                } else {
                    if (is_string($value)) {
                        $options[$key] = LitEmoji::unicodeToShortcode($value);
                    }
                }
            }

            return $options;
        };

        // TODO make this consistent no matter what the DB driver is. Will be a "breaking" change.
        if (Craft::$app->getDb()->getSupportsMb4()) {
            $this->_options = $options;
        } else {
            $this->_options = $cleanEmojiValues($options);
        }
    }

    public function getOptionsSignature()
    {
        return Wishlist::getInstance()->getItems()->getOptionsSignature($this->_options);
    }

    public function setOptionsSignature($value)
    {
        // Read-only value, but method exists to prevent query errors
    }

    public function setFieldValuesFromRequest(string $paramNamespace = '')
    {
        $this->setFieldParamNamespace($paramNamespace);
        $values = Craft::$app->getRequest()->getParam($paramNamespace, []);

        foreach ($this->fieldLayoutFields() as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } else if (!empty($this->_fieldParamNamePrefix) && UploadedFile::getInstancesByName($this->_fieldParamNamePrefix . '.' . $field->handle)) {
                // A file was uploaded for this field
                $value = null;
            } else {
                continue;
            }

            $this->setFieldValue($field->handle, $value);

            // Normalize it now in case the system language changes later
            $this->normalizeFieldValue($field->handle);
        }
    }

    public static function gqlTypeNameByContext($context): string
    {
        return 'Item';
    }

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }


    // URLs
    // -------------------------------------------------------------------------

    public function getAddUrl($params = [])
    {
        $params = array_merge([ 'elementId' => $this->elementId, 'listId' => $this->listId ], $params);

        return UrlHelper::actionUrl('wishlist/items/add', $params);
    }

    public function getRemoveUrl($params = [])
    {
        $params = array_merge([ 'elementId' => $this->elementId, 'listId' => $this->listId ], $params);

        return UrlHelper::actionUrl('wishlist/items/remove', $params);
    }

    public function getToggleUrl($params = [])
    {
        $params = array_merge([ 'elementId' => $this->elementId, 'listId' => $this->listId ], $params);
        
        return UrlHelper::actionUrl('wishlist/items/toggle', $params);
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

        $record->options = $this->getOptions();
        $record->optionsSignature = $this->getOptionsSignature();

        $record->save(false);

        $this->id = $record->id;

        parent::afterSave($isNew);
    }
}
