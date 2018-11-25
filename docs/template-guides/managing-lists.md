# Managing Lists

You can Add, Delete or Clear lists, and its recommended that these options should only be available to registered users to prevent abuse.

### Add List

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/create">
    {{ csrfInput() }}

    <input type="text" name="title" value="Favourites">
    <input type="text" name="fields[myField]" value="My Value">

    <input type="submit" value="Create New List">
</form>
```

### Delete List

```twig
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

### Clear List

```twig
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
