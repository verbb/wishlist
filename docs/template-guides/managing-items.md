# Managing Items

You can Add, Remove or Toggle items in any list. You have the option of either using a `<form>` element or simply via a URL, depending on your templating needs.

### Add Item

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="text" name="elementId" value="{{ entry.id }}">
        <input type="text" name="fields[myField]" value="My Value">

        <input type="submit" value="Add to List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    <a href="{{ item.addUrl() }}&fields[myField]=My Value">Add to List</a>
{% endfor %}
```
:::


### Remove Item

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/remove">
        {{ csrfInput() }}

        <input type="text" name="elementId" value="{{ entry.id }}">

        <input type="submit" value="Remove from List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    <a href="{{ item.removeUrl() }}">Remove from List</a>
{% endfor %}
```
:::


### Toggle Item

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/toggle">
        {{ csrfInput() }}

        <input type="text" name="elementId" value="{{ entry.id }}">

        <input type="submit" value="Toggle in List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    <a href="{{ item.toggleUrl() }}">Toggle</a>
{% endfor %}
```
:::

Any of the above actions will by made on the users' default list. You can also target a specific list, by using its ID.

::: code
```twig Form
<input type="text" name="listId" value="1234">
```

```twig URL
<a href="{{ item.addUrl(entry.id) }}&listId=1234">Add to List</a>
```
:::


## Check if in List

You can also check to see if an item is already in the list, which is useful for changing the layout based on that fact.

```twig
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    {% if item.inList %}
        <a href="{{ item.removeUrl() }}">Remove from List</a>
    {% else %}
        <a href="{{ item.addUrl() }}">Add to List</a>
    {% endif %}
{% endfor %}
```

