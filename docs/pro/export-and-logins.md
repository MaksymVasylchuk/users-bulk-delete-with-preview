# Export columns and last logins

## Extra CSV columns

**Settings → Export** adds columns to **Export Selected Users** of the free plugin:

- **Extra columns**: Display name, Website, Registered, Last login, Posts, Comments; with WooCommerce, WooCommerce orders and WooCommerce total spent. Registered, Last login and Posts are on by default.
- **User meta columns**: one meta key per line; each becomes a column.

In the format for importing (**Settings → General → Export**), only the user meta columns are added, because importers only understand WordPress field and meta names.

## CSV export of the log

The **Logs** page gets an export button: the whole deletion log as a CSV file.

## Last logins

Pro records the time of every login, from the day it is activated. The [Last login filter](./filters#activity) and the Last login export column use it, together with the last activity of WooCommerce customers when it is later.

### Importing earlier logins

**Settings → Logins → Import last logins** copies the logins known to:

- WP Last Login
- When Last Login
- other plugins that store `last_login` or `_last_login` user meta
- the sessions WordPress keeps

Only newer times replace the recorded ones, so the import can be run again at any time. On the command line: `wp ubdwpp logins import --user=admin`.
