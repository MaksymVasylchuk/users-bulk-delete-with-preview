<?php
/**
 * Logs facade
 *
 * @package     UsersBulkDeleteWithPreview\Classes\Facades
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! class_exists( 'WPUBDPLogsFacade' ) ) {
	/**
	 * Facade class for WPUBDPLogs functionality.
	 * Extends the abstract class WPUBDPFacadeAbstract.
	 */
	class WPUBDPLogsFacade extends WPUBDPFacadeAbstract {
		/**
		 * Retrieve the name of the class that this facade represents.
		 *
		 * @return string The name of the class used by this facade.
		 */
		protected static function get_class_name(): string {
			return 'WPUBDPLogs';
		}
	}
}
