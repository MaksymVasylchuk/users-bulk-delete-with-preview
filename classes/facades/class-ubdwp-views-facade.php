<?php
/**
 * Views facade
 *
 * @package     UsersBulkDeleteWithPreview\Classes\Facades
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Facade class for rendering templates.
 * Extends the abstract class UBDWPFacadeAbstract.
 *
 * Example Usage:
 *
 * $templateName = 'my-template.php';
 * $data = ['title' => 'Hello, World!', 'content' => 'This is the content.'];
 *
 * echo UBDWPViewsFacade::render_template($templateName, $data);
 *
 * This will render the specified template with the provided data.
 */
if ( ! class_exists( 'UBDWPViewsFacade' ) ) {
	/**
	 * Facade class for WPUBDPViews functionality.
	 * Inherits from the abstract class UBDWPFacadeAbstract.
	 */
	class UBDWPViewsFacade extends UBDWPFacadeAbstract {
		/**
		 * Get the name of the class that this facade represents.
		 *
		 * @return string The name of the class used by this facade.
		 */
		protected static function get_class_name(): string {
			return 'WPUBDPViews';
		}
	}
}
