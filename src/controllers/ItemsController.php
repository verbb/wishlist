<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\errors\ItemError;

use Craft;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

class ItemsController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = ['add', 'remove', 'update', 'toggle'];


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

    public function actionSaveItem()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $itemId = $request->getParam('itemId');

        if ($itemId) {
            $item = Wishlist::getInstance()->getItems()->getItemById($itemId);

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
                'item' => $item
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
                'cpEditUrl' => $item->getCpEditUrl()
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Item saved.'));

        return $this->redirectToPostedUrl($item);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $itemId = $request->getParam('itemId');
        $item = Wishlist::getInstance()->getItems()->getItemById($itemId);

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

    public function actionAdd()
    {
        $settings = Wishlist::$plugin->getSettings();
        $request = Craft::$app->getRequest();

        $errors = [];

        // By default, handle multi-items, but if not - set them up as one
        $postItems = $request->getParam('items', [[
            'elementId' => $request->getParam('elementId'),
            'fields' => $request->getParam('fields'),
            'options' => $request->getParam('options'),
        ]]);

        $variables = [
            'items' => [],
        ];

        foreach ($postItems as $key => $postItem) {
            $elementId = $postItem['elementId'] ?? '';
            
            if (!$elementId) {
                $errors[$key] = new ItemError('Element ID must be provided.');

                continue;
            }

            $element = Craft::$app->getElements()->getElementById($elementId);

            if (!$element) {
                $errors[$key] = new ItemError('Unable to find element.');

                continue;
            }

            $item = $this->_setItemFromPost($elementId);

             // Check if we're allowed to manage lists
            $this->enforceEnabledList($item->list);

            // Set any additional options on the item
            $options = $postItem['options'] ?? [];
            
            // Clear any empty options
            $options = array_filter($options);

            $item->setOptions($options);

            // Check if this is in the list
            $existingItem = Item::find()
                ->elementId($elementId)
                ->listId($item->listId)
                ->optionsSignature($item->getOptionsSignature())
                ->one();
            
            if ($existingItem && !$settings->allowDuplicates) {
                $errors[$key] = new ItemError('Item already in list.');

                continue;
            }

            // Add custom fields
            $fields = $postItem['fields'] ?? [];
            $item->setFieldValues($fields);

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

        // TOOD: Remove this at the next major breakpoint
        $variables['item'] = $item;
        $variables['notice'] = '`item` in the response is deprecated and will be removed in the next major release. Please use `items` instead.';

        return $this->returnSuccess('Item' . ((count($postItems) > 1) ? 's' : '') . ' added to list.', $variables);
    }

    public function actionRemove()
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getParam('listId');

        $listTypeId = $request->getParam('listTypeId');
        $listTypeHandle = $request->getParam('listTypeHandle');

        if (!$listTypeId && $listTypeHandle) {
            // Always take the ID first. If both are sent, Handle is ignored.
            $listType = WishList::$plugin->getListTypes()->getListTypeByHandle($listTypeHandle);
            
            if ($listType) {
                $listTypeId = $listType->id;
            }
        }
        
        $list = Wishlist::$plugin->getLists()->getList($listId, true, $listTypeId);

         // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);

        $errors = [];

        $variables = [
            'items' => [],
        ];

        // By default, handle multi-items, but if not - set them up as one
        $postItems = $request->getParam('items', [[
            'elementId' => $request->getParam('elementId'),
            'fields' => $request->getParam('fields'),
        ]]);

        foreach ($postItems as $key => $postItem) {
            $elementId = $postItem['elementId'] ?? '';
            
            if (!$elementId) {
                $errors[$key] = new ItemError('Element ID must be provided.');

                continue;
            }

            $item = Item::find()
                ->elementId($elementId)
                ->listId($list->id)
                ->one();

            if (!$item) {
                $errors[$key] = new ItemError('Unable to find item in list.');
            
                continue;
            }

            if (!Craft::$app->getElements()->deleteElement($item)) {
                $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);
            
                continue;
            }

            $variables['items'][] = $item;
        }

        if ($errors) {
            foreach ($errors as $itemError) {
                return $this->returnError($itemError->message, $itemError->params);
            }
        }

        // TOOD: Remove this at the next major breakpoint
        $variables['item'] = $item;
        $variables['notice'] = '`item` in the response is deprecated and will be removed in the next major release. Please use `items` instead.';

        return $this->returnSuccess('Items removed from list.', $variables);
    }

    public function actionToggle()
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getParam('listId');

        $listTypeId = $request->getParam('listTypeId');
        $listTypeHandle = $request->getParam('listTypeHandle');

        if (!$listTypeId && $listTypeHandle) {
            // Always take the ID first. If both are sent, Handle is ignored.
            $listType = WishList::$plugin->getListTypes()->getListTypeByHandle($listTypeHandle);
            
            if ($listType) {
                $listTypeId = $listType->id;
            }
        }

        $list = Wishlist::$plugin->getLists()->getList($listId, true, $listTypeId);

         // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);

        $errors = [];

        $variables = [
            'items' => [],
        ];

        // By default, handle multi-items, but if not - set them up as one
        $postItems = $request->getParam('items', [[
            'elementId' => $request->getParam('elementId'),
            'fields' => $request->getParam('fields'),
        ]]);

        $actions = [];

        foreach ($postItems as $key => $postItem) {
            $elementId = $postItem['elementId'] ?? '';

            if (!$elementId) {
                $errors[$key] = new ItemError('Element ID must be provided.');

                continue;
            }

            $item = Item::find()
                ->elementId($elementId)
                ->listId($list->id)
                ->one();

            if ($item) {
                if (!Craft::$app->getElements()->deleteElement($item)) {
                    $errors[$key] = new ItemError('Unable to delete item from list.', ['item' => $item]);
                    
                    continue;
                }

                $variables['items'][] = array_merge(['action' => 'removed'], $item->toArray());
            } else {
                $item = $this->_setItemFromPost($elementId);

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

        // TOOD: Remove this at the next major breakpoint
        $variables['item'] = $variables['items'][0] ?? [];
        $variables['notice'] = '`item` in the response is deprecated and will be removed in the next major release. Please use `items` instead.';

        return $this->returnSuccess('Items toggled in list.', $variables);
    }

    public function actionUpdate()
    {
        $request = Craft::$app->getRequest();
        $itemId = $request->getParam('itemId');

        if (!$itemId) {
            return $this->returnError('Item ID must be provided.');
        }

        $item = Craft::$app->getElements()->getElementById($itemId);

        if (!$item) {
            return $this->returnError('Unable to find item.');
        }

         // Check if we're allowed to manage lists
        $this->enforceEnabledList($item->list);

        $item->setFieldValuesFromRequest('fields');
        
        if (!Wishlist::$plugin->getItems()->saveElement($item)) {
            return $this->returnError('Unable to update item in list.', ['item' => $item]);
        }

        return $this->returnSuccess('Item updated in list.');
    }


    // Private Methods
    // =========================================================================

    private function _prepareVariableArray(&$variables)
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

    private function _setItemFromPost($elementId = null): Item
    {
        $request = Craft::$app->getRequest();
        $elementId = $request->getParam('elementId', $elementId);
        $listId = $request->getParam('listId');

        $listTypeId = $request->getParam('listTypeId');
        $listTypeHandle = $request->getParam('listTypeHandle');

        if (!$listTypeId && $listTypeHandle) {
            // Always take the ID first. If both are sent, Handle is ignored.
            $listType = WishList::$plugin->getListTypes()->getListTypeByHandle($listTypeHandle);
            
            if ($listType) {
                $listTypeId = $listType->id;
            }
        }

        // Create the item, and force the item's list to also be created if not already
        $item = WishList::$plugin->getItems()->createItem($elementId, $listId, $listTypeId, true);

        return $item;
    }

}