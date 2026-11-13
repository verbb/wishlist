# Adding and Removing Wishlist Items

Build controls around the element the visitor is viewing. Add and remove perform explicit actions, while toggle switches its membership. Use the URL helpers for the current visitor’s default list.

## Add an Entry to the Default List

In a Twig template for an entry, use the add URL for that entry as the action of a small POST form:

```twig
<form method="post" action="{{ craft.wishlist.getAddItemUrl(entry) }}">
    {{ csrfInput() }}
    {{ redirectInput(entry.url) }}

    <button type="submit">Save to My Wishlist</button>
</form>
```

The URL carries the entry and any extra parameters, so the form only needs Craft's CSRF input and, optionally, a redirect. After the visitor submits it, they return to the entry and the item is in their default list. Wishlist creates the list on the first add if it doesn't exist yet.

You can also use the URL as a plain link while the [`allowGetListActions`](docs:get-started/configuration) setting is enabled, which it is by default. A POST form keeps working when that setting is turned off, and it's protected by Craft's CSRF check, so it's the better choice for new templates.

This example uses the current visitor's default list. To choose a particular list type or submit custom item fields, use the complete [Managing Items](docs:template-guides/managing-items) form examples. Follow the add action, then [display the list's items](docs:template-guides/getting-list-items) and confirm the entry is present. Remove it and check the list again.

Test both a guest browser and a signed-in account if your site supports both. A guest's list belongs to their visitor session, and it isn't a public list shared by everyone. If you want to publish a shareable list, use the explicit [sharing controls](docs:template-guides/sharing-lists).

Front-end item actions accept up to 50 item targets per request. Guest visitors can create up to 100 items per 15 minutes and retain up to 500 active items. Guest creation limits use both the current client address and guest identity, so multi-node deployments must use shared persistent cache and mutex implementations so rate limits apply across every node.

## Show a Save or Remove Button

A product or article page usually shows one button that changes with the item's state. Check whether the element is already in the visitor's lists with `craft.wishlist.getInUserLists()`, then offer the matching action.

```twig
{% set inWishlist = craft.wishlist.getInUserLists(entry) %}

<form method="post" action="{{ inWishlist ? craft.wishlist.getRemoveItemUrl(entry) : craft.wishlist.getAddItemUrl(entry) }}">
    {{ csrfInput() }}

    <button type="submit">{{ inWishlist ? 'Remove from Wishlist' : 'Save to Wishlist' }}</button>
</form>
```

If you don't need different labels, `craft.wishlist.getToggleItemUrl(entry)` does the same with a single URL.

## Building Item Action URLs

`craft.wishlist.getAddItemUrl(element, params)` returns the URL of the action that adds an element to the current visitor's default list. `element` is required and can be any element, such as an entry or a Commerce product. Use the URL as a POST form's `action`.

`params` is an optional hash of extra item settings, which is encoded into the URL. It accepts the same values as the [Managing Items](docs:template-guides/managing-items) forms, such as `listType` to add to the default list of a particular list type, `listId` to add to a specific list, and `fields` to set custom field values on the item.

```twig
<form method="post" action="{{ craft.wishlist.getAddItemUrl(entry, {
    listType: 'favourites',
    fields: {
        notes: 'Gift idea',
    },
}) }}">
    {{ csrfInput() }}

    <button type="submit">Add to Favourites</button>
</form>
```

`craft.wishlist.getToggleItemUrl(element, params)` returns the URL of the action that adds an element to the current visitor's default list, or removes it if it's already there. `element` is required, and `params` accepts the same values as `getAddItemUrl()`.

`craft.wishlist.getRemoveItemUrl(element, params)` returns the URL of the action that removes an element from the current visitor's default list. `element` is required, and `params` accepts the same values as `getAddItemUrl()`, so pass `listType` or `listId` to remove it from a different list.

## Checking Whether an Element Is Saved

`craft.wishlist.getInUserLists(element)` returns `true` when the element is in any of the current visitor's lists, whatever their list type, and `false` otherwise. `element` is required. It works for guests and signed-in users. See [Displaying a User Wishlist](docs:template-guides/displaying-a-user-wishlist) for more on the visitor's lists.
