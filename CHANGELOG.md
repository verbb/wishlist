# Changelog

## 2.0.2 - 2022-09-25

### Fixed
- Fix an error when running Craft's Garbage Collection. (thanks @olivierbon).
- Fix an error when viewing list owners in the control panel.

## 2.0.1 - 2022-09-17

### Fixed
- Fix an error when deleting a list type.
- Fix multiple list types able to be set as the default.
- Fix an error running `resave` console commands.

## 2.0.0 - 2022-07-20

### Added
- Add missing translations.
- Add resave console command for elements.
- Add checks for registering events for performance.
- Memoize all services for performance.
- Rename base plugin methods.
- Add `archiveTableIfExists()` to install migration.

### Changed
- Now requires PHP `8.0.2+`.
- Now requires Craft `4.0.0+`.
- Now supports `dompdf/dompdf:^2.0`.
- `pdfAllowRemoteImages` is now `true` by default.

### Fixed
- Fix an error when uninstalling.
- Fix `project-config/rebuild` support.

### Removed
- Remove deprecated `item` and `notice` from Ajax responses.

## 1.4.14 - 2022-08-09

### Added
- Add `wishlist/items/cleanup-orphaned-items`.
- Show list owners with status and thumbnail in list element index view. (thanks @martyspain).

### Fixed
- Fix items not being removed when pruning lists.

## 1.4.13 - 2022-07-27

### Added
- Add missing English translations.

### Fixed
- Fix an error when trying to update list items.

## 1.4.12 - 2022-03-15

### Changed
- Now requires Craft 3.6.0+.

### Fixed
- Fix GraphQL generator issues in some cases (Gatsby).
- Fix when purging lists, not taking into account timezone and comparing UTC dates correctly.
- Fix an error when generating PDFs and custom fonts, where the temporary folder isn’t writable (or created).

## 1.4.11 - 2021-09-07

### Fixed
- Fix wishlist item title not being set when saving items through ajax requests.
- Fix `craft.wishlist.getInUserLists()` not implemented properly.

## 1.4.10 - 2021-07-20

### Fixed
- Fix `consolidateListsToUser` only working for default list types.
- Fix lack of table prefix for `mergeLastListOnLogin` handling.

## 1.4.9 - 2021-06-04

### Fixed
- Fix issue where trying to remove or toggle a wishlist item with the `options` param could have no effect. (thanks @benface).
- Fix `mergeLastListOnLogin` not respecting `allowDuplicates` by allowing duplicates during merging of guest and user lists.
- Add `craft.wishlist.getInUserLists()`.

## 1.4.8 - 2021-05-08

### Added
- Add the ability to match existing wishlist items by `itemId` and `options` when trying to remove or toggle a wishlist item in a list.
- Allow custom fields and options to be added when toggling a wishlist item.

## 1.4.7 - 2021-04-21

### Added
- Add notes for list/items when they have no custom fields in the control panel.

### Fixed
- Fix an error with Commerce 3.3+ and DomPDF version collision.
- Fix an error when creating a new list item in the control panel.

## 1.4.6 - 2021-04-02

### Fixed
- Fix `mergeLastListOnLogin` not correctly merging lists when logging in a user.

## 1.4.5 - 2021-03-04

### Changed
- Update `mergeLastListOnLogin` to only merge lists of the same type if they have the same title.

### Fixed
- Fix default list type not saving to project config when first installing the plugin.

## 1.4.4 - 2021-01-24

### Added
- Add `updateItemSearchIndexes` config setting to control updating search indexes for Wishlist items.
- Add `updateListSearchIndexes` config setting to control updating search indexes for Wishlist Lists.

### Changed
- Improve item and list database query performance.

### Fixed
- Fix `craft.wishlist.item` forcing the creation of a new list, when it shouldn’t.
- Fix incorrectly showing list items in other users’ lists.

## 1.4.3 - 2020-12-22

### Fixed
- Fix `lists/add-to-cart` not supporting custom error/success messages.
- Fix `lists/share-by-email` not supporting custom error/success messages.
- Fix user wishlist’s always showing the currently logged-in user, when editing a user in the control panel.

## 1.4.2 - 2020-12-16

### Added
- When performing any list or item actions from the front end, a flash error/notice is now available with the result. Use `craft.app.session.getFlash('notice')` to output this message when using the `url()` methods or using a `<form>` POST submit.
- When submitting a form for managing items, you can see a `successMessage` or `failMessage` to modify the flash message returned. This is not available when using the `url()` methods. Be sure to hash the message: `<input type="hidden" name="successMessage" value="{{ 'Override Message' | hash }}">`.

## 1.4.1 - 2020-12-07

### Changed
- Wishlist queries via GraphQL are now no longer automatically included in the public schema.

## 1.4.0 - 2020-11-10

> {warning} Please note the change in behaviour for `craft.wishlist.item()` has meant that you cannot rely on lists being auto-created anymore on page-load. Ensure anywhere you call `{% set list = craft.wishlist.lists().one() %}` you check `{% if list %}` before doing anything on the list. Ensure your templates work correctly.

### Added
- Add params to `addUrl()`, `removeUrl()` and `toggleUrl()` to make it easier to create URLs.
- Add listType handle to `craft.wishlist.item()`.
- Add `listTypeId` query param to Item queries.

### Changed
- `craft.wishlist.item()` no longer automatically creates new lists when the page is loaded, preventing lots of lists being created for guests. Previously as soon as the page loaded with this call, a list would be created for users, meaning lists could get out of control easily. Be sure to double check your templates.

## 1.3.1 - 2020-10-06

### Fixed
- Fix `optionsSignature` error when updating.

## 1.3.0 - 2020-10-04

### Added
- Guest lists are now (finally) persistent! Using cookies, even when the user closes their browser, their lists are retained for their next visit.
- Add migration for [Shortlist](https://github.com/TopShelfCraft/Shortlist) plugin for Craft 2.
- Add migration for [Upvote](https://plugins.craftcms.com/upvote) plugin.
- Add PDF template handling, to provide an easy way to generate PDF's of your lists and their content. See [docs](https://verbb.io/craft-plugins/wishlist/docs/template-guides/pdf-template).
- Add Item Options, allowing you to save additional, arbitrary content on items. See [docs](https://verbb.io/craft-plugins/wishlist/docs/template-guides/managing-items#item-options).
- Add all available config settings to be able to be managed in the control panel.
- Add support for Craft 3.5+ field layouts.
- Add support for multiple tabs for lists.
- Add support for multiple tabs for list items.
- Add support to customise the item element table columns when editing a list.
- Add new tab to user’s account, for all wishlists and items they may own.
- Add `cookieExpiry` config setting.
- Add `showListInfoTab` config setting.
- Add `pdfFilenameFormat` config setting.
- Add `pdfPath` config setting.
- Add `pdfAllowRemoteImages` config setting.
- Add `pdfPaperSize` config setting.
- Add `pdfPaperOrientation` config setting.

### Changed
- Now requires Craft 3.5+.

## 1.2.21 - 2020-10-03

### Added
- Add `craft.wishlist.plugin` Twig variable, allowing access to plugin services.
- Add `lists/update-items` controller action, to enable bulk-updating on list items, similar to a cart.
- Add BCC and CC email options when sharing a list by email.

## 1.2.20 - 2020-09-26

### Added
- Add `fields` to share-lists controller action. Allowing additional content to be used in email notifications

## 1.2.19 - 2020-09-18

### Added
- Add ability to remove items from a list when adding to cart.

### Changed
- Allow `add-to-cart` and `share-by-email` endpoints for lists to be accessed anonymously.
- Change add-to-cart behaviour so that it only adds supplied purchasables to the cart - if provided.

## 1.2.18 - 2020-09-03

### Changed
- The `update` list action can now be accessed anonymously. (thanks @BrandonJamesBorders).

## 1.2.17 - 2020-08-21

### Fixed
- Fix type check error when trying to determine list owner.

## 1.2.16 - 2020-08-10

### Added
- Add `wishlist/lists/purge-inactive-lists` console command.
- Provide the `list` object to controller actions, to allow usage like `{{ redirectInput('lists/{id}') }}`. (thanks @brandonohara).
- Add `purgeEmptyListsOnly` and `purgeEmptyGuestListsOnly` config settings.

### Fixed
- Improve error-handling for some template functions, to guard against errors.

## 1.2.15 - 2020-06-30

### Fixed
- Return list reference on `wishlist/lists/create`. (thanks @BrandonJamesBorders).

## 1.2.14 - 2020-06-15

### Added
- Add `wishlist/lists/update` action to enable list updating from the front-end.

## 1.2.13 - 2020-06-09

> {warning} Please note the deprecation of `item` for Ajax requests. If you're using Ajax to handle the response from add/delete/toggle, please adjust your Javascript code that handles this to use `items`.

### Added
- Full item model is now included in Ajax responses for `toggle` requests.

### Fixed
- Fix incorrect return values for add/delete/toggle for Ajax requests.

### Deprecated
- `item` is now deprecated in Ajax responses for add/delete/toggle actions, and will be removed in the next major release. Please adjust your code to instead rely on `items` which is an array of returned Wishlist items. This is because these actions can support multiple items, rather than just a single one.

## 1.2.12 - 2020-05-31

> {warning} Please note the change in default behaviour for `purgeInactiveLists`.

### Changed
- Change `purgeInactiveLists` to be on by default. This will prevent your wishlists from getting out of control.

## 1.2.11 - 2020-05-29

### Added
- Add `item` model to add/remove ajax actions.
- Implement GraphQL for items and lists. See [docs](https://verbb.io/craft-plugins/wishlist/docs/developers/graphql).

## 1.2.10 - 2020-05-16

> {warning} Please note the change in default behaviour for `mergeLastListOnLogin`.

### Changed
- Change `mergeLastListOnLogin` to be off by default. User lists won't be merged automatically, by default.

## 1.2.9 - 2020-05-14

### Added
- Add `resave/wishlist-items` console command.
- Add `resave/wishlist-lists` console command.

### Fixed
- Fix potential error when fetching an items list, when it doesn’t exist.

## 1.2.8 - 2020-05-12

### Added
- Return “items” when toggling an item. This will be the item ID and the action taken upon an item ('added' or 'removed').

## 1.2.7 - 2020-05-12

### Added
- Add `action` param to all item and list controller actions

## 1.2.6 - 2020-04-16

### Fixed
- Fix logging error `Call to undefined method setFileLogging()`.

## 1.2.5 - 2020-04-16

### Added
- Add `mergeLastListOnLogin`, on by default to merge guests’ lists when logging in with existing saved lists.

### Fixed
- Ensure saved lists for users aren’t overridden on next login.

## 1.2.4 - 2020-04-15

### Changed
- File logging now checks if the overall Craft app uses file logging.
- Log files now only include `GET` and `POST` additional variables.

## 1.2.3 - 2020-04-07

### Fixed
- Fix managing lists on the front-end and requiring permissions.

## 1.2.2 - 2020-04-01

### Fixed
- Only allow editing of list types if editable.
- Fix default data when installing conflicting with project config.
- Ensure plugin project config is removed when uninstalling.

## 1.2.1 - 2020-01-30

### Added
- Allow list actions to be callable via URL.
- Add `manageDisabledLists` config setting to manage disabled lists and their items (default to true).

### Fixed
- Fix error when adding a new item to list in the CP.
- Fix unable to update the element for an item through the CP.

## 1.2.0 - 2020-01-29

### Added
- Craft 3.4 compatibility.

## 1.1.2 - 2020-01-18

### Fixed
- Fix project config error.

## 1.1.1 - 2020-01-18

### Fixed
- Fix list items having incorrect search attributes.

## 1.1.0 - 2020-01-10

### Added
- Add project config support.
- Add support for add/delete/toggle multiple items. See [docs](https://verbb.io/craft-plugins/wishlist/docs/template-guides/managing-items)
- Add `purgeInactiveGuestListsDuration` to set times for guest lists to be purged, separate to user lists. This is default to 1 day.
- Add purge lists to Craft's garbage collection.
- Add guest list to user list when logging in.
- Add email share action for lists. Users can directly and easily send their list via email.
- Allow managing of list items in the CP, including add and delete.

### Changed
- Add `forceSave` to `craft.wishlist.lists()` to force a new list to be generated.

### Fixed
- Fixed SQL error for PostgreSQL. (thanks @Tam).
- Fix `craft.wishlist.item()` when called for a specific list.
- Fix issue when calling multiple lists in the same request not returning the correct list.

## 1.0.6 - 2019-03-02

### Added
- Add update controller action for items.

### Fixed
- Fix title on CP edit item page.
- Fix saving items in the CP.
- Fix missing delete action for items

## 1.0.5 - 2019-03-01

### Fixed
- Fix setting field params via URL not working correctly.

## 1.0.4 - 2019-02-27

### Fixed
- Fix purge function to only remove lists with zero items.

## 1.0.3 - 2019-02-27

### Fixed
- Fix typo in purge lists function.

## 1.0.2 - 2019-02-26

### Fixed
- Fix permissions for lists.
- Fix permissions for list types.
- Fix sidebar menu for using plugin name override.

## 1.0.1 - 2019-02-17

### Added
- Adding option to provide a list type handle or id when adding an Item. (thanks @echantigny).

### Fixed
- Fix owner column not being correct.
- Correct AJAX action for list type deletion. (thanks @AugustMiller).
- Add routing rule for index of list type. (thanks @echantigny).
- Fix error thrown when viewing list when created in the CP. (thanks @echantigny).

## 1.0.0 - 2018-11-26

- Initial release.
