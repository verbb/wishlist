<?php
namespace verbb\wishlist\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseField;

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

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('wishlist', 'Options');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('wishlist/_includes/_item-options', [
            'element' => $element,
        ]);
    }
}
