# Interfaces & Types

These are the GraphQL interfaces and types Wishlist adds. The [Queries](docs:graphql/queries) page shows them in use.

## Lists

### The `ListInterface` Interface

The interface implemented by every list. Lists use a single type, `List`, which also holds the custom fields from your list types’ **List Fields** layouts. Request those inside a `... on List` fragment, as shown in [Querying Custom Fields](docs:graphql/queries#querying-custom-fields).

As well as the fields below, the interface includes Craft’s standard element fields, such as `id`, `uid`, `title`, `status`, `enabled`, `siteId`, `dateCreated` and `dateUpdated`. A list’s `title` is its own title, which starts as its list type’s name.

```graphql
{
  wishlists(limit: 1) {
    id
    title
    typeId
    userId
    reference
    default
    items(limit: 5) {
      title
    }
  }
}
```

::: reference
#### `typeId`

**Type:** `Int`

The ID of the list’s list type.
:::

::: reference
#### `userId`

**Type:** `Int`

The ID of the user who owns the list, or `null` for a guest’s list.
:::

::: reference
#### `reference`

**Type:** `String`

The list’s reference, which identifies it in shared list links.
:::

::: reference
#### `default`

**Type:** `Boolean`

Whether the list is its owner’s default list.
:::

::: reference
#### `items`

**Type:** `[ItemInterface]`

The items in the list, each as an [Item](#the-iteminterface-interface) type. It accepts the same arguments as the [`wishlistItems` query](docs:graphql/queries#the-wishlistitems-query), so you can sort, limit or filter a list’s items. The results are always limited to this list, whatever `listId` you pass.

```graphql
{
  wishlists(userId: 1) {
    title
    items(orderBy: "dateCreated DESC", limit: 3) {
      title
      element {
        id
      }
    }
  }
}
```
:::

## Items

### The `ItemInterface` Interface

The interface implemented by every item. Items use a single type, `Item`, which also holds the custom fields from your list types’ **Item Fields** layouts. Request those inside a `... on Item` fragment.

As well as the fields below, the interface includes Craft’s standard element fields, such as `id`, `uid`, `status`, `enabled`, `siteId`, `dateCreated` and `dateUpdated`.

The `title`, `elementId`, `elementClass` and `element` fields describe the element the item links to. They return `null` when the schema can’t read that element, as [Linked Element Permissions](docs:graphql/queries#linked-element-permissions) explains, so check for `null` before using them.

```graphql
{
  wishlistItems(listId: 4562) {
    id
    listId
    title
    elementId
    elementClass
    element {
      title
      url
    }
  }
}
```

::: reference
#### `title`

**Type:** `String`

The title of the element the item links to.
:::

::: reference
#### `listId`

**Type:** `Int`

The ID of the list the item belongs to.
:::

::: reference
#### `elementId`

**Type:** `Int`

The ID of the element the item links to.
:::

::: reference
#### `elementClass`

**Type:** `String`

The class of the element the item links to, such as `craft\elements\Entry`.
:::

::: reference
#### `element`

**Type:** `ElementInterface`

The element the item links to. Use a fragment for its type to request that type’s fields, such as a product’s variants or an entry’s custom fields.

```graphql
{
  wishlistItems(listId: 4562) {
    element {
      title
      url
      ... on blog_Entry {
        summary
      }
    }
  }
}
```
:::
