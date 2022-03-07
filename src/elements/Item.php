<?php
namespace verbb\wishlist\elements;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\db\ItemQuery;
use verbb\wishlist\records\Item as ItemRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\actions\Delete;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\UploadedFile;

use yii\base\Exception;

use LitEmoji\LitEmoji;

class Item extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('wishlist', 'Wishlist Item');
    }

    public static function refHandle(): ?string
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

    public static function find(): ItemQuery
    {
        return new ItemQuery(static::class);
    }

    public static function defineSources(string $context = null): array
    {
        return [[
            'key' => '*',
            'label' => Craft::t('wishlist', 'All items'),
            'defaultSort' => ['dateCreated', 'desc'],
        ]];
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

    public ?int $id = null;
    public ?int $elementId = null;
    public ?int $elementSiteId = null;
    public ?string $elementClass = null;
    public ?int $listId = null;

    private ?ElementInterface $_element = null;
    private ?ListElement $_list = null;
    private ?FieldLayout $_fieldLayout = null;
    private array $_listItemIds = [];
    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        $this->updateTitle();
    }

    public function updateTitle(): void
    {
        if ($element = $this->getElement()) {
            $this->title = $element->title;
        }
    }

    public function getElement(): ?ElementInterface
    {
        if ($this->_element !== null) {
            return $this->_element;
        }

        if ($this->elementId === null) {
            return null;
        }

        return $this->_element = Craft::$app->getElements()->getElementById($this->elementId, $this->elementClass, $this->elementSiteId);
    }

    public function setElement($element = null): void
    {
        $this->_element = $element;
    }

    public function getList(): ?ListElement
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

    public function getElementDisplay(): ?string
    {
        return $this->elementClass ? $this->elementClass::displayName() : null;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('wishlist/lists/' . $this->getList()->getType()->handle . '/' . $this->listId . '/items/' . $this->id);
    }
    
    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        if ($list = $this->getList()) {
            return $this->_fieldLayout = $list->getType()->getItemFieldLayout();
        }

        return $this->_fieldLayout = parent::getFieldLayout();
    }

    public function getInList(): bool
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

    public function setOptions($options): void
    {
        $options = Json::decodeIfJson($options);

        if (!is_array($options)) {
            $options = [];
        }

        $cleanEmojiValues = static function(&$options) use (&$cleanEmojiValues) {
            foreach ($options as $key => $value) {
                if (is_array($value)) {
                    $cleanEmojiValues($value);
                } else if (is_string($value)) {
                    $options[$key] = LitEmoji::unicodeToShortcode($value);
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

    public function getOptionsSignature(): string
    {
        return Wishlist::$plugin->getItems()->getOptionsSignature($this->_options);
    }

    public function setOptionsSignature($value): void
    {
        // Read-only value, but method exists to prevent query errors
    }

    public function setFieldValuesFromRequest(string $paramNamespace = ''): void
    {
        $this->setFieldParamNamespace($paramNamespace);
        $values = Craft::$app->getRequest()->getParam($paramNamespace, []);

        foreach ($this->fieldLayoutFields() as $field) {
            // Do we have any post data for this field?
            if (isset($values[$field->handle])) {
                $value = $values[$field->handle];
            } else if (!empty($this->getFieldParamNamespace()) && UploadedFile::getInstancesByName($this->getFieldParamNamespace() . '.' . $field->handle)) {
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

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'Item';
    }

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }


    // URLs
    // -------------------------------------------------------------------------

    public function getAddUrl($params = []): string
    {
        $params = array_merge([ 'elementId' => $this->elementId, 'listId' => $this->listId ], $params);

        return UrlHelper::actionUrl('wishlist/items/add', $params);
    }

    public function getRemoveUrl($params = []): string
    {
        $params = array_merge([ 'elementId' => $this->elementId, 'listId' => $this->listId ], $params);

        return UrlHelper::actionUrl('wishlist/items/remove', $params);
    }

    public function getToggleUrl($params = []): string
    {
        $params = array_merge([ 'elementId' => $this->elementId, 'listId' => $this->listId ], $params);
        
        return UrlHelper::actionUrl('wishlist/items/toggle', $params);
    }


    // Events
    // -------------------------------------------------------------------------

    public function afterSave(bool $isNew): void
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

        // Refresh the title on-save
        $this->updateTitle();

        parent::afterSave($isNew);
    }
}
