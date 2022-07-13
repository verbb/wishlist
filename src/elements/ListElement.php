<?php
namespace verbb\wishlist\elements;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\db\ListQuery;
use verbb\wishlist\models\ListType;
use verbb\wishlist\records\ListRecord;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\elements\actions\Delete;
use craft\helpers\UrlHelper;
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

    public static function hasContent(): bool
    {
        return true;
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
            $key = 'listType:' . $listType->id;
            $canEditLists = Craft::$app->getUser()->checkPermission('wishlist-manageListType:' . $listType->id);

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

    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['typeId'], 'required'];

        return $rules;
    }

    public function getIsEditable(): bool
    {
        if ($type = $this->getType()) {
            return Craft::$app->getUser()->checkPermission('wishlist-manageListType:' . $type->id);
        }

        return false;
    }

    public function getCpEditUrl(): ?string
    {
        if ($listType = $this->getType()) {
            return UrlHelper::cpUrl('wishlist/lists/' . $listType->handle . '/' . $this->id);
        }

        return null;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->_fieldLayout !== null) {
            return $this->_fieldLayout;
        }

        $listType = $this->getType();

        if (!$listType) {
            return null;
        }

        return $this->_fieldLayout = $listType->getListFieldLayout();
    }

    public function getType(): ?ListType
    {
        if ($this->_listType !== null) {
            return $this->_listType;
        }

        if ($this->typeId === null) {
            return null;
        }

        return $this->_listType = Wishlist::$plugin->getListTypes()->getListTypeById($this->typeId);
    }

    public function getItems(): ?db\ItemQuery
    {
        return $this->id ? Item::find()->listId($this->id) : null;
    }

    public function getUser(): ?User
    {
        if ($this->_user !== null) {
            return $this->_user;
        }

        if ($this->userId === null) {
            return null;
        }

        return $this->_user = User::find()->id($this->userId)->one();
    }

    public function getOwnerId(): int|string|null
    {
        return $this->userId ?? $this->sessionId ?? null;
    }


    // URLs
    // -------------------------------------------------------------------------

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


    // Events
    // -------------------------------------------------------------------------

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


    // Protected methods
    // =========================================================================

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

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'owner':
                if ($owner = $this->getOwner()) {
                    return '<a href="' . $owner->getCpEditUrl() . '">' . $owner . '</a>';
                }

                return Craft::t('wishlist', 'Guest');
            case 'type':
                if ($listType = $this->getType()) {
                    return Craft::t('site', $listType->name);
                }

                return '';
            case 'items':
                return $this->getItems()->count();
            default:
                return parent::tableAttributeHtml($attribute);
        }
    }
}
