# Managing Lists

You can Add, Delete or Clear lists, and its recommended that these options should only be available to registered users to prevent abuse.

### Add List

::: code
```twig Form
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/create">
    {{ csrfInput() }}

    <input type="text" name="title" value="Favourites">
    <input type="text" name="fields[myField]" value="My Value">

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
    <input type="text" name="title" value="New Title">

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
