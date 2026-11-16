# Export to CSV

In the preview, select users and click **Export Selected Users**. The CSV file is generated on demand and downloaded directly: it is never stored on the server.

## Two formats

Choose the format in **Settings → General → Export → CSV format**.

| Format | Columns | Use it for |
|---|---|---|
| **Readable** (default) | ID, Username, Email, First Name, Last Name, Role | Reading, spreadsheets, records before a cleanup |
| **For importing** | `user_login`, `user_email`, `first_name`, `last_name`, `display_name`, `role`, `user_registered`, `user_url` | Importing on another site with `wp user import-csv` or a user import plugin |

The import format:

- uses the field names of WordPress, so importers recognise the columns;
- has **no ID column**, because an importer would overwrite the user with the same ID on the other site;
- contains only the main role of each user.

**Passwords are never exported**, in either format.

Values that a spreadsheet would run as a formula (starting with `=`, `+`, `-`, `@`, a tab or a carriage return) are written with a leading apostrophe, so opening the file is safe.

The [Pro add-on](/pro/export-and-logins) can add columns such as last login, posts and comments counts, WooCommerce totals and any user meta.

Developers can add columns with the [`ubdwp_export_columns`](./developers/hooks#ubdwp-export-columns) and [`ubdwp_export_row`](./developers/hooks#ubdwp-export-row) filters.
