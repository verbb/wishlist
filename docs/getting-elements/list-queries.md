# List Queries

You can fetch lists in your templates or PHP code using **list queries**.

::: code
```twig Twig
{# Create a new list query #}
{% set myQuery = craft.wishlist.lists() %}
```

```php PHP
// Create a new list query
$myQuery = \verbb\wishlist\elements\ListElement::find();
```
:::

Once you’ve created a list query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [List](docs:reference/list) objects will be returned.

::: tip
See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn about how element queries work.
:::

## Example

We can display the current visitor’s lists of one list type by doing the following:

1. Create a list query with `craft.wishlist.lists()`.
2. Set the [type](#type) and [limit](#limit) parameters on it.
3. Fetch the lists with `.all()`.
4. Loop through the lists using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their titles.

```twig
{# Create a list query with the 'type' and 'limit' parameters #}
{% set listsQuery = craft.wishlist.lists()
    .type('wishlist')
    .limit(10) %}

{# Fetch the lists #}
{% set lists = listsQuery.all() %}

{# Display their contents #}
{% for list in lists %}
    <p>{{ list.title }}</p>
{% endfor %}
```

In Twig, `craft.wishlist.lists()` only returns lists that belong to the current visitor, whether they’re logged in or a guest. Pass `false`, as in `craft.wishlist.lists(false)`, to query lists for every user, and only do that in templates that people with the right access will see. `ListElement::find()` in PHP isn’t scoped to the current visitor.

## Parameters

List queries support the following parameters.

::: reference
### `asArray`

Causes the query to return matching lists as arrays of data, rather than [List](docs:reference/list) objects.

::: code
```twig Twig
{# Fetch lists as arrays #}
{% set lists = craft.wishlist.lists()
    .asArray()
    .all() %}
```

```php PHP
// Fetch lists as arrays
$lists = \verbb\wishlist\elements\ListElement::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the lists’ creation dates.

| Value | Fetches lists… |
| --- | --- |
| `'>= 2018-04-01'` | that were created on or after 2018-04-01. |
| `'< 2018-05-01'` | that were created before 2018-05-01. |
| `['and', '>= 2018-04-01', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01. |

::: code
```twig Twig
{# Fetch lists created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set lists = craft.wishlist.lists()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch lists created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$lists = \verbb\wishlist\elements\ListElement::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the lists’ last-updated dates.

| Value | Fetches lists… |
| --- | --- |
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01. |
| `'< 2018-05-01'` | that were updated before 2018-05-01. |
| `['and', '>= 2018-04-01', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01. |

::: code
```twig Twig
{# Fetch lists updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set lists = craft.wishlist.lists()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch lists updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$lists = \verbb\wishlist\elements\ListElement::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `default`

Narrows the query results to lists that are their owner’s default list. Pass `true`, because `false` doesn’t filter the results.

::: code
```twig Twig
{# Fetch the current visitor’s default list #}
{% set list = craft.wishlist.lists()
    .default(true)
    .one() %}
```

```php PHP
// Fetch the user’s default list
$list = \verbb\wishlist\elements\ListElement::find()
    ->userId($user->id)
    ->default(true)
    ->one();
```
:::
:::

::: reference
### `editable`

Narrows the query results to lists whose list type the current user has permission to manage in the control panel. When no user is logged in, the query returns no lists.

::: code
```twig Twig
{# Fetch lists the current user can manage #}
{% set lists = craft.wishlist.lists(false)
    .editable()
    .all() %}
```

```php PHP
// Fetch lists the current user can manage
$lists = \verbb\wishlist\elements\ListElement::find()
    ->editable()
    ->all();
```
:::
:::

::: reference
### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch lists in a specific order #}
{% set lists = craft.wishlist.lists()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch lists in a specific order
$lists = \verbb\wishlist\elements\ListElement::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the lists’ IDs.

| Value | Fetches lists… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the list by its ID #}
{% set list = craft.wishlist.lists()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the list by its ID
$list = \verbb\wishlist\elements\ListElement::find()
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
{# Fetch lists in reverse #}
{% set lists = craft.wishlist.lists()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch lists in reverse
$lists = \verbb\wishlist\elements\ListElement::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of lists that should be returned.

::: code
```twig Twig
{# Fetch up to 10 lists #}
{% set lists = craft.wishlist.lists()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 lists
$lists = \verbb\wishlist\elements\ListElement::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many lists should be skipped in the results.

::: code
```twig Twig
{# Fetch all lists except for the first 3 #}
{% set lists = craft.wishlist.lists()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all lists except for the first 3
$lists = \verbb\wishlist\elements\ListElement::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the lists should be returned in.

::: code
```twig Twig
{# Fetch all lists in order of date created #}
{% set lists = craft.wishlist.lists()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all lists in order of date created
$lists = \verbb\wishlist\elements\ListElement::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `reference`

Narrows the query results based on the list reference number.

References are matched exactly. Wildcard and comparison syntax aren’t supported, because the reference can grant read-only access to a shared list. In Twig, pass `false` to `craft.wishlist.lists()` so the query isn’t limited to the current visitor’s own lists.

| Value | Fetches lists… |
| --- | --- |
| `'••••••••••••••'` | with a matching list reference number. |

::: code
```twig Twig
{# Fetch the shared list for the reference in the URL #}
{% set reference = craft.app.request.getQueryParam('reference') %}

{% set list = craft.wishlist.lists(false)
    .reference(reference)
    .one() %}
```

```php PHP
// Fetch the shared list for the reference in the URL
$reference = \Craft::$app->getRequest()->getQueryParam('reference');

$list = \verbb\wishlist\elements\ListElement::find()
    ->reference($reference)
    ->one();
```
:::
:::

::: reference
### `search`

Narrows the query results to only lists that match a search query.

See [Searching](https://craftcms.com/docs/5.x/system/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{# Fetch all lists that match the search query #}
{% set lists = craft.wishlist.lists()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

// Fetch all lists that match the search query
$lists = \verbb\wishlist\elements\ListElement::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `sessionId`

Narrows the query results based on the lists’ owners’ session ID. Session IDs are matched exactly.

::: warning
This is a private guest-ownership credential intended for trusted server-side code. Do not place it in URLs, templates, logs or API responses.
:::

::: code
```twig Twig
{# Fetch lists by the owners’ session ID #}
{% set lists = craft.wishlist.lists(false)
    .sessionId('••••••••••••••')
    .all() %}
```

```php PHP
// Fetch lists by the owners’ session ID
$lists = \verbb\wishlist\elements\ListElement::find()
    ->sessionId('••••••••••••••')
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the lists’ statuses.

| Value | Fetches lists… |
| --- | --- |
| `'enabled'` _(default)_ | that are enabled. |
| `'disabled'` | that are disabled. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled lists #}
{% set lists = craft.wishlist.lists()
    .status('disabled')
    .all() %}

{# Fetch all lists, regardless of status #}
{% set lists = craft.wishlist.lists()
    .status(null)
    .all() %}
```

```php PHP
// Fetch disabled lists
$lists = \verbb\wishlist\elements\ListElement::find()
    ->status('disabled')
    ->all();

// Fetch all lists, regardless of status
$lists = \verbb\wishlist\elements\ListElement::find()
    ->status(null)
    ->all();
```
:::
:::

::: reference
### `type`

Narrows the query results based on the lists’ types.

| Value | Fetches lists… |
| --- | --- |
| `'foo'` | of a type with a handle of `foo`. |
| `'not foo'` | not of a type with a handle of `foo`. |
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`. |
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`. |
| a List Type object | of a type represented by the object. |

::: code
```twig Twig
{# Fetch lists with a Foo list type #}
{% set lists = craft.wishlist.lists()
    .type('foo')
    .all() %}
```

```php PHP
// Fetch lists with a Foo list type
$lists = \verbb\wishlist\elements\ListElement::find()
    ->type('foo')
    ->all();
```
:::
:::

::: reference
### `typeId`

Narrows the query results based on the lists’ types, per the types’ IDs.

| Value | Fetches lists… |
| --- | --- |
| `1` | of a type with an ID of 1. |
| `'not 1'` | not of a type with an ID of 1. |
| `[1, 2]` | of a type with an ID of 1 or 2. |
| `['not', 1, 2]` | not of a type with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch lists of the list type with an ID of 1 #}
{% set lists = craft.wishlist.lists()
    .typeId(1)
    .all() %}
```

```php PHP
// Fetch lists of the list type with an ID of 1
$lists = \verbb\wishlist\elements\ListElement::find()
    ->typeId(1)
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the lists’ UIDs.

::: code
```twig Twig
{# Fetch the list by its UID #}
{% set list = craft.wishlist.lists()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the list by its UID
$list = \verbb\wishlist\elements\ListElement::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `userId`

Narrows the query results based on the lists’ users, per the users’ IDs.

| Value | Fetches lists… |
| --- | --- |
| `1` | of a user with an ID of 1. |
| `'not 1'` | not of a user with an ID of 1. |
| `[1, 2]` | of a user with an ID of 1 or 2. |
| `['not', 1, 2]` | not of a user with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch lists of the user with an ID of 1 #}
{% set lists = craft.wishlist.lists(false)
    .userId(1)
    .all() %}
```

```php PHP
// Fetch lists of the user with an ID of 1
$lists = \verbb\wishlist\elements\ListElement::find()
    ->userId(1)
    ->all();
```
:::
:::

::: reference
### `with`

Causes the query to return matching lists eager-loaded with related elements. Pass `items` to load each list’s items in one query, which `list.items` then returns.

::: code
```twig Twig
{# Fetch lists eager-loaded with their items #}
{% set lists = craft.wishlist.lists()
    .with(['items'])
    .all() %}
```

```php PHP
// Fetch lists eager-loaded with their items
$lists = \verbb\wishlist\elements\ListElement::find()
    ->with(['items'])
    ->all();
```
:::
:::
