# Multisite

The plugin works in the context of the **current site**.

- Selected users are **removed from the current site**: they lose their role on it. Their network account and their access to other sites stay.
- Only members of the current site are found.
- Super admins are always protected.
- The required capability is `remove_users` on the site (instead of `delete_users` on a single site).
- By default the content of removed users stays on the site; you can still give it to another user or delete it.

## Network activation

When the plugin is network activated, each site gets its own log and jobs tables, and new sites are set up automatically.

## Deleting accounts from the whole network

The free plugin never deletes network accounts. The [Pro add-on](/pro/multisite) can delete accounts that no longer belong to any site, with a network recycle bin.
