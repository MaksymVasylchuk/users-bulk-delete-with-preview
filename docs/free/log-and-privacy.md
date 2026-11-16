# Log and privacy (GDPR)

## The Logs page

**Bulk Users Delete → Logs** lists every deletion made with the plugin: who ran it, when, how many users were deleted, and the deleted accounts.

## What the log stores

For each deleted account: its ID, the date and the administrator who deleted it. Emails and display names depend on the setting below.

## Privacy settings

**Settings → General → Privacy**:

| Setting | Options |
|---|---|
| **Keep log entries for** | Number of days. Older entries are deleted automatically once a day. `0` keeps them until you delete them. |
| **Emails and names in new entries** | **Store masked** (`j***@example.com`, default), **Store in full**, **Do not store** (only user IDs) |

Finished deletion jobs are always removed after 30 days; the log keeps the record of every deletion.

## Log maintenance

Also on **Settings → General**, for administrators who can delete users:

- **Apply to existing entries**: masks or removes emails and names in older entries, following the setting above.
- **Delete entries older than the period above**
- **Delete the whole log**

These actions cannot be undone. The same can be done with `wp ubdwp logs` (see [WP-CLI](./wp-cli)).

## WordPress personal data tools

The log is part of **Tools → Export Personal Data** and **Tools → Erase Personal Data**:

- **Export** returns the record of the person's account deletion (when the email was stored in full) and the deletions the person performed.
- **Erase** removes the person's email and name from the log and from deletion jobs; the records stay as anonymous history.

With the default **masked** setting, the full email is not stored, so there is nothing to export about a deleted account.

## Privacy policy

The plugin adds a suggested paragraph to **Settings → Privacy → Policy Guide**. Copy it into your privacy policy if you use the plugin.
