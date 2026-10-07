<?php
/**
 * Users table
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- Users table preview -->
<div id="ubdwp_selection_info" class="ubdwp-selection-info" style="display: none;" aria-live="polite">
	<span class="ubdwp-selection-text"></span>
	<button type="button" class="button-link ubdwp-select-all-matching"></button>
	<button type="button" class="button-link ubdwp-clear-selection"><?php esc_html_e( 'Clear selection', 'users-bulk-delete-with-preview' ); ?></button>
</div>
<div class="ubdwp-general-reassign">
	<label for="ubdwp_general_select"><?php esc_html_e( 'Assign related content to user', 'users-bulk-delete-with-preview' ); ?>:</label>
	<!-- General select dropdown outside the table -->
	<select id="ubdwp_general_select" class="general-select">
	</select>
</div>
<input type="hidden" id="ubdwp_delete_users_nonce" name="delete_users_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_delete_users' ) ); ?>">
<input type="hidden" name="action" value="ubdwp_delete_users" id="ubdwp_delete_users_action">
<input type="hidden" id="ubdwp_export_users_nonce" name="export_users_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_export_users' ) ); ?>">

<form action="#" method="post" id="ubdwp_select_users_for_delete">

	<table id="ubdwp_user_table" class="display" style="width:100%">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Select', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'ID', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Username', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Email', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Registered', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Role', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Posts', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Assign related content to user', 'users-bulk-delete-with-preview' ); ?></th>
		</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</form>
<!-- Users table preview -->
