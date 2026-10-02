# Adding and Removing Wishlist Items

Build controls around the element the visitor is viewing. Add and remove perform explicit actions; toggle switches its membership. Use the URL helpers for the current visitor’s default list.

## Calls Used in This Task

### `craft.wishlist.getAddItemUrl(element, params)`
Returns a URL to add a given element to the default wishlist.

### `craft.wishlist.getToggleItemUrl(element, params)`
Returns a URL to toggle a given element to the default wishlist.

### `craft.wishlist.getRemoveItemUrl(element, params)`
Returns a URL to remove a given element to the default wishlist.

## Add an Entry to the Default List

In a Twig template for an entry, use the add URL for that entry:

```twig
<a href="{{ craft.wishlist.getAddItemUrl(entry) }}">Save to My Wishlist</a>
```

This example uses the current visitor's default list. To choose a particular list type or submit custom item fields, use the complete [Managing Items](docs:template-guides/managing-items) form examples. Follow the add action, then [display the list's items](docs:template-guides/getting-list-items) and confirm the entry is present. Remove it and check the list again.

Test both a guest browser and a signed-in account if your site supports both. A guest's list belongs to their visitor session; it is not a public list shared by everyone. If you want to publish a shareable list, use the explicit [sharing controls](docs:template-guides/sharing-lists).

Front-end item actions accept up to 50 item targets per request. Guest visitors can create up to 100 items per 15 minutes and retain up to 500 active items. Guest creation limits use both the current client address and guest identity; multi-node deployments must use shared persistent cache and mutex implementations so rate limits apply across every node.
