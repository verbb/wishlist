# Item

Whenever you're dealing with a list item in your template, you're actually working with a `Item` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the item.
`elementId` | ID of the linked element.
`elementSiteId` | Site ID of the linked element.
`elementDisplay` | Display name of the linked element. ie `\craft\elements\Entry` would be `Entry`.
`element` | The linked element.
`title` | The Title of the linked element.
`listId` | ID of the list this item belongs to.
`list` | The list this item belongs to.
`inList` | Whether this item is in the list (default list or otherwise).

## Methods

Method | Description
--- | ---
`getAddUrl()` | Returns the URL to add an item to a list.
`getRemoveUrl()` | Returns the URL to remove an item from a list.
`getToggleUrl()` | Returns the URL to toggle an item in a list.
