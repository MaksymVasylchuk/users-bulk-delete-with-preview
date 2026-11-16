# Pro hooks reference

Hooks of the Pro add-on (prefix `ubdwpp_`). Pro also uses the [hooks of the free plugin](/free/developers/hooks), which remain the main way to extend filters, exports and deletions. All hooks below are available since 1.0.0.

## Recycle bin

### `ubdwpp_snapshot_data`
<Badge type="info" text="filter" />

The data kept in the recycle bin for a user. Add data of your plugin here to give it back when the user is restored, or to clean it up when the user leaves the bin.

| Parameter | Type | Description |
|---|---|---|
| `$data` | `array<string, mixed>` | Snapshot data |
| `$user` | `WP_User` | User about to be deleted |

### `ubdwpp_user_restored`
<Badge type="tip" text="action" />

Fires after a user was restored from the recycle bin.

| Parameter | Type | Description |
|---|---|---|
| `$user_id` | `int` | User ID |
| `$entry` | `object` | Recycle bin entry, with the decoded data |

### `ubdwpp_bin_entries_removed`
<Badge type="tip" text="action" />

Fires after users were removed from the bin for good (emptied, expired or deleted permanently), to clean up data that was kept so they could be restored. `$entries` is an array of entries with their decoded data.

```php
// Keep a "credits" user meta in the bin and give it back on restore.
add_filter( 'ubdwpp_snapshot_data', function ( $data, $user ) {
	$data['my_credits'] = get_user_meta( $user->ID, 'credits', true );
	return $data;
}, 10, 2 );

add_action( 'ubdwpp_user_restored', function ( $user_id, $entry ) {
	if ( isset( $entry->data['my_credits'] ) ) {
		update_user_meta( $user_id, 'credits', $entry->data['my_credits'] );
	}
}, 10, 2 );
```

## Blocked registrations

| Hook | Type | Parameters | Description |
|---|---|---|---|
| `ubdwpp_registration_blocked` | filter | `( bool $blocked, string $email, string $login )` | Whether a registration is blocked |
| `ubdwpp_registration_blocked_message` | filter | `( string $message )` | The error shown when a registration is blocked |

## Filters

| Hook | Type | Parameters | Description |
|---|---|---|---|
| `ubdwpp_disposable_domains` | filter | `( array $domains )` | Lowercase domains of the "Disposable email" filter |
| `ubdwpp_wc_order_statuses` | filter | `( array $statuses )` | Order statuses counted by the WooCommerce filters, without the `wc-` prefix |
| `ubdwpp_login_import_sources` | filter | `( array $sources )` | User meta keys the last login is imported from, with plugin names; values may be Unix timestamps (seconds or milliseconds) or dates |
| `ubdwpp_saved_filter_usage` | filter | `( array $usage, string $filter_id )` | What uses a saved filter; a filter in use cannot be deleted |

## Scheduled cleanups

| Hook | Type | Parameters | Description |
|---|---|---|---|
| `ubdwpp_warnings_per_run` | filter | `( int $limit, array $schedule )` | Warning emails one run sends; the others are sent on the next runs |
| `ubdwpp_is_scheduled_job` | filter | `( bool $scheduled, int $job_id )` | Whether a deletion job was started by a scheduled cleanup |

## Multisite

| Hook | Type | Parameters | Description |
|---|---|---|---|
| `ubdwpp_network_user_deleted` | action | `( int $user_id, WP_User $user )` | After an account was deleted from the whole network |

## Settings and modules

| Hook | Type | Parameters | Description |
|---|---|---|---|
| `ubdwpp_default_settings` | filter | `( array $defaults )` | Default settings, keyed by setting name |
| `ubdwpp_sanitize_settings` | filter | `( array\|WP_Error $settings, array $input )` | Sanitize the fields of your module; return a `WP_Error` to refuse the form |
| `ubdwpp_settings_tab_end` | action | `( string $tab )` | Print sections at the end of a Pro tab of the Settings page, after the Save button |
| `ubdwpp_modules` | filter | `( array $module_classes )` | Module classes (implementing `UbdwppModuleInterface`) keyed by name |
| `ubdwpp_setup_site` | action | `( string $previous_version )` | The add-on data of the site is created or upgraded (licensed copies only); create your tables here |
| `ubdwpp_clear_scheduled_events` | action | — | The add-on is deactivated; unschedule your events |
| `ubdwpp_fs_loaded` | action | `( Freemius $freemius )` | The Freemius SDK of the add-on was started |
