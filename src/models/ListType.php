<?php
namespace verbb\wishlist\models;

use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\Plugin;
use verbb\wishlist\records\ListType as ListTypeRecord;

use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class ListType extends Model
{
    // Properties
    // =========================================================================

    public $id;
    public $name;
    public $handle;
    public $fieldLayoutId;
    public $itemFieldLayoutId;
    public $default;
    public $uid;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return $this->handle;
    }

    public function rules()
    {
        return [
            [['id', 'fieldLayoutId', 'itemFieldLayoutId'], 'number', 'integerOnly' => true],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['handle'], UniqueValidator::class, 'targetClass' => ListTypeRecord::class, 'targetAttribute' => ['handle'], 'message' => 'Not Unique'],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
        ];
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('wishlist/list-types/' . $this->id);
    }

    public function getCpEditItemUrl(): string
    {
        return UrlHelper::cpUrl('wishlist/list-types/' . $this->id . '/item');
    }

    public function getListFieldLayout(): FieldLayout
    {
        $behavior = $this->getBehavior('listFieldLayout');
        return $behavior->getFieldLayout();
    }

    public function getItemFieldLayout(): FieldLayout
    {
        $behavior = $this->getBehavior('itemFieldLayout');
        return $behavior->getFieldLayout();
    }

    public function behaviors(): array
    {
        return [
            'listFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => ListElement::class,
                'idAttribute' => 'fieldLayoutId'
            ],
            'itemFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Item::class,
                'idAttribute' => 'itemFieldLayoutId'
            ],
        ];
    }
}
