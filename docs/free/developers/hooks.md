# Hooks reference

Hooks of the free plugin. The [Pro add-on](/pro/developers/hooks) is built on them, so it is a good example of how to extend the plugin.

Hooks with **since 2.5.0** or later need that version of the plugin; check `WPUBDP_ADDON_API` (an integer, increased when hooks are added) if your code must run with older versions.

## Filters and search

### `ubdwp_filter_types`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

Adds filter types to **Choose filter type**. A type added here shows the form rows with the class `ubdwp-filter-type-{type}` and finds its users with [`ubdwp_custom_filter_user_ids`](#ubdwp-custom-filter-user-ids). The built-in types cannot be removed.

| Parameter | Type | Description |
|---|---|---|
| `$types` | `array<string, string>` | Type labels keyed by type |

See [Adding a filter type](./filter-types) for a complete example.

### `ubdwp_custom_filter_user_ids`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

Returns the users found by a filter type added with `ubdwp_filter_types`. Users that are not members of the current site are ignored.

| Parameter | Type | Description |
|---|---|---|
| `$user_ids` | `array<int>\|WP_Error\|null` | User IDs, an error, or `null` when the type is not handled |
| `$type` | `string` | Filter type |
| `$request` | `array<string, mixed>` | Raw request data (unslashed, not sanitized) |
| `$current_user_id` | `int` | User running the search |

### `ubdwp_found_user_ids`
<Badge type="info" text="filter" /> <Badge text="since 2.4.0" />

Narrows the users found by the filters, for example to apply extra filters of another plugin. The result can only be narrowed: IDs that were not found are ignored.

| Parameter | Type | Description |
|---|---|---|
| `$user_ids` | `array<int>` | User IDs found |
| `$type` | `string` | Filter type |
| `$request` | `array<string, mixed>` | Raw request data (unslashed, not sanitized) |

```php
// Never delete users who have a "vip" user meta.
add_filter( 'ubdwp_found_user_ids', function ( $user_ids ) {
	return array_values( array_filter( $user_ids, fn( $id ) => ! get_user_meta( $id, 'vip', true ) ) );
} );
```

### `ubdwp_selected_user_ids`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

Changes the users chosen in **Choose from existing users**, for example to add users of a list. Users that are not members of the current site are ignored.

| Parameter | Type | Description |
|---|---|---|
| `$user_ids` | `array<int>` | User IDs chosen in the form |
| `$request` | `array<string, mixed>` | Raw request data (unslashed, not sanitized) |

### `ubdwp_search_error`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

Filters the validation error of a search. Return `null` to accept it, for example when `at_least_one_required` is reported but the request has criteria of your plugin. Register messages of new error codes with [`ubdwp_error_messages`](#ubdwp-error-messages).

| Parameter | Type | Description |
|---|---|---|
| `$error` | `string\|null` | Error code, or `null` when the search is valid |
| `$type` | `string` | Filter type |
| `$request` | `array<string, mixed>` | Request data: fields of the plugin are sanitized, other fields are raw |

### `ubdwp_error_messages`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

Messages of error codes the plugin does not know, for example codes of an add-on.

| Parameter | Type | Description |
|---|---|---|
| `$messages` | `array<string, string>` | Translated plain-text messages keyed by error code |

### `ubdwp_preview_limit`
<Badge type="info" text="filter" /> <Badge text="since 2.3.0" />

Maximum number of users one preview can contain. Default `0` (no limit).

## Filter form

All of these are actions that print table rows (`<tr>`). Give a row the class of a filter type (`ubdwp-filter-existing`, `ubdwp-filter-find`, `ubdwp-filter-woocommerce`, or `ubdwp-filter-type-{type}` for an added type) and `style="display: none;"` to show it only for that type. The fields are sent with the preview request; read them in `ubdwp_found_user_ids` or `ubdwp_custom_filter_user_ids`.

| Action | Since | Where it prints |
|---|---|---|
| `ubdwp_filter_form_top` | 2.5.0 | Top of the form, before the filter type selector; rows for every type |
| `ubdwp_filter_form_fields` | 2.4.0 | After the filter fields, inside the form table |
| `ubdwp_find_fields_account` | 2.5.0 | End of the **Account** group of "Find users according to certain criteria" |
| `ubdwp_find_fields_activity` | 2.5.0 | End of the **Activity** group |
| `ubdwp_find_fields_spam` | 2.5.0 | End of the **Spam signs** group |
| `ubdwp_find_fields_fraud` | 2.5.0 | End of the **Fraud signals** group (shown only when something is hooked) |
| `ubdwp_find_fields_advanced` | 2.5.0 | End of the **Advanced** group |
| `ubdwp_filter_form_buttons` | 2.5.0 | After the Preview button (buttons) |

## Deletion

### `ubdwp_is_protected_user`
<Badge type="info" text="filter" /> <Badge text="since 2.3.0" />

Whether a user is protected from deletion. Administrators, users who can manage other users and super admins are protected by default. Since 2.6.0, users protected by this filter who are not privileged themselves are shown with the reason *excluded from deletion by your settings*.

| Parameter | Type | Description |
|---|---|---|
| `$protected` | `bool` | Whether the user is protected |
| `$user` | `WP_User` | User being checked |
| `$current_user_id` | `int` | User performing the deletion |

```php
// Protect every user with the "editor" role.
add_filter( 'ubdwp_is_protected_user', function ( $protected, $user ) {
	return $protected || in_array( 'editor', (array) $user->roles, true );
}, 10, 2 );
```

### `ubdwp_before_delete_user`
<Badge type="tip" text="action" /> <Badge text="since 2.4.0" />

Fires before a user is deleted (or removed from the site on multisite), while the user and their content still exist.

| Parameter | Type | Description |
|---|---|---|
| `$user_id` | `int` | User ID |
| `$reassign` | `int\|string` | User ID that receives the content, `remove_all_related_content`, or `""` for the WordPress default |
| `$user` | `WP_User` | The user |

### `ubdwp_user_deleted`
<Badge type="tip" text="action" /> <Badge text="since 2.4.0" />

Fires after a user was deleted or removed from the site. The entry contains the full email and name, before the privacy setting of the log is applied.

| Parameter | Type | Description |
|---|---|---|
| `$user_id` | `int` | User ID |
| `$entry` | `array` | `user_id`, `email`, `display_name`, `reassign`, `action` (`deleted` or `removed_from_site`) |

### `ubdwp_job_created`
<Badge type="tip" text="action" /> <Badge text="since 2.4.0" />

Fires after a deletion job was created, before it is summarized and confirmed. `$job` is the job row (`ID`, `user_id`, `status`, `mode`, `total`, `user_ids`, …).

### `ubdwp_job_finished`
<Badge type="tip" text="action" /> <Badge text="since 2.4.0" />

Fires when a job is completed, cancelled or failed.

| Parameter | Type | Description |
|---|---|---|
| `$status` | `array<string, mixed>` | Job status, as shown on the admin page: `job_id`, `owner_id`, `status`, `mode`, `total`, `deleted`, `failed`, `summary`, `failed_users`, … |
| `$job` | `object` | Job row |

### Limits

| Filter | Since | Default | Description |
|---|---|---|---|
| `ubdwp_confirmation_threshold` | 2.3.0 | `20` | From this number of users, the number must be typed to confirm |
| `ubdwp_delete_batch_size` | 2.4.0 | `50` | Users deleted per batch (1–500) |
| `ubdwp_background_time_budget` | 2.4.0 | up to `20` | Seconds one background run may spend deleting (5–120) |

### `ubdwp_confirm_texts`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

The warnings shown before users are deleted, for example to say they can be restored. `$texts` has the keys `typing`, `plain` and `cli`; `typing` and `cli` contain `%d` for the number of users.

## Export

### `ubdwp_export_columns`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

Columns of the CSV export of selected users. Add columns here and fill them with `ubdwp_export_row`. The built-in columns cannot be removed.

| Parameter | Type | Description |
|---|---|---|
| `$columns` | `array<string, string>` | Column headers keyed by column |
| `$format` | `string` | `readable`, or `import` for importers (headers are field or meta names). Since 2.6.0. |

### `ubdwp_export_row`
<Badge type="info" text="filter" /> <Badge text="since 2.5.0" />

Values of a user in the CSV export, keyed by column.

| Parameter | Type | Description |
|---|---|---|
| `$row` | `array<string, mixed>` | Values keyed by column |
| `$user` | `WP_User` | User |
| `$columns` | `array<string, string>` | Column headers keyed by column |

```php
// Add the billing country to the export.
add_filter( 'ubdwp_export_columns', function ( $columns, $format ) {
	$columns['billing_country'] = 'import' === $format ? 'billing_country' : 'Billing country';
	return $columns;
}, 10, 2 );

add_filter( 'ubdwp_export_row', function ( $row, $user ) {
	$row['billing_country'] = get_user_meta( $user->ID, 'billing_country', true );
	return $row;
}, 10, 2 );
```

## Admin pages

| Hook | Type | Since | Description |
|---|---|---|---|
| `ubdwp_settings_tabs` | filter | 2.5.0 | `( $tabs )` tabs of the Settings page; `general` is the plugin's own tab |
| `ubdwp_settings_tab` | action | 2.5.0 | `( $tab )` print the content of an added tab |
| `ubdwp_settings_page` | action | 2.4.0 | Add sections at the end of the General tab |
| `ubdwp_logs_page_actions` | action | 2.5.0 | Print buttons above the Logs table |
| `ubdwp_plugin_pages` | filter | 2.5.0 | `( $pages )` page slugs that load the plugin styles and scripts and hide notices of other plugins |
| `ubdwp_keep_admin_notice` | filter | 2.5.0 | `( $keep, $callback )` keep a notice of another plugin on the plugin pages |
