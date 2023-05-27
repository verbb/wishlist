# Available Variables
The following methods are available to call in your Twig templates:

### `craft.wishlist.lists(forUser = true, forceSave = false)`
See [List Queries](docs:getting-elements/list-queries). By default, `forUser` is set to true, ensuring only lists and items that are owned by the current user are shown. Setting this to false will fetch items for any user, so be careful. You can also force a new list to be created in case one hasn't already been created.

### `craft.wishlist.items()`
See [Item Queries](docs:getting-elements/item-queries).

### `craft.wishlist.item(elementId, listId = null, listType = null)`
Fetches the [Item](docs:developers/item) for a provided `elementId`, and if provided, the `listId`. If not provided, will look at the default list. You can also provide the handle of your `listType` to check against lists that may not be created yet.

### `craft.wishlist.getInUserLists(elementId)`
Returns `true/false` whether a provided `elementId` exists in any lists for the current user (guest, or logged-in user). This can be useful if you have multiple list types, but want to denote if an element exists in _any_ list of the user.