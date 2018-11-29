<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class ItemsController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = ['add', 'remove', 'toggle'];


    // Public Methods
    // =========================================================================

    public function actionEditItem(string $listTypeHandle, int $listId, int $itemId, Item $item = null): Response
    {
        $variables = [
            'listTypeHandle' => $listTypeHandle,
            'listId' => $listId,
            'itemId' => $itemId,
            'item' => $item,
        ];

        $this->_prepareVariableArray($variables);

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
        $itemId = $request->getBodyParam('itemId');

        if ($itemId) {
            $item = Wishlist::getInstance()->getItems()->getItemById($itemId);

            if (!$item) {
                throw new Exception(Craft::t('wishlist', 'No item with the ID “{id}”', ['id' => $itemId]));
            }
        } else {
            $item = new Item();
        }
        
        $item->setFieldValuesFromRequest('fields');

        if (!Craft::$app->getElements()->saveElement($item)) {
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


    // Front-end Methods
    // =========================================================================

    public function actionAdd()
    {
        $request = Craft::$app->getRequest();
        $elementId = $request->getParam('elementId');

        $settings = Wishlist::$plugin->getSettings();

        if (!$elementId) {
            return $this->returnError('Element ID must be provided.');
        }

        $element = Craft::$app->getElements()->getElementById($elementId);

        if (!$element) {
            return $this->returnError('Unable to find element.');
        }

        $item = $this->_setItemFromPost();

        // Check if this is in the list
        $existingItem = Item::find()->elementId($elementId)->listId($item->listId)->one();
        
        if ($existingItem && !$settings->allowDuplicates) {
            return $this->returnError('Item already in list.');
        }

        if (!Craft::$app->getElements()->saveElement($item)) {
            return $this->returnError('Unable to save item to list.', ['item' => $item]);
        }

        return $this->returnSuccess('Item added to list.');
    }

    public function actionRemove()
    {
        $request = Craft::$app->getRequest();
        $elementId = $request->getParam('elementId');
        $listId = $request->getParam('listId');

        $list = Wishlist::$plugin->getLists()->getList($listId, true);

        if (!$elementId) {
            return $this->returnError('Element ID must be provided.');
        }

        $item = Item::find()
            ->elementId($elementId)
            ->listId($list->id)
            ->one();

        if (!$item) {
            return $this->returnError('Unable to find item in list.');
        }

        if (!Craft::$app->getElements()->deleteElement($item)) {
            return $this->returnError('Unable to delete item from list.', ['item' => $item]);
        }

        return $this->returnSuccess('Item removed from list.');
    }

    public function actionToggle()
    {
        $request = Craft::$app->getRequest();
        $elementId = $request->getParam('elementId');
        $listId = $request->getParam('listId');

        $list = Wishlist::$plugin->getLists()->getList($listId, true);

        if (!$elementId) {
            return $this->returnError('Element ID must be provided.');
        }

        $item = Item::find()
            ->elementId($elementId)
            ->listId($list->id)
            ->one();

        if ($item) {
            if (!Craft::$app->getElements()->deleteElement($item)) {
                return $this->returnError('Unable to delete item from list.', ['item' => $item]);
            }
        } else {
            $item = $this->_setItemFromPost();

            if (!Craft::$app->getElements()->saveElement($item)) {
                return $this->returnError('Unable to save item to list.', ['item' => $item]);
            }
        }

        return $this->returnSuccess('Item toggled in list.');
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
    }

    private function _setItemFromPost(): Item
    {
        $request = Craft::$app->getRequest();
        $elementId = $request->getParam('elementId');
        $listId = $request->getParam('listId');

        $item = WishList::$plugin->getItems()->createItem($elementId, $listId);

        return $item;
    }

}