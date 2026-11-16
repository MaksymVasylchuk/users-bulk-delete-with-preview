# Recycle bin

With Pro, users deleted with Bulk Users Delete are kept in a recycle bin and can be restored. It works for deletions from the admin page, background jobs, scheduled cleanups and WP-CLI.

## What is kept

- the account (login, email, name), its **password hash** and **profile data**;
- its **roles**;
- which posts were moved to the trash or given to another user, so they can be given back;
- with WooCommerce, the link to its **orders and subscriptions** (see [WooCommerce customer data](./woocommerce)).

A restored user can log in with their old password.

## Settings

**Settings → Recycle bin**:

| Setting | Default |
|---|---|
| **Keep deleted users so they can be restored** | On |
| **Keep deleted users for** | 30 days. `0` keeps them until you remove them. |

The bin is emptied of expired users once a day.

## The Recycle Bin page

**Bulk Users Delete → Recycle Bin** lists the deleted users: roles, what happened to their content, who deleted them and when, and until when they are kept.

- **Restore** brings a user back.
- **Delete permanently** removes a user from the bin; they can no longer be restored.
- **Bulk actions** apply either to the selected users.
- **Search** by login, email or name.

## What is given back

| Content at deletion | After restore |
|---|---|
| Given to another user | Given back, as long as the posts still belong to that user |
| Moved to the trash (WordPress default) | Taken out of the trash with the status they had before (published posts are published again), as long as they are still in the trash |
| Permanently deleted | Cannot be given back |

Data that other plugins deleted together with the user cannot be restored either, unless they hook into the bin (see [`ubdwpp_snapshot_data`](./developers/hooks#ubdwpp-snapshot-data)).

## Privacy

The bin is part of **Tools → Export Personal Data** and **Erase Personal Data**: erasing a person removes them from the bin. Users removed from the bin are gone for good.
