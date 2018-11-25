<?php
namespace verbb\wishlist\services;

use verbb\wishlist\elements\ListElement;
use verbb\wishlist\events\ListTypeEvent;
use verbb\wishlist\models\ListType;
use verbb\wishlist\records\ListRecord;
use verbb\wishlist\records\ListType as ListTypeRecord;

use Craft;
use craft\db\Query;
use yii\base\Component;
use yii\base\Exception;

class ListTypes extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_SAVE_LISTTYPE = 'beforeSaveListType';
    const EVENT_AFTER_SAVE_LISTTYPE = 'afterSaveListType';


    // Properties
    // =========================================================================

    private $_fetchedAllListTypes = false;
    private $_listTypesById;
    private $_listTypesByHandle;
    private $_allListTypeIds;
    private $_editableListTypeIds;
    private $_defaultListType;


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

    public function getEditableListTypeIds(): array
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

    public function getAllListTypeIds(): array
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

    public function getDefaultListType()
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

        if (!$isNewListType) {
            $listTypeRecord = ListTypeRecord::findOne($listType->id);

            if (!$listTypeRecord) {
                throw new ListTypeNotFoundException("No list type exists with the ID '{$listType->id}'");
            }
        } else {
            $listTypeRecord = new ListTypeRecord();
        }

        $listTypeRecord->name = $listType->name;
        $listTypeRecord->handle = $listType->handle;
        $listTypeRecord->default = $listType->default;

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // List Field Layout
            $fieldLayout = $listType->getListFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $listType->fieldLayoutId = $fieldLayout->id;
            $listTypeRecord->fieldLayoutId = $fieldLayout->id;

            // Item Field Layout
            $itemFieldLayout = $listType->getItemFieldLayout();
            Craft::$app->getFields()->saveLayout($itemFieldLayout);
            $listType->itemFieldLayoutId = $itemFieldLayout->id;
            $listTypeRecord->itemFieldLayoutId = $itemFieldLayout->id;

            // Save the list type
            $listTypeRecord->save(false);

            // Now that we have a list type ID, save it on the model
            if (!$listType->id) {
                $listType->id = $listTypeRecord->id;
            }

            // If this was the default make all others not the default.
            if ($listType->default) {
                ListTypeRecord::updateAll(['default' => 0], ['not', ['id' => $listTypeRecord->id]]);
            }

            // Might as well update our cache of the list type while we have it.
            $this->_listTypesById[$listType->id] = $listType;

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveListType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_LISTTYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_LISTTYPE, new ListTypeEvent([
                'listType' => $listType,
                'isNew' => $isNewListType,
            ]));
        }

        return true;
    }

    public function deleteListTypeById(int $id): bool
    {
        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $listType = $this->getListTypeById($id);

            $criteria = ListElement::find();
            $criteria->typeId = $listType->id;
            $criteria->status = null;
            $criteria->limit = null;
            $lists = $criteria->all();

            foreach ($lists as $list) {
                Craft::$app->getElements()->deleteElement($list);
            }

            $fieldLayoutId = $listType->getListFieldLayout()->id;
            Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            Craft::$app->getFields()->deleteLayoutById($listType->getItemFieldLayout()->id);

            $listTypeRecord = ListTypeRecord::findOne($listType->id);
            $affectedRows = $listTypeRecord->delete();

            if ($affectedRows) {
                $transaction->commit();
            }

            return (bool)$affectedRows;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    public function getListTypeById(int $listTypeId)
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


    // Private methods
    // =========================================================================

    private function _memoizeListType(ListType $listType)
    {
        $this->_listTypesById[$listType->id] = $listType;
        $this->_listTypesByHandle[$listType->handle] = $listType;
    }

    private function _createListTypeQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'fieldLayoutId',
                'itemFieldLayoutId',
                'name',
                'handle',
                'default',
            ])
            ->from(['{{%wishlist_listtypes}}']);
    }
}
