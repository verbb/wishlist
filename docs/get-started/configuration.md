# Configuration

Create an `wishlist.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'pluginName' => 'Wishlist',
        'showListInfoTab' => true,

        // Lists
        'allowDuplicates' => false,
        'manageDisabledLists' => true,
        'mergeLastListOnLogin' => false,
        'purgeInactiveLists' => true,
        'purgeInactiveListsDuration' => 'P3M', // 3 months
        'purgeInactiveGuestListsDuration' => 'P1D', // 1 day
        'purgeEmptyListsOnly' => true,
        'purgeEmptyGuestListsOnly' => true,

        // PDF
        'pdfFilenameFormat' => 'Wishlist-{id}',
        'pdfPath' => '_pdf/wishlist',
        'pdfAllowRemoteImages' => false,
        'pdfPaperSize' => 'letter',
        'pdfPaperOrientation' => 'portrait',
    ]
];
```

### Configuration options

- `pluginName` - If you want to change the plugin name in the control panel.

#### Lists
- `allowDuplicates` - Whether to allow duplicates in lists.
- `manageDisabledLists` - Whether to allow front-end users to manage disabled lists and their items.
- `mergeLastListOnLogin` - Whether to merge a guest user’s lists with all existing lists for the user, when they log in.
- `purgeInactiveLists` - Whether to purge inactive lists after a certain duration.
- `purgeInactiveListsDuration` - If purging inactive lists is enabled, after this duration they will be purged.
- `purgeInactiveGuestListsDuration` - If purging inactive lists is enabled, after this duration only guest lists will be purged.
- `purgeEmptyListsOnly` - Whether to purge user lists only if they have no items.
- `purgeEmptyGuestListsOnly` - Whether to purge guest lists only if they have no items.

#### PDF
- `pdfPath` - Set the path to your PDF.
- `pdfFilenameFormat` - Set the defaulf PDF filename format.
- `pdfAllowRemoteImages` - Whether to allow remote images in the PDF.
- `pdfPaperSize` - Sets the paper size for the PDF.
- `pdfPaperOrientation` - Sets the paper orientation for the PDF.

## Control Panel

You can also manage configuration settings through the Control Panel by visiting Settings → Wishlist.
