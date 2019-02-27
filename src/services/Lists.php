<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\StringHelper;
use craft\models\Structure;

use yii\web\UserEvent;

class Lists extends Component
{
    // Properties
    // =========================================================================

    protected $listName = 'wishlist_list';
    private $_list;


    // Public Methods
    // =========================================================================

    public function getListById(int $id, $siteId = null)
    {
        return Craft::$app->getElements()->getElementById($id, ListElement::class, $siteId);
    }

    public function getList($id = null, $forceSave = false, $listTypeId = null): ListElement
    {
        $session = Craft::$app->getSession();

        if ($id) {
            $this->_list = Wishlist::$plugin->getLists()->getListById($id);

            if ($this->_list) {
                return $this->_list;
            }
        }

        if ($this->_list === null) {
            if ($listTypeId) {
                // Get the first list of the typeId we find for the user.  If it needs to be more precise, use id.
                $this->_list = $this->getListQueryForOwner()->typeId($listTypeId)->one();
            } else {
                $this->_list = $this->getListQueryForOwner()->default(true)->one();
            }

            if (!$this->_list) {
                $listType = null;

                if ($listTypeId) {
                    // If this list type is new for the user, let's create a new list for it.
                    $listType = Wishlist::getInstance()->getListTypes()->getListTypeById($listTypeId);
                }

                if ($listType === null) {
                    // If we still don't have a valid list type, let's get the default one.
                    $listType = Wishlist::getInstance()->getListTypes()->getDefaultListType();
                }

                $this->_list = new ListElement();
                $this->_list->reference = $this->generateReferenceNumber();
                $this->_list->typeId = $listType->id;
                $this->_list->title = $listType->name;
                $this->_list->default = $listType->default;
                $this->_list->sessionId = $this->getSessionId();
            }
        }

        $originalIp = $this->_list->lastIp;
        $originalUserId = $this->_list->userId;

        // These values should always be kept up to date when a list is retrieved from session.
        $this->_list->lastIp = Craft::$app->getRequest()->userIP;
        $this->_list->userId = Craft::$app->getUser()->getIdentity()->id ?? null;

        $changedIp = $originalIp != $this->_list->lastIp;
        $changedUserId = $originalUserId != $this->_list->userId;

        if ($this->_list->id) {
            if ($changedIp || $changedUserId) {
                Craft::$app->getElements()->saveElement($this->_list, false);
            }
        } else {
            if ($forceSave) {
                Craft::$app->getElements()->saveElement($this->_list, false);
            }
        }

        return $this->_list;
    }

    public function getListQueryForOwner()
    {
        $query = ListElement::find();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            $query->userId($currentUser->id);
        } else {
            $query->sessionId($this->getSessionId());
        }

        return $query;
    }

    public function isListOwner($list)
    {
        $id = false;
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            $id = $currentUser->id;
        } else {
            $id = $this->getSessionId();
        }

        return (bool)($list->getOwnerId() === $id);
    }

    public function createList(): ListElement
    {
        $listType = Wishlist::getInstance()->getListTypes()->getDefaultListType();

        $list = new ListElement();
        $list->reference = $this->generateReferenceNumber();
        $list->typeId = $listType->id;
        $list->title = $listType->name;
        $list->sessionId = $this->getSessionId();

        $list->lastIp = Craft::$app->getRequest()->userIP;
        $list->userId = Craft::$app->getUser()->getIdentity()->id ?? null;

        return $list;
    }

    public function purgeInactiveLists(): int
    {
        $doPurge = Wishlist::getInstance()->getSettings()->purgeInactiveLists;

        if ($doPurge) {
            $listIds = $this->_getListsIdsToPurge();

            // Taken from craft\services\Elements::deleteElement(); Using the method directly
            // takes too much resources since it retrieves the list before deleting it.

            // Delete the elements table rows, which will cascade across all other InnoDB tables
            Craft::$app->getDb()->createCommand()
                ->delete('{{%elements}}', ['id' => $listIds])
                ->execute();

            // The searchindex table is probably MyISAM, though
            Craft::$app->getDb()->createCommand()
                ->delete('{{%searchindex}}', ['elementId' => $listIds])
                ->execute();

            return \count($listIds);
        }

        return 0;
    }

    public function generateReferenceNumber(): string
    {
        return StringHelper::randomString(10);
    }

    public function generateSessionId(): string
    {
        return md5(uniqid(mt_rand(), true));
    }


    // Private Methods
    // =========================================================================

    private function getSessionId()
    {
        $session = Craft::$app->getSession();
        $sessionId = $session[$this->listName];

        if (!$sessionId) {
            $sessionId = $this->generateSessionId();
            $session->set($this->listName, $sessionId);
        }

        return $sessionId;
    }

    private function _getListsIdsToPurge(): array
    {
        $configInterval = Wishlist::getInstance()->getSettings()->purgeInactiveListsDuration;
        $edge = new \DateTime();
        $interval = new \DateInterval($configInterval);
        $interval->invert = 1;
        $edge->add($interval);

        return (new Query())
            ->select(['lists.id'])
            ->from(['{{%wishlist_lists}} lists'])
            ->join('LEFT OUTER JOIN', '{{%wishlist_items}} items', 'lists.id = items.listId')
            ->where('lists.dateUpdated <= :edge', ['edge' => $edge->format('Y-m-d H:i:s')])
            ->andWhere(['is', 'items.listId', null])
            ->column();
    }
    
}