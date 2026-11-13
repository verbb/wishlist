# Accessing the Plugin

Most Twig tasks use the list, item and action helpers directly. Access the plugin instance only when a documented integration requires it, as it isn't necessary for ordinary wishlist rendering.

The plugin instance is useful when a template needs something the helpers don't provide, such as the list types you've set up or one of the plugin's settings. For example, a page where visitors choose which list to add an item to can loop through every list type.

```twig
{% set listTypes = craft.wishlist.getPlugin().getListTypes().getAllListTypes() %}

<form method="post" action="{{ actionUrl('wishlist/items/add') }}">
    {{ csrfInput() }}
    {{ hiddenInput('elementId', entry.id) }}
    {{ hiddenInput('elementSiteId', entry.siteId) }}

    <select name="listType">
        {% for listType in listTypes %}
            <option value="{{ listType.handle }}" {{ listType.default ? 'selected' }}>{{ listType.name }}</option>
        {% endfor %}
    </select>

    <button type="submit">Add to List</button>
</form>
```

Each list type has a `name`, a `handle` and a `default` flag. The visitor's choice adds the entry to their list of that type, as described in [Managing Items](docs:template-guides/managing-items).

## Reading Settings and List Types

`craft.wishlist.getPlugin()` returns the Wishlist plugin instance. It takes no arguments. From it, `getListTypes()` gives you the list type service, with methods such as `getAllListTypes()`, `getListTypeByHandle(handle)` and `getDefaultListType()`. `getSettings()` returns the plugin's [settings](docs:get-started/configuration), so you can check a value such as `allowDuplicates` before showing a control.

```twig
{% set settings = craft.wishlist.getPlugin().getSettings() %}

{% if not settings.allowDuplicates and craft.wishlist.getInUserLists(entry) %}
    <p>Already in your wishlist</p>
{% endif %}
```

Avoid calling the plugin's services to change lists or items from a template. Use the front-end actions in [Managing Items](docs:template-guides/managing-items) and [Managing Lists](docs:template-guides/managing-lists) instead, as they check the visitor's ownership and permissions.
