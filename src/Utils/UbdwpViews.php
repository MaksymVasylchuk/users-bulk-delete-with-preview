<?php

namespace UsersBulkDeleteWithPreview\Utils;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Facade class for UBDWPLogs functionality.
 * Extends the abstract class UBDWPFacadeAbstract.
 */
class UbdwpViews {
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

	/**
	 * Includes a template with the given data.
	 *
	 * @param  string  $template_name
	 * @param  array   $data
	 *
	 * @return string|void
	 */
	public function include_template( string $template_name, array $data = array() ) {
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

		// Include the template file.
		include $template_path;
	}
}