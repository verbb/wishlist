<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;

use Craft;
use craft\helpers\StringHelper;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

class BaseController extends Controller
{
    // Protected Methods
    // =========================================================================

    protected function enforceEnabledList($list): void
    {
        $settings = Wishlist::$plugin->getSettings();

        // If it's disabled, and should we check?
        if ($list && !$list->enabled && !$settings->manageDisabledLists) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    protected function returnSuccess($message, $params = [], $object = null): Response
    {
        $request = Craft::$app->getRequest();

        // Try and determine the action automatically
        $action = debug_backtrace()[1]['function'] ?? '';
        $action = str_replace('action', '', $action);
        $action = StringHelper::toKebabCase($action);

        if ($action) {
            $params['action'] = $action;
        }

        if ($request->getAcceptsJson()) {
            $params['success'] = true;

            return $this->asJson($params);
        }

        $this->setSuccessFlash(Craft::t('wishlist', $message));
        
        if ($request->getIsPost()) {

            // Pass object to redirect for URL variables
            return $this->redirectToPostedUrl($object);
        }

        return $this->redirect($request->referrer);
    }

    protected function returnError($message, $params = []): ?Response
    {
        $request = Craft::$app->getRequest();

        $error = Craft::t('wishlist', $message);

        // Try and determine the action automatically
        $action = debug_backtrace()[1]['function'] ?? '';
        $action = str_replace('action', '', $action);
        $action = StringHelper::toKebabCase($action);

        if ($action) {
            $params['action'] = $action;
        }

        if ($request->getAcceptsJson()) {
            $params['error'] = $error;

            return $this->asJson($params);
        }

        $this->setFailFlash($error);

        if ($request->getIsPost()) {
            if ($params) {
                Craft::$app->getUrlManager()->setRouteParams($params);
            }

            return null;
        }

        return $this->redirect($request->referrer);
    }
}
