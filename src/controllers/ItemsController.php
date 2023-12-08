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
use yii\web\Response;

class ItemsController extends BaseController
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['add', 'remove', 'update', 'toggle'];


    // Public Methods
    // =========================================================================

    public function actionEditItem(string $listTypeHandle, int $listId, int $itemId = null, Item $item = null): Response
    {
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
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $itemId = $request->getParam('itemId');

        if ($itemId) {
            $item = Wishlist::$plugin->getItems()->getItemById($itemId);

            if (!$item) {
                throw new Exception(Craft::t('wishlist', 'No item with the ID “{id}”', ['id' => $itemId]));
            }
        } else {
            $item = new Item();
        }

        $item->listId = $request->getParam('listId');
        $item->elementId = $request->getParam('elementId')[0];
        $item->setFieldValuesFromRequest('fields');

        if (!Wishlist::$plugin->getItems()->saveElement($item)) {
            if ($request->getAcceptsJson()) {
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

        if ($request->getAcceptsJson()) {
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
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $itemId = $request->getParam('itemId');
        $item = Wishlist::$plugin->getItems()->getItemById($itemId);

        if (!$item) {
            throw new Exception(Craft::t('wishlist', 'Item not found with the ID “{id}”', ['id' => $itemId]));
        }

        if (!Craft::$app->getElements()->deleteElement($item)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('wishlist', 'Couldn’t delete item.'));

            // Send the item back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'item' => $item,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('wishlist', 'Item deleted.'));

        return $this->redirectToPostedUrl($item);
    }


    // Front-end Methods
    // =========================================================================

    public function actionAdd(): ?Response
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();
        $postItems = $this->_setItemsFromPost();

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
            $list = $this->_getOrCreateList($postItem);

            if ($list instanceof ItemError) {
                $errors[$key] = $list;

                continue;
            }

            // Create the item for the list and element, with additional attributes
            $item = $this->_getOrCreateItem($list, $element, $postItem);

            // Check if this is in the list
            if ($list->getHasItem($item) && !$settings->allowDuplicates) {
                $errors[$key] = new ItemError('Item already in list.');

                continue;
            }

            if (!Wishlist::$plugin->getItems()->saveElement($item)) {
                $errors[$key] = new ItemError('Unable to save item to list.', ['item' => $item]);

                continue;
            }

            $variables['items'][] = $item;
        }

        if ($errors) {
            foreach ($errors as $itemError) {
                return $this->returnError($itemError->message, $itemError->params);
            }
        }

        $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} added to list.', [
            'count' => count($postItems),
        ]);

        return $this->returnSuccess($message, $variables);
    }

    public function actionToggle(): ?Response
    {
        $postItems = $this->_setItemsFromPost();

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
            $list = $this->_getOrCreateList($postItem);

            if ($list instanceof ItemError) {
                $errors[$key] = $list;

                continue;
            }

            // Create the item for the list and element, with additional attributes
            $item = $this->_getOrCreateItem($list, $element, $postItem);

            if ($item->id) {
                if (!Craft::$app->getElements()->deleteElement($item)) {
                    $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);

                    continue;
                }

                $variables['items'][] = array_merge(['action' => 'removed'], $item->toArray());
            } else {
                if (!Wishlist::$plugin->getItems()->saveElement($item)) {
                    $errors[$key] = new ItemError('Unable to save item to list.', ['item' => $item]);

                    continue;
                }

                $variables['items'][] = array_merge(['action' => 'added'], $item->toArray());
            }
        }

        if ($errors) {
            foreach ($errors as $itemError) {
                return $this->returnError($itemError->message, $itemError->params);
            }
        }

        $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} toggled in list.', [
            'count' => count($postItems),
        ]);

        return $this->returnSuccess($message, $variables);
    }

    public function actionRemove(): ?Response
    {
        $postItems = $this->_setItemsFromPost();

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
            $list = $this->_getOrCreateList($postItem);

            if ($list instanceof ItemError) {
                $errors[$key] = $list;

                continue;
            }

            // Create the item for the list and element, with additional attributes
            $item = $this->_getOrCreateItem($list, $element, $postItem);

            if ($item->id) {
                if (!Craft::$app->getElements()->deleteElement($item)) {
                    $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);

                    continue;
                }

                $variables['items'][] = array_merge(['action' => 'removed'], $item->toArray());
            } else {
                $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);
            }
        }

        if ($errors) {
            foreach ($errors as $itemError) {
                return $this->returnError($itemError->message, $itemError->params);
            }
        }

        $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} removed from list.', [
            'count' => count($postItems),
        ]);

        return $this->returnSuccess($message, $variables);
    }

    public function actionUpdate(): ?Response
    {
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

            $item->setFieldValues($fields);
            $item->setOptions($options);

            if (!Wishlist::$plugin->getItems()->saveElement($item)) {
                $errors[$key] = new ItemError('Unable to update item to list.', ['item' => $item]);

                continue;
            }

            $variables['items'][] = $item;
        }

        if ($errors) {
            foreach ($errors as $itemError) {
                return $this->returnError($itemError->message, $itemError->params);
            }
        }

        $message = Craft::t('wishlist', '{count, number} {count, plural, =1{item} other{items}} updated in list.', [
            'count' => count($postItems),
        ]);

        return $this->returnSuccess($message, $variables);
    }


    // Private Methods
    // =========================================================================

    private function _prepareVariableArray(&$variables): void
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

        if (!empty($variables['listTypeHandle'])) {
            $variables['listType'] = Wishlist::$plugin->getListTypes()->getListTypeByHandle($variables['listTypeHandle']);
        } else if (!empty($variables['listTypeHandleId'])) {
            $variables['listType'] = Wishlist::$plugin->getListTypes()->getListTypeById($variables['listTypeId']);
        }

        $listType = $variables['listType'];
        $item = $variables['item'];

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
        return $this->request->getParam('items', [
            array_merge([
                'itemId' => $this->request->getParam('itemId'),
                'listId' => $this->request->getParam('listId'),
                'listType' => $this->request->getParam('listType'),
                'elementId' => $this->request->getParam('elementId'),
                'elementSiteId' => $this->request->getParam('elementSiteId'),
                'newList' => $this->request->getParam('newList', false),
                'fields' => $this->request->getParam('fields', []),
                'options' => $this->request->getParam('options', []),
            ], $urlPayload),
        ]);
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

    private function _getOrCreateList(array $postItem): ListElement|ItemError
    {
        $listId = $postItem['listId'] ?? null;
        $listType = $postItem['listType'] ?? null;
        $newList = $postItem['newList'] ?? false;
        
        // Get the specific list passed in, unless we specifically want to create a new list
        if ($listId && !$newList) {
            $list = Wishlist::$plugin->getLists()->getListById($listId);

            if (!$list) {
                return new ItemError('Invalid List ID "' . $listId . '".');
            }
        } else {
            // Ensure that we resolve the list type correctly
            $listParams = array_filter(['listType' => $listType]);

            // Either get the current user's list, or create a new one - unless we want a new list always created
            if ($newList) {
                $list = Wishlist::$plugin->getLists()->createList($listParams);
            } else {
                $list = Wishlist::$plugin->getLists()->getUserList($listParams);
            }

            if (!Wishlist::$plugin->getLists()->saveElement($list)) {
                return new ItemError('Unable to save list.', ['list' => $list]);
            }
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);

        return $list;
    }

    private function _getOrCreateItem(ListElement $list, ElementInterface $element, array $postItem): Item
    {
        $itemId = $postItem['itemId'] ?? null;
        $fields = $postItem['fields'] ?? [];
        $options = $postItem['options'] ?? [];

        // Check if we're passing in an itemId - that's easy
        if ($itemId) {
            return Wishlist::$plugin->getItems()->getItemById($itemId);
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

        $itemParams = array_filter(['options' => $options, 'fields' => $fields]);
        $item = WishList::$plugin->getItems()->createItem($list, $element, $itemParams);

        return $item;
    }

}
