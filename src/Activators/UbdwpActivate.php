<?php


namespace UsersBulkDeleteWithPreview\Activators;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class UbdwpActivate {

	/**
	 * Activation callback for the Users Bulk Delete With Preview plugin.
	 *
	 * @return void
	 */
	public static function ubdwp_activate_plugin(): void {
		self::check_environment();

		global $wpdb;

		// Define the table name with WordPress table prefix.
		$table_name = "{$wpdb->prefix}ubdwp_logs";

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
		add_option( 'ubdwp_plugin_db_version', WPUBDP_PLUGIN_VERSION );
	}

	/**
	 * Check if the environment meets the plugin requirements.
	 *
	 * @return void
	 */
	private static function check_environment(): void {
		// Check PHP version.
		if (version_compare(PHP_VERSION, '7.4', '<')) {
			wp_die(
				esc_html__(
					'This plugin requires PHP version 7.4 or higher.',
					'users-bulk-delete-with-preview'
				),
				esc_html__('Plugin Activation Error', 'users-bulk-delete-with-preview'),
				['back_link' => true]
			);
		}

		// Check WordPress version.
		if (version_compare(get_bloginfo('version'), '6.0', '<')) {
			wp_die(
				esc_html__(
					'You must update WordPress to version 6.0 or higher to use this plugin.',
					'users-bulk-delete-with-preview'
				),
				esc_html__('Plugin Activation Error', 'users-bulk-delete-with-preview'),
				['back_link' => true]
			);
		}
	}
}