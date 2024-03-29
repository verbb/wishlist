<?php

return [
    //
    // Email Messages
    //

    'wishlist_share_list_heading' => 'When a user shares a wishlist list:',
    'wishlist_share_list_subject' => '{{ sender.fullName }} has shared their wishlist with you on {{ siteName }}.',
    'wishlist_share_list_body' => "Hey {{ recipient.friendlyName }},\n\n" .
        "{{ sender.fullName }} ({{ sender.email }}) has shared their wishlist with you.\n\n" .
        "Have a look at it via {{ siteUrl('wishlist', { id: list.reference }) }}.",
    'Delete' => 'Delete',
    'Owner' => 'Owner',
    'No custom fields defined for this item type.' => 'No custom fields defined for this item type.',
    'All lists' => 'All lists',
    'Plugin name for the end user.' => 'Plugin name for the end user.',
    'Manage Disabled Lists' => 'Manage Disabled Lists',
    'View all wishlists' => 'View all wishlists',
    'Item' => 'Item',
    'Purge Empty Lists Only' => 'Purge Empty Lists Only',
    'Show ”Wishlists” Tab for Users' => 'Show ”Wishlists” Tab for Users',
    'Lists' => 'Lists',
    'Settings' => 'Settings',
    'The path to the template used for generating list PDFs.' => 'The path to the template used for generating list PDFs.',
    'Settings saved.' => 'Settings saved.',
    'View wishlist type - {listType}' => 'View wishlist type - {listType}',
    'Couldn’t save settings.' => 'Couldn’t save settings.',
    'Sent list share notification to {email}.' => 'Sent list share notification to {email}.',
    'Whether to delete a list after a certain period of time.' => 'Whether to delete a list after a certain period of time.',
    'Purge Inactive Lists' => 'Purge Inactive Lists',
    'Handle' => 'Handle',
    'This action is not allowed for the current user.' => 'This action is not allowed for the current user.',
    'General Settings' => 'General Settings',
    'Manage “{type}” lists' => 'Manage “{type}” lists',
    'Failed to send list share to {email} - {error}.' => 'Failed to send list share to {email} - {error}.',
    'Manage list types' => 'Manage list types',
    'You can only update your own list.' => 'You can only update your own list.',
    'No custom fields defined for this list type.' => 'No custom fields defined for this list type.',
    'Whether to allow duplicate items to be added to lists.' => 'Whether to allow duplicate items to be added to lists.',
    'Upvote lists migrated.' => 'Upvote lists migrated.',
    'Item deleted.' => 'Item deleted.',
    'Upvote' => 'Upvote',
    'How you’ll refer to this list type in the templates.' => 'How you’ll refer to this list type in the templates.',
    'Are you sure you want to delete the selected lists?' => 'Are you sure you want to delete the selected lists?',
    'Migrate Shortlist (for Craft 2)' => 'Migrate Shortlist (for Craft 2)',
    'No item options.' => 'No item options.',
    'Save as a new list' => 'Save as a new list',
    'Allow Duplicates' => 'Allow Duplicates',
    'Shortlist' => 'Shortlist',
    'Whether to delete a list after a certain period of time, only if the list is empty, and only for guests.' => 'Whether to delete a list after a certain period of time, only if the list is empty, and only for guests.',
    'Reference' => 'Reference',
    'List deleted.' => 'List deleted.',
    'Are you sure you want to delete this list?' => 'Are you sure you want to delete this list?',
    'Are you sure you want to delete this item?' => 'Are you sure you want to delete this item?',
    'Enabled' => 'Enabled',
    'Wishlist List' => 'Wishlist List',
    'Use this list type by default for new lists.' => 'Use this list type by default for new lists.',
    'Whether users should be allowed to manage lists when lists are disabled.' => 'Whether users should be allowed to manage lists when lists are disabled.',
    'All items' => 'All items',
    'No Upvote data found.' => 'No Upvote data found.',
    'No list exists with the ID “{id}”.' => 'No list exists with the ID “{id}”.',
    'PDF Filename Format' => 'PDF Filename Format',
    'What this list type will be called in the CP.' => 'What this list type will be called in the CP.',
    'You can only clear your own list.' => 'You can only clear your own list.',
    'Done' => 'Done',
    'What the generated PDF filename should look like (without extension). You can include tags that output list properties, such as {ex1} or {ex2}.' => 'What the generated PDF filename should look like (without extension). You can include tags that output list properties, such as {ex1} or {ex2}.',
    'Whether to add a tab for all users’ wishlists in their account in the control panel.' => 'Whether to add a tab for all users’ wishlists in their account in the control panel.',
    'Migrate Shortlist' => 'Migrate Shortlist',
    'List Fields' => 'List Fields',
    'Click "Migrate Shortlist" to migrate all Shortlist lists and their items to Wishlist.' => 'Click "Migrate Shortlist" to migrate all Shortlist lists and their items to Wishlist.',
    'List Type' => 'List Type',
    'Wishlists' => 'Wishlists',
    'You must supply and sender and recipient' => 'You must supply and sender and recipient',
    'Create a new list' => 'Create a new list',
    'Name' => 'Name',
    'Are you sure you want to delete the selected items?' => 'Are you sure you want to delete the selected items?',
    'No list with the ID “{id}”' => 'No list with the ID “{id}”',
    'IP Address' => 'IP Address',
    'Click "Migrate Upvote" to migrate all Upvote votes into Wishlist lists.' => 'Click "Migrate Upvote" to migrate all Upvote votes into Wishlist lists.',
    'Whether to delete a list after a certain period of time, only if the list is empty.' => 'Whether to delete a list after a certain period of time, only if the list is empty.',
    'Items' => 'Items',
    'Couldn’t save list type.' => 'Couldn’t save list type.',
    'Guest' => 'Guest',
    'Purge Empty Guest Lists Only' => 'Purge Empty Guest Lists Only',
    'Item not found with the ID “{id}”' => 'Item not found with the ID “{id}”',
    'Merge List on Login' => 'Merge List on Login',
    'Save and continue editing' => 'Save and continue editing',
    'Save' => 'Save',
    'List Types' => 'List Types',
    'No items exist for this list yet.' => 'No items exist for this list yet.',
    'Date Created' => 'Date Created',
    'Options' => 'Options',
    'No Shortlist data found.' => 'No Shortlist data found.',
    'Element' => 'Element',
    'Date Updated' => 'Date Updated',
    'Couldn’t save item.' => 'Couldn’t save item.',
    'Create a List Type' => 'Create a List Type',
    'New list type' => 'New list type',
    '{name} Wishlist’s' => '{name} Wishlist’s',
    'Items deleted.' => 'Items deleted.',
    'PDF Template' => 'PDF Template',
    'PDF' => 'PDF',
    'Shortlist lists migrated.' => 'Shortlist lists migrated.',
    'You can only delete your own list.' => 'You can only delete your own list.',
    'Couldn’t delete list.' => 'Couldn’t delete list.',
    'Couldn’t save list.' => 'Couldn’t save list.',
    'Migrations' => 'Migrations',
    'Item Fields' => 'Item Fields',
    'No lists exist for this user yet.' => 'No lists exist for this user yet.',
    'Save as a new item' => 'Save as a new item',
    'Lists deleted.' => 'Lists deleted.',
    'No item with the ID “{id}”' => 'No item with the ID “{id}”',
    'Couldn’t delete item.' => 'Couldn’t delete item.',
    'Whether to merge the current list when the user logs into their account. If a guest list is created, it will merge this into the list for the user.' => 'Whether to merge the current list when the user logs into their account. If a guest list is created, it will merge this into the list for the user.',
    'List type saved.' => 'List type saved.',
    'Manage lists' => 'Manage lists',
    'Wishlist Item' => 'Wishlist Item',
    'Wishlist' => 'Wishlist',
    'No items in this list.' => 'No items in this list.',
    'Migrate Upvote' => 'Migrate Upvote',

	'Lists' => 'Lists',
	'List Types' => 'List Types',
	'Settings' => 'Settings',
	'Manage “{type}” lists' => 'Manage “{type}” lists',
	'Wishlist' => 'Wishlist',
	'Manage list types' => 'Manage list types',
	'Manage lists' => 'Manage lists',
	'View all wishlists' => 'View all wishlists',
	'View wishlist type - {listType}' => 'View wishlist type - {listType}',
	'Options' => 'Options',
	'New list' => 'New list',
	'New {listType} list' => 'New {listType} list',
	'Wishlist List' => 'Wishlist List',
	'All lists' => 'All lists',
	'Are you sure you want to delete the selected lists?' => 'Are you sure you want to delete the selected lists?',
	'Lists deleted.' => 'Lists deleted.',
	'Title' => 'Title',
	'List Type' => 'List Type',
	'Owner' => 'Owner',
	'Items' => 'Items',
	'Date Created' => 'Date Created',
	'Date Updated' => 'Date Updated',
	'Guest' => 'Guest',
	'Wishlist Item' => 'Wishlist Item',
	'All items' => 'All items',
	'Are you sure you want to delete the selected items?' => 'Are you sure you want to delete the selected items?',
	'Items deleted.' => 'Items deleted.',
	'Item' => 'Item',
	'Type' => 'Type',
	'No item options.' => 'No item options.',
	'{name} Wishlist’s' => '{name} Wishlist’s',
	'No items exist for this list yet.' => 'No items exist for this list yet.',
	'No lists exist for this user yet.' => 'No lists exist for this user yet.',
	'General Settings' => 'General Settings',
	'Plugin Name' => 'Plugin Name',
	'Plugin name for the end user.' => 'Plugin name for the end user.',
	'Show ”Wishlists” Tab for Users' => 'Show ”Wishlists” Tab for Users',
	'Whether to add a tab for all users’ wishlists in their account in the control panel.' => 'Whether to add a tab for all users’ wishlists in their account in the control panel.',
	'Allow Duplicates' => 'Allow Duplicates',
	'Whether to allow duplicate items to be added to lists.' => 'Whether to allow duplicate items to be added to lists.',
	'Manage Disabled Lists' => 'Manage Disabled Lists',
	'Whether users should be allowed to manage lists when lists are disabled.' => 'Whether users should be allowed to manage lists when lists are disabled.',
	'Merge List on Login' => 'Merge List on Login',
	'Whether to merge the current list when the user logs into their account. If a guest list is created, it will merge this into the list for the user.' => 'Whether to merge the current list when the user logs into their account. If a guest list is created, it will merge this into the list for the user.',
	'Purge Inactive Lists' => 'Purge Inactive Lists',
	'Whether to delete a list after a certain period of time.' => 'Whether to delete a list after a certain period of time.',
	'Purge Empty Lists Only' => 'Purge Empty Lists Only',
	'Whether to delete a list after a certain period of time, only if the list is empty.' => 'Whether to delete a list after a certain period of time, only if the list is empty.',
	'Purge Empty Guest Lists Only' => 'Purge Empty Guest Lists Only',
	'Whether to delete a list after a certain period of time, only if the list is empty, and only for guests.' => 'Whether to delete a list after a certain period of time, only if the list is empty, and only for guests.',
	'PDF' => 'PDF',
	'PDF Template' => 'PDF Template',
	'The path to the template used for generating list PDFs.' => 'The path to the template used for generating list PDFs.',
	'PDF Filename Format' => 'PDF Filename Format',
	'What the generated PDF filename should look like (without extension). You can include tags that output list properties, such as {ex1} or {ex2}.' => 'What the generated PDF filename should look like (without extension). You can include tags that output list properties, such as {ex1} or {ex2}.',
	'Save' => 'Save',
	'Migrate Shortlist' => 'Migrate Shortlist',
	'Migrate Shortlist (for Craft 2)' => 'Migrate Shortlist (for Craft 2)',
	'Click "Migrate Shortlist" to migrate all Shortlist lists and their items to Wishlist.' => 'Click "Migrate Shortlist" to migrate all Shortlist lists and their items to Wishlist.',
	'No Shortlist data found.' => 'No Shortlist data found.',
	'Done' => 'Done',
	'Migrate Upvote' => 'Migrate Upvote',
	'Click "Migrate Upvote" to migrate all Upvote votes into Wishlist lists.' => 'Click "Migrate Upvote" to migrate all Upvote votes into Wishlist lists.',
	'No Upvote data found.' => 'No Upvote data found.',
	'Save and continue editing' => 'Save and continue editing',
	'Save as a new list' => 'Save as a new list',
	'Are you sure you want to delete this list?' => 'Are you sure you want to delete this list?',
	'Delete' => 'Delete',
	'No custom fields defined for this list type.' => 'No custom fields defined for this list type.',
	'List Items' => 'List Items',
	'Customize' => 'Customize',
	'No items in this list.' => 'No items in this list.',
	'Enabled' => 'Enabled',
	'Reference' => 'Reference',
	'IP Address' => 'IP Address',
	'Migrations' => 'Migrations',
	'Shortlist' => 'Shortlist',
	'Upvote' => 'Upvote',
	'Save as a new item' => 'Save as a new item',
	'Are you sure you want to delete this item?' => 'Are you sure you want to delete this item?',
	'No custom fields defined for this item type.' => 'No custom fields defined for this item type.',
	'Element' => 'Element',
	'Choose' => 'Choose',
	'New list type' => 'New list type',
	'Name' => 'Name',
	'What this list type will be called in the CP.' => 'What this list type will be called in the CP.',
	'Handle' => 'Handle',
	'How you’ll refer to this list type in the templates.' => 'How you’ll refer to this list type in the templates.',
	'Use this list type by default for new lists.' => 'Use this list type by default for new lists.',
	'No item with the ID “{id}”' => 'No item with the ID “{id}”',
	'Couldn’t save item.' => 'Couldn’t save item.',
	'Item saved.' => 'Item saved.',
	'Item not found with the ID “{id}”' => 'Item not found with the ID “{id}”',
	'Couldn’t delete item.' => 'Couldn’t delete item.',
	'Item deleted.' => 'Item deleted.',
	'Create a List Type' => 'Create a List Type',
	'List Fields' => 'List Fields',
	'Item Fields' => 'Item Fields',
	'This action is not allowed for the current user.' => 'This action is not allowed for the current user.',
	'List type saved.' => 'List type saved.',
	'Couldn’t save list type.' => 'Couldn’t save list type.',
	'Shortlist lists migrated.' => 'Shortlist lists migrated.',
	'Upvote lists migrated.' => 'Upvote lists migrated.',
	'Create a new list' => 'Create a new list',
	'No list exists with the ID “{id}”.' => 'No list exists with the ID “{id}”.',
	'Couldn’t delete list.' => 'Couldn’t delete list.',
	'List deleted.' => 'List deleted.',
	'Couldn’t save list.' => 'Couldn’t save list.',
	'List saved.' => 'List saved.',
	'You can only update your own list.' => 'You can only update your own list.',
	'You can only delete your own list.' => 'You can only delete your own list.',
	'You can only clear your own list.' => 'You can only clear your own list.',
	'You must supply and sender and recipient' => 'You must supply and sender and recipient',
	'Sent list share notification to {email}.' => 'Sent list share notification to {email}.',
	'No list with the ID “{id}”' => 'No list with the ID “{id}”',
	'Couldn’t save settings.' => 'Couldn’t save settings.',
	'Settings saved.' => 'Settings saved.',
	'Wishlists' => 'Wishlists',
	'An error occurred while generating this PDF.' => 'An error occurred while generating this PDF.',
];