# Managing Lists

You can Add, Delete or Clear lists, and its recommended that these options should only be available to registered users to prevent abuse.

### Add List

::: code
```twig Form
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/create">
    {{ csrfInput() }}

    {# Optional: Pass a custom title #}
    <input type="text" name="title" value="Favourites">

    {# Optional: Pass content for custom fields #}
    <input type="text" name="fields[myField]" value="My Value">

    {# Optional: Pass a specific list type ID #}
    <input type="text" name="typeId" value="2">

    <input type="submit" value="Create New List">
</form>
```

```twig URL
<a href="{{ actionUrl('wishlist/lists/create', { title: 'Favourites', fields: { myField: 'My Value' } }) }}">
    Create New List
</a>
```
:::

### Update List

::: code
```twig Form
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/update">
    {{ csrfInput() }}

    <input type="hidden" name="listId" value="234">

    {# Optional: Pass a custom title #}
    <input type="text" name="title" value="New Title">

    {# Optional: Pass a specific list type ID #}
    <input type="text" name="typeId" value="2">

    <input type="submit" value="Update">
</form>
```

```twig URL
<a href="{{ actionUrl('wishlist/lists/update', { title: 'New Title' }) }}">
    Update List
</a>```
:::


### Delete List

::: code
```twig Form
{% for list in craft.wishlist.lists().all() %}
    <h3>{{ list.title }}</h3>

    <form method="POST">
        <input type="hidden" name="action" value="wishlist/lists/delete">
        {{ csrfInput() }}

        <input type="text" name="listId" value="{{ list.id }}">

        {# Optional: Pass a specific list type ID #}
        <input type="text" name="typeId" value="2">

        <input type="submit" value="Delete List">
    </form>
{% endfor %}
```

```twig URL
<a href="{{ actionUrl('wishlist/lists/delete', { listId: list.id }) }}">
    Delete List
</a>
```
:::


### Clear List

::: code
```twig Form
{% for list in craft.wishlist.lists().all() %}
    <h3>{{ list.title }}</h3>

    <form method="POST">
        <input type="hidden" name="action" value="wishlist/lists/clear">
        {{ csrfInput() }}

        <input type="text" name="listId" value="{{ list.id }}">

        {# Optional: Pass a specific list type ID #}
        <input type="text" name="typeId" value="2">

        <input type="submit" value="Clear List">
    </form>
{% endfor %}
```

```twig URL
<a href="{{ actionUrl('wishlist/lists/clear', { listId: list.id }) }}">
    Clear List
</a>
```
:::

```

## Message Flash
When performing any of the above actions a "Flash message" will appear based on whether the task you complete was successful or failed. You can modify these messages for your own needs by providing extra params in your request.

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/update">
    <input type="hidden" name="successMessage" value="Successfully updated!">
    <input type="hidden" name="failMessage" value="Unable to update your list">
    {{ csrfInput() }}

    <input type="hidden" name="listId" value="234">

    <input type="submit" value="Update List">
</form>
```

The above shows by providing a `successMessage` or `failMessage` param in your request, you can set the flash message that appears when this form is submitted.