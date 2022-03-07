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

];