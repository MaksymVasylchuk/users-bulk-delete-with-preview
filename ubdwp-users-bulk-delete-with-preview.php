<?php
/**
 * Plugin Name: Users Bulk Delete With Preview
 * Plugin URI: https://github.com/MaksymVasylchuk/users-bulk-delete-with-preview
 * Description: Effortlessly delete multiple WordPress users with our Users Bulk Delete With Preview plugin. View and confirm user details before removal to ensure accuracy and avoid mistakes. Streamline your user management process with ease and confidence!
 * Version: 1.1.1
 * Requires at least 6.2
 * Tested up to: 6.6.2
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

// Include Composer's autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

use UsersBulkDeleteWithPreview\Activators\UbdwpActivate;
use UsersBulkDeleteWithPreview\Core\UbdwpLoader;


add_action('init', function () {
	//Init constants, actions, filters, etc.
	UbdwpLoader::get_instance()->initialize();

	register_activation_hook(__FILE__, array(UbdwpActivate::class, 'ubdwp_activate_plugin'));
});
