<?php
/**
 * Settings Page
 *
 * @package UsersBulkDeleteWithPreview\Templates
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

$title       = $title ?? '';
$privacy     = $privacy ?? array( 'retention_days' => 0, 'log_email' => 'masked' );
$tabs        = $tabs ?? array( 'general' => __( 'General', 'users-bulk-delete-with-preview' ) );
$current_tab = $current_tab ?? 'general';
$page_url    = $page_url ?? '';

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

?>
<!-- Settings page -->
<div class="wrap ubdwp-page ubdwp-settings-page">
	<h2><?php echo esc_html( $title ); ?></h2>
	<?php if ( count( $tabs ) > 1 ) : ?>
		<nav class="nav-tab-wrapper ubdwp-settings-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'users-bulk-delete-with-preview' ); ?>">
			<?php foreach ( $tabs as $tab => $label ) : ?>
				<a href="<?php echo esc_url( 'general' === $tab ? $page_url : add_query_arg( 'tab', $tab, $page_url ) ); ?>" class="nav-tab<?php echo $tab === $current_tab ? ' nav-tab-active' : ''; ?>"<?php echo $tab === $current_tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>
	<div id="ubdwp_poststuff">
		<div id="post-body" class="metabox-holder columns-1">
			<div id="ubdwp_notices">
			</div>
			<?php if ( 'general' !== $current_tab ) : ?>
				<?php
				/**
				 * Fires to print the content of a tab added with "ubdwp_settings_tabs".
				 *
				 * @since 2.5.0
				 *
				 * @param string $tab Current tab.
				 */
				do_action( 'ubdwp_settings_tab', $current_tab );
				?>
			<?php else : ?>
			<section class="ubdwp-card" aria-labelledby="ubdwp_privacy_title">
				<header class="ubdwp-card-header">
					<h3 class="ubdwp-card-title" id="ubdwp_privacy_title"><span class="dashicons dashicons-privacy" aria-hidden="true"></span> <?php esc_html_e( 'Privacy', 'users-bulk-delete-with-preview' ); ?></h3>
					<p class="ubdwp-card-description"><?php esc_html_e( 'What the deletion log keeps about deleted users, and for how long.', 'users-bulk-delete-with-preview' ); ?></p>
				</header>
				<input type="hidden" id="ubdwp_privacy_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_privacy' ) ); ?>">
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
				<footer class="ubdwp-card-footer">
					<button type="button" class="button button-primary ubdwp-privacy-save"><?php esc_html_e( 'Save privacy settings', 'users-bulk-delete-with-preview' ); ?></button>
				</footer>
			</section>
			<?php if ( ! empty( $can_maintain ) ) : ?>
			<section class="ubdwp-card" aria-labelledby="ubdwp_maintenance_title">
				<header class="ubdwp-card-header">
					<h3 class="ubdwp-card-title" id="ubdwp_maintenance_title"><span class="dashicons dashicons-database" aria-hidden="true"></span> <?php esc_html_e( 'Log maintenance', 'users-bulk-delete-with-preview' ); ?></h3>
					<p class="ubdwp-card-description">
						<?php esc_html_e( 'These actions change the existing entries of the log and cannot be undone.', 'users-bulk-delete-with-preview' ); ?>
						<a href="<?php echo esc_url( $logs_url ?? '' ); ?>"><?php esc_html_e( 'Open the Logs page', 'users-bulk-delete-with-preview' ); ?></a>
					</p>
				</header>
				<footer class="ubdwp-card-footer ubdwp-privacy-actions">
					<button type="button" class="button button-secondary ubdwp-logs-anonymize"><?php esc_html_e( 'Apply to existing entries', 'users-bulk-delete-with-preview' ); ?></button>
					<button type="button" class="button button-secondary ubdwp-logs-purge-old"><?php esc_html_e( 'Delete entries older than the period above', 'users-bulk-delete-with-preview' ); ?></button>
					<button type="button" class="button button-secondary ubdwp-button-danger-outline ubdwp-logs-purge-all"><?php esc_html_e( 'Delete the whole log', 'users-bulk-delete-with-preview' ); ?></button>
				</footer>
			</section>
			<?php endif; ?>
			<?php
			/**
			 * Fires at the end of the General tab of the Settings page, to add sections of other plugins.
			 *
			 * @since 2.4.0
			 */
			do_action( 'ubdwp_settings_page' );
			?>
			<?php endif; ?>
		</div>
	</div>
</div>
<!-- Settings page -->
