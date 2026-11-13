# Item

An item is one entry in a list, linking the list to an element such as an entry or a Commerce product. Whenever you’re working with an item in your templates, you’re working with an `Item` object. You get them from a list’s `items`, `list.getItem()` or an [item query](docs:getting-elements/item-queries).

```twig
{% for item in list.items %}
    {% set element = item.element %}

    {% if element %}
        <a href="{{ element.url }}">{{ element.title }}</a>
        <a href="{{ item.getRemoveUrl() }}">Remove</a>
    {% endif %}
{% endfor %}
```

The linked element can have been deleted since the item was added, so check `item.element` before using it.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the item.
:::

::: reference
### `elementId`

**Type:** `int|null`

The ID of the element the item links to.
:::

::: reference
### `elementSiteId`

**Type:** `int|null`

The ID of the site the linked element was added from.
:::

::: reference
### `elementClass`

**Type:** `string|null`

The class of the linked element, such as `craft\elements\Entry`.
:::

::: reference
### `elementDisplay`

**Type:** `string|null`

The display name of the linked element’s type, such as `Entry` for `craft\elements\Entry`. Twig reads it through `getElementDisplay()`.
:::

::: reference
### `element`

**Type:** `craft\base\ElementInterface|null`

The element the item links to, or `null` when it no longer exists. Twig reads it through `getElement()`.
:::

::: reference
### `elementTitle`

**Type:** `string|null`

The title of the linked element, or `null` when it no longer exists. The item’s `title` holds the same value.
:::

::: reference
### `listId`

**Type:** `int|null`

The ID of the [list](docs:reference/list) the item belongs to.
:::

::: reference
### `list`

**Type:** `verbb\wishlist\elements\ListElement|null`

The [list](docs:reference/list) the item belongs to. Twig reads it through `getList()`.
:::

::: reference
### `options`

**Type:** `array`

The extra options stored with the item, such as a product’s size or colour.
:::

::: reference
### `optionsSignature`

**Type:** `string`

An MD5 hash of the item’s options, used to tell apart items for the same element with different options. It’s read-only.
:::

## Methods

::: reference
### `getAddUrl()`

**Arguments:** `params` (`array`, optional, default `[]`) · **Returns:** `string|null`

Returns the URL that adds the item’s element to a list, or `null` when the linked element no longer exists.
:::

::: reference
### `getRemoveUrl()`

**Arguments:** `params` (`array`, optional, default `[]`) · **Returns:** `string|null`

Returns the URL that removes this item from its list, or `null` when the linked element no longer exists.
:::

::: reference
### `getToggleUrl()`

**Arguments:** `params` (`array`, optional, default `[]`) · **Returns:** `string|null`

Returns the URL that adds the item’s element to a list, or removes it when it’s already there, or `null` when the linked element no longer exists.
:::

::: reference
### `getInList()`

**Arguments:** `list` (`verbb\wishlist\elements\ListElement|null`, optional, default the item’s own list) · **Returns:** `bool`

Returns whether a list contains an item for the same element, site and options as this item.
:::
