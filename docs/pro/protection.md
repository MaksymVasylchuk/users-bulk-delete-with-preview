# Protection and blocked registrations

**Settings → Protection** has three parts.

## Never delete

Users protected here are never deleted, from any page, scheduled cleanup or WP-CLI. In the preview they are marked **Protected**, and the result says *excluded from deletion by your settings*.

| Setting | What it protects |
|---|---|
| **Never delete users with the roles** | Every user with one of the chosen roles. Administrators and users who can manage users are always protected. |
| **Never delete these users** | User IDs, usernames or emails, one per line |
| **Never delete users at the email domains** | For example your company domain |
| **Never delete customers** | With an active subscription or membership; with an unfinished order (processing or on hold) |

## Blocked registrations

When **Deleted users cannot register again with the same email or username** is on (the default), the email and username of every deleted user are blocked.

- Gmail dots and `+tags` are ignored: `john.doe+1@gmail.com` is the same as `johndoe@gmail.com`. For other domains, the email must match exactly (case does not matter).
- **Block for** N days, or `0` for ever.
- Only a masked hint of the email is kept, so the list cannot be read back.
- Administrators can still create these users by hand.

### The Blocked Registrations list

Enter an email and click:

- **Check**: is it blocked?
- **Block**: block it by hand
- **Unblock**: allow it again

The list shows each blocked email hint, the reason (user deleted, or blocked by hand), who blocked it and until when.

Restoring a user from the [recycle bin](./recycle-bin) unblocks them. Erasing a person with **Tools → Erase Personal Data** unblocks them too.
