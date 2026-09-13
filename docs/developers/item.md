# Item
Whenever you're dealing with a list item in your template, you're actually working with a `Item` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

ID of the item.
:::

::: reference
### `elementId`

**Type:** `int|null`

ID of the linked element.
:::

::: reference
### `elementSiteId`

**Type:** `int|null`

Site ID of the linked element.
:::

::: reference
### `elementDisplay`

**Type:** `string|null`

Display name of the linked element. ie `\craft\elements\Entry` would be `Entry`.
:::

::: reference
### `element`

**Type:** `craft\base\ElementInterface|null`

The linked element.
:::

::: reference
### `title`

**Type:** `string|null`

The Title of the linked element.
:::

::: reference
### `listId`

**Type:** `int|null`

ID of the list this item belongs to.
:::

::: reference
### `list`

**Type:** `verbb\wishlist\elements\ListElement|null`

The list this item belongs to.
:::

::: reference
### `options`

**Type:** `array`

Any additional options to store with an item.
:::

::: reference
### `optionsSignature`

**Type:** `string`

An MD5 hash of the options, used for comparing items uniqueness.
:::


## Methods

::: reference
### `getAddUrl(params)`

**Returns:** `string|null`

Returns the URL to add an item to a list.
:::

::: reference
### `getRemoveUrl(params)`

**Returns:** `string|null`

Returns the URL to remove an item from a list.
:::

::: reference
### `getToggleUrl(params)`

**Returns:** `string|null`

Returns the URL to toggle an item in a list.
:::

::: reference
### `getInList(list)`

**Returns:** `bool`

Whether this item is in the list (default list or otherwise).
:::
