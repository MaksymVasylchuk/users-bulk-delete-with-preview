# Getting started

## Install

1. In WordPress, go to **Plugins → Add New**.
2. Search for **Users Bulk Delete With Preview**.
3. Click **Install Now**, then **Activate**.

You can also download the zip from [WordPress.org](https://wordpress.org/plugins/users-bulk-delete-with-preview/) and upload it in **Plugins → Add New → Upload Plugin**.

::: tip Back up first
Deleting users cannot be undone with the free plugin. Make a database backup before a large cleanup. The [Pro recycle bin](/pro/recycle-bin) keeps deleted users so they can be restored.
:::

## Your first cleanup in five steps

This example removes subscribers who never filled in their first name and never wrote anything, a common sign of spam registrations.

1. Open **Bulk Users Delete**.
2. In **Choose filter type**, pick **Find users according to certain criteria**.
3. Set the filters:
   - **User Role**: Subscriber
   - **User Meta**: `first_name` → **Meta is empty or missing**
   - **Content**: tick **Only users without posts or comments on this site**
4. Click **Preview**. Check the list: these are the only users that can be deleted.
5. Select the users, or click **Select all … users of the preview** (the button shows the number). Then click **Delete selected users** and confirm.

The result screen lists the deleted users and what happened to their content. The deletion is recorded on the **Logs** page.

::: info Why "Meta is empty or missing" and not "Meta does not exist"?
WordPress stores an empty `first_name` for every user who left it blank, so the meta key exists. "Meta is empty or missing" finds both cases.
:::

## Next steps

- All filters: [Finding users](./finding-users)
- What the confirmation means: [Preview and delete](./preview-and-delete)
- Thousands of users: [Large sites and background jobs](./background-jobs)
