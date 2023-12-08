<?php
namespace verbb\wishlist\helpers;

use verbb\wishlist\elements\Item;
use verbb\wishlist\elements\ListElement;

use craft\base\ElementInterface;
use craft\helpers\Json;
use craft\helpers\UrlHelper as CraftUrlHelper;

class UrlHelper extends CraftUrlHelper
{
    // Static Methods
    // =========================================================================

    public static function addUrl(ElementInterface $element, array $params = []): string
    {
        $defaultParams = ['elementId' => $element->id, 'elementSiteId' => $element->siteId];
        $params = static::encodeUrlParams($defaultParams, $params);

        return static::actionUrl('wishlist/items/add', ['wl' => $params]);
    }

    public static function toggleUrl(ElementInterface $element, array $params = []): string
    {
        $defaultParams = ['elementId' => $element->id, 'elementSiteId' => $element->siteId];
        $params = static::encodeUrlParams($defaultParams, $params);

        return static::actionUrl('wishlist/items/toggle', ['wl' => $params]);
    }

    public static function removeUrl(ElementInterface $element, array $params = []): string
    {
        $defaultParams = ['elementId' => $element->id, 'elementSiteId' => $element->siteId];
        $params = static::encodeUrlParams($defaultParams, $params);

        return static::actionUrl('wishlist/items/remove', ['wl' => $params]);
    }

    public static function encodeUrlParams(array $params, array $extraParams): string
    {
        $params = array_merge($params, $extraParams);

        return self::_base64UrlEncode(Json::encode($params));
    }

    public static function decodeUrlParams(string $params): array
    {
        return Json::decode(self::_base64UrlDecode($params));
    }


    // Private Methods
    // =========================================================================

    private static function _base64UrlEncode(string $input): string
    {
        return strtr(base64_encode($input), '+/=', '._-');
    }

    private static function _base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '._-', '+/='));
    }
}