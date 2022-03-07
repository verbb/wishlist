<?php
namespace verbb\wishlist\services;

use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\models\ListType;
use verbb\wishlist\records\ListType as ListTypeRecord;

use Craft;
use craft\base\Field;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

use yii\base\Component;
use yii\web\HttpException;

use Throwable;

class ListTypes extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_SAVE_LISTTYPE = 'beforeSaveListType';
    public const EVENT_AFTER_SAVE_LISTTYPE = 'afterSaveListType';
    public const CONFIG_LISTTYPES_KEY = 'wishlist.listTypes';


    // Properties
    // =========================================================================

    private bool $_fetchedAllListTypes = false;
    private ?array $_listTypesById = null;
    private ?array $_listTypesByHandle = null;
    private ?array $_allListTypeIds = null;
    private ?array $_editableListTypeIds = null;
    private ?ListType $_defaultListType = null;
    private array $_savingListTypes = [];


    // Public Methods
    // =========================================================================

    public function getEditableListTypes(): array
    {
        $editableListTypeIds = $this->getEditableListTypeIds();
        $editableListTypes = [];

        foreach ($this->getAllListTypes() as $listTypes) {
            if (in_array($listTypes->id, $editableListTypeIds, false)) {
                $editableListTypes[] = $listTypes;
            }
        }

        return $editableListTypes;
    }

    public function getEditableListTypeIds(): ?array
    {
        if (null === $this->_editableListTypeIds) {
            $this->_editableListTypeIds = [];
            $allListTypeIds = $this->getAllListTypeIds();

            foreach ($allListTypeIds as $listTypeId) {
                if (Craft::$app->getUser()->checkPermission('wishlist-manageListType:' . $listTypeId)) {
                    $this->_editableListTypeIds[] = $listTypeId;
                }
            }
        }

        return $this->_editableListTypeIds;
    }

    public function getAllListTypeIds(): ?array
    {
        if (null === $this->_allListTypeIds) {
            $this->_allListTypeIds = [];
            $listTypes = $this->getAllListTypes();

            foreach ($listTypes as $listType) {
                $this->_allListTypeIds[] = $listType->id;
            }
        }

        return $this->_allListTypeIds;
    }

    public function getAllListTypes(): array
    {
        if (!$this->_fetchedAllListTypes) {
            $results = $this->_createListTypeQuery()->all();

            foreach ($results as $result) {
                $this->_memoizeListType(new ListType($result));
            }

            $this->_fetchedAllListTypes = true;
        }

        return $this->_listTypesById ?: [];
    }

    public function getListTypeByHandle($handle)
    {
        if (isset($this->_listTypesByHandle[$handle])) {
            return $this->_listTypesByHandle[$handle];
        }

        if ($this->_fetchedAllListTypes) {
            return null;
        }

        $result = $this->_createListTypeQuery()
            ->where(['handle' => $handle])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeListType(new ListType($result));

        return $this->_listTypesByHandle[$handle];
    }

    public function getDefaultListType(): ?ListType
    {
        if ($this->_defaultListType !== null) {
            return $this->_defaultListType;
        }

        $row = $this->_createListTypeQuery()
            ->where(['default' => 1])
            ->one();

        if (!$row) {
            $row = $this->_createListTypeQuery()->one();

            if (!$row) {
                return null;
            }
        }

        return $this->_defaultListType = new ListType($row);
    }

    public function saveListType(ListType $listType, bool $runValidation = true): bool
    {
        $isNewListType = !$listType->id;

        // Fire a 'beforeSaveListType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_LISTTYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_LISTTYPE, new ListTypeEvent([
                'listType' => $listType,
                'isNew' => $isNewListType,
            ]));
        }

        if ($runValidation && !$listType->validate()) {
            Craft::info('List type not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($isNewListType) {
            $listType->uid = StringHelper::UUID();
        } else {
            $existingListTypeRecord = ListTypeRecord::find()
                ->where(['id' => $listType->id])
                ->one();

            if (!$existingListTypeRecord) {
                throw new HttpException(404, "No list type exists with the ID '{$listType->id}'");
            }

            $listType->uid = $existingListTypeRecord->uid;
        }

        $this->_savingListTypes[$listType->uid] = $listType;

        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'name' => $listType->name,
            'handle' => $listType->handle,
            'default' => $listType->default,
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

        $configData['listFieldLayouts'] = $generateLayoutConfig($listType->getFieldLayout());
        $configData['itemFieldLayouts'] = $generateLayoutConfig($listType->getItemFieldLayout());

        $configPath = self::CONFIG_LISTTYPES_KEY . '.' . $listType->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewListType) {
            $listType->id = Db::idByUid('{{%wishlist_listtypes}}', $listType->uid);
        }

        return true;
    }

    public function handleChangedListType(ConfigEvent $event): void
    {
        $listTypeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields and sites are processed
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Basic data
            $listTypeRecord = $this->_getListTypeRecord($listTypeUid);
            $isNewListType = $listTypeRecord->getIsNewRecord();
            $fieldsService = Craft::$app->getFields();

            $listTypeRecord->uid = $listTypeUid;
            $listTypeRecord->name = $data['name'];
            $listTypeRecord->handle = $data['handle'];
            $listTypeRecord->default = $data['default'];

            if (!empty($data['listFieldLayouts']) && !empty($config = reset($data['listFieldLayouts']))) {
                // Save the main field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $listTypeRecord->fieldLayoutId;
                $layout->type = ListElement::class;
                $layout->uid = key($data['listFieldLayouts']);

                $fieldsService->saveLayout($layout);

                $listTypeRecord->fieldLayoutId = $layout->id;
            } else if ($listTypeRecord->fieldLayoutId) {
                // Delete the main field layout
                $fieldsService->deleteLayoutById($listTypeRecord->fieldLayoutId);
                $listTypeRecord->fieldLayoutId = null;
            }

            if (!empty($data['itemFieldLayouts']) && !empty($config = reset($data['itemFieldLayouts']))) {
                // Save the item field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $listTypeRecord->itemFieldLayoutId;
                $layout->type = Item::class;
                $layout->uid = key($data['itemFieldLayouts']);

                $fieldsService->saveLayout($layout);

                $listTypeRecord->itemFieldLayoutId = $layout->id;
            } else if ($listTypeRecord->itemFieldLayoutId) {
                // Delete the item field layout
                $fieldsService->deleteLayoutById($listTypeRecord->itemFieldLayoutId);
                $listTypeRecord->itemFieldLayoutId = null;
            }

            // // If this was the default make all others not the default.
            // if ($listType->default) {
            //     ListTypeRecord::updateAll(['default' => 0], ['not', ['id' => $listTypeRecord->id]]);
            // }

            $listTypeRecord->save(false);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_allListTypeIds = null;
        $this->_editableListTypeIds = null;
        $this->_fetchedAllListTypes = false;

        unset(
            $this->_listTypesById[$listTypeRecord->id],
            $this->_listTypesByHandle[$listTypeRecord->handle]
        );

        // Fire an 'afterSaveListType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_LISTTYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_LISTTYPE, new ListTypeEvent([
                'listType' => $this->getListTypeById($listTypeRecord->id),
                'isNew' => empty($this->_savingListTypes[$listTypeUid]),
            ]));
        }
    }

    public function deleteListTypeById(int $id): bool
    {
        $listType = $this->getListTypeById($id);
        Craft::$app->getProjectConfig()->remove(self::CONFIG_LISTTYPES_KEY . '.' . $listType->uid);
        return true;
    }

    public function handleDeletedListType(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $listTypeRecord = $this->_getListTypeRecord($uid);

        if (!$listTypeRecord->id) {
            return;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $lists = ListElement::find()
                ->typeId($listTypeRecord->id)
                ->anyStatus()
                ->limit(null)
                ->all();

            foreach ($lists as $list) {
                Craft::$app->getElements()->deleteElement($list);
            }

            $fieldLayoutId = $listTypeRecord->fieldLayoutId;
            $itemFieldLayoutId = $listTypeRecord->itemFieldLayoutId;
            Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);

            if ($itemFieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($itemFieldLayoutId);
            }

            $listTypeRecord->delete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Clear caches
        $this->_allListTypeIds = null;
        $this->_editableListTypeIds = null;
        $this->_fetchedAllListTypes = false;

        unset(
            $this->_listTypesById[$listTypeRecord->id],
            $this->_listTypesByHandle[$listTypeRecord->handle]
        );
    }

    public function pruneDeletedField(FieldEvent $event): void
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $listTypes = $projectConfig->get(self::CONFIG_LISTTYPES_KEY);

        // Loop through the list types and prune the UID from field layouts.
        if (is_array($listTypes)) {
            foreach ($listTypes as $listTypeUid => $listType) {
                if (!empty($listType['listFieldLayouts'])) {
                    foreach ($listType['listFieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_LISTTYPES_KEY . '.' . $listTypeUid . '.listFieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                            }
                        }
                    }
                }

                if (!empty($listType['itemFieldLayouts'])) {
                    foreach ($listType['itemFieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_LISTTYPES_KEY . '.' . $listTypeUid . '.itemFieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid);
                            }
                        }
                    }
                }
            }
        }
    }

    public function getListTypeById(int $listTypeId): ?ListType
    {
        if (isset($this->_listTypesById[$listTypeId])) {
            return $this->_listTypesById[$listTypeId];
        }

        if ($this->_fetchedAllListTypes) {
            return null;
        }

        $result = $this->_createListTypeQuery()
            ->where(['id' => $listTypeId])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeListType(new ListType($result));

        return $this->_listTypesById[$listTypeId];
    }

    public function getListTypeByUid(string $uid): ?ListType
    {
        return ArrayHelper::firstWhere($this->getAllListTypes(), 'uid', $uid, true);
    }


    // Private methods
    // =========================================================================

    private function _memoizeListType(ListType $listType): void
    {
        $this->_listTypesById[$listType->id] = $listType;
        $this->_listTypesByHandle[$listType->handle] = $listType;
    }

    private function _createListTypeQuery(): Query
    {
        return (new Query())
            ->select([
                'listTypes.id',
                'listTypes.fieldLayoutId',
                'listTypes.itemFieldLayoutId',
                'listTypes.name',
                'listTypes.handle',
                'listTypes.default',
                'listTypes.uid',
            ])
            ->from(['{{%wishlist_listtypes}} listTypes']);
    }

    private function _getListTypeRecord(string $uid): ListTypeRecord
    {
        if ($listType = ListTypeRecord::findOne(['uid' => $uid])) {
            return $listType;
        }

        return new ListTypeRecord();
    }
}
