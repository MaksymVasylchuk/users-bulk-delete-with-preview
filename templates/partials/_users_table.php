<?php
/**
 * Users table
 *
 * @package WPUserBulkDeleteWithPreviw\Templates\Partials
 */

?>
<!-- Users table preview -->
<div class="form-group mb-4">
	<label for="generalSelect"><?php esc_html_e( 'Assign related content to user', 'users-bulk-delete-with-preview' ); ?>:</label>
	<br>
	<!-- General select dropdown outside the table -->
	<select id="generalSelect" class="general-select">
	</select>
</div>
<input type="hidden" id="delete_users_nonce" name="delete_users_nonce" value="<?php echo esc_attr( wp_create_nonce( 'delete_users_nonce' ) ); ?>">
<input type="hidden" name="action" value="delete_users_action" id="delete_users_action">
<input type="hidden" id="export_users_nonce" name="export_users_nonce" value="<?php echo esc_attr( wp_create_nonce( 'export_users_nonce' ) ); ?>">

<form action="#" method="post" id="select_users_for_delete">

	<table id="userTable" class="display" style="width:100%">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Select', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'ID', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Username', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Email', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Registered', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Role', 'users-bulk-delete-with-preview' ); ?></th>
			<th><?php esc_html_e( 'Assign related content to user', 'users-bulk-delete-with-preview' ); ?></th>
		</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</form>
<!-- Users table preview -->
