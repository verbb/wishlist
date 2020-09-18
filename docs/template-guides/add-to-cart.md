# Add to Cart

A great feature for a Commerce site is to allow your customers to create wishlist's of product they want to purchase, saved to their account for later purchase. To provide an even more seamless user experience would be to allow customers to add these wishlist items to their cart to purchase.

That's exactly what you can do with the following code.

First, you'll want to add some purchasables to your list - commonly Commerce variants, but custom purchasables are supported.

```twig
{% for product in craft.products().type('clothing').all() %}
    {% for variant in product.variants %}
        <form method="POST">
            <input type="hidden" name="action" value="wishlist/items/add">
            {{ csrfInput() }}

            <input type="text" name="elementId" value="{{ variant.id }}">

            <input type="submit" value="Add {{ variant.title }} to List">
        </form>
    {% endfor %}
{% endfor %}
```

Once variants are in your list, you can add all of them to your cart in a single request:

```twig
{% set list = craft.wishlist.lists().default(true).one() %}

<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/add-to-cart">
    {{ csrfInput() }}
    {{ redirectInput('/shop/cart') }}

    <input type="text" name="listId" value="{{ list.id }}">

    <input type="submit" value="Add to Cart">
</form>
```

This will look through any Purchasable objects in the list, and add it to your cart.

For even greater flexibility, you can include a few other useful bits of information, such as quantity or line item options. You might even have these saved as custom fields on the Item object.

```twig
{% set list = craft.wishlist.lists().default(true).one() %}

<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/add-to-cart">
    {{ csrfInput() }}
    {{ redirectInput('/shop/cart') }}

    <input type="text" name="listId" value="{{ list.id }}">

    {% for item in list.items.all() %}
        <input type="text" name="purchasables[{{ item.id }}][qty]" value="10">
        <input type="text" name="purchasables[{{ item.id }}][options][test]" value="Some Value">
    {% endfor %}

    <input type="submit" value="Add to Cart">
</form>
```

The above will add only the purchasables you supply to the cart. For example, you might want to allow adding individual items to your cart.

```twig
{% set list = craft.wishlist.lists().default(true).one() %}

{% for item in list.items.all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/lists/add-to-cart">
        {{ csrfInput() }}
        {{ redirectInput('/shop/cart') }}

        <input type="text" name="listId" value="{{ list.id }}">

        <input type="text" name="purchasables[{{ item.id }}][qty]" value="1">
        <input type="text" name="purchasables[{{ item.id }}][options][test]" value="Some Value">

        <input type="submit" value="Add to Cart">
    </form>
{% endfor %}
```

If you had 5 items in your list, you'll have 5 `<form>` elements outputted. Each would have their own "Add to Cart" button.

### Removing from list
You can also set items to be removed from the list, once added to the cart. By default, items will remain in the users wishlist.

When adding the entire list to the cart, you can clear all items from the list.

```twig
{% set list = craft.wishlist.lists().default(true).one() %}

<form method="POST">
    <input type="hidden" name="action" value="wishlist/lists/add-to-cart">
    {{ csrfInput() }}
    {{ redirectInput('/shop/cart') }}

    <input type="text" name="listId" value="{{ list.id }}">
    <input type="checkbox" name="clearList" value="true" checked>

    <input type="submit" value="Add to Cart">
</form>
```

We're using a checkbox field to allow the user to opt-in or out of this behaviour, but you could also make this a hidden input.

Similarly, if you were adding items individually to the cart:

```twig
{% for item in list.items.all() %}
    <form method="POST">
        <input type="hidden" name="action" value="wishlist/lists/add-to-cart">
        {{ csrfInput() }}
        {{ redirectInput('/shop/cart') }}

        <input type="text" name="listId" value="{{ list.id }}">

        <input type="text" name="purchasables[{{ item.id }}][qty]" value="1">
        <input type="text" name="purchasables[{{ item.id }}][options][test]" value="Some Value {{ item.id }}">
        <input type="checkbox" name="purchasables[{{ item.id }}][removeFromList]" value="true" checked>

        <input type="submit" value="Add to Cart">
    </form>
{% endfor %}
```

The above would only add the chosen item to the cart, and also remove only the chosen it from the list.
