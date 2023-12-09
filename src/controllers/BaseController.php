<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;
use verbb\wishlist\models\Settings;

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
        /* @var Settings $settings */
        $settings = Wishlist::$plugin->getSettings();

        // If it's disabled, and should we check?
        if ($list && !$list->enabled && !$settings->manageDisabledLists) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    protected function returnSuccess($message, $params = [], $object = null): Response
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

    protected function returnError($message, $params = []): ?Response
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
