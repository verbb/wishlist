<?php
namespace verbb\wishlist\elements;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\db\ListQuery;
use verbb\wishlist\models\ListTypeModel;
use verbb\wishlist\records\ListRecord;

use Craft;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;

use yii\base\Exception;
use yii\base\InvalidConfigException;

class ListElement extends Element
{
    // Properties
    // =========================================================================

    public $reference;
    public $lastIp;
    public $typeId;
    public $userId;
    public $sessionId;
    public $default;

    private $_listType;


    // Public Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('wishlist', 'Wishlist List');
    }

    public function __toString(): string
    {
        return (string)$this->title;
    }

    public function getName()
    {
        return $this->title;
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
                    'editable' => $editable
                ],
                'defaultSort' => ['postDate', 'desc']
            ]
        ];

        $sources[] = ['heading' => Craft::t('wishlist', 'List Types')];

        foreach ($listTypes as $listType) {
            $key = 'listType:'.$listType->id;
            $canEditLists = Craft::$app->getUser()->checkPermission('wishlist-manageListType:'.$listType->id);

            $sources[$key] = [
                'key' => $key,
                'label' => $listType->name,
                'data' => [
                    'handle' => $listType->handle,
                    'editable' => $canEditLists
                ],
                'criteria' => ['typeId' => $listType->id, 'editable' => $editable]
            ];
        }

        return $sources;
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

    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = [['typeId'], 'required'];

        return $rules;
    }

    public static function find(): ElementQueryInterface
    {
        return new ListQuery(static::class);
    }

    public function getIsEditable(): bool
    {
        if ($this->getType()) {
            $id = $this->getType()->id;

            return Craft::$app->getUser()->checkPermission('wishlist-manageListType:'.$id);
        }

        return false;
    }

    public function getCpEditUrl()
    {
        $listType = $this->getType();

        if ($listType) {
            return UrlHelper::cpUrl('wishlist/lists/' . $listType->handle . '/' . $this->id);
        }

        return null;
    }

    public function getFieldLayout()
    {
        $listType = $this->getType();

        return $listType ? $listType->getListFieldLayout() : null;
    }

    public function getType()
    {
        if ($this->_listType) {
            return $this->_listType;
        }

        return $this->typeId ? $this->_listType = Wishlist::$plugin->getListTypes()->getListTypeById($this->typeId) : null;
    }

    public function getItems()
    {
        return $this->id ? Item::find()->listId($this->id) : null;
    }

    public function getUser()
    {
        return $this->userId ? User::find()->id($this->userId)->one() : null;
    }

    public function getOwnerId()
    {
        return $this->userId ?? $this->sessionId ?? null;
    }

    public function getOwner()
    {
        if ($this->userId) {
            return $this->getUser();
        }

        return null;
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


    // URLs
    // -------------------------------------------------------------------------

    public function getAddToCartUrl()
    {
        return UrlHelper::actionUrl('wishlist/lists/add-to-cart', [ 'listId' => $this->id ]);
    }


    // Events
    // -------------------------------------------------------------------------

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $listRecord = ListRecord::findOne($this->id);

            if (!$listRecord) {
                throw new Exception('Invalid list id: '.$this->id);
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

        return parent::afterSave($isNew);
    }


    // Protected methods
    // =========================================================================

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

    protected function tableAttributeHtml(string $attribute): string
    {
        /* @var $listType ListType */
        $listType = $this->getType();

        switch ($attribute) {
            case 'owner':
                $owner = $this->getOwner();

                if ($owner) {
                    return '<a href="' . $owner->getCpEditUrl() . '">' . $owner . '</a>';
                }

                return Craft::t('wishlist', 'Guest');

            case 'type':
                return ($listType ? Craft::t('site', $listType->name) : '');

            case 'items':
                return $this->items->count();

            default:
                return parent::tableAttributeHtml($attribute);
        }
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated'
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated'
            ],
        ];
    }
}
