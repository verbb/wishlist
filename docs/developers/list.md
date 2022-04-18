# List
Whenever you're dealing with a list in your template, you're actually working with a `List` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the list.
`reference` | A unique identifier for this list, often used in sharing.
`typeId` | The List Type ID.
`userId` | If logged in, this will be the user ID of the owner for this list.
`sessionId` | If a guest, this will contain the unqiue session ID used to identify this guest.
`default` | Whether this list is marked as the default list for users.
`title` | The title of this list.
`lastIp` | A record of the last known IP for the guest or user of this list.
