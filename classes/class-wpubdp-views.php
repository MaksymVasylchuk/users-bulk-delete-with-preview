<?php
/**
 * Views class
 *
 * @package     WPUserBulkDeleteWithPreviw\Classes
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Class WPUBDPViews
 *
 * Handles rendering of templates with provided data.
 */
if ( ! class_exists( 'WPUBDPViews' ) ) {
	/**
	 * Facade class for WPUBDPLogs functionality.
	 * Extends the abstract class WPUBDPFacadeAbstract.
	 */
	class WPUBDPViews {
		// Path to the directory containing template files.
		const TEMPLATE_PATH = WPUBDP_PLUGIN_DIR . 'templates/';

		/**
		 * Renders a template with the given data.
		 *
		 * @param  string $template_name  The name of the template file to render.
		 * @param  array  $data          An associative array of data to pass to the template.
		 *
		 * @return string              The rendered template content.
		 */
		public function render_template( string $template_name, array $data = array() ): string {
			// Construct the full path to the template file.
			$template_path = self::TEMPLATE_PATH . $template_name;

			// Check if the template file exists.
			if ( ! file_exists( $template_path ) ) {
				return ''; // Return empty string if file does not exist.
			}

			// Extract data array to individual variables.
			// extract( $data ). We do not use extract to avoid issues.
			foreach ( $data as $key => $value ) {
				${$key} = $value;
			}

			// Start output buffering.
			ob_start();

			// Include the template file.
			include $template_path;

			// Get the buffered output and clean the buffer.
			$output = ob_get_clean();

			return $output;
		}
	}
}
