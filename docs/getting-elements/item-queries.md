# Item Queries

You can fetch items in your templates or PHP code using **item queries**.

::: code
```twig Twig
{# Create a new item query #}
{% set myQuery = craft.wishlist.items() %}
```

```php PHP
// Create a new item query
$myQuery = \verbb\wishlist\elements\Item::find();
```
:::

Once you’ve created an item query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Item](docs:reference/item) objects will be returned.

::: tip
See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn about how element queries work.
:::

## Example

We can display the items in the current visitor’s default list by doing the following:

1. Fetch the visitor’s default list with `craft.wishlist.getUserList()`.
2. Create an item query with `craft.wishlist.items()`.
3. Set the [listId](#listid) and [limit](#limit) parameters on it.
4. Fetch the items with `.all()`.
5. Loop through the items using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output the title of the element each item links to.

```twig
{# Get the current visitor’s default list #}
{% set list = craft.wishlist.getUserList() %}

{# A visitor with no saved list gets a new, unsaved one #}
{% if list.id %}
    {# Create an item query with the 'listId' and 'limit' parameters #}
    {% set itemsQuery = craft.wishlist.items()
        .listId(list.id)
        .limit(10) %}

    {# Fetch the items #}
    {% set items = itemsQuery.all() %}

    {# Display their contents #}
    {% for item in items %}
        <p>{{ item.elementTitle }}</p>
    {% endfor %}
{% endif %}
```

Unlike `craft.wishlist.lists()`, `craft.wishlist.items()` isn’t limited to the current visitor’s lists. Narrow it with [listId](#listid) or another parameter before showing the results on a public page, and check the list has an ID first, because `listId(null)` doesn’t filter the results.

## Parameters

Item queries support the following parameters.

::: reference
### `asArray`

Causes the query to return matching items as arrays of data, rather than [Item](docs:reference/item) objects.

::: code
```twig Twig
{# Fetch items as arrays #}
{% set items = craft.wishlist.items()
    .asArray()
    .all() %}
```

```php PHP
// Fetch items as arrays
$items = \verbb\wishlist\elements\Item::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the items’ creation dates.

| Value | Fetches items… |
| --- | --- |
| `'>= 2018-04-01'` | that were created on or after 2018-04-01. |
| `'< 2018-05-01'` | that were created before 2018-05-01. |
| `['and', '>= 2018-04-01', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01. |

::: code
```twig Twig
{# Fetch items created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set items = craft.wishlist.items()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch items created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$items = \verbb\wishlist\elements\Item::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the items’ last-updated dates.

| Value | Fetches items… |
| --- | --- |
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01. |
| `'< 2018-05-01'` | that were updated before 2018-05-01. |
| `['and', '>= 2018-04-01', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01. |

::: code
```twig Twig
{# Fetch items updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set items = craft.wishlist.items()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch items updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$items = \verbb\wishlist\elements\Item::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `elementClass`

Narrows the query results based on the class of the elements the items link to.

::: code
```twig Twig
{# Fetch items created for an element with the provided class #}
{% set items = craft.wishlist.items()
    .elementClass('craft\\elements\\Entry')
    .all() %}
```

```php PHP
// Fetch items created for an element with the provided class
$items = \verbb\wishlist\elements\Item::find()
    ->elementClass(\craft\elements\Entry::class)
    ->all();
```
:::
:::

::: reference
### `elementId`

Narrows the query results based on the IDs of the elements the items link to.

| Value | Fetches items… |
| --- | --- |
| `1` | created for an element with an ID of 1. |
| `'not 1'` | not created for an element with an ID of 1. |
| `[1, 2]` | created for an element with an ID of 1 or 2. |
| `['not', 1, 2]` | not created for an element with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch items created for an element with an ID of 1 #}
{% set items = craft.wishlist.items()
    .elementId(1)
    .all() %}
```

```php PHP
// Fetch items created for an element with an ID of 1
$items = \verbb\wishlist\elements\Item::find()
    ->elementId(1)
    ->all();
```
:::
:::

::: reference
### `elementSiteId`

Narrows the query results based on the site the linked element was saved for, per the site’s ID.

| Value | Fetches items… |
| --- | --- |
| `1` | created for an element in a site with an ID of 1. |

::: code
```twig Twig
{# Fetch items created for an element with an ID of 1, for a site with an ID of 2 #}
{% set items = craft.wishlist.items()
    .elementId(1)
    .elementSiteId(2)
    .all() %}
```

```php PHP
// Fetch items created for an element with an ID of 1, for a site with an ID of 2
$items = \verbb\wishlist\elements\Item::find()
    ->elementId(1)
    ->elementSiteId(2)
    ->all();
```
:::
:::

::: reference
### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch items in a specific order #}
{% set items = craft.wishlist.items()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch items in a specific order
$items = \verbb\wishlist\elements\Item::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the items’ IDs.

| Value | Fetches items… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the item by its ID #}
{% set item = craft.wishlist.items()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the item by its ID
$item = \verbb\wishlist\elements\Item::find()
    ->id(1)
    ->one();
```
:::

::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::
:::

::: reference
### `inReverse`

Causes the query results to be returned in reverse order.

::: code
```twig Twig
{# Fetch items in reverse #}
{% set items = craft.wishlist.items()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch items in reverse
$items = \verbb\wishlist\elements\Item::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of items that should be returned.

::: code
```twig Twig
{# Fetch up to 10 items  #}
{% set items = craft.wishlist.items()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 items
$items = \verbb\wishlist\elements\Item::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `listId`

Narrows the query results based on the IDs of the lists the items belong to.

| Value | Fetches items… |
| --- | --- |
| `1` | with a list ID of 1. |
| `'not 1'` | not with a list ID of 1. |
| `[1, 2]` | with a list ID of 1 or 2. |
| `['not', 1, 2]` | not with a list ID of 1 or 2. |

::: code
```twig Twig
{# Fetch items in the list with an ID of 1 #}
{% set items = craft.wishlist.items()
    .listId(1)
    .all() %}
```

```php PHP
// Fetch items in the list with an ID of 1
$items = \verbb\wishlist\elements\Item::find()
    ->listId(1)
    ->all();
```
:::
:::

::: reference
### `listTypeHandle`

Narrows the query results based on the handle of the list type the items’ lists use.

::: code
```twig Twig
{# Fetch items in lists of the Favourites list type #}
{% set items = craft.wishlist.items()
    .listTypeHandle('favourites')
    .all() %}
```

```php PHP
// Fetch items in lists of the Favourites list type
$items = \verbb\wishlist\elements\Item::find()
    ->listTypeHandle('favourites')
    ->all();
```
:::
:::

::: reference
### `listTypeId`

Narrows the query results based on the ID of the list type the items’ lists use.

| Value | Fetches items… |
| --- | --- |
| `1` | with a list type ID of 1. |
| `'not 1'` | not with a list type ID of 1. |
| `[1, 2]` | with a list type ID of 1 or 2. |
| `['not', 1, 2]` | not with a list type ID of 1 or 2. |

::: code
```twig Twig
{# Fetch items in lists of the list type with an ID of 1 #}
{% set items = craft.wishlist.items()
    .listTypeId(1)
    .all() %}
```

```php PHP
// Fetch items in lists of the list type with an ID of 1
$items = \verbb\wishlist\elements\Item::find()
    ->listTypeId(1)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many items should be skipped in the results.

::: code
```twig Twig
{# Fetch all items except for the first 3 #}
{% set items = craft.wishlist.items()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all items except for the first 3
$items = \verbb\wishlist\elements\Item::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `options`

Narrows the query results to items saved with the given options. Wishlist compares a signature of the options, so the keys can be in any order, but every key and value has to match. Use it to find the item for one variation of an element, such as a product in a particular size.

::: code
```twig Twig
{# Fetch the item for an element in size large #}
{% set item = craft.wishlist.items()
    .listId(list.id)
    .elementId(product.id)
    .options({ size: 'large' })
    .one() %}
```

```php PHP
// Fetch the item for an element in size large
$item = \verbb\wishlist\elements\Item::find()
    ->listId($list->id)
    ->elementId($product->id)
    ->options(['size' => 'large'])
    ->one();
```
:::
:::

::: reference
### `optionsSignature`

Narrows the query results based on the items’ options signature, an MD5 hash of their options sorted by key. Passing an array of options works the same as [options](#options).

::: code
```twig Twig
{# Fetch items that share an item’s options #}
{% set items = craft.wishlist.items()
    .optionsSignature(item.optionsSignature)
    .all() %}
```

```php PHP
// Fetch items that share an item’s options
$items = \verbb\wishlist\elements\Item::find()
    ->optionsSignature($item->optionsSignature)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the items should be returned in.

::: code
```twig Twig
{# Fetch all items in order of date created #}
{% set items = craft.wishlist.items()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all items in order of date created
$items = \verbb\wishlist\elements\Item::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only items that match a search query.

See [Searching](https://craftcms.com/docs/5.x/system/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{# Fetch all items that match the search query #}
{% set items = craft.wishlist.items()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

// Fetch all items that match the search query
$items = \verbb\wishlist\elements\Item::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the items’ statuses.

| Value | Fetches items… |
| --- | --- |
| `'enabled'` _(default)_ | that are enabled. |
| `'disabled'` | that are disabled. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled items #}
{% set items = craft.wishlist.items()
    .status('disabled')
    .all() %}

{# Fetch all items, regardless of status #}
{% set items = craft.wishlist.items()
    .status(null)
    .all() %}
```

```php PHP
// Fetch disabled items
$items = \verbb\wishlist\elements\Item::find()
    ->status('disabled')
    ->all();

// Fetch all items, regardless of status
$items = \verbb\wishlist\elements\Item::find()
    ->status(null)
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the items’ UIDs.

::: code
```twig Twig
{# Fetch the item by its UID #}
{% set item = craft.wishlist.items()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the item by its UID
$item = \verbb\wishlist\elements\Item::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `with`

Causes the query to return matching items eager-loaded with related elements. Pass `element` to load the element each item links to for all the items in one query, which `item.element` then returns. Wishlist loads all the linked elements as the first item’s element type, so only eager-load `element` when the items all link to the same type of element, such as by adding the [elementClass](#elementclass) parameter.

::: code
```twig Twig
{# Fetch items eager-loaded with their linked elements #}
{% set items = craft.wishlist.items()
    .listId(list.id)
    .with(['element'])
    .all() %}
```

```php PHP
// Fetch items eager-loaded with their linked elements
$items = \verbb\wishlist\elements\Item::find()
    ->listId($list->id)
    ->with(['element'])
    ->all();
```
:::
:::
