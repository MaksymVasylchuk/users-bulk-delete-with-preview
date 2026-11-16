# Troubleshooting and FAQ

## A user I expected is not in the preview

- Your own account is never included.
- On multisite, only members of the current site are found.
- Check the filters: they are combined, so every one must match.
- For profile fields, use **Meta is empty or missing** rather than **Meta does not exist** (WordPress saves empty values).

## A user is marked "Protected"

Administrators, users who can manage other users and super admins are always protected. With the Pro add-on, users can also be protected by the **Never delete** settings; the reason then says *excluded from deletion by your settings*.

## A background job stays at 0%

Background jobs need WP-Cron or Action Scheduler. See [Large sites and background jobs](./background-jobs#when-a-background-job-does-not-move).

## Can I restore deleted users?

Not with the free plugin. The [Pro recycle bin](/pro/recycle-bin) keeps deleted users so they can be restored.

## What happens to the posts of deleted users?

See [What happens to their content](./preview-and-delete#what-happens-to-their-content).

## Can I delete administrators?

No. Use the standard WordPress **Users** screen for them.

## Can deleted users register again?

Yes, with the free plugin. The [Pro add-on](/pro/protection) can block the emails and usernames of deleted users.

## Can I delete inactive users automatically?

Not with the free plugin: every deletion is started by you, after the preview. The Pro add-on has [scheduled cleanups](/pro/scheduled-cleanups) and records the [last login](/pro/export-and-logins) of each user.

## Other plugins' notices are missing on the plugin pages

The plugin hides notices of other plugins on its own pages, so long lists of banners do not push the form down. They still show on the other admin pages.

## Where do I get help?

Ask on the [support forum](https://wordpress.org/support/plugin/users-bulk-delete-with-preview/) on WordPress.org.
