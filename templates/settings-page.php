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
$pro_url     = $pro_url ?? '';

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
			<section class="ubdwp-card" aria-labelledby="ubdwp_export_title">
				<header class="ubdwp-card-header">
					<h3 class="ubdwp-card-title" id="ubdwp_export_title"><span class="dashicons dashicons-media-spreadsheet" aria-hidden="true"></span> <?php esc_html_e( 'Export', 'users-bulk-delete-with-preview' ); ?></h3>
					<p class="ubdwp-card-description"><?php esc_html_e( 'The CSV file of "Export Selected Users" on the Bulk Users Delete page.', 'users-bulk-delete-with-preview' ); ?></p>
				</header>
				<table class="form-table ubdwp-export-settings" role="presentation">
					<tr>
						<th scope="row"><label for="ubdwp_export_format"><?php esc_html_e( 'CSV format', 'users-bulk-delete-with-preview' ); ?></label></th>
						<td>
							<select id="ubdwp_export_format">
								<option value="readable" <?php selected( $export_format ?? 'readable', 'readable' ); ?>><?php esc_html_e( 'Readable (ID, Username, Email, First Name, Last Name, Role)', 'users-bulk-delete-with-preview' ); ?></option>
								<option value="import" <?php selected( $export_format ?? 'readable', 'import' ); ?>><?php esc_html_e( 'For importing (user_login, user_email, first_name, last_name, display_name, role, user_registered, user_url)', 'users-bulk-delete-with-preview' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'The import format uses the field names of WordPress, so the file can be imported on another site with "wp user import-csv" or a user import plugin. It has no ID column (importers would overwrite the user with the same ID there) and only the main role of each user. Passwords are never exported.', 'users-bulk-delete-with-preview' ); ?></p>
						</td>
					</tr>
				</table>
				<footer class="ubdwp-card-footer">
					<button type="button" class="button button-primary ubdwp-export-save"><?php esc_html_e( 'Save export settings', 'users-bulk-delete-with-preview' ); ?></button>
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
			<?php if ( ! empty( $pro_url ) ) : ?>
			<section class="ubdwp-card ubdwp-pro-card" aria-labelledby="ubdwp_pro_title">
				<header class="ubdwp-card-header">
					<h3 class="ubdwp-card-title" id="ubdwp_pro_title"><span class="dashicons dashicons-star-filled" aria-hidden="true"></span> <?php esc_html_e( 'Pro add-on', 'users-bulk-delete-with-preview' ); ?></h3>
					<p class="ubdwp-card-description"><?php esc_html_e( 'Everything in this plugin stays free. The paid add-on adds:', 'users-bulk-delete-with-preview' ); ?></p>
				</header>
				<ul class="ubdwp-pro-list">
					<li><?php esc_html_e( 'Recycle bin: restore deleted users with their password, profile, roles and posts.', 'users-bulk-delete-with-preview' ); ?></li>
					<li><?php esc_html_e( 'Scheduled cleanups with saved filters, reports and warning emails.', 'users-bulk-delete-with-preview' ); ?></li>
					<li><?php esc_html_e( 'Last login, spam and fraud filters, WooCommerce, membership and LMS filters.', 'users-bulk-delete-with-preview' ); ?></li>
					<li><?php esc_html_e( 'Protection: never delete chosen users, and stop deleted users from registering again.', 'users-bulk-delete-with-preview' ); ?></li>
				</ul>
				<footer class="ubdwp-card-footer">
					<a class="button button-secondary" href="<?php echo esc_url( $pro_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more and try it for 7 days', 'users-bulk-delete-with-preview' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'users-bulk-delete-with-preview' ); ?></span></a>
				</footer>
			</section>
			<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<!-- Settings page -->
