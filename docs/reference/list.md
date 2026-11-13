# List

A list is a collection of items saved by a user or a guest, such as their wishlist or a gift registry. Whenever you’re working with a list in your templates, you’re working with a `List` object. You get one from `craft.wishlist.getUserList()` or a [list query](docs:getting-elements/list-queries).

```twig
{% set list = craft.wishlist.getUserList() %}

<h2>{{ list.title }}</h2>

{% for item in list.items %}
    <p>{{ item.elementTitle }}</p>
{% endfor %}
```

A visitor who hasn’t saved a list yet gets a new, unsaved list from `craft.wishlist.getUserList()`, so its `id` is `null` and it has no items.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

The ID of the list, or `null` when the list hasn’t been saved yet.
:::

::: reference
### `title`

**Type:** `string|null`

The title of the list. A new list takes its list type’s name as its title.
:::

::: reference
### `reference`

**Type:** `string|null`

A unique identifier for the list, used in shared list links and PDF URLs.
:::

::: reference
### `typeId`

**Type:** `int|null`

The ID of the list’s list type.
:::

::: reference
### `type`

**Type:** `verbb\wishlist\models\ListType`

The list’s list type. When the list has no list type, this is the default list type. Twig reads it through `getType()`.
:::

::: reference
### `userId`

**Type:** `int|null`

The ID of the user who owns the list, or `null` when a guest owns it.
:::

::: reference
### `user`

**Type:** `craft\elements\User|null`

The user who owns the list, or `null` when a guest owns it. Twig reads it through `getUser()`.
:::

::: reference
### `sessionId`

**Type:** `string|null`

The private credential that identifies the guest who owns the list. It remains available to trusted server-side code but is omitted from GraphQL and serialized element responses. Do not expose it in templates, URLs, logs or API responses.
:::

::: reference
### `default`

**Type:** `bool|null`

Whether the list is its owner’s default list.
:::

::: reference
### `lastIp`

**Type:** `string|null`

The last known IP address of the user or guest who owns the list.
:::

::: reference
### `items`

**Type:** `verbb\wishlist\elements\Item[]`

The [items](docs:reference/item) in the list, newest first. Twig reads it through `getItems()`. To narrow the items down further, use an [item query](docs:getting-elements/item-queries) with the list’s `id`.
:::

## Methods

::: reference
### `getItem()`

**Arguments:** `element` (`craft\base\ElementInterface`), `params` (`array`, optional, default `[]`) · **Returns:** `verbb\wishlist\elements\Item|null`

Returns the [item](docs:reference/item) in the list that links to the given element, or `null` when the element isn’t in the list. Pass item query parameters as `params` to match a particular item, such as one with certain options.

```twig
{% set item = list.getItem(product, { options: { size: 'large' } }) %}
```
:::

::: reference
### `getHasItem()`

**Arguments:** `item` (`verbb\wishlist\elements\Item`) · **Returns:** `bool`

Returns whether the list contains an item for the same element, site and options as the given item. In Twig, you can call it as `list.hasItem(item)`.
:::

::: reference
### `isEmpty()`

**Returns:** `bool`

Returns whether the list has no items.
:::

::: reference
### `getAddItemUrl()`

**Arguments:** `element` (`craft\base\ElementInterface`), `params` (`array`, optional, default `[]`) · **Returns:** `string`

Returns the URL that adds the given element to a list of this list’s type.
:::

::: reference
### `getToggleItemUrl()`

**Arguments:** `element` (`craft\base\ElementInterface`), `params` (`array`, optional, default `[]`) · **Returns:** `string`

Returns the URL that adds the given element to a list of this list’s type, or removes it when it’s already there.
:::

::: reference
### `getRemoveItemUrl()`

**Arguments:** `element` (`craft\base\ElementInterface`), `params` (`array`, optional, default `[]`) · **Returns:** `string`

Returns the URL that removes the given element from a list of this list’s type.
:::

::: reference
### `getPdfUrl()`

**Returns:** `string`

Returns the URL to download the list as a PDF, as [PDF Template](docs:template-guides/pdf-template) describes.
:::

::: reference
### `getAddToCartUrl()`

**Returns:** `string`

Returns the URL of the action that adds the list’s items to the Craft Commerce cart, as [Add to Cart](docs:template-guides/add-to-cart) describes.
:::
