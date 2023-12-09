<?php
namespace verbb\wishlist\services;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\db\ListQuery;
use verbb\wishlist\models\ListType;
use verbb\wishlist\models\Settings;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\User as UserElement;
use craft\helpers\ArrayHelper;
use craft\helpers\ConfigHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;

use DateTime;

use yii\web\Cookie;
use yii\web\UserEvent;

use Throwable;

class Lists extends Component
{
    // Properties
    // =========================================================================

    protected string $listName = 'wishlist_list';


    // Public Methods
    // =========================================================================

    public function getListById(int $id, ?int $siteId = null): ?ListElement
    {
        return Craft::$app->getElements()->getElementById($id, ListElement::class, $siteId);
    }

    public function saveElement(ElementInterface $element, bool $runValidation = true, bool $propagate = true): bool
    {
        $updateListSearchIndexes = Wishlist::$plugin->getSettings()->updateListSearchIndexes;

        return Craft::$app->getElements()->saveElement($element, $runValidation, $propagate, $updateListSearchIndexes);
    }

    public function getUserList(array $params = []): ListElement
    {
        // Ensure that the params are populated with the correct list type
        $params['type'] = $this->_getListType($params);

        // Returns the current users list (logged-in or guest).
        $query = $this->getListQueryForUser($params);

        if ($list = $query->one()) {
            return $list;
        }

        // Otherwise, create a new list (unsaved) so we can action things with.
        return $this->createList($params);
    }

    public function getListQueryForUser(array $params = []): ListQuery
    {
        $query = ListElement::find();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            $query->userId($currentUser->id);
        } else {
            $query->sessionId($this->getSessionId());
            $query->userId(':empty:');
        }

        Craft::configure($query, $params);

        return $query;
    }

    public function createList(array $params = []): ListElement
    {
        $listType = $params['type'] ?? $this->_getListType($params);

        $list = new ListElement();
        $list->reference = $this->generateReferenceNumber();
        $list->typeId = $listType->id;
        $list->title = $listType->name;
        $list->sessionId = $this->getSessionId();

        $list->lastIp = Craft::$app->getRequest()->userIP;
        $list->userId = Craft::$app->getUser()->getIdentity()->id ?? null;

        Craft::configure($list, $params);

        return $list;
    }

    public function getInUserLists(ElementInterface $element): bool
    {
        // Get all lists for the current user (session).
        $userListIds = $this->getListQueryForUser()->ids();

        if (!$userListIds) {
            return false;
        }

        return Item::find()
            ->elementId($element->id)
            ->elementSiteId($element->siteId)
            ->listId($userListIds)
            ->exists();
    }

    public function isListOwner(ListElement $list): bool
    {
        $id = false;
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($currentUser) {
            $id = $currentUser->id;
        } else {
            $id = $this->getSessionId();
        }

        return (int)$list->getOwnerId() === (int)$id;
    }

    public function purgeInactiveLists(): int
    {
        $doPurge = Wishlist::$plugin->getSettings()->purgeInactiveLists;

        if ($doPurge) {
            // Allow batch processing for large items/lists
            $limit = 200;
            $offset = 0;
            $listCount = 0;

            do {
                $listIds = $this->_getListsIdsToPurge($limit, $offset);

                // Taken from craft\services\Elements::deleteElement(); Using the method directly
                // takes too many resources since it retrieves the list before deleting it.

                // Get element IDs for items first before foreign
                $itemIds = [];

                foreach ($listIds as $listId) {
                    $itemIds = array_merge($itemIds, Item::find()
                        ->listId($listId)
                        ->status(null)
                        ->ids());
                }

                // Delete the elements' table rows, which will cascade across all other InnoDB tables
                Db::delete('{{%elements}}', ['id' => $listIds]);

                // The searchindex table is probably MyISAM, though
                Db::delete('{{%searchindex}}', ['elementId' => $listIds]);

                // Remove all items for lists. `wishlist_items` will take care of itself
                Db::delete('{{%elements}}', ['id' => $itemIds]);

                $offset = $offset + $limit;

                $listCount = $listCount + count($listIds);
            } while ($listIds);

            return $listCount;
        }

        return 0;
    }

    public function generateReferenceNumber(): string
    {
        return StringHelper::randomString(10);
    }

    public function generateSessionId(): string
    {
        return md5(uniqid(random_int(0, mt_getrandmax()), true));
    }

    public function loginHandler(UserEvent $event): void
    {
        $user = $event->identity;

        // Consolidate lists to the user
        $this->consolidateListsToUser($user);
    }

    public function consolidateListsToUser(UserElement $user, array $lists = null): bool
    {
        try {
            /* @var Settings $settings */
            $settings = Wishlist::$plugin->getSettings();
            $db = Craft::$app->getDb();

            // Try and find the default list for the guest
            $sessionId = $this->getSessionId();

            Db::update('{{%wishlist_lists}}', ['userId' => $user->id], ['sessionId' => $sessionId, 'userId' => null]);

            Wishlist::info('Moving guest lists for session "' . $sessionId . '" to user "' . $user->id . '"');

            if ($settings->mergeLastListOnLogin) {
                // Check if we've now got multiple lists for a logged-in user. We need to merge them
                $listTypes = Wishlist::$plugin->getListTypes()->getAllListTypes();

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
                                Db::update('{{%wishlist_items}}', ['listId' => $oldestList->id], ['listId' => $userList->id]);

                                // Delete the newer list, now the items have been moved off
                                Db::delete('{{%elements}}', ['id' => $userList->id]);
                            }

                            // Now, check if there are any duplicates, and we should check. We ditch the oldest item(s).
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

                                // Save the first occurrence (newest updated) for each item
                                $processedItems = [];

                                foreach ($duplicateItems as $duplicateItem) {
                                    $key = implode('_', [$duplicateItem['listId'], $duplicateItem['elementId'], $duplicateItem['optionsSignature']]);

                                    // If first occurrence, save and delete all instances
                                    if (!isset($processedItems[$key])) {
                                        $processedItems[$key] = $duplicateItem;

                                        continue;
                                    }

                                    // Soft-delete the element, just for safety
                                    $now = new DateTime();

                                    Db::update('{{%elements}}', ['dateDeleted' => Db::prepareDateForDb($now)], ['id' => $duplicateItem['id']]);
                                }
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            Wishlist::error('{e} - {f}: {l}.', ['e' => $e->getMessage(), 'f' => $e->getFile(), 'l' => $e->getLine()]);
        }

        return true;
    }

    public function addEditUserListInfoTab(array &$context): void
    {
        if (!$context['isNewUser']) {
            $context['tabs']['wishlistInfo'] = [
                'label' => Craft::t('wishlist', 'Wishlists'),
                'url' => '#wishlistInfo',
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
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

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

            // Save this as a cookie for better persistence
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

    private function _getListType(array &$params = []): ListType
    {
        // Normalize from templates, where `listType` is more user-friendly and unambiguous to `type``.
        $typeHandle = ArrayHelper::remove($params, 'listType');

        // Ensure that we resolve the list type correctly
        if ($typeHandle) {
            return WishList::$plugin->getListTypes()->getListTypeByHandle($typeHandle);
        }

        return Wishlist::$plugin->getListTypes()->getDefaultListType();
    }

    private function _getListsIdsToPurge($limit = null, $offset = null): array
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

        $configInterval = ConfigHelper::durationInSeconds($settings->purgeInactiveListsDuration);
        $edge = new DateTime();
        $interval = DateTimeHelper::secondsToInterval($configInterval);
        $edge->sub($interval);

        $query = (new Query())
            ->select(['lists.id'])
            ->from(['{{%wishlist_lists}} lists'])
            ->join('LEFT OUTER JOIN', '{{%wishlist_items}} items', 'lists.id = [[items.listId]]')
            ->where('[[lists.dateUpdated]] <= :edge', ['edge' => Db::prepareDateForDb($edge)])
            ->limit($limit)
            ->offset($offset);

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
            ->andWhere(['is', '[[lists.userId]]', null])
            ->limit($limit)
            ->offset($offset);

        if ($settings->purgeEmptyGuestListsOnly) {
            $query->andWhere(['is', '[[items.listId]]', null]);
        }

        $guestIds = $query->column();

        return array_merge($userIds, $guestIds);
    }

}
