<?php
/**
 * Plugin Name: Users Bulk Delete With Preview
 * Plugin URI: https://github.com/MaksymVasylchuk/users-bulk-delete-with-preview
 * Description: Effortlessly delete multiple WordPress users with our Users Bulk Delete With Preview plugin. View and confirm user details before removal to ensure accuracy and avoid mistakes. Streamline your user management process with ease and confidence!
 * Version: 2.2.2
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Tested up to: 7.1.2
 * Author: maksymvasylchuk
 * Author URI: https://github.com/MaksymVasylchuk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: users-bulk-delete-with-preview
 * Domain Path: /languages
 *
 * @package UsersBulkDeleteWithPreview
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Define plugin constants before activation hooks run.
defined( 'WPUBDP_PLUGIN_FILE' ) || define( 'WPUBDP_PLUGIN_FILE', __FILE__ );
defined( 'WPUBDP_PLUGIN_DIR' ) || define( 'WPUBDP_PLUGIN_DIR', plugin_dir_path( WPUBDP_PLUGIN_FILE ) );
defined( 'WPUBDP_PLUGIN_URL' ) || define( 'WPUBDP_PLUGIN_URL', plugin_dir_url( WPUBDP_PLUGIN_FILE ) );
defined( 'WPUBDP_PLUGIN_VERSION' ) || define( 'WPUBDP_PLUGIN_VERSION', '2.2.2' );
defined( 'WPUBDP_BASE_NAME' ) || define( 'WPUBDP_BASE_NAME', plugin_basename( WPUBDP_PLUGIN_FILE ) );

// Include Composer's autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

use UsersBulkDeleteWithPreview\Activators\UbdwpActivate;
use UsersBulkDeleteWithPreview\Core\UbdwpLoader;

register_activation_hook( WPUBDP_PLUGIN_FILE, array( UbdwpActivate::class, 'ubdwp_activate_plugin' ) );

add_action( 'plugins_loaded', function () {
	// Initialize actions, filters, pages, and assets.
	UbdwpLoader::get_instance();
});
