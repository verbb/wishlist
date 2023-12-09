# Managing Lists
You can Add, Delete or Clear lists, and its recommended that these options should only be available to registered users to prevent abuse.

## Add List

::: code
```twig Form
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/create">
    {{ csrfInput() }}

    {# Optional: Pass a custom title #}
    <input type="hidden" name="title" value="Favourites">

    {# Optional: Pass content for custom fields #}
    <input type="hidden" name="fields[myField]" value="My Value">

    {# Optional: Pass a specific list type ID #}
    <input type="hidden" name="typeId" value="2">

    <input type="submit" value="Create New List">
</form>
```

```twig URL
<a href="{{ actionUrl('wishlist/lists/create', { title: 'Favourites', fields: { myField: 'My Value' } }) }}">
    Create New List
</a>
```
:::

## Update List

::: code
```twig Form
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/update">
    {{ csrfInput() }}

    <input type="hidden" name="listId" value="234">

    {# Optional: Pass a custom title #}
    <input type="hidden" name="title" value="New Title">

    {# Optional: Pass a specific list type ID #}
    <input type="hidden" name="typeId" value="2">

    <input type="submit" value="Update">
</form>
```

```twig URL
<a href="{{ actionUrl('wishlist/lists/update', { title: 'New Title' }) }}">
    Update List
</a>
```
:::


## Delete List

::: code
```twig Form
{% for list in craft.wishlist.lists().all() %}
    <h3>{{ list.title }}</h3>

    <form method="POST">
        <input type="hidden" name="action" value="wishlist/lists/delete">
        {{ csrfInput() }}

        <input type="hidden" name="listId" value="{{ list.id }}">

        {# Optional: Pass a specific list type ID #}
        <input type="hidden" name="typeId" value="2">

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


## Clear List

::: code
```twig Form
{% for list in craft.wishlist.lists().all() %}
    <h3>{{ list.title }}</h3>

    <form method="POST">
        <input type="hidden" name="action" value="wishlist/lists/clear">
        {{ csrfInput() }}

        <input type="hidden" name="listId" value="{{ list.id }}">

        {# Optional: Pass a specific list type ID #}
        <input type="hidden" name="typeId" value="2">

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


## Duplicate List
You can also duplicate a list, including any list items. This can be useful when a list is shared by one user, and another user wants to duplicate it for their own. It will assign this duplicate list to the current user.

This requires either a `listId` or `reference` to the origin list to duplicate. It's also required to have a logged-in user, which it will assign the new list to. This is to protect against abuse with mass-duplication of lists anonymously.

```twig
{% for list in craft.wishlist.lists().all() %}
    <h3>{{ list.title }}</h3>

    <form method="POST">
        <input type="hidden" name="action" value="wishlist/lists/duplicate-list">
        {{ csrfInput() }}

        <input type="hidden" name="listId" value="{{ list.id }}">

        <input type="submit" value="Duplicate List">
    </form>
{% endfor %}
```


## Message Flash
When performing any of the above actions a "Flash message" will appear based on whether the task you complete was successful or failed. You can modify these messages for your own needs by providing extra params in your request.

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/update">
    <input type="hidden" name="successMessage" value="{{ 'Successfully updated!' | hash }}">
    <input type="hidden" name="failMessage" value="{{ 'Unable to update your list' | hash }}">
    {{ csrfInput() }}

    <input type="hidden" name="listId" value="234">

    <input type="submit" value="Update List">
</form>
```

The above shows by providing a `successMessage` or `failMessage` param in your request, you can set the flash message that appears when this form is submitted.