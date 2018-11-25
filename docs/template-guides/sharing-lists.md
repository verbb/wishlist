# Sharing Lists

A common scenario is to share a users wishlist with another user, often a guest or third-party who doesn't have an account. You can do this by using the `reference` attribute on a list, which makes it easy to generate unique URLs.

:::tip
How your sharing implemention works is up to you – Wishlist doesn't force you into a particular scenario. As such, the below are example templates that you'll need to implement.
:::

First, you'll want the owner of the list to be able to copy a URL to their list.

```twig
{% for list in craft.wishlist.lists().all() %}
    <h3>{{ list.title }}</h3>

    Share this list: https://example-site.com/wishlist?id={{ list.reference }}
{% endfor %}
```

In order to actually show the list to the public, you'll need to create a template called `wishlist` in your templates directory. Note you can call this what you want, just make it match the URL above.

Then, its just a matter of querying items based on the provided reference.

```twig
{% set reference = craft.app.request.getParam('id') %}

{% if reference %}
    {% set list = craft.wishlist.lists(false).reference(reference).one() %}

    {% if list %}
        <h3>{{ list.title }}</h3>

        <ul>
            {% for item in list.items %}
                <li>{{ item.title }}</li>
            {% endfor %}
        </ul>
    {% endif %}
{% endif %}
```

Remember to use `craft.wishlist.lists(false)` to fetch lists that don't belong to the current user, which in this case will be the new user viewing the list. Be careful with this.