# Displaying a User Wishlist

Start with the current user’s list. Keep ownership filtering enabled for customer-facing pages, because a query across all users belongs only in an appropriately authorised context.

The current user is whoever is viewing the page, whether they're signed in or a guest. Guest lists belong to the visitor's session. This example belongs on a "My Wishlist" page and lists each saved element with a link to it.

```twig
{% set list = craft.wishlist.getUserList() %}
{% set items = list.getItems() %}

{% if items %}
    <ul>
        {% for item in items %}
            {% set element = item.getElement() %}

            {% if element %}
                <li><a href="{{ element.url }}">{{ element.title }}</a></li>
            {% endif %}
        {% endfor %}
    </ul>
{% else %}
    <p>Your wishlist is empty.</p>
{% endif %}
```

[Getting List Items](docs:template-guides/getting-list-items) shows more ways to output a list, and [Adding and Removing Wishlist Items](docs:template-guides/adding-and-removing-wishlist-items) covers the buttons that fill it.

## Getting the Default List

`craft.wishlist.getUserList(params)` returns the current user's default list. A list is always returned, even when the user hasn't added anything yet. In that case it's a new, unsaved list with no items, which Wishlist saves the first time the user adds an item.

`params` is an optional hash of [list query parameters](docs:getting-elements/list-queries). Pass `listType` with a list type's handle to get the user's list of that type instead of the default list type.

```twig
{% set favourites = craft.wishlist.getUserList({ listType: 'favourites' }) %}
```

## Listing All of a User's Lists

`craft.wishlist.lists(forUser = true)` returns a [list query](docs:getting-elements/list-queries). By default, `forUser` is set to true, ensuring only lists and items that are owned by the current user are shown. Setting this to false will fetch items for any user, so be careful.

Use it when a user can have several lists, such as one per list type or lists they've created themselves.

```twig
{% for list in craft.wishlist.lists().all() %}
    <h2>{{ list.title }}</h2>
    <p>{{ list.getItems()|length }} items</p>
{% endfor %}
```

Only use `craft.wishlist.lists(false)` in templates restricted to administrators or staff, such as a report of every list.

## Querying Items

`craft.wishlist.items()` returns an [item query](docs:getting-elements/item-queries). It takes no arguments.

Unlike `craft.wishlist.lists()`, this query isn't limited to the current user, so it returns items from every list unless you filter it. Use `listId` to limit it to one of the user's lists.

```twig
{% set list = craft.wishlist.getUserList() %}

{% if list.id %}
    {% set recentItems = craft.wishlist.items()
        .listId(list.id)
        .orderBy('dateCreated desc')
        .limit(3)
        .all() %}
{% endif %}
```

The `list.id` check matters because a list the user hasn't saved yet has no ID, and an empty `listId` doesn't filter the query.

## Checking Whether an Element Is Saved

`craft.wishlist.getInUserLists(element)` returns `true/false` whether a provided `element` exists in any lists for the current user (guest, or logged-in user). This can be useful if you have multiple list types, but want to denote if an element exists in _any_ list of the user. `element` is required.

```twig
{% for entry in craft.entries.section('products').all() %}
    <h2>{{ entry.title }}</h2>

    {% if craft.wishlist.getInUserLists(entry) %}
        <p>In your wishlist</p>
    {% endif %}
{% endfor %}
```
