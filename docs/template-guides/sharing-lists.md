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

## Emailing

You can also email your lists directly via a form.

```twig
<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/share-by-email">
    {{ csrfInput() }}

    <input type="text" name="listId" value="{{ list.id }}">

    <input type="text" name="sender[firstName]" value="Sender">
    <input type="text" name="sender[lastName]" value="Person">
    <input type="text" name="sender[email]" value="sender@gmail.com">

    <input type="text" name="recipient[firstName]" value="Recipient">
    <input type="text" name="recipient[lastName]" value="Person">
    <input type="text" name="recipient[email]" value="recipient@gmail.com">

    <input type="submit" value="Send Email">
</form>
```

Please note that the sender and recipient details are required, including a first name, last name and email. This is not only generally a good idea to provide for both the sender and the recipient for trust, but required to send an email. This will also help your emails look less 'spammy' if the recipient recognises the person sending the email.

If the sender is a logged in user, you can of course set these inputs to hidden, and populate with the `currentUser` content from Craft.

```twig
<input type="text" name="sender[firstName]" value="{{ currentUser.firstName }}">
<input type="text" name="sender[lastName]" value="{{ currentUser.lastName }}">
<input type="text" name="sender[email]" value="{{ currentUser.email }}">
```

You can customise the content of this email via the control panel by going to **Utilities** → **System Messages**.

You can also provide any additional variables you want to access in your email templates using `fields`.

```twig
<input type="text" name="fields[personalMessage]" value="Check these out!">
```

And if your email templates, you could use:

```twig
{{ fields.personalMessage }}
```
