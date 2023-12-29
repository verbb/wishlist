<?php
namespace verbb\wishlist\elements;

use verbb\wishlist\Wishlist;
use verbb\wishlist\helpers\UrlHelper;
use verbb\wishlist\elements\db\ItemQuery;
use verbb\wishlist\records\Item as ItemRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\db\EagerLoadPlan;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\web\UploadedFile;

use yii\base\Exception;

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

    public static function trackChanges(): bool
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
        return [
            [
                'key' => '*',
                'label' => Craft::t('wishlist', 'All items'),
                'defaultSort' => ['dateCreated', 'desc'],
            ],
        ];
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'Item';
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

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|false|null
    {
        if ($handle === 'element') {
            // Get the source element IDs
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select(['id as source', 'elementId as target'])
                ->from(['{{%wishlist_items}}'])
                ->where(['and', ['id' => $sourceElementIds], ['not', ['elementId' => null]]])
                ->all();

            // This isn't amazing, but its benefit is pretty considerable. The thinking here is that its
            // unlikely you'll be fetching comments across multiple different element types
            $firstElement = $sourceElements[0] ?? [];

            if (!$firstElement) {
                return null;
            }

            return [
                'elementType' => $firstElement->elementClass,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }


    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?int $elementId = null;
    public ?int $elementSiteId = null;
    public ?string $elementClass = null;
    public ?int $listId = null;
    
    private ?ElementInterface $_element = null;
    private ?string $_elementTitle = null;
    private ?ListElement $_list = null;
    private ?FieldLayout $_fieldLayout = null;
    private array $_listItemIds = [];
    private array $_options = [];


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        $this->title = $this->getElementTitle();
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function canSave(User $user): bool
    {
        return true;
    }

    public function canDuplicate(User $user): bool
    {
        return true;
    }

    public function canDelete(User $user): bool
    {
        return true;
    }

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    public function getElementTitle(): ?string
    {
        if ($this->_elementTitle !== null) {
            return $this->_elementTitle;
        }

        if ($element = $this->getElement()) {
            return $element->title;
        }

        return null;
    }

    public function setElementTitle($value): void
    {
        $this->_elementTitle = $value;
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

    public function setElement(?ElementInterface $element = null): void
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

    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if ($handle === 'element') {
            $this->_element = $elements[0] ?? null;
        } else {
            parent::setEagerLoadedElements($handle, $elements, $plan);
        }
    }

    public function getInList(ListElement $list = null): bool
    {
        // Default to searching in the same list
        $list = $list ?? $this->getList();

        // Find the exact matching item
        $existingItem = Item::find()
            ->elementId($this->elementId)
            ->elementSiteId($this->elementSiteId)
            ->listId($list->id)
            ->optionsSignature($this->getOptionsSignature())
            ->one();

        return (bool)$existingItem;
    }

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function setOptions(string|array $options): void
    {
        $options = Json::decodeIfJson($options);

        if (!is_array($options)) {
            $options = [];
        }

        $this->_options = $options;
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

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }

    public function getAddUrl(array $params = []): ?string
    {
        $element = $this->getElement();

        if (!$element) {
            return null;
        }

        return UrlHelper::addUrl($element, $params);
    }

    public function getToggleUrl(array $params = []): ?string
    {
        $element = $this->getElement();

        if (!$element) {
            return null;
        }

        return UrlHelper::toggleUrl($element, $params);
    }

    public function getRemoveUrl(array $params = []): ?string
    {
        $element = $this->getElement();

        if (!$element) {
            return null;
        }

        $params = array_merge(['itemId' => $this->id], $params);
        
        return UrlHelper::removeUrl($element, $params);
    }

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

        parent::afterSave($isNew);
    }


    // Protected Methods
    // =========================================================================

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('wishlist/lists/' . $this->getList()->getType()->handle . '/' . $this->listId . '/items/' . $this->id);
    }
}
