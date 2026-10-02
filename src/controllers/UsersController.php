<?php
namespace verbb\wishlist\controllers;

use verbb\wishlist\Wishlist;

use Craft;
use craft\controllers\EditUserTrait;
use craft\web\CpScreenResponseBehavior;
use craft\web\Controller;

use yii\web\ForbiddenHttpException;
use yii\web\Response;

class UsersController extends Controller
{
    // Traits
    // =========================================================================

    use EditUserTrait;


    // Constants
    // =========================================================================

    public const SCREEN_WISHLIST = 'wishlist';


    // Public Methods
    // =========================================================================

    public function actionIndex(?int $userId = null): Response
    {
        $this->requireCpRequest();

        $user = $this->editedUser($userId);

        if (!Wishlist::$plugin->getListTypes()->getEditableListTypeIds()) {
            throw new ForbiddenHttpException('User not authorized to perform this action.');
        }

        /** @var Response|CpScreenResponseBehavior $response */
        $response = $this->asEditUserScreen($user, 'wishlist');

        $content = Craft::$app->getView()->renderTemplate('wishlist/_includes/_editUserTab', [
            'user' => $user,
        ]);

        return $response->contentHtml($content);
    }
}
