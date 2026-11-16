# Preview and delete

## The preview

**Preview** shows the users that match the filters in a paged table: ID, username, email, registration date, role and number of posts. The table is loaded page by page from the server, so it stays fast with tens of thousands of users.

- Tick users one by one, or use **Select all … users of the preview** (the button shows the number) to select every user found, not only the visible page.
- Protected users are marked **Protected** and cannot be selected.
- **Previous step** goes back to the filters without losing them.

## What happens to their content

For each user, the **Assign related content to user** column decides what happens to their posts. A choice above the table applies to every selected user.

| Choice | Result |
|---|---|
| A user | Their posts are given to that user |
| Remove all related content | Their posts (any status) and comments are deleted permanently |
| Nothing selected (default) | WordPress decides: on a single site their posts and pages go to the trash; on multisite they stay |

## Confirmation

**Delete selected users** opens a summary:

- how many users are selected, will be deleted and will be skipped (with the reason);
- what happens to their posts;
- **How to run the deletion**: **In this browser tab** (keep the page open) or **In the background** (see [background jobs](./background-jobs)).

When 20 or more users would be deleted, you must type their number to confirm. The server checks it too.

## The result

The last step lists the deleted users and what happened to their content, and the users that were not deleted with the reason, for example:

- *Protected: administrators and other privileged users cannot be deleted.*
- *Protected: excluded from deletion by your settings.* (a rule of the [Pro add-on](/pro/protection))
- *You cannot delete your own account.*

Every deletion is recorded on the [Logs page](./log-and-privacy).

::: warning Deletions are final
With the free plugin, deleted users cannot be restored. Export them first if you may need their data, or use the [Pro recycle bin](/pro/recycle-bin).
:::
