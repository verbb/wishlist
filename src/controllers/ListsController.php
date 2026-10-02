<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\errors\ItemError;
use verbb\wishlist\errors\ListError;
use verbb\wishlist\events\AddLineItemEvent;
use verbb\wishlist\events\AddToCartEvent;
use verbb\wishlist\models\Settings;

use Craft;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\mail\Message;
use craft\web\View;

use craft\commerce\Plugin as Commerce;
use craft\commerce\base\Purchasable;

use yii\base\Exception;
use yii\helpers\Markdown;
use yii\validators\EmailValidator;
use yii\web\HttpException;
use yii\web\Response;

use DateTimeInterface;
use Stringable;
use Throwable;

class ListsController extends BaseController
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_ADD_TO_CART = 'beforeAddToCart';
    public const EVENT_AFTER_ADD_TO_CART = 'afterAddToCart';
    public const EVENT_BEFORE_ADD_LINE_ITEM = 'beforeAddLineItem';
    public const EVENT_AFTER_ADD_LINE_ITEM = 'afterAddLineItem';


    // Properties
    // =========================================================================

    public static ?Commerce $commercePlugin = null;

    protected array|bool|int $allowAnonymous = ['create', 'delete', 'clear', 'update', 'update-items', 'add-to-cart', 'share-by-email'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $settings = Wishlist::$plugin->getSettings();
        $postOnlyActions = ['create', 'update', 'update-items', 'delete', 'clear', 'add-to-cart'];

        if (in_array($action->id, $postOnlyActions, true)) {
            if (!$settings->allowGetListActions) {
                $this->requirePostRequest();
            } elseif ($this->request->getIsGet()) {
                // Deprecated in 3.0.22.
                Craft::$app->getDeprecator()->log(
                    'wishlist.listActions.get',
                    'GET requests for front-end Wishlist list actions have been deprecated and will be removed in Wishlist 4. Submit these actions with POST instead.',
                );
            }
        }

        return true;
    }

    public function init(): void
    {
        parent::init();

        self::$commercePlugin = Craft::$app->getPlugins()->getPlugin('commerce');
    }

    public function actionIndex(): Response
    {
        // Remove all inactive lists older than a certain date in config.
        Wishlist::$plugin->getLists()->purgeInactiveLists();

        return $this->renderTemplate('wishlist/lists/index');
    }

    public function actionEditList(string $listTypeHandle, int $listId = null, ListElement $list = null): Response
    {
        $this->requireCpRequest();

        $listType = null;

        $variables = [
            'listTypeHandle' => $listTypeHandle,
            'listId' => $listId,
            'list' => $list,
        ];

        // Make sure a correct list type handle was passed, so we can check permissions
        if ($listTypeHandle) {
            $listType = Wishlist::$plugin->getListTypes()->getListTypeByHandle($listTypeHandle);
        }

        if (!$listType) {
            throw new Exception('The list type was not found.');
        }

        $this->requirePermission('wishlist-manageListType:' . $listType->uid);
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

    public function actionDeleteList(): ?Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $session = Craft::$app->getSession();

        $listId = $this->request->getRequiredParam('listId');
        $list = ListElement::find()->id($listId)->status(null)->one();

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        $this->enforceListPermissions($list);

        if (!Craft::$app->getElements()->deleteElement($list)) {
            if ($this->request->getAcceptsJson()) {
                $this->asJson(['success' => false]);
            }

            $session->setError(Craft::t('wishlist', 'Couldn’t delete list.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'list' => $list,
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            $this->asJson(['success' => true]);
        }

        $session->setNotice(Craft::t('wishlist', 'List deleted.'));

        return $this->redirectToPostedUrl($list);
    }

    public function actionSaveList(): ?Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();

        $list = $this->_setListFromPost();

        $this->enforceListPermissions($list);

        if (!Wishlist::$plugin->getLists()->saveElement($list)) {
            if ($this->request->getAcceptsJson()) {
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

        if ($this->request->getAcceptsJson()) {
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

    public function actionCreate(): ?Response
    {
        if ($this->request->getParam('listId')) {
            throw new HttpException(400, Craft::t('wishlist', 'An existing list cannot be created again.'));
        }

        $list = $this->_setListFromPost(false);
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

    public function actionUpdate(): ?Response
    {
        $listId = $this->request->getParam('listId');

        if (!$listId) {
            return $this->returnError('List ID must be provided.');
        }

        $list = $this->_setListFromPost(false);

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners (or users with manage-others permission) can update lists
        if (!Wishlist::$plugin->getLists()->canModifyListContent($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only update your own list.'));
        }

        if (!Wishlist::$plugin->getLists()->saveElement($list)) {
            return $this->returnError('Unable to update list.', ['list' => $list]);
        }

        return $this->returnSuccess('List updated.', [], $list);
    }

    public function actionUpdateItems(): ?Response
    {
        $listId = $this->request->getParam('listId');

        if (!$listId) {
            return $this->returnError('List ID must be provided.');
        }

        $list = Wishlist::$plugin->getLists()->getListById($listId);

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list with the ID “{id}”', ['id' => $listId]));
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners (or users with manage-others permission) can update lists
        if (!Wishlist::$plugin->getLists()->canModifyListContent($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only update your own list.'));
        }

        $variables = [];
        $errors = [];

        if ($items = $this->request->getParam('items')) {
            $listItems = [];

            foreach (array_keys($items) as $itemId) {
                $item = Wishlist::$plugin->getItems()->getItemById($itemId);

                if (!$item || $item->listId !== $list->id) {
                    throw new HttpException(404, Craft::t('wishlist', 'Unable to find item in list.'));
                }

                $listItems[$itemId] = $item;
            }

            foreach ($listItems as $itemId => $item) {
                $removeItem = $this->request->getParam("items.{$itemId}.remove");
                $fields = $this->request->getParam("items.{$itemId}.fields", []);

                $item->setFieldValues($fields);

                if ($removeItem) {
                    if (!Craft::$app->getElements()->deleteElement($item)) {
                        $errors[$itemId] = new ItemError('Unable to delete item from list.', ['item' => $item]);
                    }
                } elseif (!Wishlist::$plugin->getItems()->saveElement($item)) {
                    $errors[$itemId] = new ItemError('Unable to update item in list.', ['item' => $item]);
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

    public function actionDelete(): ?Response
    {
        $listId = $this->request->getRequiredParam('listId');

        $list = ListElement::find()->id($listId)->status(null)->one();

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners (or users with manage-others permission) can delete lists
        if (!Wishlist::$plugin->getLists()->canModifyListContent($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only delete your own list.'));
        }

        if (!Craft::$app->getElements()->deleteElement($list)) {
            $error = new ListError('Unable to delete list.', ['list' => $list]);

            return $this->returnError($error->message, $error->params);
        }

        return $this->returnSuccess('List deleted.', [], $list);
    }

    public function actionClear(): ?Response
    {
        $listId = $this->request->getRequiredParam('listId');

        $list = ListElement::find()->id($listId)->status(null)->one();

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        // Only owners (or users with manage-others permission) can clear lists
        if (!Wishlist::$plugin->getLists()->canModifyListContent($list)) {
            throw new Exception(Craft::t('wishlist', 'You can only clear your own list.'));
        }

        if (!Wishlist::$plugin->getItems()->deleteItemsForList($listId)) {
            $error = new ListError('Unable to clear list.', ['list' => $list]);

            return $this->returnError($error->message, $error->params);
        }

        return $this->returnSuccess('List cleared.', [], $list);
    }

    public function actionAddToCart(): ?Response
    {
        if (!self::$commercePlugin) {
            return null;
        }

        $listId = $this->request->getRequiredParam('listId');

        $list = ListElement::findOne($listId);

        if (!$list) {
            throw new Exception(Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]));
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list, false);

        $canModifyList = Wishlist::$plugin->getLists()->canModifyListContent($list);
        $reference = $this->request->getParam('reference');

        if (!$canModifyList && !Wishlist::$plugin->getLists()->hasMatchingReference($list, $reference)) {
            throw new HttpException(403, Craft::t('wishlist', 'A valid shared-list reference is required.'));
        }

        $populateListFieldOptions = $this->request->getParam('populateListFieldOptions');
        $populateItemFieldOptions = $this->request->getParam('populateItemFieldOptions');

        // Check to see if we want to add all the items in the list, or just specific ones
        $addingPurchasables = $this->request->getParam('purchasables');

        if (!$canModifyList && ($populateListFieldOptions || $populateItemFieldOptions || $this->request->getParam('clearList') || $this->_hasRequestedItemRemoval($addingPurchasables))) {
            throw new HttpException(403, Craft::t('wishlist', 'Shared lists cannot be modified or used to populate private field values.'));
        }

        $cart = Commerce::getInstance()->getCarts()->getCart(true);
        $itemIdsToRemove = [];

        // Fire a 'beforeAddToCart' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_ADD_TO_CART)) {
            $this->trigger(self::EVENT_BEFORE_ADD_TO_CART, new AddToCartEvent([
                'cart' => $cart,
                'list' => $list,
            ]));
        }

        foreach (ArrayHelper::index($list->getItems(), 'id') as $key => $item) {
            if (is_a($item->getElement(), Purchasable::class)) {
                $purchasable = $item->getElement();

                // Check if we're trying to add specific purchasables - default to adding all
                if ($addingPurchasables) {
                    // If there's no supplied data for this item, don't add it to the cart
                    $itemData = $this->request->getParam("purchasables.{$key}", '');

                    if (!$itemData) {
                        continue;
                    }
                }

                $note = $this->request->getParam("purchasables.{$key}.note", '');
                $options = $this->request->getParam("purchasables.{$key}.options") ?: [];
                $qty = (int)$this->request->getParam("purchasables.{$key}.qty", 1);

                // Check if we should populate using the List/Item custom fields
                if ($populateListFieldOptions) {
                    $options = array_merge($options, $list->getFieldValues());
                }

                if ($populateItemFieldOptions) {
                    $options = array_merge($options, $item->getFieldValues());
                }

                // Ignore zero value qty for multi-add forms https://github.com/craftcms/commerce/issues/330#issuecomment-384533139
                if ($qty > 0) {
                    $lineItem = Commerce::getInstance()->getLineItems()->resolveLineItem($cart, $purchasable->id, $options);

                    // New line items already have a qty of one.
                    if ($lineItem->id) {
                        $lineItem->qty += $qty;
                    } else {
                        $lineItem->qty = $qty;
                    }

                    $lineItem->note = $note;

                    // Fire a 'beforeAddLineItem' event
                    if ($this->hasEventHandlers(self::EVENT_BEFORE_ADD_LINE_ITEM)) {
                        $this->trigger(self::EVENT_BEFORE_ADD_LINE_ITEM, new AddLineItemEvent([
                            'cart' => $cart,
                            'list' => $list,
                            'item' => $item,
                            'lineItem' => $lineItem,
                        ]));
                    }

                    $cart->addLineItem($lineItem);

                    // Fire a 'afterAddLineItem' event
                    if ($this->hasEventHandlers(self::EVENT_AFTER_ADD_LINE_ITEM)) {
                        $this->trigger(self::EVENT_AFTER_ADD_LINE_ITEM, new AddLineItemEvent([
                            'cart' => $cart,
                            'list' => $list,
                            'item' => $item,
                            'lineItem' => $lineItem,
                        ]));
                    }

                    // Should we remove it from the list?
                    $removeFromList = $this->request->getParam("purchasables.{$key}.removeFromList", false);

                    if ($removeFromList && $canModifyList) {
                        $itemIdsToRemove[] = $item->id;
                    }
                }
            }
        }

        if (!Craft::$app->getElements()->saveElement($cart, false)) {
            return $this->returnError('Unable to add items to cart.', [
                'list' => $list,
            ]);
        }

        foreach ($itemIdsToRemove as $itemId) {
            Craft::$app->getElements()->deleteElementById($itemId);
        }

        // Fire a 'afterAddToCart' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_ADD_TO_CART)) {
            $this->trigger(self::EVENT_AFTER_ADD_TO_CART, new AddToCartEvent([
                'cart' => $cart,
                'list' => $list,
            ]));
        }

        // Should we remove all items from the list after adding?
        if ($canModifyList && $this->request->getParam('clearList')) {
            Wishlist::$plugin->getItems()->deleteItemsForList($listId);
        }

        return $this->returnSuccess('Items added to cart.', [], $list);
    }

    public function actionShareByEmail(): ?Response
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

        $listId = $this->request->getRequiredParam('listId');
        $list = ListElement::findOne($listId);

        if (!$list) {
            $message = Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]);

            Wishlist::error($message);

            return $this->returnError($message);
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        $sender = $this->_createShareUser($this->request->getRequiredParam('sender'));
        $recipient = $this->_createShareUser($this->request->getRequiredParam('recipient'));

        if (!$sender || !$recipient) {
            $message = Craft::t('wishlist', 'You must supply a sender and recipient with valid names and email addresses.');

            Wishlist::error($message);

            return $this->returnError($message);
        }

        $variables = [
            'list' => $list,
            'sender' => $sender,
            'recipient' => $recipient,
            'fields' => $this->request->getParam('fields'),
            'shareUrl' => UrlHelper::siteUrl('wishlist', ['id' => $list->reference]),
        ];

        try {
            $mail = $this->_renderEmail('wishlist_share_list', $variables)
                ->setTo($recipient);

            if ($cc = $this->request->getParam('cc')) {
                $mail->setCc(explode(',', $cc));
            }

            if ($bcc = $this->request->getParam('bcc')) {
                $mail->setBcc(explode(',', $bcc));
            }

            if ($settings->attachPdfToEmail) {
                $pdf = Wishlist::$plugin->getPdf()->renderPdf($list);

                $pdfPath = Assets::tempFilePath('pdf');
                file_put_contents($pdfPath, $pdf);

                $filenameFormat = Wishlist::$plugin->getSettings()->pdfFilenameFormat;
                $filename = $this->getView()->renderObjectTemplate($filenameFormat, $list);

                $mail->attach($pdfPath, ['fileName' => $filename . '.pdf', 'contentType' => 'application/pdf']);
            }

            $mail->send();

            $message = Craft::t('wishlist', 'Sent list share notification to {email}.', ['email' => $recipient->email]);

            Wishlist::info($message);

            return $this->returnSuccess($message);
        } catch (Throwable $e) {
            $message = Craft::t('wishlist', 'Failed to send list share to {email} - {error}.', [
                'email' => $recipient->email,
                'error' => $e->getMessage(),
            ]);

            Wishlist::error($message);

            return $this->returnError($message);
        }
    }

    public function actionDuplicateList(): ?Response
    {
        $this->requirePostRequest();

        $list = null;
        $listId = $this->request->getParam('listId');
        $reference = $this->request->getParam('reference');

        if (!$listId && !$reference) {
            $message = Craft::t('wishlist', 'Must provide either “listId” or “reference”.');

            Wishlist::error($message);

            return $this->returnError($message);
        }

        if ($listId) {
            $list = ListElement::find()->id($listId)->one();
        }

        if ($reference) {
            $list = ListElement::find()->reference($reference)->one();
        }

        if (!$list) {
            $message = Craft::t('wishlist', 'No list exists with the ID “{id}”.', ['id' => $listId]);

            Wishlist::error($message);

            return $this->returnError($message);
        }

        // Check if we're allowed to manage lists
        $this->enforceEnabledList($list);
        $this->enforceListPermissions($list);

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser) {
            $message = Craft::t('wishlist', 'Only logged-in users can duplicate a list');

            Wishlist::error($message);

            return $this->returnError($message);
        }

        $newList = Craft::$app->getElements()->duplicateElement($list, [
            'userId' => $currentUser->id,
        ]);

        if ($newList->getErrors()) {
            $message = Craft::t('wishlist', 'Unable to duplicate list “{errors}”.', ['errors' => Json::encode($newList->getErrors())]);

            Wishlist::error($message);

            return $this->returnError($message);
        }

        $message = Craft::t('wishlist', 'Wishlist duplicated.');

        Wishlist::info($message);

        return $this->returnSuccess($message);
    }


    // Private Methods
    // =========================================================================

    private function _createShareUser(mixed $attributes): ?User
    {
        if (!is_array($attributes)) {
            return null;
        }

        $values = [];

        foreach (['firstName', 'lastName', 'email'] as $attribute) {
            $value = $attributes[$attribute] ?? null;

            if (!is_scalar($value) || trim((string)$value) === '') {
                return null;
            }

            $values[$attribute] = trim((string)$value);
        }

        if (!(new EmailValidator())->validate($values['email'])) {
            return null;
        }

        return new User($values);
    }

    private function _getHtmlEmailVariables(array $variables): array
    {
        foreach (['sender', 'recipient'] as $key) {
            if (($variables[$key] ?? null) instanceof User) {
                $variables[$key] = $this->_escapeEmailUser($variables[$key]);
            }
        }

        if (($variables['list'] ?? null) instanceof ListElement) {
            $variables['list'] = $this->_escapeEmailList($variables['list']);
        }

        foreach (['fields', 'shareUrl'] as $key) {
            if (array_key_exists($key, $variables)) {
                $variables[$key] = $this->_escapeEmailMarkdownValue($variables[$key]);
            }
        }

        return $variables;
    }

    private function _escapeEmailUser(User $user): User
    {
        $escapedUser = clone $user;

        foreach (['username', 'email', 'firstName', 'lastName', 'fullName'] as $attribute) {
            if (is_string($user->$attribute)) {
                $escapedUser->$attribute = $this->_escapeEmailMarkdownValue($user->$attribute);
            }
        }

        if (is_string($user->friendlyName)) {
            $escapedUser->setFriendlyName($this->_escapeEmailMarkdownValue($user->friendlyName));
        }

        $escapedUser->setName($this->_escapeEmailMarkdownValue($user->name));

        return $escapedUser;
    }

    private function _escapeEmailList(ListElement $list): ListElement
    {
        $escapedList = clone $list;

        if (is_string($list->title)) {
            $escapedList->title = $this->_escapeEmailMarkdownValue($list->title);
        }

        foreach ($list->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            $escapedList->setFieldValue($field->handle, $this->_escapeEmailMarkdownValue($list->getFieldValue($field->handle)));
        }

        return $escapedList;
    }

    private function _escapeEmailMarkdownValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = preg_replace('/\R/u', ' ', $value);

            return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|~])/u', '\\\\$1', $value);
        }

        if (is_array($value)) {
            $escaped = [];

            foreach ($value as $key => $item) {
                if (is_string($key)) {
                    $key = $this->_escapeEmailMarkdownValue($key);
                }

                $escaped[$key] = $this->_escapeEmailMarkdownValue($item);
            }

            return $escaped;
        }

        if ($value instanceof Stringable) {
            return $this->_escapeEmailMarkdownValue((string)$value);
        }

        if (is_object($value) && !$value instanceof DateTimeInterface) {
            return null;
        }

        return $value;
    }

    private function _prepareVariableArray(array &$variables): void
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
        } elseif (!empty($variables['listTypeHandleId'])) {
            $variables['listType'] = Wishlist::$plugin->getListTypes()->getListTypeById($variables['listTypeId']);
        }

        $listType = $variables['listType'];
        $list = $variables['list'];

        if ($list->id) {
            $this->enforceListPermissions($list);

            if ($list->typeId !== $listType->id) {
                throw new HttpException(404);
            }
        }

        $form = $listType->getFieldLayout()->createForm($list);
        $variables['tabs'] = $form->getTabMenu();
        $variables['fieldsHtml'] = $form->render();
    }

    private function _setListFromPost(bool $allowUserAssignment = true): ListElement
    {
        $listId = $this->request->getParam('listId');

        if ($listId) {
            $list = Wishlist::$plugin->getLists()->getListById($listId);

            if (!$list) {
                throw new Exception(Craft::t('wishlist', 'No list with the ID “{id}”', ['id' => $listId]));
            }

            $this->enforceListPermissions($list);
        } else {
            $list = Wishlist::$plugin->getLists()->createList();
        }

        $typeId = (int)$this->request->getParam('typeId', $list->typeId);

        if ($list->id && $typeId !== $list->typeId) {
            $this->_enforceListTypeChangePermission($list, $typeId);
        }

        $list->typeId = $typeId;
        $list->enabled = (bool)$this->request->getParam('enabled', $list->enabled);
        $list->title = $this->request->getParam('title', $list->title);

        if ($allowUserAssignment) {
            $userId = $this->request->getParam('userId');

            if ($userId) {
                $list->userId = is_array($userId) ? $userId[0] : $userId;
            }
        }

        $list->setFieldValuesFromRequest('fields');

        return $list;
    }

    private function _enforceListTypeChangePermission(ListElement $list, int $typeId): void
    {
        $targetType = Wishlist::$plugin->getListTypes()->getListTypeById($typeId);

        if (!$targetType) {
            throw new HttpException(404, Craft::t('wishlist', 'Unable to find the requested list type.'));
        }

        if ($this->request->getIsCpRequest()) {
            $this->requirePermission('wishlist-manageListType:' . $targetType->uid);

            return;
        }

        if (Wishlist::$plugin->getLists()->isListOwner($list) || Craft::$app->getUser()->getIsAdmin()) {
            return;
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser || !$currentUser->can('wishlist-manageOthersListType:' . $targetType->uid)) {
            throw new HttpException(403);
        }
    }

    private function _hasRequestedItemRemoval(mixed $purchasables): bool
    {
        if (!is_array($purchasables)) {
            return false;
        }

        foreach ($purchasables as $purchasable) {
            if (is_array($purchasable) && !empty($purchasable['removeFromList'])) {
                return true;
            }
        }

        return false;
    }

    private function _renderEmail(string $key, array $variables): Message
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

        $mailer = Craft::$app->getMailer();
        $language = Craft::$app->getRequest()->getIsSiteRequest() ? Craft::$app->language : Craft::$app->getSites()->getPrimarySite()->language;
        $systemMessage = Craft::$app->getSystemMessages()->getMessage($key, $language);

        $view = Craft::$app->getView();
        // Preserve system messages saved with the original Wishlist 3 default.
        $bodyTemplate = str_replace("{{ siteUrl('wishlist', { id: list.reference }) }}", '{{ shareUrl }}', $systemMessage->body);

        $subject = Wishlist::$plugin->getTemplates()->renderSandboxedString($systemMessage->subject, $variables);
        $textBody = Wishlist::$plugin->getTemplates()->renderSandboxedString($bodyTemplate, $variables);

        // Use custom template if configured, otherwise fall back to Craft's default
        if ($settings->templateEmail) {
            $template = $settings->templateEmail;
            $templateMode = View::TEMPLATE_MODE_SITE;
        } else {
            $template = '_special/email';
            $templateMode = View::TEMPLATE_MODE_CP;
        }

        $htmlBody = null;

        try {
            $templateVariables = array_merge($variables, [
                'language' => $language,
            ]);

            // Only add body variable when using Craft's default template
            if (!$settings->templateEmail) {
                // Preserve trusted message formatting while keeping request values plain text.
                $htmlVariables = $this->_getHtmlEmailVariables($variables);
                $htmlTextBody = Wishlist::$plugin->getTemplates()->renderSandboxedString($bodyTemplate, $htmlVariables, 'html');
                $templateVariables['body'] = Template::raw(Markdown::process($htmlTextBody));
            }

            $htmlBody = $view->renderTemplate($template, $templateVariables, $templateMode);

            if (empty(trim($htmlBody))) {
                $htmlBody = null;
            }
        } catch (Throwable $e) {
            Wishlist::error('Error rendering email template: {message}', [
                'message' => $e->getMessage(),
            ]);
        }

        $message = $mailer->compose()
            ->setSubject($subject)
            ->setTextBody($textBody);

        if ($htmlBody) {
            $message->setHtmlBody($htmlBody);
        }

        return $message;
    }
}
