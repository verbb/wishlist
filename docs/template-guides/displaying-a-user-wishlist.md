# Displaying a User Wishlist

Start with the current user’s list. Keep ownership filtering enabled for customer-facing pages; a query across all users belongs only in an appropriately authorised context.

## Calls Used in This Task

### `craft.wishlist.getUserList(params)`
Returns the default list for the current user. The `params` can be a collection of [query params](docs:getting-elements/list-queries).

### `craft.wishlist.lists(forUser = true)`
See [List Queries](docs:getting-elements/list-queries). By default, `forUser` is set to true, ensuring only lists and items that are owned by the current user are shown. Setting this to false will fetch items for any user, so be careful.

### `craft.wishlist.items()`
See [Item Queries](docs:getting-elements/item-queries).

### `craft.wishlist.getInUserLists(element)`
Returns `true/false` whether a provided `element` exists in any lists for the current user (guest, or logged-in user). This can be useful if you have multiple list types, but want to denote if an element exists in _any_ list of the user.
