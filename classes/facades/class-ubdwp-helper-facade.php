<?php
/**
 * Helper facade
 *
 * @package     UsersBulkDeleteWithPreview\Classes\Facades
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! class_exists( 'UBDWPHelperFacade' ) ) {
	/**
	 * Facade class for the UBDWPHelper functionality.
	 * Extends the abstract class UBDWPFacadeAbstract.
	 */
	class UBDWPHelperFacade extends UBDWPFacadeAbstract {
		/**
		 * Get the class name that this facade is representing.
		 *
		 * @return string The name of the class being used by this facade.
		 */
		protected static function get_class_name(): string {
			return 'UBDWPHelper';
		}
	}
}
