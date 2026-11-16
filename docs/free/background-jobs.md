# Large sites and background jobs

Every deletion runs as a **job**, in batches of 50 users.

## In this browser tab or in the background

| Mode | How it runs | Use it for |
|---|---|---|
| **In this browser tab** | The page sends one batch after another; a progress bar shows the progress | A few hundred users; keep the tab open |
| **In the background** | The server deletes batches on its own, with [Action Scheduler](https://actionscheduler.org/) when it is available (for example with WooCommerce), otherwise with WP-Cron | Thousands of users; you can close the page |

## Deletion Jobs page

**Bulk Users Delete → Deletion Jobs** lists the jobs with their mode, status, progress and numbers of deleted and not deleted users. While a job is running, the page refreshes by itself.

- **Cancel** stops a running job after the current batch. Users already deleted stay deleted.
- Finished jobs are removed after 30 days. The [Logs page](./log-and-privacy) keeps the record of every deletion.

## Tips for tens of thousands of users

1. Narrow the users down with the filters, then use **Select all … users of the preview** (the button shows the number).
2. Choose **In the background** in the confirmation.
3. Back up the database first.
4. Or use WP-CLI: `wp ubdwp delete … --background` (see [WP-CLI](./wp-cli)).

## When a background job does not move

Background jobs need WP-Cron or Action Scheduler to run. If a job stays at 0%:

- Check that WP-Cron is not disabled (`DISABLE_WP_CRON` in `wp-config.php`). If it is, a real cron job must call `wp-cron.php`.
- Run the job by hand: `wp ubdwp jobs run <id> --user=<admin>`.
- With WooCommerce, check **Tools → Scheduled Actions** for past-due actions of the `ubdwp` group.
