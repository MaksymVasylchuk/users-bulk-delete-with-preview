# Notifications

**Settings → Notifications**.

## Administrators

| Setting | Description |
|---|---|
| **Email when a deletion job finishes** | For deletions running in the background or with WP-CLI. Several emails separated by commas. Scheduled cleanups send their own report instead. |
| **Webhook URL** | Receives a JSON POST request when any deletion job finishes. No personal data is sent. |

### Webhook payload

```json
{
  "event": "ubdwp.job_finished",
  "site": "https://example.com/",
  "job_id": 42,
  "status": "done",
  "mode": "background",
  "scheduled": false,
  "total": 120,
  "deleted": 118,
  "failed": 2,
  "owner_id": 1,
  "time": "2026-11-15T19:10:00+00:00"
}
```

The request is sent with `wp_safe_remote_post()`, which refuses local and private addresses. Use it for Slack, Discord, Zapier, Make and similar services.

## Deleted users

**Email deleted users → Tell users that their account was deleted** sends an email to every user deleted with the plugin, including scheduled cleanups. The **Subject** and **Text** can use:

| Placeholder | Value |
|---|---|
| `{site_name}` | Name of the site |
| `{display_name}` | User's display name |
| `{user_login}` | User's login |

::: tip
Other plugins may send their own email when an account is deleted (for example Ultimate Member). Turn one of them off so users do not get two emails.
:::
