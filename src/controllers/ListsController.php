<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\errors\ItemError;
use verbb\wishlist\errors\ListError;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Localization;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;
use craft\commerce\base\Purchasable;

use yii\base\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class ListsController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = ['create', 'delete', 'clear', 'update', 'update-items', 'add-to-cart', 'share-by-email'];
    public static $commercePlugin;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$commercePlugin = Craft::$app->getPlugins()->getPlugin('commerce');
    }

    public function actionIndex(): Response
    {
        // Remove all inactive lists older than a certain date in config.
        Wishlist::getInstance()->getLists()->purgeInactiveLists();

        return $this->renderTemplate('wishlist/lists/index');
    }

    public function actionEditList(string $listTypeHandle, int $listId = null, ListElement $list = null): Response
    {
        $listType = null;

        $variables = [
            'listTypeHandle' => $listTypeHandle,
            'listId' => $listId,
            'list' => $list,
        ];

        // Make sure a correct list type handle was passed so we can check permissions
        if ($listTypeHandle) {
            $listType = Wishlist::$plugin->getListTypes()->getListTypeByHandle($listTypeHandle);
        }

        if (!$listType) {
            throw new Exception('The list type was not found.');
        }

        $this->requirePermission('wishlist-manageListType:' . $listType->id);
        $variables['listType'] = $listType;

        $this->_prepareVariableArray($variables);

        if (!empty($variables['list']->id)) {
            $variables['title'] = $variables['list']->title;
        } else {
            $variables['title'] = Craft::t('wishlist', 'Create a new list');
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'wishlist/lists/' . $variables['listTypeHandle'] . '/{id}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'];

        return $this->renderTemplate('wishlist/lists/_edit', $variables);
    }

    public function actionDeleteList()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $listId = $request->getRequiredParam('listId');
        $list = ListElement::findOne($listId);

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        $this->enforceListPermissions($list);

        if (!Craft::$app->getElements()->deleteElement($list)) {
            if ($request->getAcceptsJson()) {
                $this->asJson(['success' => false]);
            }

            $session->setError(Craft::t('wishlist', 'Couldn’t delete list.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'list' => $list,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            $this->asJson(['success' => true]);
        }

        $session->setNotice(Craft::t('wishlist', 'List deleted.'));

        return $this->redirectToPostedUrl($list);
    }

    public function actionSaveList()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $list = $this->_setListFromPost();

        $this->enforceListPermissions($list);

        if (!Wishlist::$plugin->getLists()->saveElement($list)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $list->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('wishlist', 'Couldn’t save list.'));

            // Send the category back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'list' => $list,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $list->id,
                'title' => $list->title,
                'status' => $list->getStatus(),
                'url' => $list->getUrl(),
                'cpEditUrl' => $list->getCpEditUrl(),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'List saved.'));

        return $this->redirectToPostedUrl($list);
    }


    // Front-end Methods
    // =========================================================================

    public function actionCreate()
    {
        $request = Craft::$app->getRequest();

        $list = $this->_setListFromPost();
        $list->enabled = true;

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        if (!Wishlist::$plugin->getLists()->saveElement($list)) {
            $error = new ListError('Unable to save list.', ['list' => $list]);

            return $this->returnError($error->message, $error->params);
        }

        return $this->returnSuccess('List saved.', [
            'id' => $list->id,
            'reference' => $list->reference,
            'title' => $list->title,
            'status' => $list->getStatus(),
            'url' => $list->getUrl(),
            'cpEditUrl' => $list->getCpEditUrl(),
        ], $list);
    }

    public function actionUpdate()
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getParam('listId');

        if (!$listId) {
            return $this->returnError('List ID must be provided.');
        }

        $list = $this->_setListFromPost();

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners can update their own lists
        if (!WishList::$plugin->getLists()->isListOwner($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only update your own list.'));
        }

        if (!Wishlist::$plugin->getLists()->saveElement($list)) {
            return $this->returnError('Unable to update list.', ['list' => $list]);
        }

        return $this->returnSuccess('List updated.', [], $list);
    }

    public function actionUpdateItems()
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getParam('listId');

        if (!$listId) {
            return $this->returnError('List ID must be provided.');
        }

        $list = $this->_setListFromPost();

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners can update their own lists
        if (!WishList::$plugin->getLists()->isListOwner($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only update your own list.'));
        }

        $variables = [];
        $errors = [];

        if ($items = $request->getParam('items')) {
            foreach ($items as $itemId => $item) {
                $removeItem = $request->getParam("items.{$itemId}.remove");
                $fields = $request->getParam("items.{$itemId}.fields", []);

                $item = Wishlist::getInstance()->getItems()->getItemById($itemId);
                $item->setFieldValues($fields);

                if ($removeItem) {
                    if (!Craft::$app->getElements()->deleteElement($item)) {
                        $errors[$itemId] = new ItemError('Unable to delete item from list.', ['item' => $item]);
                    }
                } else {
                    if (!Wishlist::$plugin->getItems()->saveElement($item)) {
                        $errors[$itemId] = new ItemError('Unable to update item in list.', ['item' => $item]);
                    }
                }

                $variables['items'][] = $item;
            }
        }

        if ($errors) {
            foreach ($errors as $itemError) {
                return $this->returnError($itemError->message, $itemError->params);
            }
        }

        return $this->returnSuccess('List items updated.', $variables, $list);
    }

    public function actionDelete()
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getRequiredParam('listId');

        $list = ListElement::findOne($listId);

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners can delete their own lists
        if (!WishList::$plugin->getLists()->isListOwner($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only delete your own list.'));
        }

        if (!Craft::$app->getElements()->deleteElement($list)) {
            $error = new ListError('Unable to delete list.', ['list' => $list]);

            return $this->returnError($error->message, $error->params);
        }

        return $this->returnSuccess('List deleted.', [], $list);
    }

    public function actionClear()
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getRequiredParam('listId');

        $list = ListElement::findOne($listId);

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners can clear their own lists
        if (!WishList::$plugin->getLists()->isListOwner($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only clear your own list.'));
        }

        if (!Wishlist::$plugin->getItems()->deleteItemsForList($listId)) {
            $error = new ListError('Unable to clear list.', ['list' => $list]);

            return $this->returnError($error->message, $error->params);
        }

        return $this->returnSuccess('List cleared.', [], $list);
    }

    public function actionAddToCart()
    {
        if (!self::$commercePlugin) {
            return;
        }

        $request = Craft::$app->getRequest();
        $listId = $request->getRequiredParam('listId');

        $list = ListElement::findOne($listId);

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        $cart = Commerce::getInstance()->getCarts()->getCart(true);

        // Check to see if we want to add all the items in the list, or just specific ones
        $addingPurchasables = $request->getParam('purchasables');

        foreach ($list->getItems()->indexBy('id')->all() as $key => $item) {
            if (is_a($item->getElement(), Purchasable::class)) {
                $purchasable = $item->getElement();

                // Check if we're trying to add specific purchasables - default to adding all
                if ($addingPurchasables) {
                    // If there's no supplied data for this item, don't add it to the cart
                    $itemData = $request->getParam("purchasables.{$key}", '');

                    if (!$itemData) {
                        continue;
                    }
                }

                $note = $request->getParam("purchasables.{$key}.note", '');
                $options = $request->getParam("purchasables.{$key}.options") ?: [];
                $qty = (int)$request->getParam("purchasables.{$key}.qty", 1);

                // Ignore zero value qty for multi-add forms https://github.com/craftcms/commerce/issues/330#issuecomment-384533139
                if ($qty > 0) {
                    $lineItem = Commerce::getInstance()->getLineItems()->resolveLineItem($cart->id, $purchasable->id, $options);

                    // New line items already have a qty of one.
                    if ($lineItem->id) {
                        $lineItem->qty += $qty;
                    } else {
                        $lineItem->qty = $qty;
                    }

                    $lineItem->note = $note;
                    $cart->addLineItem($lineItem);

                    // Should we remove it from the list?
                    $removeFromList = $request->getParam("purchasables.{$key}.removeFromList", false);

                    if ($removeFromList) {
                        Craft::$app->getElements()->deleteElementById($item->id);
                    }
                }
            }
        }

        if (!Craft::$app->getElements()->saveElement($cart, false)) {
            return $this->returnError('Unable to add items to cart.', [
                'list' => $list,
            ]);
        }

        // Should we remove all items from the list after adding?
        $clearList = $request->getParam('clearList');

        if ($clearList) {
            Wishlist::$plugin->getItems()->deleteItemsForList($listId);
        }

        return $this->returnSuccess('Items added to cart.', [], $list);
    }

    public function actionShareByEmail()
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getRequiredParam('listId');

        $list = ListElement::findOne($listId);

        if (!$list) {
            $message = Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]);

            Wishlist::error($message);

            return $this->returnError($message);
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        $sender = $request->getRequiredParam('sender');
        $recipient = $request->getRequiredParam('recipient');

        if (!$sender || !$recipient) {
            $message = Craft::t('wishlist', 'You must supply and sender and recipient');

            Wishlist::error($message);

            return $this->returnError($message);
        }

        // Create user elements for sender/recipient
        $sender = new User($sender);
        $recipient = new User($recipient);

        $variables = [
            'list' => $list,
            'sender' => $sender,
            'recipient' => $recipient,
            'fields' => $request->getParam('fields'),
        ];

        try {
            $mail = Craft::$app->getMailer()
                ->composeFromKey('wishlist_share_list', $variables)
                ->setTo($recipient);

            if ($cc = $request->getParam('cc')) {
                $mail->setCc(explode(',', $cc));
            }

            if ($bcc = $request->getParam('bcc')) {
                $mail->setBcc(explode(',', $bcc));
            }

            $mail->send();

            $message = Craft::t('wishlist', 'Sent list share notification to {email}.', ['email' => $recipient->email]);

            Wishlist::log($message);

            return $this->returnSuccess($message);
        } catch (\Throwable $e) {
            $message = Craft::t('wishlist', 'Failed to send list share to {email} - {error}.', [
                'email' => $recipient->email,
                'error' => $e->getMessage(),
            ]);

            Wishlist::error($message);

            return $this->returnError($message);
        }
    }


    // Protected Methods
    // =========================================================================

    protected function enforceListPermissions(ListElement $list)
    {
        if (!$list->getType()) {
            Craft::error('Attempting to access a list that doesn’t have a type', __METHOD__);
            throw new HttpException(404);
        }

        // We shouldn't be checking front-end requests for permissions
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            return;
        }

        $this->requirePermission('wishlist-manageListType:' . $list->getType()->id);
    }


    // Private Methods
    // =========================================================================

    private function _prepareVariableArray(&$variables)
    {
        // List related checks
        if (empty($variables['list'])) {
            if (!empty($variables['listId'])) {
                $variables['list'] = Craft::$app->getElements()->getElementById($variables['listId'], ListElement::class);

                if (!$variables['list']) {
                    throw new Exception('Missing list data.');
                }
            } else {
                $variables['list'] = new ListElement();
                $variables['list']->typeId = $variables['listType']->id;
            }
        }

        if (!empty($variables['listTypeHandle'])) {
            $variables['listType'] = Wishlist::$plugin->getListTypes()->getListTypeByHandle($variables['listTypeHandle']);
        } else if (!empty($variables['listTypeHandleId'])) {
            $variables['listType'] = Wishlist::$plugin->getListTypes()->getListTypeById($variables['listTypeId']);
        }

        $listType = $variables['listType'];
        $list = $variables['list'];

        $form = $listType->getFieldLayout()->createForm($list);
        $variables['tabs'] = $form->getTabMenu();
        $variables['fieldsHtml'] = $form->render();
    }

    private function _setListFromPost(): ListElement
    {
        $request = Craft::$app->getRequest();
        $listId = $request->getParam('listId');

        if ($listId) {
            $list = Wishlist::getInstance()->getLists()->getListById($listId);

            if (!$list) {
                throw new Exception(Craft::t('wishlist', 'No list with the ID “{id}”', ['id' => $listId]));
            }
        } else {
            $list = Wishlist::$plugin->getLists()->createList();
        }

        $list->typeId = $request->getParam('typeId', $list->typeId);
        $list->enabled = (bool)$request->getParam('enabled', $list->enabled);
        $list->title = $request->getParam('title', $list->title);

        $list->setFieldValuesFromRequest('fields');

        return $list;
    }
}
