# Free plugin

**Users Bulk Delete With Preview** finds WordPress users with filters, shows them in a preview, and deletes the ones you select. Nothing is deleted before you have seen the list and confirmed.

It is free and available on [WordPress.org](https://wordpress.org/plugins/users-bulk-delete-with-preview/).

## What it does

- **Filters**: existing users, role, email, registration date, user meta, users without posts or comments, WooCommerce customers (bought a product, or without orders). See [Finding users](./finding-users).
- **Preview**: a paged table of the users found, with their posts count. Select some or all of them. See [Preview and delete](./preview-and-delete).
- **What happens to their content**: give their posts to another user, delete them permanently, or let WordPress handle them.
- **Safe by default**: administrators and users who can manage users are protected; large deletions ask you to type the number of users.
- **Large sites**: deletions run in batches, in the browser tab or in the background, and can be followed and cancelled on the Deletion Jobs page. See [Large sites and background jobs](./background-jobs).
- **CSV export** of the selected users, readable or ready for importing on another site. See [Export to CSV](./export).
- **Deletion log** with privacy settings and the WordPress personal data tools. See [Log and privacy](./log-and-privacy).
- **Multisite**: users are removed from the current site; their network account stays. See [Multisite](./multisite).
- **WP-CLI**: `wp ubdwp find`, `wp ubdwp delete`, `wp ubdwp jobs`, `wp ubdwp logs`. See [WP-CLI](./wp-cli).
- **Translations**: English, Ukrainian, German, French, Spanish, Italian and Portuguese (Brazil).

## Requirements

- WordPress 6.2 or newer
- PHP 8.0 or newer

## Where to find it

After activation, the plugin adds a **Bulk Users Delete** menu with four pages:

| Page | What it is for |
|---|---|
| Bulk Users Delete | Find, preview, export and delete users |
| Deletion Jobs | Follow, run or cancel deletions |
| Logs | Every deletion made with the plugin |
| Settings | Privacy of the log, export format |

## Need more?

The optional [Pro add-on](/pro/) adds a recycle bin, saved filters, scheduled cleanups, many more filters and protection rules. Everything described in this section stays free.
