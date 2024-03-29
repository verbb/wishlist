# Managing Items
You can Add, Remove, Update or Toggle items in any list. You have the option of either using a `<form>` element or simply via a URL, depending on your templating needs.

## Add Item

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="text" name="elementId" value="{{ entry.id }}">

        {# Optional: Pass content for custom fields #}
        <input type="text" name="fields[myField]" value="My Value">

        {# Optional: Pass specific list type handle #}
        <input type="text" name="listTypeHandle" value="favourites">

        <input type="submit" value="Add to List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    <a href="{{ item.addUrl() }}">Add to List</a>

    {# Optional: Pass content for custom fields #}
    <a href="{{ item.addUrl({ fields: { myField: 'My Value' } }) }}">Add to List</a>

    {# Optional: Pass specific list type handle #}
    <a href="{{ item.addUrl({ listTypeHandle: 'favourites' }) }}">Add to List</a>
{% endfor %}
```
:::


## Remove Item

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


## Toggle Item

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

## Update Item

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/update">
        {{ csrfInput() }}

        {% set item = craft.wishlist.item(324) %}

        {% if item %}
            <input type="hidden" name="itemId" value="{{ item.id }}">
        {% endif %}

        <input type="text" name="fields[plainText]" value="Updated Value">

        <input type="submit" value="Update">
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

Any of the above actions will be made on the users' default list. You can also target a specific list, by using its ID.

::: code
```twig Form
<input type="text" name="listId" value="1234">
```

```twig URL
<a href="{{ item.addUrl({ listId: 1234 }) }}">Add to List</a>
```
:::

## List Types
The above actions will all be actioned on the default list. It's common to specify another list type to manage items on. You might have a list type called 'Favourites', which you want to add/delete/toggle on.

To make use of this, you need to supply either the `listTypeHandle` or `listTypeId` in your actions.

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="text" name="elementId" value="{{ entry.id }}">
        <input type="text" name="listTypeHandle" value="favourites">

        <input type="submit" value="Add to Favourites">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    <a href="{{ item.addUrl({ listTypeHandle: 'favourites' }) }}">Add to Favourites</a>
{% endfor %}
```
:::

## Multiple Items
You can also manage multiple items at a time, for example, adding multiple items:

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/items/add">
    {{ csrfInput() }}

    {% for entry in craft.entries.section('news').all() %}
        <input type="text" name="items[{{ loop.index }}][elementId]" value="{{ entry.id }}">

        {# Optional: Pass content for custom fields #}
        <input type="text" name="items[{{ loop.index }}][fields][myField]" value="My Value">
    {% endfor %}

    <input type="submit" value="Add to List">
</form>
```

## Check if in List
You can also check to see if an item is already in the list, which is useful for changing the layout based on that fact.

```twig
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id) %}

    {% if item.getInList() %}
        <a href="{{ item.removeUrl() }}">Remove from List</a>
    {% else %}
        <a href="{{ item.addUrl() }}">Add to List</a>
    {% endif %}
{% endfor %}
```

You can also check if an item is in a list that isn't the default one. For instance, checking if the item is in your `Favourites` list.

```twig
{% for entry in craft.entries.section('news').all() %}
    {% set item = craft.wishlist.item(entry.id, null, 'favourites') %}

    {% if item.getInList() %}
        <a href="{{ item.removeUrl() }}">Remove from List</a>
    {% else %}
        <a href="{{ item.addUrl() }}">Add to List</a>
    {% endif %}
{% endfor %}
```

## Managing All List Items
You can also manage the list items in a provided list. The example below shows a "cart-like" experience, where you can manage the entire list of items in one go. Here, we can remove each item individually, but update all items in the list with a single button.

 ```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/update-items">
    {{ csrfInput() }}

    <input type="hidden" name="listId" value="{{ list.id }}">

    {% for item in list.items.all() %}
        <input type="checkbox" name="items[{{ item.id }}][remove]" value="1"> Remove Item

        {# Optional: Pass content for custom fields #}
        <input type="text" name="items[{{ item.id }}][fields][myField]" value="{{ item.myField }}">
    {% endfor %}

    <input type="submit" value="Update List">
</form>
```

## Item Options
You can also store additional, arbitrary content alongside a Wishlist item in the form of item options. This content won't be visible to users, unless you decide to output it. It will be visible in the control panel, when editing an item.

 ```twig
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="text" name="elementId" value="{{ entry.id }}">

        {# Store a custom field from the entry into our item #}
        <input type="text" name="options[myCustomField]" value="{{ entry.myCustomField }}">

        {# Store an arbitrary value #}
        <input type="text" name="options[someOption]" value="Some Value">

        <input type="submit" value="Add to List">
    </form>
{% endfor %}
```

## Submit with JavaScript (Ajax)
You can also trigger any of the above actions through JavaScript.

:::code
```js JavaScript
let $form = document.querySelector('#my-wishlist-form');
let data = new FormData($form);

fetch('/', {
    method: 'post',
    body: data,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
}).then(function(response) {
    response.json().then(function(data) {
        console.log(data);
    });
});
```

```js jQuery
let data = $('#my-wishlist-form').serialize();

$.ajax({
    type: 'POST',
    url: '/',
    data: data,
    cache: false,
    dataType: 'json',
    error: function(jqXHR, textStatus, errorThrown) {
        console.log(jqXHR)
    },
    success: function(response) {
        console.log(response);
    }
});
```
:::

If you're unable to use `FormData` or `serialize()`, or you're constructing the payload for the action yourself, you'll need to provide the CSRF token and the action to perform in your payload.

```twig
<script type="text/javascript">
    window.csrfTokenName = "{{ craft.app.config.general.csrfTokenName|e('js') }}";
    window.csrfTokenValue = "{{ craft.app.request.csrfToken|e('js') }}";
</script>
```

```js
let data = {
    action: 'wishlist/items/add',
    elementId: 1234,
    fields: {
        myField: 'My Value',
    },
};

// Add the CSRF Token
data[csrfTokenName] = csrfTokenValue;
```

## Message Flash
When performing any of the above actions a "Flash message" will appear based on whether the task you complete was successful or failed. You can modify these messages for your own needs by providing extra params in your request.

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/items/add">
    <input type="hidden" name="successMessage" value="{{ 'Successfully added!' | hash }}">
    <input type="hidden" name="failMessage" value="{{ 'Unable to add to your list' | hash }}">
    {{ csrfInput() }}

    <input type="text" name="elementId" value="{{ entry.id }}">

    <input type="submit" value="Add to List">
</form>
```

The above shows by providing a `successMessage` or `failMessage` param in your request, you can set the flash message that appears when this form is submitted.
