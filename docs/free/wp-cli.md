# WP-CLI

The commands use the same filters, protections and log as the admin page. Run them as an administrator with `--user`.

```bash
wp help ubdwp
```

## wp ubdwp find

Lists the users that match the filters, without deleting anything.

```bash
wp ubdwp find --role=subscriber --meta-key=first_name --meta-compare=empty --format=count --user=admin
wp ubdwp find --email=@example.com --email-compare=ends-with --user=admin
wp ubdwp find --products=12,15 --format=count --user=admin
```

| Option | Description |
|---|---|
| `--ids=<ids>` | Comma-separated user IDs, or `all` for all users of the site |
| `--role=<roles>` | Comma-separated roles |
| `--email=<value>` | Email value to compare |
| `--email-compare=<compare>` | `equal` (default), `not-equal`, `like`, `not-like`, `ends-with` |
| `--registered=<date>` | Registration date, `YYYY-MM-DD`, site timezone |
| `--registered-compare=<compare>` | `after` (default), `before`, `on`, `between` |
| `--registered-to=<date>` | End date for `between` |
| `--meta-key=<key>` | User meta key |
| `--meta-compare=<compare>` | `not-exists`, `empty`, `equal` (default), `not-equal`, `like`, `not-like`, `equal-date`, `not-equal-date`, `less-date`, `less-equal-date`, `greater-date`, `greater-equal-date`, `equal-number`, `not-equal-number`, `less-number`, `less-equal-number`, `greater-number`, `greater-equal-number` |
| `--meta-value=<value>` | User meta value to compare |
| `--without-content` | Only users without posts or comments on the site |
| `--without-orders` | Only users without WooCommerce orders |
| `--products=<ids>` | Customers who bought one of these WooCommerce products |
| `--any-product` | Customers who bought any WooCommerce product |
| `--format=<format>` | `table` (default), `csv`, `json`, `ids`, `count` |

## wp ubdwp delete

Deletes the users that match the filters (on multisite: removes them from the site). Shows a summary first; protected users and users you cannot delete are skipped. Takes every filter of `find`, plus:

| Option | Description |
|---|---|
| `--reassign=<user>` | User ID that receives the content, or `remove-content` to delete posts and comments permanently. Without it, WordPress core behavior applies. |
| `--batch-size=<number>` | Users deleted per batch |
| `--dry-run` | Only show the summary |
| `--yes` | Do not ask for confirmation |
| `--background` | Create a background job instead of deleting now |

```bash
wp ubdwp delete --role=subscriber --meta-key=first_name --meta-compare=empty --dry-run --user=admin
wp ubdwp delete --ids=12,15 --reassign=remove-content --yes --user=admin
wp ubdwp delete --role=subscriber --without-content --background --yes --user=admin
```

## wp ubdwp jobs

Lists, runs or cancels deletion jobs.

```bash
wp ubdwp jobs --user=admin
wp ubdwp jobs run 12 --user=admin
wp ubdwp jobs cancel 12 --user=admin
```

`--format=table|csv|json` for the list.

## wp ubdwp logs

```bash
wp ubdwp logs purge --older-than=90 --user=admin   # delete entries older than 90 days
wp ubdwp logs purge --all --yes --user=admin       # delete the whole log
wp ubdwp logs anonymize --yes --user=admin         # apply the email privacy setting to existing entries
```
