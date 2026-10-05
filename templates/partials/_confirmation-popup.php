<?php
/**
 * Confirmation popup
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
<!-- Confirmation Modal -->
<dialog id="confirmModal" class="ubdwp-dialog" aria-labelledby="confirmModalLabel">
	<div class="ubdwp-dialog-content">
			<div class="ubdwp-dialog-header">
				<h2 class="ubdwp-dialog-title" id="confirmModalLabel"><?php esc_html_e( 'Confirm Deletion', 'users-bulk-delete-with-preview' ); ?></h2>
				<button type="button" class="ubdwp-dialog-close" data-ubdwp-close aria-label="<?php esc_attr_e( 'Close', 'users-bulk-delete-with-preview' ); ?>"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="ubdwp-dialog-body">
				<ul id="ubdwp_delete_summary" class="ubdwp-delete-summary"></ul>
				<table id="ubdwp_delete_skipped" class="wp-list-table widefat fixed striped" style="display: none;">
					<thead>
					<tr>
						<th><?php esc_html_e( 'User ID', 'users-bulk-delete-with-preview' ); ?></th>
						<th><?php esc_html_e( 'Username', 'users-bulk-delete-with-preview' ); ?></th>
						<th><?php esc_html_e( 'Email', 'users-bulk-delete-with-preview' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'users-bulk-delete-with-preview' ); ?></th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
				<fieldset id="ubdwp_delete_mode" class="ubdwp-delete-mode">
					<legend><?php esc_html_e( 'How to run the deletion', 'users-bulk-delete-with-preview' ); ?></legend>
					<label>
						<input type="radio" name="ubdwp_delete_mode" value="browser" checked>
						<?php esc_html_e( 'In this browser tab (keep the page open)', 'users-bulk-delete-with-preview' ); ?>
					</label>
					<label>
						<input type="radio" name="ubdwp_delete_mode" value="background">
						<?php esc_html_e( 'In the background (you can leave this page; progress is shown on the Deletion Jobs page)', 'users-bulk-delete-with-preview' ); ?>
					</label>
				</fieldset>
				<p id="ubdwp_confirm_text" class="ubdwp-confirm-text"></p>
				<p id="ubdwp_confirm_typing" style="display: none;">
					<label for="ubdwp_confirm_input" class="screen-reader-text"><?php esc_html_e( 'Number of users to delete', 'users-bulk-delete-with-preview' ); ?></label>
					<input type="text" id="ubdwp_confirm_input" inputmode="numeric" autocomplete="off">
				</p>
			</div>
			<div class="ubdwp-dialog-footer">
				<button type="button" id="ubdwp_cancel_delete" class="button button-secondary" data-ubdwp-close><?php esc_html_e( 'Cancel', 'users-bulk-delete-with-preview' ); ?></button>
				<button type="button" id="confirmDelete" class="button button-primary ubdwp-button-danger"><?php esc_html_e( 'Delete', 'users-bulk-delete-with-preview' ); ?></button>
			</div>
	</div>
</dialog>
<!-- Confirmation Modal -->