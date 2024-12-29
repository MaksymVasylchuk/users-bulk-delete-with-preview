<?php
/**
 * Views utils
 *
 * @package     UsersBulkDeleteWithPreview\Traits
 */

namespace UsersBulkDeleteWithPreview\Utils;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Utility class for handling views and templates.
 */
class UbdwpViews {

	/**
	 * Path to the directory containing template files.
	 */
	private const TEMPLATE_PATH = WPUBDP_PLUGIN_DIR . 'templates/';

	/**
	 * Renders a template with the given data and returns the output as a string.
	 *
	 * @param string $template_name The name of the template file to render.
	 * @param array  $data          An associative array of data to pass to the template.
	 * @return string The rendered template content.
	 */
	public function render_template(string $template_name, array $data = []): string {
		// Construct the full path to the template file.
		$template_path = self::TEMPLATE_PATH . $template_name;

		// Check if the template file exists.
		if (!file_exists($template_path)) {
			return ''; // Return an empty string if the file does not exist.
		}

		// Create variables from the data array.
		foreach ($data as $key => $value) {
			${$key} = $value;
		}

		// Start output buffering.
		ob_start();

		// Include the template file.
		include $template_path;

		// Get the buffered output and clean the buffer.
		return ob_get_clean();
	}

	/**
	 * Includes a template with the given data. Outputs the content directly.
	 *
	 * @param string $template_name The name of the template file to include.
	 * @param array  $data          An associative array of data to pass to the template.
	 * @return void|string Outputs the template content or returns an empty string if the file doesn't exist.
	 */
	public function include_template(string $template_name, array $data = []) {
		// Construct the full path to the template file.
		$template_path = self::TEMPLATE_PATH . $template_name;

		// Check if the template file exists.
		if (!file_exists($template_path)) {
			return ''; // Return an empty string if the file does not exist.
		}

		// Create variables from the data array.
		foreach ($data as $key => $value) {
			${$key} = $value;
		}

		// Include the template file.
		include $template_path;
	}
}
