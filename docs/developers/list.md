# List
Whenever you're dealing with a list in your template, you're actually working with a `List` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the list.
`reference` | A unique identifier for this list, often used in sharing.
`typeId` | The List Type ID.
`userId` | If logged in, this will be the user ID of the owner for this list.
`sessionId` | If a guest, this will contain the unique session ID used to identify this guest.
`default` | Whether this list is marked as the default list for users.
`title` | The title of this list.
`lastIp` | A record of the last known IP for the guest or user of this list.

## Methods

Method | Description
--- | ---
`getItems()` | Returns an [Item](docs:getting-elements/item-queries) query.
`getItem(element, params)` | Returns an [Item](docs:developers/item) object for the given element, and query params.
`hasItem(item)` | Whether the provided item is in this list.
`getAddItemUrl(element, params)` | Returns the URL to add an item to the list.
`getToggleItemUrl(element, params)` | Returns the URL to remove an item from the list.
`getRemoveItemUrl(element, params)` | Returns the URL to toggle an item in the list.
