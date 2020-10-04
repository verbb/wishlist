<?php
namespace verbb\wishlist\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseField;
use craft\helpers\Html;

use yii\base\InvalidArgumentException;

class OptionsField extends BaseField
{
    // Public Methods
    // =========================================================================

    public function attribute(): string
    {
        return 'options';
    }

    public function hasCustomWidth(): bool
    {
        return false;
    }


    // Protected Methods
    // =========================================================================

    protected function defaultLabel(ElementInterface $element = null, bool $static = false)
    {
        return Craft::t('wishlist', 'Options');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false)
    {
        return Craft::$app->getView()->renderTemplate('wishlist/_includes/_item-options', [
            'element' => $element,
        ]);
    }
}
