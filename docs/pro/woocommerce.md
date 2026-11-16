# WooCommerce customer data

When a customer is deleted, WooCommerce keeps their orders as guest orders. **Settings → Customer data** decides what happens to them.

| Setting | Options |
|---|---|
| **Paid orders** | **Keep as guest orders** (WooCommerce default), **Remove the personal data** (names, addresses, email and phone are replaced, like the WooCommerce privacy tools do). Paid orders are kept for your accounting and taxes. |
| **Unpaid orders** (pending payment, failed, cancelled, draft) | Keep as guest orders, Remove the personal data, **Delete** |
| **Subscriptions** | **Delete** (WooCommerce Subscriptions default), **Cancel and keep as history**. Either way there are no more renewal payments. |
| **Downloads** | Revoke the download permissions (links in old order emails stop working) |

## With the recycle bin

The cleanup waits until the user leaves the bin for good:

- while the user is in the bin, their orders and subscriptions are untouched;
- **restoring** the user gives their orders (and kept subscriptions) back;
- when the user is removed from the bin (expired, emptied or deleted permanently), the cleanup above runs.

With the recycle bin off, the cleanup happens right after the deletion and cannot be undone.

## Multisite

On a network, deleting a user removes them from the site only: the account and its orders stay, so nothing is cleaned up.
