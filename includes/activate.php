<?php
/**
 * Activation callback for the Users Bulk Delete With Preview plugin.
 *
 * This function is called when the plugin is activated. It performs the following actions:
 * 1. Checks the WordPress version to ensure compatibility.
 * 2. Creates the required database table if it does not already exist.
 * 3. Sets the plugin version in the options table.
 *
 * @package     WPUserBulkDeleteWithPreviw\Includes.
 */

if ( ! function_exists( 'wpubdp_activate_plugin' ) ) {
	/**
	 * Activation callback for the Users Bulk Delete With Preview plugin.
	 *
	 * @return void
	 */
	function wpubdp_activate_plugin() {
		// Check if the WordPress version is at least 6.4.
		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			// Display an error message and stop the activation process if the WordPress version is too old.
			wp_die(
				esc_html__(
					'You must update WordPress to use the plugin.',
					'users_bulk_delete_with_preview'
				)
			);
		}

		global $wpdb;

		// Define the table name with WordPress table prefix.
		$table_name = "{$wpdb->prefix}wpubdp_logs";

		// Get the charset and collation for the current WordPress database.
		$charset_collate = $wpdb->get_charset_collate();

		// SQL statement to create the new table.
		$sql = "
        CREATE TABLE {$table_name} (
            ID bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) NOT NULL,
            user_deleted_data TEXT NOT NULL,
            deletion_time DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE='InnoDB' {$charset_collate};";

		// Include the WordPress upgrade functions.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create or update the database table.
		dbDelta( $sql );

		// Set the plugin version in the options table.
		add_option( 'wpubdp_plugin_db_version', WPUBDP_PLUGIN_VERSION );
	}
}
