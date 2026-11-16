# Multisite network

The free plugin only removes users from a site. On a network, that can leave **accounts without a site**: they belong to no site any more but still exist. Pro lets super admins clean them up.

## Accounts Without a Site

In the **network admin**, **Users → Accounts Without a Site** lists these accounts (username, email, name, registration date). Select them and click **Delete selected accounts from the network**. They go to the **recycle bin of the network**, where they can be restored or deleted permanently.

## Settings

| Setting | Description |
|---|---|
| **Delete from the network** | When a super admin removes a user from their last site with Bulk Users Delete, also delete the network account |
| **Keep deleted accounts for** | Days in the network recycle bin. `0` keeps them until you remove them. |

## Per-site features

Everything else (recycle bin, schedules, filters, protection) works per site, as on a single site. Blocked registrations are shared by the whole network, like the accounts.
