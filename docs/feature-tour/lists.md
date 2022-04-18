# Lists
Lists are simply that - a list of elements for a particular user. Any element on your Craft site can be added to a list, such as Entries, Categories and Commerce Products/Variants. A default list is always created for a user when they first add an item to their list. Use the [Template Guides](docs:template-guides/managing-items) to allow users to manage the items in their list.

Lists are session-based, and are populated as long as the current session is active. For guests, as soon as their session ends – such as they close their browser – their list items will be gone. For registered and logged-in users, lists will be persisted against their account, so It's important to prompt your users to log in or register to save their list contents. In fact, it works pretty similar to a typical Cart!

## Sharing Lists
A benefit of creating lists is being able to share them, be is a wishlist of items for a Commerce store, or even just to share bits of content a user has liked. It's easy to create and share lists and their contents publicly via a URL.

See the [Template Guide](docs:template-guides/sharing-lists).

## Add to Cart
Through a single function, you can provide your customers a way to automatically add their wishlist items to their cart. This provides an excellent shopping experience that's quick and easy, promoting more sales for your shop!

See the [Template Guide](docs:template-guides/add-to-cart).

## List Types
A default List Type named `Wishlist` is created for you when you install the plugin. You're free to rename or change this, but It's important to always have a default List Type set so that new list creators will be using the nominated default information.

It's also in List Types where you can set any custom fields for both lists and list items. These custom fields can be populated by users when adding or creating lists, for any additional information you require for your use-cases.