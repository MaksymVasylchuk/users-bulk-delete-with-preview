# Pro data and uninstall

## Per site

| Kind | Name | Content |
|---|---|---|
| Table | `{prefix}ubdwpp_recycle_bin` | Recycle bin |
| Option | `ubdwpp_settings` | Settings of every Pro tab |
| Option | `ubdwpp_saved_filters` | Saved filters |
| Option | `ubdwpp_schedules` | Scheduled cleanups |
| Option | `ubdwpp_db_version` | Database version of the add-on |
| Cron event | `ubdwpp_run_schedules` | Hourly check of scheduled cleanups |
| Cron event | `ubdwpp_recycle_bin_cleanup` | Daily removal of expired bin entries |
| Cron event | `ubdwpp_blocklist_cleanup` | Daily removal of expired blocks |
| Cron event | `ubdwpp_network_bin_cleanup` | Daily removal of expired entries of the network bin (multisite, main site) |

## Network-wide (shared by all sites, like the accounts)

| Kind | Name | Content |
|---|---|---|
| Table | `{base_prefix}ubdwpp_blocklist` | Blocked registrations: hashes of emails and usernames, and a masked hint |
| Table | `{base_prefix}ubdwpp_network_bin` | Recycle bin of the network (multisite) |
| Site option | `ubdwpp_blocklist_db`, `ubdwpp_blocklist_key` | Version and hashing key of the blocklist |
| Site option | `ubdwpp_login_tracking_since` | When Pro started recording logins |
| Site option | `ubdwpp_network`, `ubdwpp_network_db_version` | Network settings (multisite) |
| User meta | `ubdwpp_last_login` | Time of the last login |
| User meta | `ubdwpp_warned_{schedule}` | A scheduled cleanup warned the user |

## Without a license

The add-on keeps all of this. Its tables are only created once a license is active, and the features (including scheduled cleanups) do not run until then.

## Uninstall

Deleting Pro from **Plugins** removes all of the above, on every site of the network. The data of the free plugin (log, jobs) is kept: it is removed when the free plugin is deleted.
