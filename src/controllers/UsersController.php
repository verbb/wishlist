<?php
namespace verbb\wishlist\controllers;

use Craft;
use craft\controllers\EditUserTrait;
use craft\web\CpScreenResponseBehavior;
use craft\web\Controller;

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
        $user = $this->editedUser($userId);

        /** @var Response|CpScreenResponseBehavior $response */
        $response = $this->asEditUserScreen($user, 'wishlist');

        $content = Craft::$app->getView()->renderTemplate('wishlist/_includes/_editUserTab', [
            'user' => $user,
        ]);

        return $response->contentHtml($content);
    }
}
