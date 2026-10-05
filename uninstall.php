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

/**
 * Drop plugin data for the current site.
 *
 * @return void
 */
function ubdwp_uninstall_current_site(): void {
	global $wpdb;

	delete_option( 'ubdwp_plugin_db_version' );
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', "{$wpdb->prefix}ubdwp_logs" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Drop custom plugin table, in this case cache is not needed and we only delete custom table for this plugin.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', "{$wpdb->prefix}ubdwp_jobs" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Drop custom plugin table, in this case cache is not needed and we only delete custom table for this plugin.
	wp_clear_scheduled_hook( 'ubdwp_run_deletion_job' );
	wp_clear_scheduled_hook( 'ubdwp_daily_cleanup' );
	delete_option( 'ubdwp_privacy' );

	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'ubdwp_run_deletion_job', array(), 'ubdwp' );
	}
}

if ( is_multisite() ) {
	$ubdwp_site_ids = get_sites( array(
		'fields' => 'ids',
		'number' => 0,
	) );

	foreach ( $ubdwp_site_ids as $ubdwp_site_id ) {
		switch_to_blog( (int) $ubdwp_site_id );
		ubdwp_uninstall_current_site();
		restore_current_blog();
	}

	return;
}

ubdwp_uninstall_current_site();
