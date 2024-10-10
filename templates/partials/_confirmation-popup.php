<?php
/**
 * Confirmation popup
 *
 * @package WPUserBulkDeleteWithPreviw\Templates\Partials
 */

?>
<!-- Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="confirmModalLabel"><?php esc_html_e( 'Confirm Deletion', 'users_bulk_delete_with_preview' ); ?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="<?php esc_html_e( 'Close', 'users_bulk_delete_with_preview' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<?php esc_html_e( 'Are you sure you want to delete this(these) user(s)?', 'users_bulk_delete_with_preview' ); ?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal"><?php esc_html_e( 'Cancel', 'users_bulk_delete_with_preview' ); ?></button>
				<button type="button" id="confirmDelete" class="btn btn-danger"><?php esc_html_e( 'Delete', 'users_bulk_delete_with_preview' ); ?></button>
			</div>
		</div>
	</div>
</div>
<!-- Confirmation Modal -->