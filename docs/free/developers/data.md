# Data and uninstall

What the free plugin stores, per site (on multisite, each site has its own):

| Kind | Name | Content |
|---|---|---|
| Table | `{prefix}ubdwp_logs` | Deletion log |
| Table | `{prefix}ubdwp_jobs` | Deletion jobs (finished jobs are removed after 30 days, unstarted ones after a day) |
| Option | `ubdwp_plugin_db_version` | Database version of the plugin |
| Option | `ubdwp_privacy` | `retention_days`, `log_email` (`masked`, `full`, `none`) |
| Option | `ubdwp_export` | `format` (`readable`, `import`) |
| Cron event | `ubdwp_daily_cleanup` | Daily cleanup of old log entries and jobs |
| Cron event or Action Scheduler action | `ubdwp_run_deletion_job` (group `ubdwp`) | Runs background jobs |
| User meta (network-wide) | `ubdwp_hide_pro_hints` | A user hid the tips about the Pro add-on |

## Uninstall

Deleting the plugin from **Plugins** removes all of the above on every site of the network: tables, options, scheduled events and user meta. The data of the Pro add-on is not touched by this; it is removed when the add-on itself is deleted (see [Pro data](/pro/developers/data)).

## Capabilities

| Action | Single site | Multisite |
|---|---|---|
| Open the pages, search, export | `manage_options` and `list_users` | the same, on the site |
| Delete or remove users | the above and `delete_users` | the above and `remove_users` |
| Log maintenance | the above and `delete_users` | the above and `remove_users` |
