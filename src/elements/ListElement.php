<?php
namespace verbb\wishlist\elements;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\db\ItemQuery;
use verbb\wishlist\elements\db\ListQuery;
use verbb\wishlist\helpers\UrlHelper;
use verbb\wishlist\models\ListType;
use verbb\wishlist\records\ListRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\models\FieldLayout;
use craft\web\UploadedFile;

use yii\base\Exception;

class ListElement extends Element
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('wishlist', 'Wishlist List');
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function defineSources(string $context = null): array
    {
        if ($context === 'index') {
            $listTypes = Wishlist::$plugin->getListTypes()->getEditableListTypes();
            $editable = true;
        } else {
            $listTypes = Wishlist::$plugin->getListTypes()->getAllListTypes();
            $editable = false;
        }

        $listTypeIds = [];

        foreach ($listTypes as $listType) {
            $listTypeIds[] = $listType->id;
        }

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('wishlist', 'All lists'),
                'criteria' => [
                    'typeId' => $listTypeIds,
                    'editable' => $editable,
                ],
                'defaultSort' => ['postDate', 'desc'],
            ],
        ];

        $sources[] = ['heading' => Craft::t('wishlist', 'List Types')];

        foreach ($listTypes as $listType) {
            $key = 'listType:' . $listType->uid;
            $canEditLists = Craft::$app->getUser()->checkPermission('wishlist-manageListType:' . $listType->uid);

            $sources[$key] = [
                'key' => $key,
                'label' => $listType->name,
                'data' => [
                    'handle' => $listType->handle,
                    'editable' => $canEditLists,
                ],
                'criteria' => ['typeId' => $listType->id, 'editable' => $editable],
            ];
        }

        return $sources;
    }

    public static function find(): ListQuery
    {
        return new ListQuery(static::class);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'List';
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'confirmationMessage' => Craft::t('wishlist', 'Are you sure you want to delete the selected lists?'),
            'successMessage' => Craft::t('wishlist', 'Lists deleted.'),
        ]);

        return $actions;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'type' => ['label' => Craft::t('wishlist', 'List Type')],
            'owner' => ['label' => Craft::t('wishlist', 'Owner')],
            'items' => ['label' => Craft::t('wishlist', 'Items')],
            'reference' => ['label' => Craft::t('wishlist', 'Reference')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'type';
        }

        return $attributes;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
            ],
        ];
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if ($handle == 'items') {
            $sourceElementIds = ArrayHelper::getColumn($sourceElements, 'id');

            $map = (new Query())
                ->select('listId as source, id as target')
                ->from(['{{%wishlist_items}}'])
                ->where(['listId' => $sourceElementIds])
                ->all();

            return [
                'elementType' => Item::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }


    // Properties
    // =========================================================================

    public ?string $reference = null;
    public ?string $lastIp = null;
    public ?int $typeId = null;
    public ?int $userId = null;
    public ?string $sessionId = null;
    public ?bool $default = null;

    private ?ListType $_listType = null;
    private ?User $_owner = null;
    private ?User $_user = null;
    private ?FieldLayout $_fieldLayout = null;
    private array $_items = [];


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->title;
    }

    public function getName(): ?string
    {
        return $this->title;
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

    public function isEmpty(): bool
    {
        return count($this->getItems()) ? false : true;
    }

    public function getIsEditable(): bool
    {
        if ($type = $this->getType()) {
            return Craft::$app->getUser()->checkPermission('wishlist-manageListType:' . $type->uid);
        }

        return false;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $listType = $this->getType();

        if (!$listType) {
            return null;
        }

        return $this->_fieldLayout = $listType->getListFieldLayout();
    }

    public function getType(): ListType
    {
        if ($this->_listType) {
            return $this->_listType;
        }

        $listTypeService = Wishlist::$plugin->getListTypes();

        if ($this->typeId && $listType = $listTypeService->getListTypeById($this->typeId)) {
            return $this->_listType = $listType;
        }

        return $this->_listType = $listTypeService->getDefaultListType();
    }

    public function setType(ListType $listType): void
    {
        $this->_listType = $listType;
        $this->typeId = $listType->id;
    }

    public function setEagerLoadedElements(string $handle, array $elements): void
    {
        if ($handle == 'items') {
            $this->setItems($elements);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    public function getItems(): array
    {
        if ($this->_items) {
            return $this->_items;
        }

        if ($this->id) {
            return $this->_items = Item::find()->listId($this->id)->all();
        }

        return [];
    }

    public function setItems(array $items): void
    {
        $this->_items = $items;
    }

    public function getItem(ElementInterface $element, array $params = []): ?Item
    {
        if ($this->id) {
            $query = Item::find()
                ->listId($this->id)
                ->elementId($element->id)
                ->elementSiteId($element->siteId);

            Craft::configure($query, $params);

            return $query->one();
        }

        return null;
    }

    public function getHasItem(Item $item): bool
    {
        return $item->getInList($this);
    }

    public function getUser(): ?User
    {
        if ($this->_user !== null) {
            return $this->_user;
        }

        if ($this->userId === null) {
            return null;
        }

        return $this->_user = User::find()
            ->status(null)
            ->id($this->userId)
            ->one();
    }

    public function getOwnerId(): int|string|null
    {
        return $this->userId ?? $this->sessionId ?? null;
    }

    public function getOwner(): ?User
    {
        if ($this->_owner !== null) {
            return $this->_owner;
        }

        if ($this->userId === null) {
            return null;
        }

        return $this->_owner = $this->getUser();
    }

    public function getAddItemUrl(ElementInterface $element, array $params = []): string
    {
        $params = array_merge(['listType' => $this->getType()->handle], $params);

        return UrlHelper::addUrl($element, $params);
    }

    public function getToggleItemUrl(ElementInterface $element, array $params = []): string
    {
        $params = array_merge(['listType' => $this->getType()->handle], $params);
        
        return UrlHelper::toggleUrl($element, $params);
    }

    public function getRemoveItemUrl(ElementInterface $element, array $params = []): string
    {
        $params = array_merge(['listType' => $this->getType()->handle], $params);
        
        return UrlHelper::removeUrl($element, $params);
    }

    public function getPdfUrl(): string
    {
        return UrlHelper::actionUrl("wishlist/pdf?listId={$this->id}");
    }

    public function getGqlTypeName(): string
    {
        return static::gqlTypeNameByContext($this);
    }

    public function getAddToCartUrl(): string
    {
        return UrlHelper::actionUrl('wishlist/lists/add-to-cart', ['listId' => $this->id]);
    }

    public function beforeSave(bool $isNew): bool
    {
        if ($this->duplicateOf && $isNew) {
            // Ensure when duplicating to reset some values
            $this->reference = Wishlist::$plugin->getLists()->generateReferenceNumber();
            $this->lastIp = null;
        }

        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $listRecord = ListRecord::findOne($this->id);

            if (!$listRecord) {
                throw new Exception('Invalid list id: ' . $this->id);
            }
        } else {
            $listRecord = new ListRecord();
            $listRecord->id = $this->id;
        }

        $listRecord->typeId = $this->typeId;
        $listRecord->reference = $this->reference;
        $listRecord->lastIp = $this->lastIp;
        $listRecord->userId = $this->userId;
        $listRecord->sessionId = $this->sessionId;
        $listRecord->default = $this->default;

        $listRecord->save(false);

        parent::afterSave($isNew);
    }


    // Protected methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['typeId'], 'required'];

        return $rules;
    }

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute == 'owner') {
            $owner = $this->getOwner();

            return $owner ? Cp::elementChipHtml($owner) : Craft::t('wishlist', 'Guest');
        } else if ($attribute == 'type') {
            if ($listType = $this->getType()) {
                return Craft::t('site', $listType->name);
            }

            return '';
        } else if ($attribute == 'items') {
            return count($this->getItems());
        }

        return parent::attributeHtml($attribute);
    }

    protected function cpEditUrl(): ?string
    {
        if ($listType = $this->getType()) {
            return UrlHelper::cpUrl('wishlist/lists/' . $listType->handle . '/' . $this->id);
        }

        return null;
    }
}
