<?php
/**
 * Logs Page
 *
 * @package UsersBulkDeleteWithPreview\Templates
 */

$title = $title ?? '';

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

?>
<!-- Logs page -->
<div class="wrap">
	<h2><?php echo esc_html( $title ); ?></h2>
	<div id="poststuff_logs">
		<div id="post-body" class="metabox-holder columns-1">

            <input type="hidden" id="logs_datatable_nonce" name="logs_datatable_nonce" value="<?php echo esc_attr( wp_create_nonce( 'logs_datatable_nonce' ) ); ?>">

			<div id="notices">
			</div>
			<!-- Logs table -->
			<table id="logs" class="display" style="width:100%">
				<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'User', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Deleted Users Count', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Deleted User Data', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Deletion Time', 'users-bulk-delete-with-preview' ); ?></th>
				</tr>
				</thead>
			</table>
			<!-- Logs table -->

		</div>
	</div>
</div>
<!-- Logs page -->