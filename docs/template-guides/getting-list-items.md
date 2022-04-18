# Getting List Items
No doubt you'll want to display the contents of a user's list at some point. To do this, you'll need to first fetch the list first, which will be for the current user, and then loop through the contained items.

Be sure to check if the list exists first. Lists are only created when you add something to it for the first time.

```twig
{# Get the list #}
{% set list = craft.wishlist.lists().default(true).one() %}

{# Display list items #}
{% if list %}
    <ul>
        {% for item in list.items.all() %}
            <li>{{ item.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

You could also display items for different list types, rather than just the default list.

```twig
{# Get the list #}
{% set list = craft.wishlist.lists().type('favourite').one() %}

{# Display list items #}
{% if list %}
    <ul>
        {% for item in list.items.all() %}
            <li>{{ item.title }}</li>
        {% endfor %}
    </ul>
{% endif %}
```

What you **don't** want to do is fetch just the items, as this will return all the items across your site for all users, as below

```twig
{# Again - Don't do this! #}
{% set items = craft.wishlist.items().all() %}

<ul>
    {% for item in items %}
        <li>{{ item.title }}</li>
    {% endfor %}
</ul>
```