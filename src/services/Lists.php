<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\User as UserElement;
use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\Structure;

use DateTime;

use yii\web\Cookie;
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

    public function saveElement(ElementInterface $element, bool $runValidation = true, bool $propagate = true)
    {
        $updateListSearchIndexes = Wishlist::$plugin->getSettings()->updateListSearchIndexes;

        return Craft::$app->getElements()->saveElement($element, $runValidation, $propagate, $updateListSearchIndexes);
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
                Wishlist::$plugin->getLists()->saveElement($this->_lists[$cacheKey], false);
            }
        } else {
            if ($forceSave) {
                Wishlist::$plugin->getLists()->saveElement($this->_lists[$cacheKey], false);
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

        return (bool)((int)$list->getOwnerId() === (int)$id);
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
        try {
            $settings = Wishlist::$plugin->getSettings();
            $db = Craft::$app->getDb();

            // Try and find the default list for the guest
            $sessionId = $this->getSessionId();

            $db->createCommand()
                ->update('{{%wishlist_lists}}', ['userId' => $user->id], ['sessionId' => $sessionId, 'userId' => null])
                ->execute();

            Wishlist::log('Moving guest lists for session "' . $sessionId . '" to user "' . $user->id . '"');

            if ($settings->mergeLastListOnLogin) {
                // Check if we've now got multiple lists for a logged-in user. We need to merge them
                $listTypes = Wishlist::getInstance()->getListTypes()->getAllListTypes();

                // We want to merge lists per list type
                foreach ($listTypes as $listType) {
                    $userLists = ListElement::find()
                        ->userId($user->id)
                        ->typeId($listType->id)
                        ->orderBy('dateCreated asc')
                        ->all();

                    if ($userLists) {
                        $oldestList = $userLists[0];

                        // Update all list items to belong to the oldest list
                        foreach ($userLists as $userList) {
                            // Ensure that we check against the title - they should be the same to merge
                            if ($oldestList->id != $userList->id && $oldestList->title == $userList->title) {
                                $db->createCommand()
                                    ->update('{{%wishlist_items}}', ['listId' => $oldestList->id], ['listId' => $userList->id])
                                    ->execute();
                            
                                // Delete the newer list, now the items have been moved off
                                $db->createCommand()
                                    ->delete('{{%elements}}', ['id' => $userList->id])
                                    ->execute();
                            }

                            // Now, check if there are any duplicates and we should check. We ditch the oldest item(s).
                            if (!$settings->allowDuplicates) {
                                $db->getSchema()->refresh();

                                $duplicateItems = $db->createCommand("
                                    SELECT wishlist_items.*
                                    FROM {{%wishlist_items}} [[wishlist_items]]
                                    JOIN (SELECT [[listId]], [[elementId]], [[optionsSignature]], COUNT(*)
                                        FROM {{%wishlist_items}} [[wishlist_items]]
                                        JOIN {{%elements}} [[elements]] ON [[elements.id]] = [[wishlist_items.id]]
                                        WHERE [[elements.dateDeleted]] IS NULL 
                                        AND [[listId]] = :list_id
                                        GROUP BY [[listId]], [[elementId]], [[optionsSignature]]
                                        HAVING count(*) > 1 ) temp
                                    ON [[wishlist_items.listId]] = [[temp.listId]]
                                    AND [[wishlist_items.elementId]] = [[temp.elementId]]
                                    AND [[wishlist_items.optionsSignature]] = [[temp.optionsSignature]]
                                    JOIN {{%elements}} [[elements]] ON [[elements.id]] = [[wishlist_items.id]]
                                    WHERE [[elements.dateDeleted]] IS NULL 
                                    ORDER BY [[wishlist_items.dateUpdated]] DESC
                                    ", [':list_id' => $oldestList->id])->queryAll();

                                // Save the first occurence (newest updated) for each item
                                $processedItems = [];

                                foreach ($duplicateItems as $duplicateItem) {
                                    $key = implode('_', [$duplicateItem['listId'], $duplicateItem['elementId'], $duplicateItem['optionsSignature']]);
                                    
                                    // If first occurence, save and delete all other instances
                                    if (!isset($processedItems[$key])) {
                                        $processedItems[$key] = $duplicateItem;

                                        continue;
                                    }

                                    // Soft-delete the element, just for safety
                                    $now = new DateTime();

                                    $db->createCommand()
                                        ->update('{{%elements}}', ['dateDeleted' => Db::prepareDateForDb($now)], ['id' => $duplicateItem['id']])
                                        ->execute();
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Wishlist::error(Craft::t('app', '{e} - {f}: {l}.', ['e' => $e->getMessage(), 'f' => $e->getFile(), 'l' => $e->getLine()]));
        }

        return true;
    }

    public function addEditUserListInfoTab(array &$context)
    {
        if (!$context['isNewUser']) {
            $context['tabs']['wishlistInfo'] = [
                'label' => Craft::t('wishlist', 'Wishlists'),
                'url' => '#wishlistInfo'
            ];
        }
    }

    public function addEditUserListInfoTabContent(array &$context): string
    {
        if (!$context['user'] || $context['isNewUser']) {
            return '';
        }

        return Craft::$app->getView()->renderTemplate('wishlist/_includes/_editUserTab', $context);
    }


    // Private Methods
    // =========================================================================

    private function getSessionId()
    {
        $settings = Wishlist::getInstance()->getSettings();

        $session = Craft::$app->getSession();
        $sessionId = $session[$this->listName];

        $cookieName = 'Wishlist:sessionId';

        // If no session, check for a saved cookie, allowing us to retain lists after sessions have ended
        if (!$sessionId) {
            $sessionId = Craft::$app->getRequest()->getRawCookies()->getValue($cookieName);
        }

        // If still no session, we better generate a new one.
        if (!$sessionId) {
            $sessionId = $this->generateSessionId();
            $session->set($this->listName, $sessionId);

            $configInterval = ConfigHelper::durationInSeconds($settings->cookieExpiry);
            $expiry = (new DateTime())->add(DateTimeHelper::secondsToInterval($configInterval));

            // Save this as a cookie for better persistency
            $cookie = Craft::createObject(Craft::cookieConfig([
                'class' => Cookie::class,
                'name' => $cookieName,
                'value' => $sessionId,
                'httpOnly' => false,
                'expire' => $expiry->getTimestamp(),
            ]));

            Craft::$app->getResponse()->getRawCookies()->add($cookie);
        }

        return $sessionId; 
    }

    private function _getListsIdsToPurge(): array
    {
        $settings = Wishlist::getInstance()->getSettings();

        $configInterval = ConfigHelper::durationInSeconds($settings->purgeInactiveListsDuration);
        $edge = new DateTime();
        $interval = DateTimeHelper::secondsToInterval($configInterval);
        $edge->sub($interval);

        $query = (new Query())
            ->select(['lists.id'])
            ->from(['{{%wishlist_lists}} lists'])
            ->join('LEFT OUTER JOIN', '{{%wishlist_items}} items', 'lists.id = [[items.listId]]')
            ->where('[[lists.dateUpdated]] <= :edge', ['edge' => Db::prepareDateForDb($edge)]);

        if ($settings->purgeEmptyListsOnly) {
            $query->andWhere(['is', '[[items.listId]]', null]);
        }

        $userIds = $query->column();

        $configInterval = ConfigHelper::durationInSeconds($settings->purgeInactiveGuestListsDuration);
        $edge = new DateTime();
        $interval = DateTimeHelper::secondsToInterval($configInterval);
        $edge->sub($interval);

        $query = (new Query())
            ->select(['lists.id'])
            ->from(['{{%wishlist_lists}} lists'])
            ->join('LEFT OUTER JOIN', '{{%wishlist_items}} items', 'lists.id = [[items.listId]]')
            ->where('[[lists.dateUpdated]] <= :edge', ['edge' => Db::prepareDateForDb($edge)])
            ->andWhere(['is', '[[lists.userId]]', null]);

        if ($settings->purgeEmptyGuestListsOnly) {
            $query->andWhere(['is', '[[items.listId]]', null]);
        }

        $guestIds = $query->column();

        return array_merge($userIds, $guestIds);
    }
    
}
