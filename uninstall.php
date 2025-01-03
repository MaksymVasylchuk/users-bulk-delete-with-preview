<?php
/**
 * Uninstall callback for the Users Bulk Delete With Preview plugin.
 *
 * This function is called when the plugin is uninstall. It performs the following actions:
 * 1. Checks ability to uninstall plugins.
 * 2. Drop custom plugin table.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option('ubdwp_plugin_db_version');

global $wpdb;
$wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS %i", "{$wpdb->prefix}ubdwp_logs") ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Drop custom plugin table, in this case cache is not needed and we only delete custom table for this plugin.
