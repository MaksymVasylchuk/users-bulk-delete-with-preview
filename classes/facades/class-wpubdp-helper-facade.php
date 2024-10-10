<?php
/**
 * Helper facade
 *
 * @package     WPUserBulkDeleteWithPreviw\Classes\Facades
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! class_exists( 'WPUBDPHelperFacade' ) ) {
	/**
	 * Facade class for the WPUBDPHelper functionality.
	 * Extends the abstract class WPUBDPFacadeAbstract.
	 */
	class WPUBDPHelperFacade extends WPUBDPFacadeAbstract {
		/**
		 * Get the class name that this facade is representing.
		 *
		 * @return string The name of the class being used by this facade.
		 */
		protected static function get_class_name(): string {
			return 'WPUBDPHelper';
		}
	}
}
