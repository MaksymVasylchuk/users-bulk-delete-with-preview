# Scheduled cleanups

A scheduled cleanup runs a [saved filter](./saved-filters) on a schedule and either emails a report or deletes the users found.

## Create one

1. Save a filter on the Bulk Users Delete page first.
2. Go to **Bulk Users Delete → Scheduled Cleanups → Add scheduled cleanup**.
3. Follow the four steps:

| Step | What you set |
|---|---|
| **Filter** | A name and the saved filter. **How many users match now?** counts them. |
| **Action** | **Email a report only** (nobody is deleted; start with this) or **Delete the users** (in the background; with the recycle bin on, they can be restored) |
| **Schedule** | Every hour, twice a day, every day, every week or every 30 days; the next run; and whether the cleanup is active |
| **Review** | Who gets the report, and a summary |

## When it deletes users

- **At most N per run**: if more users match, nobody is deleted and the report says the limit was reached. Check the filter or raise the limit.
- **Warning emails**: users can be warned by email a number of days before they are deleted. Logging in keeps the account: the warning is cancelled, and if the user matches the filter again later, they are warned again.
- Protected users are never warned or deleted.
- The deletion runs as a background job and is recorded on the Logs page.

### Placeholders of the warning email

| Placeholder | Value |
|---|---|
| `{site_name}` | Name of the site |
| `{display_name}` | User's display name |
| `{user_login}` | User's login |
| `{deletion_date}` | Date of the deletion |
| `{days}` | Days of the warning period |
| `{login_url}` | Login page of the site |

## The report

The report email says what happened (nothing to do, reported, over the limit, users warned, deleting, deleted, error) and lists the users matching the filter. Leave the recipients empty for no emails; no email is sent when nobody matches.

## Run, pause, edit

Each cleanup on the page has **Edit**, **Run now**, **Pause** / **Resume** and **Delete**. Its last result and next run are shown.

## WP-Cron

Cleanups are checked every hour by WP-Cron. If WP-Cron is disabled on the site (`DISABLE_WP_CRON`), the page says so: a real cron job must then call `wp-cron.php`. You can also run a cleanup with `wp ubdwpp schedules run`.
