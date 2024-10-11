<?php
/**
 * Users facade
 *
 * @package     UsersBulkDeleteWithPreview\Classes\Facades
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! class_exists( 'UBDWPUsersFacade' ) ) {
	/**
	 * Facade class for UBDWPUsers functionality.
	 * Inherits from the abstract class UBDWPFacadeAbstract.
	 */
	class UBDWPUsersFacade extends UBDWPFacadeAbstract {
		/**
		 * Get the name of the class that this facade represents.
		 *
		 * @return string The name of the class used by this facade.
		 */
		protected static function get_class_name(): string {
			return 'UBDWPUsers';
		}
	}
}
