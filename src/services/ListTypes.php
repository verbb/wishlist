<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\models\ListType;
use verbb\wishlist\records\ListType as ListTypeRecord;

use Craft;
use craft\base\Field;
use craft\base\MemoizableArray;
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

    private ?MemoizableArray $_listTypes = null;


    // Public Methods
    // =========================================================================

    public function getAllListTypes(): array
    {
        return $this->_listTypes()->all();
    }

    public function getAllListTypeIds(): array
    {
        return ArrayHelper::getColumn($this->getAllListTypes(), 'id', false);
    }

    public function getListTypeByHandle(string $handle): ?ListType
    {
        return $this->_listTypes()->firstWhere('handle', $handle, true);
    }

    public function getListTypeById(int $id): ?ListType
    {
        return $this->_listTypes()->firstWhere('id', $id);
    }

    public function getListTypeByUid(string $uid): ?ListType
    {
        return $this->_listTypes()->firstWhere('uid', $uid, true);
    }

    public function getDefaultListType(): ?ListType
    {
        return $this->_listTypes()->firstWhere('default', true);
    }

    public function getEditableListTypes(): array
    {
        $userSession = Craft::$app->getUser();
        
        return ArrayHelper::where($this->getAllListTypes(), function(ListType $listType) use ($userSession) {
            return $userSession->checkPermission("wishlist-manageListType:$listType->uid");
        }, true, true, false);
    }

    public function getEditableListTypeIds(): array
    {
        return ArrayHelper::getColumn($this->getEditableListTypes(), 'id', false);
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
            Wishlist::info('List type not saved due to validation error.');

            return false;
        }

        if ($isNewListType) {
            $listType->uid = StringHelper::UUID();
        } else if (!$listType->uid) {
            $listType->uid = Db::uidById('{{%wishlist_listtypes}}', $listType->id);
        }

        $configPath = self::CONFIG_LISTTYPES_KEY . '.' . $listType->uid;
        Craft::$app->getProjectConfig()->set($configPath, $listType->getConfig(), "Save the “{$listType->handle}” list type");

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
        $fieldsService = Craft::$app->getFields();

        try {
            // Basic data
            $listTypeRecord = $this->_getListTypeRecord($listTypeUid);
            $isNewListType = $listTypeRecord->getIsNewRecord();

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

                $fieldsService->saveLayout($layout, false);

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

                $fieldsService->saveLayout($layout, false);

                $listTypeRecord->itemFieldLayoutId = $layout->id;
            } else if ($listTypeRecord->itemFieldLayoutId) {
                // Delete the item field layout
                $fieldsService->deleteLayoutById($listTypeRecord->itemFieldLayoutId);
                $listTypeRecord->itemFieldLayoutId = null;
            }

            $listTypeRecord->save(false);

            // If this was the default make all others not the default.
            if ($listTypeRecord->default) {
                foreach ($this->getAllListTypes() as $otherListType) {
                    if ($otherListType->uid !== $listTypeUid) {
                        $otherListType->default = false;

                        $this->saveListType($otherListType);
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_listTypes = null;

        // Fire an 'afterSaveListType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_LISTTYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_LISTTYPE, new ListTypeEvent([
                'listType' => $this->getListTypeById($listTypeRecord->id),
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
        $elementsService = Craft::$app->getElements();
        $fieldsService = Craft::$app->getFields();

        try {
            $lists = ListElement::find()
                ->typeId($listTypeRecord->id)
                ->status(null)
                ->all();

            foreach ($lists as $list) {
                $elementsService->deleteElement($list);
            }

            if ($listTypeRecord->fieldLayoutId) {
                $fieldsService->deleteLayoutById($listTypeRecord->fieldLayoutId);
            }

            if ($listTypeRecord->itemFieldLayoutId) {
                $fieldsService->deleteLayoutById($listTypeRecord->itemFieldLayoutId);
            }

            Db::delete('{{%wishlist_listtypes}}', ['id' => $listTypeRecord->id]);

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Clear caches
        $this->_listTypes = null;

        // Invalidate caches
        Craft::$app->getElements()->invalidateCachesForElementType(ListElement::class);
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


    // Private methods
    // =========================================================================

    private function _listTypes(): MemoizableArray
    {
        if (!isset($this->_listTypes)) {
            $listTypes = [];

            foreach ($this->_createListTypeQuery()->all() as $result) {
                $listTypes[] = new ListType($result);
            }

            $this->_listTypes = new MemoizableArray($listTypes);
        }

        return $this->_listTypes;
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
        return ListTypeRecord::findOne(['uid' => $uid]) ?? new ListTypeRecord();
    }
}
