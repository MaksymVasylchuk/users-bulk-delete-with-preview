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

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ubdwp_logs" );// db call ok; no-cache ok.
