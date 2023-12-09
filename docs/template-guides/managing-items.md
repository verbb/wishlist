# Managing Items
You can Add, Remove, Update or Toggle items in any list. You have the option of either using a `<form>` element or simply via a URL, depending on your templating needs.

The following examples demonstrate a common use-case for Wishlist, where you'll loop through a collection of entries (`news` entries), and adding buttons to add the entry to your wishlist, toggle it, or remove it.

## Add Item

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="hidden" name="elementId" value="{{ entry.id }}">
        <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">

        {# Optional: Pass content for custom fields #}
        <input type="hidden" name="fields[myField]" value="My Value">

        {# Optional: Pass specific list type handle #}
        <input type="hidden" name="listType" value="favourites">

        <input type="submit" value="Add to List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    <a href="{{ craft.wishlist.addItemUrl(entry) }}">Add to List</a>

    {# Optional: Pass content for custom fields #}
    <a href="{{ craft.wishlist.addItemUrl(entry, { fields: { myField: 'My Value' } }) }}">Add to List</a>

    {# Optional: Pass specific list type handle #}
    <a href="{{ craft.wishlist.addItemUrl(entry, { listType: 'favourites' }) }}">Add to List</a>
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

        <input type="hidden" name="elementId" value="{{ entry.id }}">
        <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">

        <input type="submit" value="Remove from List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    <a href="{{ craft.wishlist.removeItemUrl(entry) }}">Remove from List</a>
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

        <input type="hidden" name="elementId" value="{{ entry.id }}">
        <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">

        <input type="submit" value="Toggle in List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    <a href="{{ craft.wishlist.toggleItemUrl(entry) }}">Toggle</a>
{% endfor %}
```
:::

## Update Item

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/items/update">
    {{ csrfInput() }}

    {# Get a specific item #}
    {% set item = craft.wishlist.items().id(123).one() %}

    {% if item %}
        <input type="hidden" name="itemId" value="{{ item.id }}">
    {% endif %}

    <input type="hidden" name="fields[plainText]" value="Updated Value">

    <input type="submit" value="Update">
</form>
```

Any of the above actions will be made on the user's default list. If the list doesn't already exist, Wishlist will create it. You can also target a specific list, by using its ID.

::: code
```twig Form
<input type="hidden" name="listId" value="1234">
```

```twig URL
<a href="{{ craft.wishlist.addItemUrl(entry, { listId: 1234 }) }}">Add to List</a>
```
:::

## List Types
The above actions will all be actioned on the default list. It's common to specify another list type to manage items on. You might have a list type called 'Favourites', which you want to add/delete/toggle on.

To make use of this, you need to supply the `listType` in your actions.

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="hidden" name="elementId" value="{{ entry.id }}">
        <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">
        <input type="hidden" name="listType" value="favourites">

        <input type="submit" value="Add to Favourites">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    <a href="{{ craft.wishlist.addItemUrl(entry, { listType: 'favourites' }) }}">Add to Favourites</a>
{% endfor %}
```
:::

## New Lists
As Wishlist supports having multiple lists and multiple list types, when adding or toggling items for a given list type, the assumption is to deal with a single list of that type. For example, your default list type might be "Wishlist", and you have two more types called "Favourites" and "Public".

When adding an element to the list type of your choice, Wishlist will create the list if it doesn't exist, or fetch that list and add the item to that list.

There are cases where you may want a new list created when adding an item. You could have multiple "Favourites" lists, each with their own purpose. You can pass in `newList` as a parameter to force a new list to be created, rather than just using the first available one for the user.

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="hidden" name="elementId" value="{{ entry.id }}">
        <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">
        <input type="hidden" name="listType" value="favourites">
        <input type="hidden" name="newList" value="1">

        <input type="submit" value="Add to Favourites">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    <a href="{{ craft.wishlist.addItemUrl(entry, { listType: 'favourites', newList: true }) }}">Add to Favourites</a>
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
        <input type="hidden" name="items[{{ loop.index0 }}][elementId]" value="{{ entry.id }}">
        <input type="hidden" name="items[{{ loop.index0 }}][elementSiteId]" value="{{ entry.siteId }}">

        {# Optional: Pass content for custom fields #}
        <input type="hidden" name="items[{{ loop.index0 }}][fields][myField]" value="My Value">
    {% endfor %}

    <input type="submit" value="Add to List">
</form>
```

## Check if in List
You can also check to see if an item is already in the list, which is useful for changing the layout based on that fact.

```twig
{# Get the default list for the user #}
{% set list = craft.wishlist.getUserList() %}

{% for entry in craft.entries.section('news').all() %}
    {% if list.getItem(entry) %}
        <a href="{{ craft.wishlist.removeItemUrl(entry) }}">Remove from List</a>
    {% else %}
        <a href="{{ craft.wishlist.addItemUrl(entry) }}">Add to List</a>
    {% endif %}
{% endfor %}
```

You can also check if an item is in a list that isn't the default one. For instance, checking if the item is in your `Favourites` list.

```twig
{# Get the Favourites list for the user #}
{% set list = craft.wishlist.getUserList({ listType: 'favourites' }) %}

{% for entry in craft.entries.section('news').all() %}
    {% if list.getItem(entry) %}
        <a href="{{ craft.wishlist.removeItemUrl(entry) }}">Remove from List</a>
    {% else %}
        <a href="{{ craft.wishlist.addItemUrl(entry) }}">Add to List</a>
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
        <input type="hidden" name="items[{{ item.id }}][fields][myField]" value="{{ item.myField }}">
    {% endfor %}

    <input type="submit" value="Update List">
</form>
```

## Bulk Actions
For any actions (add, remove, toggle, update) you can also action these for multiple lists, by providing a `listId[]` parameter for all the lists you'd like to action with.

For example, you might like to add a single element to all the lists a user has.

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/items/add">
    {{ csrfInput() }}

    {# Fetch all the lists for the user #}
    {% for list in craft.wishlist.lists() %}
        <input type="hidden" name="listId[]" value="{{ list.id }}">
    {% endfor %}

    <input type="hidden" name="elementId" value="{{ entry.id }}">

    <input type="submit" value="Bulk Add">
</form>
```

Similarly, you can follow the same structure for other actions like remove/toggle/update.

## Item Options
You can also store additional, arbitrary content alongside a Wishlist item in the form of item options. This content won't be visible to users, unless you decide to output it. It will be visible in the control panel, when editing an item.

::: code
```twig Form
{% for entry in craft.entries.section('news').all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/items/add">
        {{ csrfInput() }}

        <input type="hidden" name="elementId" value="{{ entry.id }}">
        <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">

        {# Store a custom field from the entry into our item #}
        <input type="hidden" name="options[myCustomField]" value="{{ entry.myCustomField }}">

        {# Store an arbitrary value #}
        <input type="hidden" name="options[someOption]" value="Some Value">

        <input type="submit" value="Add to List">
    </form>
{% endfor %}
```

```twig URL
{% for entry in craft.entries.section('news').all() %}
    {% set options = { myCustomField: entry.myCustomField, someOption: 'Some Value' } %}
    
    <a href="{{ craft.wishlist.addItemUrl(entry, { options: options }) }}">Add to List</a>
{% endfor %}
```
:::

When using item options, you should always ensure that you pass in the same options when managing items. For example, the above shows adding a news entry to your list, with the provided options. We should check to see if that entry with the options set exists, and remove it with the same options.

```twig
{# Get the default list for the user #}
{% set list = craft.wishlist.getUserList() %}

{% for entry in craft.entries.section('news').all() %}
    {% set options = { myCustomField: entry.myCustomField, someOption: 'Some Value' } %}

    {% if list.getItem(entry, { options: options }) %}
        <a href="{{ craft.wishlist.removeItemUrl(entry, { options: options }) }}">Remove from List</a>
    {% endif %}
{% endfor %}
```

Without this, Wishlist would find _any_ entry in the list, and would remove the first occurence of that entry in the list, which is likely undesired behaviour.

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
    elementSiteId: 1,
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

    <input type="hidden" name="elementId" value="{{ entry.id }}">
    <input type="hidden" name="elementSiteId" value="{{ entry.siteId }}">

    <input type="submit" value="Add to List">
</form>
```

The above shows by providing a `successMessage` or `failMessage` param in your request, you can set the flash message that appears when this form is submitted.
