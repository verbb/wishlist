# Item Queries
You can fetch items in your templates or PHP code using **item queries**.

:::code
```twig Twig
{# Create a new item query #}
{% set myQuery = craft.wishlist.items() %}
```

```php PHP
// Create a new item query
$myQuery = \verbb\wishlist\elements\Item::find();
```
:::

Once you’ve created an item query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Item](docs:developers/item) objects will be returned.

:::tip
See Introduction to [Element Queries](https://craftcms.com/docs/4.x/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example

We can display items for a given user by doing the following:

1. Create an item query with `craft.wishlist.items()`.
2. Set the [listId](#listId), [limit](#limit) and [status](#status) parameters on it.
3. Fetch all items with `.all()` and output.
4. Loop through the items using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a items query with the 'listId', 'limit', and 'status' parameters #}
{% set itemsQuery = craft.wishlist.items()
    .listId(1)
    .limit(10)
    .status(true) %}

{# Fetch the Items #}
{% set items = itemsQuery.all() %}

{# Display their contents #}
{% for item in items %}
    <p>{{ item.item }}</p>
{% endfor %}
```

## Parameters

Item queries support the following parameters:


<!-- BEGIN PARAMS -->


### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v4/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v4/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig Twig
{# Fetch all items, regardless of status #}
{% set items = craft.wishlist.items()
    .anyStatus()
    .all() %}
```

```php PHP
// Fetch all items, regardless of status
$items = \verbb\wishlist\elements\Item::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`

Causes the query to return matching items as arrays of data, rather than [Item](docs:developers/item) objects.

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



### `dateCreated`

Narrows the query results based on the items’ creation dates.

Possible values include:

| Value | Fetches items…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

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
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$items = \verbb\wishlist\elements\Item::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the items’ last-updated dates.

Possible values include:

| Value | Fetches items…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

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
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$items = \verbb\wishlist\elements\Item::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `elementId`

Narrows the query results based on the linked element per the provided IDs.

Possible values include:

| Value | Fetches items…
| - | -
| `1` | created for an element with an ID of 1.
| `'not 1'` | not created for an element with an ID of 1.
| `[1, 2]` | created for an element with an ID of 1 or 2.
| `['not', 1, 2]` | not created for an element with an ID of 1 or 2.

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



### `elementSiteId`

Narrows the query results based on the site the linked element was saved for, per the site’s ID.

Possible values include:

| Value | Fetches items…
| - | -
| `1` | created for an element in a site with an ID of 1.
| `':empty:'` | created in a field that isn’t set to manage blocks on a per-site basis.

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



### `elementClass`

Narrows the query results based on the class name the linked element was saved for.

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
    ->elementClass(Entry::class)
    ->all();
```
:::



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



### `id`

Narrows the query results based on the items’ IDs.

Possible values include:

| Value | Fetches items…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

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



### `listId`

Narrows the query results based on the items’ List IDs.

Possible values include:

| Value | Fetches items…
| - | -
| `1` | with a list ID of 1.
| `'not 1'` | not with a list ID of 1.
| `[1, 2]` | with a list ID of 1 or 2.
| `['not', 1, 2]` | not with a list ID of 1 or 2.

::: code
```twig Twig
{# Fetch the item by its ID #}
{% set item = craft.wishlist.items()
    .listId(1)
    .one() %}
```

```php PHP
// Fetch the item by its ID
$item = \verbb\wishlist\elements\Item::find()
    ->listId(1)
    ->one();
```
:::



### `listTypeId`

Narrows the query results based on the items’ List Type IDs.

Possible values include:

| Value | Fetches items…
| - | -
| `1` | with a list type ID of 1.
| `'not 1'` | not with a list type ID of 1.
| `[1, 2]` | with a list type ID of 1 or 2.
| `['not', 1, 2]` | not with a list type ID of 1 or 2.

::: code
```twig Twig
{# Fetch the item by its ID #}
{% set item = craft.wishlist.items()
    .listTypeId(1)
    .one() %}
```

```php PHP
// Fetch the item by its ID
$item = \verbb\wishlist\elements\Item::find()
    ->listTypeId(1)
    ->one();
```
:::



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



### `search`

Narrows the query results to only items that match a search query.

See [Searching](https://craftcms.com/docs/4.x/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

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



### `status`

Narrows the query results based on the items’ statuses.

Possible values include:

| Value | Fetches items…
| - | -
| `'live'` _(default)_ | that are live.
| `'pending'` | that are pending (enabled with a Post Date in the future).
| `'expired'` | that are expired (enabled with an Expiry Date in the past).
| `'disabled'` | that are disabled.
| `['live', 'pending']` | that are live or pending.

::: code
```twig Twig
{# Fetch disabled items #}
{% set items = craft.wishlist.items()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled items
$items = \verbb\wishlist\elements\Item::find()
    ->status('disabled')
    ->all();
```
:::



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


<!-- END PARAMS -->
