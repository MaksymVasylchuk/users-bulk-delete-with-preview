<?php
/**
 * Deletion Jobs Page
 *
 * @package UsersBulkDeleteWithPreview\Templates
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

$title = $title ?? '';
$jobs  = $jobs ?? array();

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

?>
<!-- Deletion jobs page -->
<div class="wrap ubdwp-page">
	<h2><?php echo esc_html( $title ); ?></h2>
	<div id="poststuff_logs">
		<div id="post-body" class="metabox-holder columns-1">
			<div id="notices">
			</div>
			<p class="description"><?php esc_html_e( 'Deletions started on the Bulk Users Delete page or with WP-CLI. Background jobs continue after you leave the page; this page refreshes while a job is running.', 'users-bulk-delete-with-preview' ); ?></p>
			<?php if ( empty( $jobs ) ) : ?>
				<p><?php esc_html_e( 'No deletion jobs yet.', 'users-bulk-delete-with-preview' ); ?></p>
			<?php else : ?>
			<!-- Deletion jobs -->
			<table id="ubdwp_jobs" class="wp-list-table widefat fixed striped ubdwp-jobs">
				<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Started by', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Mode', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Status', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Progress', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Deleted', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Not deleted', 'users-bulk-delete-with-preview' ); ?></th>
					<th><?php esc_html_e( 'Created', 'users-bulk-delete-with-preview' ); ?></th>
					<th><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'users-bulk-delete-with-preview' ); ?></span></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $jobs as $job ) : ?>
					<?php
					$owner = get_userdata( (int) $job['owner_id'] );
					$modes = array(
						'browser'    => __( 'Browser', 'users-bulk-delete-with-preview' ),
						'background' => __( 'Background', 'users-bulk-delete-with-preview' ),
						'cli'        => __( 'WP-CLI', 'users-bulk-delete-with-preview' ),
					);
					?>
					<tr>
						<td><?php echo esc_html( $job['job_id'] ); ?></td>
						<td><?php echo esc_html( $owner ? $owner->user_login : '#' . $job['owner_id'] ); ?></td>
						<td><?php echo esc_html( $modes[ $job['mode'] ] ?? $job['mode'] ); ?></td>
						<td>
							<?php echo esc_html( $job['status_label'] ); ?>
							<?php if ( '' !== $job['message'] ) : ?>
								<br><span class="description"><?php echo esc_html( $job['message'] ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( sprintf( '%d / %d (%d%%)', $job['position'], $job['total'], $job['percent'] ) ); ?></td>
						<td><?php echo esc_html( $job['deleted'] ); ?></td>
						<td><?php echo esc_html( $job['failed'] ); ?></td>
						<td><?php echo esc_html( get_date_from_gmt( $job['created_at'], 'Y-m-d H:i' ) ); ?></td>
						<td>
							<?php if ( ! empty( $can_cancel ) && ! $job['finished'] ) : ?>
								<button type="button" class="button button-secondary ubdwp-cancel-job" data-job-id="<?php echo esc_attr( $job['job_id'] ); ?>"><?php esc_html_e( 'Cancel', 'users-bulk-delete-with-preview' ); ?></button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>
</div>
<!-- Deletion jobs page -->
