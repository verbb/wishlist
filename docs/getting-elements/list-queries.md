# List Queries
You can fetch lists in your templates or PHP code using **list queries**.

:::code
```twig Twig
{# Create a new list query #}
{% set myQuery = craft.wishlist.lists() %}
```

```php PHP
// Create a new list query
$myQuery = \verbb\wishlist\elements\ListElement::find();
```
:::

Once you’ve created a list query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [List](docs:developers/list) objects will be returned.

:::tip
See Introduction to [Element Queries](https://docs.craftcms.com/v3/dev/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example
We can display lists for a given user by doing the following:

1. Create a list query with `craft.wishlist.lists()`.
2. Set the [typeId](#typeId), [limit](#limit) and [status](#status) parameters on it.
3. Fetch all lists with `.all()` and output.
4. Loop through the lists using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a lists query with the 'typeId', 'limit', and 'status' parameters #}
{% set listsQuery = craft.wishlist.lists()
    .typeId(1)
    .limit(10)
    .status(true) %}

{# Fetch the Lists #}
{% set lists = listsQuery.all() %}

{# Display their contents #}
{% for list in lists %}
    <p>{{ list.list }}</p>
{% endfor %}
```

## Parameters

List queries support the following parameters:


<!-- BEGIN PARAMS -->


### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig
{# Fetch all lists, regardless of status #}
{% set lists = craft.wishlist.lists()
    .anyStatus()
    .all() %}
```

```php
// Fetch all lists, regardless of status
$lists = \verbb\wishlist\elements\ListElement::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`

Causes the query to return matching lists as arrays of data, rather than [List](docs:developers/list) objects.

::: code
```twig
{# Fetch lists as arrays #}
{% set lists = craft.wishlist.lists()
    .asArray()
    .all() %}
```

```php
// Fetch lists as arrays
$lists = \verbb\wishlist\elements\ListElement::find()
    ->asArray()
    ->all();
```
:::



### `dateCreated`

Narrows the query results based on the lists’ creation dates.

Possible values include:

| Value | Fetches lists…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch lists created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set lists = craft.wishlist.lists()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch lists created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$lists = \verbb\wishlist\elements\ListElement::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the lists’ last-updated dates.

Possible values include:

| Value | Fetches lists…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch lists updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set lists = craft.wishlist.lists()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch lists updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$lists = \verbb\wishlist\elements\ListElement::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `default`

Fetch the list that is set as the default.

::: code
```twig
{# Fetch list that is the default #}
{% set list = craft.wishlist.lists()
    .default(true)
    .one() %}
```

```php
// Fetch list that is the default
$list = \verbb\wishlist\elements\ListElement::find()
    ->default(true)
    ->one();
```
:::



### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig
{# Fetch lists in a specific order #}
{% set lists = craft.wishlist.lists()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch lists in a specific order
$lists = \verbb\wishlist\elements\ListElement::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::



### `id`

Narrows the query results based on the lists’ IDs.

Possible values include:

| Value | Fetches lists…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

::: code
```twig
{# Fetch the list by its ID #}
{% set list = craft.wishlist.lists()
    .id(1)
    .one() %}
```

```php
// Fetch the list by its ID
$list = \verbb\wishlist\elements\ListElement::find()
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
```twig
{# Fetch lists in reverse #}
{% set lists = craft.wishlist.lists()
    .inReverse()
    .all() %}
```

```php
// Fetch lists in reverse
$lists = \verbb\wishlist\elements\ListElement::find()
    ->inReverse()
    ->all();
```
:::



### `limit`

Determines the number of lists that should be returned.

::: code
```twig
{# Fetch up to 10 lists  #}
{% set lists = craft.wishlist.lists()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 lists
$lists = \verbb\wishlist\elements\ListElement::find()
    ->limit(10)
    ->all();
```
:::



### `offset`

Determines how many lists should be skipped in the results.

::: code
```twig
{# Fetch all lists except for the first 3 #}
{% set lists = craft.wishlist.lists()
    .offset(3)
    .all() %}
```

```php
// Fetch all lists except for the first 3
$lists = \verbb\wishlist\elements\ListElement::find()
    ->offset(3)
    ->all();
```
:::



### `orderBy`

Determines the order that the lists should be returned in.

::: code
```twig
{# Fetch all lists in order of date created #}
{% set lists = craft.wishlist.lists()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php
// Fetch all lists in order of date created
$lists = \verbb\wishlist\elements\ListElement::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::



### `reference`

Narrows the query results based on the list reference number.

Possible values include:

| Value | Fetches lists…
| - | -
| `'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'` | with a matching list reference number

::: code
```twig
{# Fetch the requested order #}
{% set reference = craft.app.request.getQueryParam('reference') %}

{% set lists = craft.wishlist.lists()
    .reference(reference)
    .one() %}
```

```php
// Fetch the requested order
$reference = Craft::$app->request->getQueryParam('reference');

$lists = \verbb\wishlist\elements\ListElement::find()
    ->reference($reference)
    ->one();
```
:::



### `search`

Narrows the query results to only lists that match a search query.

See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all lists that match the search query #}
{% set lists = craft.wishlist.lists()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all lists that match the search query
$lists = \verbb\wishlist\elements\ListElement::find()
    ->search($searchQuery)
    ->all();
```
:::



### `sessionId`

Narrows the query results based on the lists’ owners’ session ID.

::: code
```twig
{# Fetch lists by the owners’ session ID #}
{% set lists = craft.wishlist.lists()
    .sessionId('xxxxxxxxxxxxxxxxxxxxx')
    .all() %}
```

```php
// Fetch lists by the owners’ session ID
$lists = \verbb\wishlist\elements\ListElement::find()
    .sessionId('xxxxxxxxxxxxxxxxxxxxx')
    ->all();
```
:::



### `status`

Narrows the query results based on the lists’ statuses.

Possible values include:

| Value | Fetches lists…
| - | -
| `'live'` _(default)_ | that are live.
| `'pending'` | that are pending (enabled with a Post Date in the future).
| `'expired'` | that are expired (enabled with an Expiry Date in the past).
| `'disabled'` | that are disabled.
| `['live', 'pending']` | that are live or pending.

::: code
```twig
{# Fetch disabled lists #}
{% set lists = craft.wishlist.lists()
    .status('disabled')
    .all() %}
```

```php
// Fetch disabled lists
$lists = \verbb\wishlist\elements\ListElement::find()
    ->status('disabled')
    ->all();
```
:::



### `type`

Narrows the query results based on the lists’ types.

Possible values include:

| Value | Fetches lists…
| - | -
| `'foo'` | of a type with a handle of `foo`.
| `'not foo'` | not of a type with a handle of `foo`.
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
| a List Type object | of a type represented by the object.

::: code
```twig
{# Fetch lists with a Foo list type #}
{% set lists = craft.wishlist.lists()
    .type('foo')
    .all() %}
```

```php
// Fetch lists with a Foo list type
$lists = \verbb\wishlist\elements\ListElement::find()
    ->type('foo')
    ->all();
```
:::



### `typeId`

Narrows the query results based on the lists’ types, per the types’ IDs.

Possible values include:

| Value | Fetches lists…
| - | -
| `1` | of a type with an ID of 1.
| `'not 1'` | not of a type with an ID of 1.
| `[1, 2]` | of a type with an ID of 1 or 2.
| `['not', 1, 2]` | not of a type with an ID of 1 or 2.

::: code
```twig
{# Fetch lists of the list type with an ID of 1 #}
{% set lists = craft.wishlist.lists()
    .typeId(1)
    .all() %}
```

```php
// Fetch lists of the list type with an ID of 1
$lists = \verbb\wishlist\elements\ListElement::find()
    ->typeId(1)
    ->all();
```
:::



### `uid`

Narrows the query results based on the lists’ UIDs.

::: code
```twig
{# Fetch the list by its UID #}
{% set list = craft.wishlist.lists()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the list by its UID
$list = \verbb\wishlist\elements\ListElement::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::



### `userId`

Narrows the query results based on the lists’ users, per the users’ IDs.

Possible values include:

| Value | Fetches lists…
| - | -
| `1` | of a user with an ID of 1.
| `'not 1'` | not of a user with an ID of 1.
| `[1, 2]` | of a user with an ID of 1 or 2.
| `['not', 1, 2]` | not of a user with an ID of 1 or 2.

::: code
```twig
{# Fetch lists of the user with an ID of 1 #}
{% set lists = craft.wishlist.lists()
    .userId(1)
    .all() %}
```

```php
// Fetch lists of the user with an ID of 1
$lists = \verbb\wishlist\elements\ListElement::find()
    ->userId(1)
    ->all();
```
:::


<!-- END PARAMS -->
