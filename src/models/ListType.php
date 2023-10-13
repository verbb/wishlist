<?php
namespace verbb\wishlist\models;

use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\records\ListType as ListTypeRecord;

use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class ListType extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?int $fieldLayoutId = null;
    public ?int $itemFieldLayoutId = null;
    public ?bool $default = null;
    public ?string $uid = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return (string)$this->handle;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('wishlist/list-types/' . $this->id);
    }

    public function getCpEditItemUrl(): string
    {
        return UrlHelper::cpUrl('wishlist/list-types/' . $this->id . '/item');
    }

    public function getListFieldLayout(): FieldLayout
    {
        return $this->getBehavior('listFieldLayout')->getFieldLayout();
    }

    public function getItemFieldLayout(): FieldLayout
    {
        return $this->getBehavior('itemFieldLayout')->getFieldLayout();
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'default' => $this->default,
        ];

        $generateLayoutConfig = function(FieldLayout $fieldLayout): array {
            $fieldLayoutConfig = $fieldLayout->getConfig();

            if ($fieldLayoutConfig) {
                if (empty($fieldLayout->id)) {
                    $layoutUid = StringHelper::UUID();
                    $fieldLayout->uid = $layoutUid;
                } else {
                    $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
                }

                return [$layoutUid => $fieldLayoutConfig];
            }

            return [];
        };

        $config['listFieldLayouts'] = $generateLayoutConfig($this->getFieldLayout());
        $config['itemFieldLayouts'] = $generateLayoutConfig($this->getItemFieldLayout());

        return $config;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        return [
            [['id', 'fieldLayoutId', 'itemFieldLayoutId'], 'number', 'integerOnly' => true],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['handle'], UniqueValidator::class, 'targetClass' => ListTypeRecord::class, 'targetAttribute' => ['handle'], 'message' => 'Not Unique'],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
        ];
    }

    protected function defineBehaviors(): array
    {
        $behaviors = parent::defineBehaviors();

        $behaviors['listFieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => ListElement::class,
            'idAttribute' => 'fieldLayoutId',
        ];

        $behaviors['itemFieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Item::class,
            'idAttribute' => 'itemFieldLayoutId',
        ];

        return $behaviors;
    }
}
