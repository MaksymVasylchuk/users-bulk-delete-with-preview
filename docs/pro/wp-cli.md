# WP-CLI

Pro adds four command groups. Like the free commands, run them as an administrator with `--user`. They are available while the license is active.

## wp ubdwpp filters

```bash
wp ubdwpp filters list --user=admin
wp ubdwpp filters find "Spam subscribers" --format=count --user=admin
wp ubdwpp filters delete "Spam subscribers" --dry-run --user=admin
wp ubdwpp filters delete "Spam subscribers" --reassign=editor --yes --user=admin
```

| Command | Options |
|---|---|
| `list` | `--format=table\|csv\|json\|ids` |
| `find <filter>` | Name or ID of the saved filter; `--format=table\|csv\|json\|ids\|count` |
| `delete <filter>` | `--reassign=<user>` (ID or login, or `remove` to delete the content permanently), `--batch-size=<number>` (default 50), `--dry-run`, `--background`, `--yes` |

Administrators and other protected users are skipped. With the recycle bin on, deleted users can be restored.

## wp ubdwpp schedules

```bash
wp ubdwpp schedules list --user=admin
wp ubdwpp schedules run "Inactive subscribers" --user=admin
```

`run` runs a cleanup now; deletions run as a background job, as they do on schedule. `--yes` skips the confirmation when the cleanup deletes users.

## wp ubdwpp bin

```bash
wp ubdwpp bin list --search=example.com --user=admin
wp ubdwpp bin restore 12 13 --user=admin
wp ubdwpp bin restore --all --yes --user=admin
wp ubdwpp bin purge --older-than=7 --yes --user=admin
wp ubdwpp bin purge --all --yes --user=admin
```

| Command | Options |
|---|---|
| `list` | `--search=<text>`, `--limit=<number>` (default 100), `--format=table\|csv\|json\|ids\|count` |
| `restore [<entry>...]` | Entry IDs from `list`, or `--all` (with `--yes`) |
| `purge` | `--older-than=<days>` (default: the period of the settings) or `--all`; `--yes` |

## wp ubdwpp logins

```bash
wp ubdwpp logins import --user=admin
```

Imports the last logins known to other plugins and to the sessions WordPress keeps. Only newer times replace the recorded ones.
