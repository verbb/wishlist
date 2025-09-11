<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\elements\ListElement;
use verbb\wishlist\models\Settings;

use Craft;
use craft\helpers\StringHelper;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;

class BaseController extends Controller
{
    // Protected Methods
    // =========================================================================

    protected function enforceEnabledList(?ListElement $list): void
    {
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

        // If it's disabled, and should we check?
        if ($list && !$list->enabled && !$settings->manageDisabledLists) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    protected function enforceListPermissions(ListElement $list, bool $enforceOwner = true): void
    {
        if (!$list->getType()) {
            Craft::error('Attempting to access a list that doesn’t have a type', __METHOD__);
            throw new HttpException(404);
        }

        // If this is a front-end request, ensure that it's the owner of the list making changes
        if ($enforceOwner) {
            if (Craft::$app->getRequest()->getIsSiteRequest()) {
                $currentUser = Craft::$app->getUser()->getIdentity();

                // If an admin, assume they have permission to edit another list
                if (Craft::$app->getUser()->getIsAdmin()) {
                    return;
                }

                // If logged in, easy check
                if ($currentUser) {
                    if ($currentUser->id !== $list->userId) {
                        throw new HttpException(403);
                    }

                    return;
                }

                if ($list->sessionId !== Craft::$app->getSession()->get('wishlist_list')) {
                    // Check if the guests session matches the lists
                    throw new HttpException(403);
                }

                return;
            }
            
            $this->requirePermission('wishlist-manageListType:' . $list->getType()->uid);
        }
    }

    protected function returnSuccess(string $message, array $params = [], ?object $object = null): Response
    {
        // Try and determine the action automatically
        $action = debug_backtrace()[1]['function'] ?? '';
        $action = str_replace('action', '', $action);
        $action = StringHelper::toKebabCase($action);

        if ($action) {
            $params['action'] = $action;
        }

        if ($this->request->getAcceptsJson()) {
            $params['success'] = true;

            return $this->asJson($params);
        }

        $this->setSuccessFlash(Craft::t('wishlist', $message));

        if ($this->request->getIsPost()) {

            // Pass object to redirect for URL variables
            return $this->redirectToPostedUrl($object);
        }

        return $this->redirect($this->request->referrer);
    }

    protected function returnError(string $message, array $params = []): ?Response
    {
        $error = Craft::t('wishlist', $message);

        // Try and determine the action automatically
        $action = debug_backtrace()[1]['function'] ?? '';
        $action = str_replace('action', '', $action);
        $action = StringHelper::toKebabCase($action);

        if ($action) {
            $params['action'] = $action;
        }

        if ($this->request->getAcceptsJson()) {
            $params['error'] = $error;

            return $this->asJson($params);
        }

        $this->setFailFlash($error);

        if ($this->request->getIsPost()) {
            if ($params) {
                Craft::$app->getUrlManager()->setRouteParams($params);
            }

            return null;
        }

        return $this->redirect($this->request->referrer);
    }
}
