<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\errors\ItemError;
use verbb\wishlist\helpers\UrlHelper;
use verbb\wishlist\models\Settings;

use Craft;
use craft\base\ElementInterface;

use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\HttpException;
use yii\web\Response;

class ItemsController extends BaseController
{
    // Constants
    // =========================================================================

    private const ITEM_REQUEST_LIMIT = 50;


    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['add', 'remove', 'update', 'toggle'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (in_array($action->id, ['add', 'toggle', 'remove', 'update'], true)) {
            $settings = Wishlist::$plugin->getSettings();

            if (!$settings->allowGetListActions) {
                $this->requirePostRequest();
            } elseif ($this->request->getIsGet()) {
                // Deprecated in 3.0.22.
                Craft::$app->getDeprecator()->log(
                    'wishlist.itemActions.get',
                    'GET requests for front-end Wishlist item actions have been deprecated and will be removed in Wishlist 4. Submit these actions with POST instead.',
                );
            }
        }

        return true;
    }

    public function actionEditItem(string $listTypeHandle, int $listId, int $itemId = null, Item $item = null): Response
    {
        $this->requireCpRequest();

        $variables = [
            'listTypeHandle' => $listTypeHandle,
            'listId' => $listId,
            'itemId' => $itemId,
            'item' => $item,
        ];

        $this->_prepareVariableArray($variables);

        // Properly bootstrap a new item
        if (!$variables['item']->id) {
            $variables['item']->listId = $listId;
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'wishlist/lists/' . $listTypeHandle . '/' . $listId . '/items/{id}';

        // // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'];

        return $this->renderTemplate('wishlist/items/_edit', $variables);
    }

    public function actionSaveItem(): ?Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $itemId = $this->request->getParam('itemId');
        $listId = $this->request->getRequiredParam('listId');
        $list = Wishlist::$plugin->getLists()->getListById($listId);

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list with the ID “{id}”', ['id' => $listId]));
        }

        $this->enforceListPermissions($list);

        if ($itemId) {
            $item = Wishlist::$plugin->getItems()->getItemById($itemId);

            if (!$item) {
                throw new Exception(Craft::t('wishlist', 'No item with the ID “{id}”', ['id' => $itemId]));
            }

            if ($item->listId !== $list->id) {
                throw new HttpException(404, Craft::t('wishlist', 'Unable to find item in list.'));
            }
        } else {
            $item = new Item();
            $item->listId = $list->id;
        }

        $item->setFieldValuesFromRequest('fields');

        // Element is a little special to cater for multiple types
        $elementType = $this->request->getParam('elementType');
        $item->elementId = $this->request->getParam('elementId')[$elementType][0] ?? null;

        if (!Wishlist::$plugin->getItems()->saveElement($item)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $item->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('wishlist', 'Couldn’t save item.'));

            // Send the category back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $item->id,
                'title' => $item->title,
                'status' => $item->getStatus(),
                'url' => $item->getUrl(),
                'cpEditUrl' => $item->getCpEditUrl(),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Item saved.'));

        return $this->redirectToPostedUrl($item);
    }

    public function actionDelete(): ?Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $itemId = $this->request->getParam('itemId');
        $item = Wishlist::$plugin->getItems()->getItemById($itemId);

        if (!$item) {
            throw new Exception(Craft::t('wishlist', 'Item not found with the ID “{id}”', ['id' => $itemId]));
        }

        $list = $item->getList();

        if (!$list) {
            throw new HttpException(404, Craft::t('wishlist', 'Unable to find list for item.'));
        }

        $this->enforceListPermissions($list);

        if (!Craft::$app->getElements()->deleteElement($item)) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('wishlist', 'Couldn’t delete item.'));

            // Send the item back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('wishlist', 'Item deleted.'));

        return $this->redirectToPostedUrl($item);
    }


    // Front-end Methods
    // =========================================================================

    public function actionAdd(): ?Response
    {
        $postItems = $this->_setItemsFromPost();
        $this->_enforceItemTargetLimit($postItems);

        return $this->runGuestStorageAction(function() use ($postItems) {
            $itemCount = 0;

            /* @var Settings $settings */
            $settings = Wishlist::$plugin->getSettings();

            $errors = [];
            $variables = [];
            $batchNewListId = null;

            foreach ($postItems as $key => $postItem) {
                // Get the element we're trying to action
                $element = $this->_getElementForItem($postItem);

                if ($element instanceof ItemError) {
                    $errors[$key] = $element;

                    continue;
                }

                $wantsNewList = !empty($postItem['newList']);
                $postItemForLists = $this->_resolvePostItemForBatchNewList($postItem, $batchNewListId);

                // Get the existing list (either passed in, or the users default), or create it
                $lists = $this->_getOrCreateLists($postItemForLists);

                foreach ($lists as $list) {
                    if ($list instanceof ItemError) {
                        $errors[$key] = $list;

                        continue;
                    }

                    if ($wantsNewList && $batchNewListId === null) {
                        $batchNewListId = $list->id;
                    }

                    // Check if we're allowed to manage lists
                    $this->enforceListPermissions($list);

                    // Create the item for the list and element, with additional attributes
                    $item = $this->_getOrCreateItem($list, $element, $postItem);

                    if ($item instanceof ItemError) {
                        $errors[$key] = $item;

                        continue;
                    }

                    // Check if this is in the list
                    if ($list->getHasItem($item)) {
                        // If we allow duplicates and this is one, ensure that it's still added and not updated
                        if (!$settings->allowDuplicates) {
                            $errors[$key] = new ItemError('Item already in list.');

                            continue;
                        } else {
                            // Create the item, as we're adding a new duplicate
                            $item = $this->_createItem($list, $element, $postItem);

                            if ($item instanceof ItemError) {
                                $errors[$key] = $item;

                                continue;
                            }
                        }
                    }

                    if (!$this->saveItemWithGuestLimits($item, $list)) {
                        $errors[$key] = new ItemError('Unable to save item to list.', ['item' => $item]);

                        continue;
                    }

                    $variables['items'][] = $item;

                    $itemCount++;
                }
            }

            if ($errors) {
                foreach ($errors as $itemError) {
                    return $this->returnError($itemError->message, $itemError->params);
                }
            }

            $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} added to list.', [
                'count' => $itemCount,
            ]);

            return $this->returnSuccess($message, $variables);
        });
    }

    public function actionToggle(): ?Response
    {
        $postItems = $this->_setItemsFromPost();
        $this->_enforceItemTargetLimit($postItems);

        return $this->runGuestStorageAction(function() use ($postItems) {
            $itemCount = 0;

            $errors = [];
            $variables = [];
            $batchNewListId = null;

            foreach ($postItems as $key => $postItem) {
                // Get the element we're trying to action
                $element = $this->_getElementForItem($postItem);

                if ($element instanceof ItemError) {
                    $errors[$key] = $element;

                    continue;
                }

                $wantsNewList = !empty($postItem['newList']);
                $postItemForLists = $this->_resolvePostItemForBatchNewList($postItem, $batchNewListId);

                // Get the existing list (either passed in, or the users default), or create it
                $lists = $this->_getOrCreateLists($postItemForLists);

                foreach ($lists as $list) {
                    if ($list instanceof ItemError) {
                        $errors[$key] = $list;

                        continue;
                    }

                    if ($wantsNewList && $batchNewListId === null) {
                        $batchNewListId = $list->id;
                    }

                    // Check if we're allowed to manage lists
                    $this->enforceListPermissions($list);

                    // Create the item for the list and element, with additional attributes
                    $item = $this->_getOrCreateItem($list, $element, $postItem);

                    if ($item instanceof ItemError) {
                        $errors[$key] = $item;

                        continue;
                    }

                    if ($item->id) {
                        if (!Craft::$app->getElements()->deleteElement($item)) {
                            $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);

                            continue;
                        }

                        $variables['items'][] = array_merge(['action' => 'removed'], $item->toArray());
                    } else {
                        if (!$this->saveItemWithGuestLimits($item, $list)) {
                            $errors[$key] = new ItemError('Unable to save item to list.', ['item' => $item]);

                            continue;
                        }

                        $variables['items'][] = array_merge(['action' => 'added'], $item->toArray());
                    }

                    $itemCount++;
                }
            }

            if ($errors) {
                foreach ($errors as $itemError) {
                    return $this->returnError($itemError->message, $itemError->params);
                }
            }

            $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} toggled in list.', [
                'count' => $itemCount,
            ]);

            return $this->returnSuccess($message, $variables);
        });
    }

    public function actionRemove(): ?Response
    {
        $postItems = $this->_setItemsFromPost();
        $this->_enforceItemTargetLimit($postItems);

        return $this->runGuestStorageAction(function() use ($postItems) {
            $itemCount = 0;

            $errors = [];
            $variables = [];

            foreach ($postItems as $key => $postItem) {
                // Get the element we're trying to action
                $element = $this->_getElementForItem($postItem);

                if ($element instanceof ItemError) {
                    $errors[$key] = $element;

                    continue;
                }

                // Get the existing list (either passed in, or the users default), or create it
                $lists = $this->_getOrCreateLists($postItem);

                foreach ($lists as $list) {
                    if ($list instanceof ItemError) {
                        $errors[$key] = $list;

                        continue;
                    }

                    // Check if we're allowed to manage lists
                    $this->enforceListPermissions($list);

                    // Create the item for the list and element, with additional attributes
                    $item = $this->_getOrCreateItem($list, $element, $postItem);

                    if ($item instanceof ItemError) {
                        $errors[$key] = $item;

                        continue;
                    }

                    if ($item->id) {
                        if (!Craft::$app->getElements()->deleteElement($item)) {
                            $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);

                            continue;
                        }

                        $variables['items'][] = array_merge(['action' => 'removed'], $item->toArray());

                        $itemCount++;
                    } else {
                        $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);
                    }
                }
            }

            if ($errors) {
                foreach ($errors as $itemError) {
                    return $this->returnError($itemError->message, $itemError->params);
                }
            }

            $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} removed from list.', [
                'count' => $itemCount,
            ]);

            return $this->returnSuccess($message, $variables);
        });
    }

    public function actionUpdate(): ?Response
    {
        $itemCount = 0;
        $postItems = $this->_setItemsFromPost();

        $errors = [];
        $variables = [];

        foreach ($postItems as $key => $postItem) {
            $itemId = $postItem['itemId'] ?? null;
            $fields = $postItem['fields'] ?? [];
            $options = $postItem['options'] ?? [];

            if (!$itemId) {
                $errors[$key] = new ItemError('Item ID must be provided.');

                continue;
            }

            $item = Wishlist::$plugin->getItems()->getItemById($itemId);

            if (!$item) {
                $errors[$key] = new ItemError('Unable to find item in list.');

                continue;
            }

            // Check if we're allowed to manage lists
            $this->enforceEnabledList($item->getList());
            $this->enforceListPermissions($item->getList());

            $item->setFieldValues($fields);
            $item->setOptions($options);

            if (!Wishlist::$plugin->getItems()->saveElement($item)) {
                $errors[$key] = new ItemError('Unable to update item to list.', ['item' => $item]);

                continue;
            }

            $variables['items'][] = $item;

            $itemCount++;
        }

        if ($errors) {
            foreach ($errors as $itemError) {
                return $this->returnError($itemError->message, $itemError->params);
            }
        }

        $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} updated in list.', [
            'count' => $itemCount,
        ]);

        return $this->returnSuccess($message, $variables);
    }


    // Private Methods
    // =========================================================================

    private function _resolvePostItemForBatchNewList(array $postItem, ?int &$batchNewListId): array
    {
        if (!empty($postItem['newList']) && $batchNewListId !== null) {
            return array_merge($postItem, [
                'newList' => false,
                'listId' => $batchNewListId,
            ]);
        }

        return $postItem;
    }

    private function _prepareVariableArray(array &$variables): void
    {
        // List related checks
        if (empty($variables['item'])) {
            if (!empty($variables['itemId'])) {
                $variables['item'] = Craft::$app->getElements()->getElementById($variables['itemId'], Item::class);

                if (!$variables['item']) {
                    throw new Exception('Missing item data.');
                }
            } else {
                $variables['item'] = new Item();
            }
        }

        $variables['list'] = Craft::$app->getElements()->getElementById($variables['listId'], ListElement::class);

        if (!$variables['list']) {
            throw new HttpException(404, Craft::t('wishlist', 'Unable to find list.'));
        }

        $this->enforceListPermissions($variables['list']);

        if (!empty($variables['listTypeHandle'])) {
            $variables['listType'] = Wishlist::$plugin->getListTypes()->getListTypeByHandle($variables['listTypeHandle']);
        } elseif (!empty($variables['listTypeHandleId'])) {
            $variables['listType'] = Wishlist::$plugin->getListTypes()->getListTypeById($variables['listTypeId']);
        }

        $listType = $variables['listType'];
        $item = $variables['item'];

        if (!$listType || $variables['list']->typeId !== $listType->id || ($item->id && $item->listId !== $variables['list']->id)) {
            throw new HttpException(404);
        }

        // For new items, they should have an associated listId
        if (!$item->listId) {
            $item->listId = $variables['list']->id;
        }

        $form = $listType->getItemFieldLayout()->createForm($item);
        $variables['tabs'] = $form->getTabMenu();
        $variables['fieldsHtml'] = $form->render();
    }

    private function _setItemsFromPost(): array
    {
        // If the request is coming through via a URL, params are encoded for easy URLs (that can't be easily tampered with)
        $urlPayload = $this->request->getParam('wl', []);

        if ($urlPayload) {
            $urlPayload = UrlHelper::decodeUrlParams($urlPayload);
        }

        // By default, handle multi-items, but if not - set them up as one
        $baseItem = array_merge([
            'itemId' => $this->request->getParam('itemId'),
            'listId' => $this->request->getParam('listId'),
            'listType' => $this->request->getParam('listType'),
            'elementId' => $this->request->getParam('elementId'),
            'elementSiteId' => $this->request->getParam('elementSiteId'),
            'newList' => $this->request->getParam('newList', false),
            'listTitle' => $this->request->getParam('listTitle', null),
            'listEnabled' => $this->request->getParam('listEnabled', true),
            'listFields' => $this->request->getParam('listFields', []),
            'fields' => $this->request->getParam('fields', []),
            'options' => $this->request->getParam('options', []),
        ], $urlPayload);

        // Merge any item attributes which would override the base values above
        $items = $this->request->getParam('items') ?? [];

        if ($items && is_array($items)) {
            $this->enforceItemRequestLimit(count($items), self::ITEM_REQUEST_LIMIT);

            foreach ($items as $key => $item) {
                if (!is_array($item)) {
                    throw new BadRequestHttpException(Craft::t('wishlist', 'Each submitted item must be an array.'));
                }

                $items[$key] = array_merge($baseItem, $item);
            }
        } else {
            $items = [$baseItem];
        }

        return $items;
    }

    private function _getElementForItem(array $postItem): ElementInterface|ItemError
    {
        $elementId = $postItem['elementId'] ?? null;
        $elementSiteId = $postItem['elementSiteId'] ?? null;

        if (!$elementId) {
            return new ItemError('Element ID must be provided.');
        }

        $element = Craft::$app->getElements()->getElementById($elementId, null, $elementSiteId);

        if (!$element) {
            return new ItemError('Unable to find element.');
        }

        return $element;
    }

    private function _getOrCreateLists(array $postItem): array
    {
        $lists = [];

        $listIds = $postItem['listId'] ?? null;
        $listType = $postItem['listType'] ?? null;
        $newList = $postItem['newList'] ?? false;

        // List IDs can either be a single ID or an array. Don't forget null is allowed to create the list.
        if ($newList) {
            // A new-list request creates one shared list, regardless of ignored listId values.
            $listIds = [null];
        } elseif (!is_array($listIds)) {
            $listIds = [$listIds];
        }

        foreach ($listIds as $listId) {
            $list = null;

            $listFields = $postItem['listFields'] ?? [];
            $isExistingList = $listId && !$newList;

            // Get the specific list passed in, unless we specifically want to create a new list
            if ($isExistingList) {
                $list = Wishlist::$plugin->getLists()->getListById($listId);

                if (!$list) {
                    $lists[] = new ItemError('Invalid List ID "' . $listId . '".');

                    continue;
                }

                $this->enforceEnabledList($list);
                $this->enforceListPermissions($list);
            } else {
                // Ensure that we resolve the list type correctly
                $listParams = array_filter(['listType' => $listType]);

                // Either get the current user's list, or create a new one - unless we want a new list always created
                if ($newList) {
                    $list = Wishlist::$plugin->getLists()->createList($listParams);
                } else {
                    $list = Wishlist::$plugin->getLists()->getUserList($listParams);
                }

                $list->title = $postItem['listTitle'] ?? $list->title;
                $list->enabled = $postItem['listEnabled'] ?? $list->enabled;

                $this->enforceEnabledList($list);
                $this->enforceListPermissions($list);
            }

            if ($listFields) {
                $list->setFieldValues($listFields);
            }

            if (!$isExistingList || $listFields) {
                if (!$this->saveListWithGuestLimits($list)) {
                    $lists[] = new ItemError('Unable to save list.', ['list' => $list]);

                    continue;
                }
            }

            $lists[] = $list;
        }

        return $lists;
    }

    private function _enforceItemTargetLimit(array $postItems): void
    {
        $targetCount = 0;

        foreach ($postItems as $postItem) {
            $listIds = $postItem['listId'] ?? null;
            $targetCount += !empty($postItem['newList']) || !is_array($listIds) ? 1 : max(1, count($listIds));

            $this->enforceItemRequestLimit($targetCount, self::ITEM_REQUEST_LIMIT);
        }
    }

    private function _getOrCreateItem(ListElement $list, ElementInterface $element, array $postItem): Item|ItemError
    {
        if ($item = $this->_getItem($list, $element, $postItem)) {
            return $item;
        }

        return $this->_createItem($list, $element, $postItem);
    }

    private function _getItem(ListElement $list, ElementInterface $element, array $postItem): ?Item
    {
        $itemId = $postItem['itemId'] ?? null;
        $fields = $postItem['fields'] ?? [];
        $options = $postItem['options'] ?? [];

        // Check if we're passing in an itemId - that's easy
        if ($itemId) {
            $item = Wishlist::$plugin->getItems()->getItemById($itemId);

            if (!$item || $item->listId !== $list->id || $item->elementId !== $element->id || $item->elementSiteId !== $element->siteId) {
                throw new HttpException(404, Craft::t('wishlist', 'Unable to find item in list.'));
            }

            return $item;
        }

        // Try and find an existing item for the list, with all the appropriate params
        $query = Item::find()
            ->listId($list->id)
            ->elementId($element->id)
            ->elementSiteId($element->siteId)
            ->id($itemId)
            ->options($options);

        if ($item = $query->one()) {
            return $item;
        }

        return null;
    }

    private function _createItem(ListElement $list, ElementInterface $element, array $postItem): Item|ItemError
    {
        if ($this->request->getIsSiteRequest() && !Wishlist::$plugin->getItems()->canAddElementFromSite($element)) {
            return new ItemError('Unable to find element.');
        }

        $fields = $postItem['fields'] ?? [];
        $options = $postItem['options'] ?? [];

        $itemParams = array_filter(['options' => $options, 'fields' => $fields]);
        $item = WishList::$plugin->getItems()->createItem($list, $element, $itemParams);

        return $item;
    }

}
