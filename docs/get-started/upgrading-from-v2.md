# Upgrading from v2
While the [changelog](https://github.com/verbb/wishlist/blob/craft-5/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Lists
Getting the default list for the current user has a new shortcut for clarity with `craft.wishlist.getUserList()`.

In addition, when calling `getUserList()`, you don't need to check if it returns a list, as it always will - even if it's the default one.

```twig
{# Wishlist v2 #}
{% set list = craft.wishlist.lists().default(true).one() %}

{% if list %}
    <ul>
        {% for item in list.items.all() %}
            <li>{{ item.title }}</li>
        {% endfor %}
    </ul>
{% endif %}

{# Wishlist v3 #}
{% set list = craft.wishlist.getUserList() %}

<ul>
    {% for item in list.items.all() %}
        <li>{{ item.title }}</li>
    {% endfor %}
</ul>
```

You can also pass in any [List](docs:getting-elements/list-queries) query params. For example, getting a list of a specific type, or by its ID.

```twig
{% set list = craft.wishlist.getUserList({ listType: 'favourites' }) %}
{% set list = craft.wishlist.getUserList({ id: 1234 }) %}
```

You can still use `craft.wishlist.lists()` to fetch lists for the current user, so `craft.wishlist.getUserList()` is mostly a shortcut that will always return a list element. If you continue to use the former, ensure you retain any checks that the `list` exists.

### In List
You can now easily determine if an element is in a list. Rather than fetching an item and using that, you can pass in the element itself, and any additional query params.

```twig
{# Wishlist v2 #}
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    {% if item.getInList() %}
        <a href="{{ item.removeUrl() }}">Remove from List</a>
    {% else %}
        <a href="{{ item.addUrl() }}">Add to List</a>
    {% endif %}
{% endfor %}

{# Wishlist v3 #}
{% set list = craft.wishlist.getUserList() %}

{% for entry in craft.entries.section('news').all() %}
    {% if list.getItem(entry) %}
        <a href="{{ list.removeItemUrl(entry) }}">Remove from List</a>
    {% else %}
        <a href="{{ list.addItemUrl(entry) }}">Add to List</a>
    {% endif %}
{% endfor %}
```

The main difference being that this requires getting the list for the user and checking against that. You can also pass in extra params, such as options:

```twig
{% set list = craft.wishlist.getUserList() %}

{% for entry in craft.entries.section('news').all() %}
    {% set options = { someValue: 'test' } %}

    {% if list.getItem(entry, { options: options }) %}
        <a href="{{ list.removeItemUrl(entry, { options: options }) }}">Remove from List</a>
    {% else %}
        <a href="{{ list.addItemUrl(entry, { options: options }) }}">Add to List</a>
    {% endif %}
{% endfor %}
```

Or, for a specific list type.

```twig
{% set list = craft.wishlist.getUserList({ listType: 'favourites' }) %}

{% for entry in craft.entries.section('news').all() %}
    {% if list.getItem(entry) %}
        <a href="{{ list.removeItemUrl(entry) }}">Remove from List</a>
    {% else %}
        <a href="{{ list.addItemUrl(entry) }}">Add to List</a>
    {% endif %}
{% endfor %}
```

### Force-Save
We've removed the `forceSave` option when calling `craft.wishlist.lists()` as it's no longer required. Lists are now created automatically as soon as you add or toggle an element to be included in the list of your choice. This improves performance by not having multiple lists created for users whenever a page renders.

## Items

### Add/Toggle/Remove
The URL helpers for links are now globally available. You'll also need to supply an element, not just an element's ID.

```twig
{# Wishlist v2 #}
{% set item = craft.wishlist.item(entry.id) %}

<a href="{{ item.addUrl() }}">Add to List</a>
<a href="{{ item.toggleUrl() }}">Toggle in List</a>
<a href="{{ item.removeUrl() }}">Remove from List</a>

{# Wishlist v3 #}
<a href="{{ craft.wishlist.addItemUrl(entry) }}">Add to List</a>
<a href="{{ craft.wishlist.toggleItemUrl(entry) }}">Toggle in List</a>
<a href="{{ craft.wishlist.removeItemUrl(entry) }}">Remove from List</a>
```

We've also renamed `listTypeHandle` and removed `listTypeId` when using the forms, or when adding as params to the URL helpers. This is now just `listType`.

```twig
{# Wishlist v2 #}
<form method="POST">
    <input type="hidden" name="action" value="wishlist/items/add">
    {{ csrfInput() }}

    <input type="hidden" name="elementId" value="{{ entry.id }}">
    <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">
    <input type="hidden" name="listTypeHandle" value="favourites">

    <input type="submit" value="Add to List">
</form>

{# Wishlist v3 #}
<form method="POST">
    <input type="hidden" name="action" value="wishlist/items/add">
    {{ csrfInput() }}

    <input type="hidden" name="elementId" value="{{ entry.id }}">
    <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">
    <input type="hidden" name="listType" value="favourites">

    <input type="submit" value="Add to List">
</form>
```

### Getting an Item
The `craft.wishlist.item()` function is now deprecated, in favour of two other methods to handle the same functionality.

If you wanted to fetch an item, you can query them via `craft.wishlist.items(params)`, or if in the context of a list `list.getItem(params)`.

```twig
{# Wishlist v2 #}
{% set item = craft.wishlist.item(elementId, listId, listTypeHandle, elementSiteId) %}

{# Wishlist v3 #}
{% set item = craft.wishlist.items()
    .listId(listId)
    .listTypeHandle(listTypeHandle)
    .elementId(elementId)
    .elementSiteId(elementSiteId)
    .one() %}
```

Querying is much more flexible, and you're not locked into function parameters.

The other common use-case for this was to add/remove/toggle elements added to the list. These can now be accessed globally, or in the context of a list.

- `craft.wishlist.addItemUrl(entry)`
- `craft.wishlist.toggleItemUrl(entry)`
- `craft.wishlist.removeItemUrl(entry)`
- `list.addItemUrl(entry)`
- `list.toggleItemUrl(entry)`
- `list.removeItemUrl(entry)`
