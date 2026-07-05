<?php
/**
 * Class for handling plugin activation
 *
 * @package     UsersBulkDeleteWithPreview\Activators
 */

namespace UsersBulkDeleteWithPreview\Activators;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for handling plugin activation logic.
 */
class UbdwpActivate {

	/**
	 * Activation callback for the Users Bulk Delete With Preview plugin.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 *
	 * @return void
	 */
	public static function ubdwp_activate_plugin( bool $network_wide = false ): void {
		// Ensure the environment meets the plugin requirements.
		self::check_environment();

		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array(
				'fields' => 'ids',
				'number' => 0,
			) );

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::setup_current_site();
				restore_current_blog();
			}

			return;
		}

		self::setup_current_site();
	}

	/**
	 * Initialize plugin data for a newly created multisite site.
	 *
	 * @param \WP_Site $site New site object.
	 *
	 * @return void
	 */
	public static function ubdwp_initialize_new_site( \WP_Site $site ): void {
		if ( ! is_multisite() ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active_for_network( WPUBDP_BASE_NAME ) ) {
			return;
		}

		switch_to_blog( (int) $site->blog_id );
		self::setup_current_site();
		restore_current_blog();
	}

	/**
	 * Ensure the current site's plugin data is up to date after plugin updates.
	 *
	 * @return void
	 */
	public static function ubdwp_maybe_upgrade_current_site(): void {
		if ( get_option( 'ubdwp_plugin_db_version' ) === WPUBDP_PLUGIN_VERSION ) {
			return;
		}

		self::setup_current_site();
	}

	/**
	 * Create or update the custom plugin table for the current site.
	 *
	 * @return void
	 */
	private static function setup_current_site(): void {
		global $wpdb;

		// Define the table name with WordPress table prefix.
		$table_name = "{$wpdb->prefix}ubdwp_logs";

		// Get the charset and collation for the current WordPress database.
		$charset_collate = $wpdb->get_charset_collate();

		// SQL statement to create the new table.
		$sql = "
        CREATE TABLE {$table_name} (
            ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) NOT NULL,
            user_deleted_data TEXT NOT NULL,
            deletion_time DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE='InnoDB' {$charset_collate};";

		// Include the WordPress upgrade functions.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create or update the database table.
		dbDelta( $sql );

		// Set the plugin version in the options table.
			update_option( 'ubdwp_plugin_db_version', WPUBDP_PLUGIN_VERSION );
	}

	/**
	 * Check if the environment meets the plugin requirements.
	 *
	 * @return void
	 */
	private static function check_environment(): void {
		// Check if the PHP version is at least 8.0.
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			wp_die(
				esc_html__(
					'This plugin requires PHP version 8.0 or higher.',
					'users-bulk-delete-with-preview'
				),
				esc_html__( 'Plugin Activation Error', 'users-bulk-delete-with-preview' ),
				array( 'back_link' => true )
			);
		}

		// Check if the WordPress version is at least 6.2.
		if ( version_compare( get_bloginfo( 'version' ), '6.2', '<' ) ) {
			wp_die(
				esc_html__(
					'You must update WordPress to version 6.2 or higher to use this plugin.',
					'users-bulk-delete-with-preview'
				),
				esc_html__( 'Plugin Activation Error', 'users-bulk-delete-with-preview' ),
				array( 'back_link' => true )
			);
		}
	}
}
