# Queries

Wishlist adds GraphQL queries for fetching lists and their items, so a headless front end can show a user’s wishlist or a shared list. Read [Craft’s GraphQL documentation](https://craftcms.com/docs/5.x/development/graphql.html) first if you haven’t set up a GraphQL endpoint before.

## Enable Wishlist in Your Schema

Wishlist’s queries only appear in schemas that can read at least one list type. In the control panel, go to **GraphQL** → **Schemas**, edit the schema your client uses (or the **Public Schema**), and under **Wishlist** enable **View all wishlists**, or **View wishlist type - [name]** for each list type the schema should read. Save the schema. A schema that can read only some list types only receives lists and items from those types.

The **Wishlist** options only appear once you’ve created a list type.

GraphQL has no idea who the current visitor is, so these queries aren’t limited to one user’s lists the way `craft.wishlist.lists()` is in Twig. Anyone with access to a schema can read every list of the list types it allows, so use a private schema and token for anything other than lists you’re happy to make public.

### Linked Element Permissions

An item links to another element, such as an entry or a Commerce product. Reading that element through an item needs the schema to allow it too, as well as the list type. That means the entry’s section, the asset’s volume, the product’s product type, the user’s group and so on, plus the element’s site. A disabled element, draft or revision also needs the schema’s matching option for querying those. [The `authorizeLinkedElement` event](docs:developers/events#the-authorizelinkedelement-event) decides access for element types Wishlist doesn’t know about.

When the schema can’t read an item’s linked element, the item is still returned, but its `title`, `elementId`, `elementClass` and `element` fields are `null`. Use the item’s own `id` and `listId` to show a placeholder for it.

Queries that filter with `title`, `search`, `elementId` or `elementClass`, or sort by the linked element’s title, ID or class, only return items whose linked elements the schema can read. Wishlist applies this before `limit` and `offset`, including for a list’s nested `items`. These queries check permissions for every matching item, so give them a specific `listId` when you search within one list. Ordinary sorting, such as `orderBy: "id ASC"`, keeps the placeholders.

## Querying Lists

### The `wishlists` Query

Returns a list of lists, each as a [List](docs:graphql/interfaces-types#the-listinterface-interface) type. Use it to fetch a user’s lists, a list shared by its reference, or every list of a list type.

The query accepts the same arguments as Craft’s element queries, such as `id`, `uid`, `site`, `status`, `title`, `search`, `limit`, `offset` and `orderBy`, plus the Wishlist arguments below. Each one works like the [list query parameter](docs:getting-elements/list-queries) of the same name.

| Argument | Type | Description |
| --- | --- | --- |
| `typeId` | `[QueryArgument]` | Narrows the results to lists of the list types with these IDs. |
| `userId` | `[QueryArgument]` | Narrows the results to lists owned by the users with these IDs. |
| `reference` | `[String]` | Narrows the results to the lists with these references. References are matched exactly. |
| `default` | `Boolean` | Narrows the results to lists that are their owner’s default list when `true`. |

This example fetches the lists owned by the user with an ID of 1, with the title of each item’s linked element.

```graphql
{
  wishlists(userId: 1) {
    id
    title
    default
    items {
      id
      title
    }
  }
}
```

```json
{
  "data": {
    "wishlists": [
      {
        "id": "4562",
        "title": "Wishlist",
        "default": true,
        "items": [
          {
            "id": "4570",
            "title": "Linen Shirt"
          }
        ]
      },
      {
        "id": "4588",
        "title": "Favourites",
        "default": false,
        "items": []
      }
    ]
  }
}
```

A shared list link carries the list’s reference, which you can pass on to fetch that one list.

```graphql
query SharedList($reference: [String]) {
  wishlists(reference: $reference, limit: 1) {
    title
    items {
      element {
        id
        title
        url
      }
    }
  }
}
```

```json
{
  "reference": "f6a2c39e1b7d"
}
```

```json
{
  "data": {
    "wishlists": [
      {
        "title": "Birthday Ideas",
        "items": [
          {
            "element": {
              "id": "14931",
              "title": "Linen Shirt",
              "url": "https://example.com/shop/linen-shirt"
            }
          }
        ]
      }
    ]
  }
}
```

## Querying Items

### The `wishlistItems` Query

Returns a list of items, each as an [Item](docs:graphql/interfaces-types#the-iteminterface-interface) type. Use it to fetch the items in one list, or to find which lists include a particular element.

The query accepts the same arguments as Craft’s element queries, such as `id`, `uid`, `site`, `status`, `search`, `limit`, `offset` and `orderBy`, plus the Wishlist arguments below. Each one works like the [item query parameter](docs:getting-elements/item-queries) of the same name.

| Argument | Type | Description |
| --- | --- | --- |
| `listId` | `[QueryArgument]` | Narrows the results to items in the lists with these IDs. |
| `elementId` | `[QueryArgument]` | Narrows the results to items linked to the elements with these IDs. |
| `elementClass` | `[String]` | Narrows the results to items linked to elements of these classes, such as `craft\elements\Entry`. |

This example fetches the newest item in the list with an ID of 4562, with its linked element.

```graphql
{
  wishlistItems(listId: 4562, limit: 1) {
    id
    listId
    element {
      id
      title
    }
  }
}
```

```json
{
  "data": {
    "wishlistItems": [
      {
        "id": "4570",
        "listId": 4562,
        "element": {
          "id": "14931",
          "title": "Linen Shirt"
        }
      }
    ]
  }
}
```

This example counts how many lists include the entry with an ID of 14931, by fetching the items linked to it.

```graphql
{
  wishlistItems(elementId: 14931) {
    listId
  }
}
```

```json
{
  "data": {
    "wishlistItems": [
      { "listId": 4562 },
      { "listId": 4601 }
    ]
  }
}
```

### Querying Custom Fields

Lists and items use a single type each, `List` and `Item`, which also hold the custom fields from your list types’ **List Fields** and **Item Fields** layouts. Request those inside a fragment. This example assumes the list field layout has a Plain Text field with the handle `occasion` and the item layout has a Number field with the handle `priority`.

```graphql
{
  wishlists(userId: 1) {
    title
    ... on List {
      occasion
    }
    items {
      title
      ... on Item {
        priority
      }
    }
  }
}
```

```json
{
  "data": {
    "wishlists": [
      {
        "title": "Birthday Ideas",
        "occasion": "30th birthday",
        "items": [
          {
            "title": "Linen Shirt",
            "priority": 1
          }
        ]
      }
    ]
  }
}
```
