<?php
/**
 * Confirmation popup
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="confirmModalLabel"><?php esc_html_e( 'Confirm Deletion', 'users-bulk-delete-with-preview' ); ?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'users-bulk-delete-with-preview' ); ?>"></button>
			</div>
			<div class="modal-body">
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
				<p id="ubdwp_confirm_text" class="ubdwp-confirm-text"></p>
				<p id="ubdwp_confirm_typing" style="display: none;">
					<label for="ubdwp_confirm_input" class="screen-reader-text"><?php esc_html_e( 'Number of users to delete', 'users-bulk-delete-with-preview' ); ?></label>
					<input type="text" id="ubdwp_confirm_input" inputmode="numeric" autocomplete="off">
				</p>
			</div>
			<div class="modal-footer">
				<button type="button" id="ubdwp_cancel_delete" class="button button-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'users-bulk-delete-with-preview' ); ?></button>
				<button type="button" id="confirmDelete" class="button button-primary ubdwp-button-danger"><?php esc_html_e( 'Delete', 'users-bulk-delete-with-preview' ); ?></button>
			</div>
		</div>
	</div>
</div>
<!-- Confirmation Modal -->