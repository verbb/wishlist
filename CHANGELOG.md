# Changelog

## 1.1.0 - 2020-01-10

### Added
- Add project config support.
- Add support for add/delete/toggle multiple items. See [docs](https://verbb.io/craft-plugins/wishlist/docs/template-guides/managing-items)
- Add `purgeInactiveGuestListsDuration` to set times for guest lists to be purged, separate to user lists. This is default to 1 day.
- Add purge lists to Craft's garbage collection.
- Add guest list to user list when logging in.
- Add email share action for lists. Users can directly and easily send their list via email.
- Allow managing of list items in the CP, including add and delete.

### Fixed
- Fixed SQL error for PostgreSQL. (thanks @Tam).
- Fix `craft.wishlist.item()` when called for a specific list.

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
