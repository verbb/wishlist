<?php
namespace verbb\wishlist\helpers;

use yii\helpers\Markdown as YiiMarkdown;

class Markdown extends YiiMarkdown
{
    // Static Methods
    // =========================================================================

    /**
     * Process markdown text
     *
     * @param string $markdown
     * @param string|null $flavor
     * @return string
     */
    public static function process($markdown, $flavor = null): string
    {
        return parent::process($markdown, $flavor);
    }
}
