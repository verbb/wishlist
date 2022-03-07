<?php
namespace verbb\wishlist\elements\db;

use verbb\wishlist\WishList;
use verbb\wishlist\models\ListType;

use Craft;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class ListQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public bool $editable = false;
    public mixed $reference = null;
    public mixed $typeId = null;
    public mixed $default = null;
    public mixed $userId = null;
    public mixed $sessionId = null;


    // Public Methods
    // =========================================================================

    public function __set($name, $value)
    {
        switch ($name) {
            case 'type':
                $this->type($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    public function editable(bool $value = true): static
    {
        $this->editable = $value;

        return $this;
    }

    public function type($value): static
    {
        if ($value instanceof ListType) {
            $this->typeId = $value->id;
        } else if ($value !== null) {
            $this->typeId = (new Query())
                ->select(['id'])
                ->from(['{{%wishlist_listtypes}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->typeId = null;
        }

        return $this;
    }

    public function typeId($value): static
    {
        $this->typeId = $value;

        return $this;
    }

    public function reference($value): static
    {
        $this->reference = $value;

        return $this;
    }

    public function default($value): static
    {
        $this->default = $value;

        return $this;
    }

    public function userId($value): static
    {
        $this->userId = $value;

        return $this;
    }

    public function sessionId($value): static
    {
        $this->sessionId = $value;

        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        // See if 'type' were set to invalid handles
        if ($this->typeId === []) {
            return false;
        }

        $this->joinElementTable('wishlist_lists');

        $this->query->select([
            'wishlist_lists.id',
            'wishlist_lists.reference',
            'wishlist_lists.typeId',
            'wishlist_lists.lastIp',
            'wishlist_lists.userId',
            'wishlist_lists.sessionId',
            'wishlist_lists.default',
        ]);

        if ($this->reference) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_lists.reference', $this->reference));
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_lists.typeId', $this->typeId));
        }

        if ($this->default) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_lists.default', $this->default));
        }

        if ($this->userId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_lists.userId', $this->userId));
        }

        if ($this->sessionId) {
            $this->subQuery->andWhere(Db::parseParam('wishlist_lists.sessionId', $this->sessionId));
        }

        $this->_applyEditableParam();

        return parent::beforePrepare();
    }


    // Private Methods
    // =========================================================================

    private function _applyEditableParam(): void
    {
        if (!$this->editable) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            throw new QueryAbortedException();
        }

        // Limit the query to only the sections the user has permission to edit
        $this->subQuery->andWhere([
            'wishlist_lists.typeId' => Wishlist::$plugin->getListTypes()->getEditableListTypeIds(),
        ]);
    }
}
