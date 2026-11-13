# Permissions

Wishlist's permissions are for the staff who look after your customers' lists in the control panel, and for the rare front-end account that needs to change lists belonging to someone else. A customer service team might look up and tidy a customer's wishlist, while a site builder sets up list types. Set them in **Settings → Users → User Groups**, or on an individual user's **Permissions** tab.

**Access Wishlist** is listed under **General**, with Craft's other permissions for reaching each plugin's section. The others are listed under the **Wishlist** heading.

| Permission | What It Allows |
| --- | --- |
| Access Wishlist | Reach Wishlist's section of the control panel. |
| Manage list types | Open **List Types** to create, edit and delete list types. |
| Manage lists | Show **Lists** in Wishlist's navigation. The permissions nested under it decide which lists can be seen and changed. |
| Manage “{type}” lists | See that list type's lists on the **Lists** screen, and create, edit and delete them in the control panel. |
| Manage other users’ “{type}” lists | On the front end, change, clear and delete other people's lists of that type, and add or remove their items, the same way the list's owner can. |

Each list type gets its own pair of permissions nested under **Manage lists**, labelled with the list type's name, such as **Manage “Wishlist” lists** and **Manage other users’ “Wishlist” lists**. They're built from your list types, so a new type's permissions appear as soon as it's saved and disappear when it's deleted. They're tied to the list type rather than its name, so renaming a type keeps the access you've already given.

**Manage list types** and the list permissions work inside Wishlist's section, so they need **Access Wishlist** too. Moving a list to a different type in the control panel needs **Manage “{type}” lists** for the type it's moving to. List types are stored in Craft's project config, so they can only be changed where admin changes are allowed.

**Manage other users’ “{type}” lists** is the only one that applies outside the control panel, and it doesn't need **Access Wishlist**. Without it, a logged-in visitor can only change their own lists, and anyone viewing a shared list can read it without being able to change it. Give it only to accounts that genuinely act on behalf of other customers, because it lets that account empty or delete any list of that type.

Admins have every permission, and Wishlist's settings are only available to admins.
