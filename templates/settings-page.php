<?php
/**
 * Settings Page
 *
 * @package UsersBulkDeleteWithPreview\Templates
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

$title   = $title ?? '';
$privacy = $privacy ?? array( 'retention_days' => 0, 'log_email' => 'masked' );

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

?>
<!-- Settings page -->
<div class="wrap ubdwp-page">
	<h2><?php echo esc_html( $title ); ?></h2>
	<div id="poststuff_logs">
		<div id="post-body" class="metabox-holder columns-1">
			<div id="notices">
			</div>
			<h3><?php esc_html_e( 'Privacy', 'users-bulk-delete-with-preview' ); ?></h3>
			<input type="hidden" id="privacy_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_privacy' ) ); ?>">
			<table class="form-table ubdwp-privacy" role="presentation">
				<tr>
					<th scope="row"><label for="ubdwp_retention_days"><?php esc_html_e( 'Keep log entries for', 'users-bulk-delete-with-preview' ); ?></label></th>
					<td>
						<div class="ubdwp-field-row">
							<input type="number" id="ubdwp_retention_days" class="ubdwp-short" min="0" max="3650" step="1" value="<?php echo esc_attr( $privacy['retention_days'] ); ?>">
							<span><?php esc_html_e( 'days', 'users-bulk-delete-with-preview' ); ?></span>
						</div>
						<p class="description"><?php esc_html_e( 'Older entries and finished deletion jobs are deleted automatically once a day. 0 keeps them until you delete them.', 'users-bulk-delete-with-preview' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ubdwp_log_email"><?php esc_html_e( 'Emails and names in new entries', 'users-bulk-delete-with-preview' ); ?></label></th>
					<td>
						<select id="ubdwp_log_email">
							<option value="masked" <?php selected( $privacy['log_email'], 'masked' ); ?>><?php esc_html_e( 'Store masked (j***@example.com)', 'users-bulk-delete-with-preview' ); ?></option>
							<option value="full" <?php selected( $privacy['log_email'], 'full' ); ?>><?php esc_html_e( 'Store in full', 'users-bulk-delete-with-preview' ); ?></option>
							<option value="none" <?php selected( $privacy['log_email'], 'none' ); ?>><?php esc_html_e( 'Do not store (only user IDs)', 'users-bulk-delete-with-preview' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'The log is also included in Tools → Export Personal Data and Erase Personal Data.', 'users-bulk-delete-with-preview' ); ?></p>
					</td>
				</tr>
			</table>
			<p class="submit ubdwp-privacy-actions">
				<button type="button" class="button button-primary ubdwp-privacy-save"><?php esc_html_e( 'Save privacy settings', 'users-bulk-delete-with-preview' ); ?></button>
			</p>
			<?php if ( ! empty( $can_maintain ) ) : ?>
			<h3><?php esc_html_e( 'Log maintenance', 'users-bulk-delete-with-preview' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'These actions change the existing entries of the log and cannot be undone.', 'users-bulk-delete-with-preview' ); ?>
				<a href="<?php echo esc_url( $logs_url ?? '' ); ?>"><?php esc_html_e( 'Open the Logs page', 'users-bulk-delete-with-preview' ); ?></a>
			</p>
			<p class="submit ubdwp-privacy-actions">
				<button type="button" class="button button-secondary ubdwp-logs-anonymize"><?php esc_html_e( 'Apply to existing entries', 'users-bulk-delete-with-preview' ); ?></button>
				<button type="button" class="button button-secondary ubdwp-logs-purge-old"><?php esc_html_e( 'Delete entries older than the period above', 'users-bulk-delete-with-preview' ); ?></button>
				<button type="button" class="button button-secondary ubdwp-button-danger-outline ubdwp-logs-purge-all"><?php esc_html_e( 'Delete the whole log', 'users-bulk-delete-with-preview' ); ?></button>
			</p>
			<?php endif; ?>
			<?php
			/**
			 * Fires at the end of the plugin Settings page, to add sections of other plugins.
			 *
			 * @since 2.4.0
			 */
			do_action( 'ubdwp_settings_page' );
			?>
		</div>
	</div>
</div>
<!-- Settings page -->
