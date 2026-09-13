# List
Whenever you're dealing with a list in your template, you're actually working with a `List` object.

<span id="attributes"></span>

## Properties

::: reference
### `id`

**Type:** `int|null`

ID of the list.
:::

::: reference
### `reference`

**Type:** `string|null`

A unique identifier for this list, often used in sharing.
:::

::: reference
### `typeId`

**Type:** `int|null`

The List Type ID.
:::

::: reference
### `userId`

**Type:** `int|null`

If logged in, this will be the user ID of the owner for this list.
:::

::: reference
### `sessionId`

**Type:** `string|null`

If a guest, this will contain the unique session ID used to identify this guest.
:::

::: reference
### `default`

**Type:** `bool|null`

Whether this list is marked as the default list for users.
:::

::: reference
### `title`

**Type:** `string|null`

The title of this list.
:::

::: reference
### `lastIp`

**Type:** `string|null`

A record of the last known IP for the guest or user of this list.
:::


## Methods

::: reference
### `getItems()`

**Returns:** `array`

Returns an [Item](docs:getting-elements/item-queries) query.
:::

::: reference
### `getItem(element, params)`

**Returns:** `verbb\wishlist\elements\Item|null`

Returns an [Item](docs:developers/item) object for the given element, and query params.
:::

::: reference
### `hasItem(item)`

Whether the provided item is in this list.
:::

::: reference
### `getAddItemUrl(element, params)`

**Returns:** `string`

Returns the URL to add an item to the list.
:::

::: reference
### `getToggleItemUrl(element, params)`

**Returns:** `string`

Returns the URL to remove an item from the list.
:::

::: reference
### `getRemoveItemUrl(element, params)`

**Returns:** `string`

Returns the URL to toggle an item in the list.
:::
