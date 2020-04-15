<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\User as UserElement;
use craft\helpers\StringHelper;
use craft\models\Structure;

use yii\web\UserEvent;

class Lists extends Component
{
    // Properties
    // =========================================================================

    protected $listName = 'wishlist_list';
    private $_lists = [];


    // Public Methods
    // =========================================================================

    public function getListById(int $id, $siteId = null)
    {
        return Craft::$app->getElements()->getElementById($id, ListElement::class, $siteId);
    }

    public function getList($id = null, $forceSave = false, $listTypeId = null): ListElement
    {
        $session = Craft::$app->getSession();

        $cacheKey = $id . ':' . $listTypeId;

        if ($id) {
            $this->_lists[$cacheKey] = Wishlist::$plugin->getLists()->getListById($id);

            if ($this->_lists[$cacheKey]) {
                return $this->_lists[$cacheKey];
            }
        }

        // We need maintain potential different lists in our cache
        if (!isset($this->_lists[$cacheKey])) {
            $this->_lists[$cacheKey] = null;
        }

        if ($this->_lists[$cacheKey] === null) {
            if ($listTypeId) {
                // Get the first list of the typeId we find for the user.  If it needs to be more precise, use id.
                $this->_lists[$cacheKey] = $this->getListQueryForOwner()->typeId($listTypeId)->one();
            } else {
                $this->_lists[$cacheKey] = $this->getListQueryForOwner()->default(true)->one();
            }

            if (!$this->_lists[$cacheKey]) {
                $listType = null;

                if ($listTypeId) {
                    // If this list type is new for the user, let's create a new list for it.
                    $listType = Wishlist::getInstance()->getListTypes()->getListTypeById($listTypeId);
                }

                if ($listType === null) {
                    // If we still don't have a valid list type, let's get the default one.
                    $listType = Wishlist::getInstance()->getListTypes()->getDefaultListType();
                }

                $this->_lists[$cacheKey] = new ListElement();
                $this->_lists[$cacheKey]->reference = $this->generateReferenceNumber();
                $this->_lists[$cacheKey]->typeId = $listType->id;
                $this->_lists[$cacheKey]->title = $listType->name;
                $this->_lists[$cacheKey]->default = $listType->default;
                $this->_lists[$cacheKey]->sessionId = $this->getSessionId();
            }
        }

        $originalIp = $this->_lists[$cacheKey]->lastIp;
        $originalUserId = $this->_lists[$cacheKey]->userId;

        // These values should always be kept up to date when a list is retrieved from session.
        $this->_lists[$cacheKey]->lastIp = Craft::$app->getRequest()->userIP;
        $this->_lists[$cacheKey]->userId = Craft::$app->getUser()->getIdentity()->id ?? null;

        $changedIp = $originalIp != $this->_lists[$cacheKey]->lastIp;
        $changedUserId = $originalUserId != $this->_lists[$cacheKey]->userId;

        if ($this->_lists[$cacheKey]->id) {
            if ($changedIp || $changedUserId) {
                Craft::$app->getElements()->saveElement($this->_lists[$cacheKey], false);
            }
        } else {
            if ($forceSave) {
                Craft::$app->getElements()->saveElement($this->_lists[$cacheKey], false);
            }
        }

        return $this->_lists[$cacheKey];
    }

    public function getListQueryForOwner()
    {
        $query = ListElement::find();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            $query->userId($currentUser->id);
        } else {
            $query->sessionId($this->getSessionId());
            $query->userId(':empty:');
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

    public function loginHandler(UserEvent $event)
    {
        $user = $event->identity;

        // Consolidates lists to the user
        $this->consolidateListsToUser($user);
    }

    public function consolidateListsToUser(UserElement $user, array $lists = null): bool
    {
        // Try and find the default list for the guest
        $sessionId = $this->getSessionId();

        Craft::$app->getDb()->createCommand()
            ->update('{{%wishlist_lists}}', ['userId' => $user->id], ['sessionId' => $sessionId, 'default' => true, 'userId' => null])
            ->execute();

        return true;
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

        $userIds = (new Query())
            ->select(['lists.id'])
            ->from(['{{%wishlist_lists}} lists'])
            ->join('LEFT OUTER JOIN', '{{%wishlist_items}} items', 'lists.id = [[items.listId]]')
            ->where('[[lists.dateUpdated]] <= :edge', ['edge' => $edge->format('Y-m-d H:i:s')])
            ->andWhere(['is', '[[items.listId]]', null])
            ->column();

        $configInterval = Wishlist::getInstance()->getSettings()->purgeInactiveGuestListsDuration;
        $edge = new \DateTime();
        $interval = new \DateInterval($configInterval);
        $interval->invert = 1;
        $edge->add($interval);

        $guestIds = (new Query())
            ->select(['lists.id'])
            ->from(['{{%wishlist_lists}} lists'])
            ->join('LEFT OUTER JOIN', '{{%wishlist_items}} items', 'lists.id = [[items.listId]]')
            ->where('[[lists.dateUpdated]] <= :edge', ['edge' => $edge->format('Y-m-d H:i:s')])
            ->andWhere(['is', '[[items.listId]]', null])
            ->andWhere(['is', '[[lists.userId]]', null])
            ->column();

        return array_merge($userIds, $guestIds);
    }
    
}
