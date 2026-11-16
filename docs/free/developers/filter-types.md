# Adding a filter type

A filter type is an entry of **Choose filter type** with its own form rows. This example adds **Find users who never logged in to the forum**, based on a user meta key of a forum plugin.

Four hooks are involved:

1. [`ubdwp_filter_types`](./hooks#ubdwp-filter-types) adds the type to the list.
2. [`ubdwp_filter_form_fields`](./hooks#filter-form) prints its form rows.
3. [`ubdwp_search_error`](./hooks#ubdwp-search-error) accepts the request (unknown types are rejected with `select_type` by default).
4. [`ubdwp_custom_filter_user_ids`](./hooks#ubdwp-custom-filter-user-ids) returns the users found.

```php
<?php
/**
 * Plugin Name: Forum filter for Users Bulk Delete With Preview
 */

const MY_FORUM_TYPE = 'my_forum_inactive';

// 1. The type in "Choose filter type".
add_filter( 'ubdwp_filter_types', function ( $types ) {
	$types[ MY_FORUM_TYPE ] = __( 'Find users who never posted in the forum', 'my-forum-filter' );
	return $types;
} );

// 2. Its form rows: the class "ubdwp-filter-type-{type}" shows them only for this type.
add_action( 'ubdwp_filter_form_fields', function () {
	?>
	<tr class="ubdwp-filter-type-<?php echo esc_attr( MY_FORUM_TYPE ); ?>" style="display: none;">
		<th scope="row"><label for="my_forum_days"><?php esc_html_e( 'Registered more than', 'my-forum-filter' ); ?></label></th>
		<td><input type="number" id="my_forum_days" name="my_forum_days" min="1" value="30"> <?php esc_html_e( 'days ago', 'my-forum-filter' ); ?></td>
	</tr>
	<?php
} );

// 3. Accept the request of this type.
add_filter( 'ubdwp_search_error', function ( $error, $type, $request ) {
	if ( MY_FORUM_TYPE !== $type ) {
		return $error;
	}

	return absint( $request['my_forum_days'] ?? 0 ) > 0 ? null : 'my_forum_days_missing';
}, 10, 3 );

add_filter( 'ubdwp_error_messages', function ( $messages ) {
	$messages['my_forum_days_missing'] = __( 'Please enter a number of days.', 'my-forum-filter' );
	return $messages;
} );

// 4. The users of this type. The request is raw: sanitize it.
add_filter( 'ubdwp_custom_filter_user_ids', function ( $user_ids, $type, $request ) {
	if ( MY_FORUM_TYPE !== $type ) {
		return $user_ids;
	}

	$days = absint( $request['my_forum_days'] ?? 0 );

	return get_users( array(
		'fields'       => 'ID',
		'date_query'   => array( array( 'before' => $days . ' days ago' ) ),
		'meta_key'     => 'forum_post_count', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_compare' => 'NOT EXISTS',
	) );
}, 10, 3 );
```

What the plugin does for you:

- the users are checked against the current site and the protected users;
- the preview, export, confirmation, deletion, jobs and log work as for the built-in types;
- your own account is never deleted.

To add a field to an existing type instead (for example to **Find users according to certain criteria**), print a row with the class `ubdwp-filter-find` in one of the `ubdwp_find_fields_*` actions and narrow the result with [`ubdwp_found_user_ids`](./hooks#ubdwp-found-user-ids).
