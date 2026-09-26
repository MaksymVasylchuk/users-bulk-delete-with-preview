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
	 * Drop the plugin log table together with a deleted multisite site.
	 *
	 * @param array<string> $tables  Tables WordPress will drop for the site.
	 * @param int           $site_id ID of the site being deleted.
	 *
	 * @return array<string> Tables to drop.
	 */
	public static function ubdwp_drop_site_tables( array $tables, int $site_id ): array {
		global $wpdb;

		$tables[] = $wpdb->get_blog_prefix( $site_id ) . 'ubdwp_logs';

		return $tables;
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

		self::remove_legacy_export_files();

		// Set the plugin version in the options table.
		update_option( 'ubdwp_plugin_db_version', WPUBDP_PLUGIN_VERSION );
	}

	/**
	 * Remove CSV exports stored in uploads by plugin versions before 2.2.1.
	 *
	 * @return void
	 */
	private static function remove_legacy_export_files(): void {
		$upload_dir = wp_upload_dir( null, false );

		if ( empty( $upload_dir['basedir'] ) ) {
			return;
		}

		$export_dir = trailingslashit( $upload_dir['basedir'] ) . 'ubdwp-exports';

		if ( ! is_dir( $export_dir ) ) {
			return;
		}

		$files = array_merge(
			glob( trailingslashit( $export_dir ) . 'users_export_*.csv' ) ?: array(),
			glob( trailingslashit( $export_dir ) . 'index.php' ) ?: array()
		);

		foreach ( $files as $file ) {
			wp_delete_file( $file );
		}

		@rmdir( $export_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Directory is only removed when empty.
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
