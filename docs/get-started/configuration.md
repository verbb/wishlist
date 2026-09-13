# Configuration

You can customise Wishlist’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `wishlist.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will change the name displayed in the control panel:

```php
<?php

return [
    'pluginName' => 'Wishlist Tools',
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `pluginName`

**Type:** `string` · **Default:** `'Wishlist'`

If you want to change the plugin name in the control panel.
:::


### Lists

::: reference
#### `allowDuplicates`

**Type:** `bool` · **Default:** `false`

Whether to allow duplicates in lists.
:::


::: reference
#### `manageDisabledLists`

**Type:** `bool` · **Default:** `true`

Whether to allow front-end users to manage disabled lists and their items.
:::


::: reference
#### `mergeLastListOnLogin`

**Type:** `bool` · **Default:** `false`

Whether to merge a user’s lists with all existing lists for the user, when they log in. This can be useful when lists are modified when logged out, but a user logs in. Any existing lists will be merged.
:::


::: reference
#### `purgeInactiveLists`

**Type:** `bool` · **Default:** `true`

Whether to purge inactive lists after a certain duration.
:::


::: reference
#### `purgeInactiveListsDuration`

**Type:** `string` · **Default:** `'P3M'`

If purging inactive lists is enabled, after this duration they will be purged.
:::


::: reference
#### `purgeInactiveGuestListsDuration`

**Type:** `string` · **Default:** `'P1D'`

If purging inactive lists is enabled, after this duration only guest lists will be purged.
:::


::: reference
#### `purgeEmptyListsOnly`

**Type:** `bool` · **Default:** `true`

Whether to purge user lists only if they have no items.
:::


::: reference
#### `purgeEmptyGuestListsOnly`

**Type:** `bool` · **Default:** `true`

Whether to purge guest lists only if they have no items.
:::


::: reference
#### `cookieExpiry`

**Type:** `mixed` · **Default:** `0`

Set how long of an expiry guest users' lists should have, before being forgotten. Provide as `0` to be session-based, or a [DateInterval](https://www.php.net/manual/en/dateinterval.format.php) string.
:::


### PDF

::: reference
#### `pdfPath`

**Type:** `string` · **Default:** `'_pdf/wishlist'`

Set the path to your PDF.
:::


::: reference
#### `pdfFilenameFormat`

**Type:** `string` · **Default:** `'Wishlist-{id}'`

Set the default PDF filename format.
:::


::: reference
#### `pdfAllowRemoteImages`

**Type:** `bool` · **Default:** `true`

Whether to allow remote images in the PDF.
:::


::: reference
#### `pdfPaperSize`

**Type:** `string` · **Default:** `'letter'`

Sets the paper size for the PDF.
:::


::: reference
#### `pdfPaperOrientation`

**Type:** `string` · **Default:** `'portrait'`

Sets the paper orientation for the PDF.
:::


### Email

::: reference
#### `templateEmail`

**Type:** `string|null` · **Default:** `null`

The template Wishlist will use for HTML emails.
:::


::: reference
#### `attachPdfToEmail`

**Type:** `bool` · **Default:** `false`

Whether to attach the PDF to the email. See PDF settings for generating the PDF.
:::


::: reference
### `showListInfoTab`

**Type:** `bool` · **Default:** `true`

Whether the list editing screen displays its information tab.
:::

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Wishlist.
