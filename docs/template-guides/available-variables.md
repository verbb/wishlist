# Available Variables
The following methods are available to call in your Twig templates:

### `craft.wishlist.getPlugin()`
Returns an instance of the Wishlist plugin.

### `craft.wishlist.getUserList(params)`
Returns the default list for the current user. The `params` can be a collection of [query params](docs:getting-elements/list-queries).

### `craft.wishlist.lists(forUser = true)`
See [List Queries](docs:getting-elements/list-queries). By default, `forUser` is set to true, ensuring only lists and items that are owned by the current user are shown. Setting this to false will fetch items for any user, so be careful.

### `craft.wishlist.items()`
See [Item Queries](docs:getting-elements/item-queries).

### `craft.wishlist.getAddItemUrl(element, params)`
Returns a URL to add a given element to the default wishlist.

### `craft.wishlist.getToggleItemUrl(element, params)`
Returns a URL to toggle a given element to the default wishlist.

### `craft.wishlist.getRemoveItemUrl(element, params)`
Returns a URL to remove a given element to the default wishlist.

### `craft.wishlist.getInUserLists(element)`
Returns `true/false` whether a provided `element` exists in any lists for the current user (guest, or logged-in user). This can be useful if you have multiple list types, but want to denote if an element exists in _any_ list of the user.
