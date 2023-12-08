# Getting List Items
No doubt you'll want to display the contents of a user's list at some point. To do this, you'll need to first fetch the list first, which will be for the current user, and then loop through the contained items.

## Single List
If you're after just a single list, the `getUserList()` method will return the default list for the current user. This includes whether the user is logged in, or a guest. `getUserList()` will always return a List element, even if the user hasn't added any items to their list, as it will be their empty, but default list.

```twig
{% set list = craft.wishlist.getUserList() %}

{% if list.items %}
    <ul>
        {% for item in list.items.all() %}
            <li>{{ item.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

You can also pass in a specific list type:

```twig
{% set list = craft.wishlist.getUserList({ listType: 'favourite' }) %}

{% if list.items %}
    <ul>
        {% for item in list.items.all() %}
            <li>{{ item.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

## Multiple Lists
As Wishlist can support multiple lists, and multiple list types, you can also loop through those multiple lists. You can do this with a more general query for lists with `craft.wishlist.lists()`.

```twig
{% for list in craft.wishlist.lists().all() %}
    <p>#{{ list.id }} - {{ list.title }} - {{ list.type }}</p>

    <ul>
        {% for item in list.items.all() %}
            <li>{{ item.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

Note that by default, this will still only return lists for the current user. You can change this be passing `false` into `lists()`.

```twig
{% for list in craft.wishlist.lists(false).all() %}
    <p>#{{ list.id }} - {{ list.title }} - {{ list.type }} - {{ list.user }}</p>

    <ul>
        {% for item in list.items.all() %}
            <li>{{ item.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```
