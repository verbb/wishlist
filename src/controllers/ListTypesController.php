<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\elements\Item;
use verbb\wishlist\models\ListType;
use verbb\wishlist\models\ListTypeSite;

use Craft;
use craft\web\Controller;

use yii\web\HttpException;
use yii\web\Response;

class ListTypesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requirePermission('wishlist-manageListTypes');

        parent::init();
    }

    public function actionListTypeIndex(): Response
    {
        $listTypes = Wishlist::getInstance()->getListTypes()->getAllListTypes();

        return $this->renderTemplate('wishlist/list-types/index', compact('listTypes'));
    }

    public function actionEditListType(int $listTypeId = null, ListType $listType = null): Response
    {
        $variables = [
            'listTypeId' => $listTypeId,
            'listType' => $listType,
        ];

        $variables['brandNewListType'] = false;

        if (empty($variables['listType'])) {
            if (!empty($variables['listTypeId'])) {
                $listTypeId = $variables['listTypeId'];
                $variables['listType'] = Wishlist::getInstance()->getListTypes()->getListTypeById($listTypeId);

                if (!$variables['listType']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['listType'] = new ListType();
                $variables['brandNewListType'] = true;
            }
        }

        if (!empty($variables['listTypeId'])) {
            $variables['title'] = $variables['listType']->name;
        } else {
            $variables['title'] = Craft::t('wishlist', 'Create a List Type');
        }

        $tabs = [
            'listTypeSettings' => [
                'label' => Craft::t('wishlist', 'Settings'),
                'url' => '#list-type-settings',
            ],
            'listFields' => [
                'label' => Craft::t('wishlist', 'List Fields'),
                'url' => '#list-fields',
            ],
            'itemFields' => [
                'label' => Craft::t('wishlist', 'Item Fields'),
                'url' => '#item-fields',
            ]
        ];

        $variables['tabs'] = $tabs;
        $variables['selectedTab'] = 'listTypeSettings';

        return $this->renderTemplate('wishlist/list-types/_edit', $variables);
    }

    public function actionSaveListType()
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser->can('manageWishlist')) {
            throw new HttpException(403, Craft::t('wishlist', 'This action is not allowed for the current user.'));
        }

        $request = Craft::$app->getRequest();
        $this->requirePostRequest();

        $listType = new ListType();

        // Shared attributes
        $listType->id = Craft::$app->getRequest()->getParam('listTypeId');
        $listType->name = Craft::$app->getRequest()->getParam('name');
        $listType->handle = Craft::$app->getRequest()->getParam('handle');
        $listType->default = (bool)Craft::$app->getRequest()->getParam('default');

        // Set the list type field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = ListElement::class;
        $listType->getBehavior('listFieldLayout')->setFieldLayout($fieldLayout);

        // Set the item field layout
        $itemFieldLayout = Craft::$app->getFields()->assembleLayoutFromPost('item-layout');
        $itemFieldLayout->type = Item::class;
        $listType->getBehavior('itemFieldLayout')->setFieldLayout($itemFieldLayout);

        // Save it
        if (Wishlist::getInstance()->getListTypes()->saveListType($listType)) {
            Craft::$app->getSession()->setNotice(Craft::t('wishlist', 'List type saved.'));
            $this->redirectToPostedUrl($listType);
        } else {
            Craft::$app->getSession()->setError(Craft::t('wishlist', 'Couldn’t save list type.'));
        }

        // Send the listType back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'listType' => $listType
        ]);
    }

    public function actionDeleteListType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $listTypeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Wishlist::getInstance()->getListTypes()->deleteListTypeById($listTypeId);
        return $this->asJson(['success' => true]);
    }
}
