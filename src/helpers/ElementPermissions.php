<?php
namespace verbb\wishlist\helpers;

use Craft;
use craft\controllers\AppController;
use craft\controllers\ElementIndexesController;
use craft\controllers\ElementSearchController;
use craft\controllers\ElementSelectorModalsController;
use craft\controllers\RelationalFieldsController;
use craft\web\Application;

class ElementPermissions
{
    // Static Methods
    // =========================================================================

    public static function isElementReadRequest(string $elementType): bool
    {
        if (static::isElementIndexRequest($elementType)) {
            return true;
        }

        if (!(Craft::$app instanceof Application) || !Craft::$app->getRequest()->getIsCpRequest()) {
            return false;
        }

        $controller = Craft::$app->controller;
        $actionId = $controller?->action?->id;

        // These actions expose query results directly, including mixed chips and nested list/item queries.
        // Ordinary CP lifecycle queries must retain access to records outside the current user's list types.
        return ($controller instanceof AppController && in_array($actionId, ['render-elements', 'render-components'], true))
            || ($controller instanceof RelationalFieldsController && $actionId === 'structured-input-html')
            || ($controller instanceof ElementSelectorModalsController && $actionId === 'body')
            || (class_exists(ElementSearchController::class) && $controller instanceof ElementSearchController && $actionId === 'search');
    }

    public static function isElementIndexRequest(string $elementType): bool
    {
        if (!(Craft::$app instanceof Application) || !(Craft::$app->controller instanceof ElementIndexesController)) {
            return false;
        }

        // Match the index controller's effective type, leaving other element queries in bulk actions unchanged.
        $requestedType = Craft::$app->getRequest()->getParam('elementType');

        return is_string($requestedType) && is_a($requestedType, $elementType, true);
    }
}
