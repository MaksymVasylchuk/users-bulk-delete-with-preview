<?php
/**
 * Base Page
 *
 * @package     UsersBulkDeleteWithPreview\Pages
 */

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;

/**
 * Base class for admin pages in the Users Bulk Delete With Preview plugin.
 */
abstract class UbdwpBasePage {

	const MANAGE_OPTIONS_CAP = 'manage_options';
	const LIST_USERS_CAP     = 'list_users';

	public $current_user_id;

	/**
	 * Render the page using the provided template and data.
	 *
	 * @param string $template_name Template file name.
	 * @param array  $data          Data to pass to the template.
	 */
	protected function render_template(string $template_name, array $data = array()): void {
		if (!current_user_can(self::MANAGE_OPTIONS_CAP)) {
			wp_die(UbdwpHelperFacade::get_error_message('permission_error'));
		}

		UbdwpViewsFacade::include_template($template_name, $data); // Ensures secure template rendering.
	}

	public function get_current_user_id(): int {
		if (is_null($this->current_user_id)) {
			$this->current_user_id = get_current_user_id();
		}
		return $this->current_user_id;
	}

	/**
	 * Register an AJAX call.
	 *
	 * @param string   $action   Action name.
	 * @param callable $callback Callback function.
	 */
	protected function register_ajax_call(string $action, callable $callback): void {
		add_action("wp_ajax_{$action}", $callback);
	}

	/**
	 * Validate an AJAX request.
	 *
	 * @param string $nonce_field Nonce field to verify.
	 * @param string $action      Action to verify against.
	 * @param string $type        Request type (POST or GET).
	 */
	protected function verify_nonce(string $nonce_field, string $action, string $type = 'POST'): void {
		$field = null;

		if (strtoupper($type) === 'POST') {
			$field = sanitize_text_field($_POST[$nonce_field] ?? '');
		} elseif (strtoupper($type) === 'GET') {
			$field = sanitize_text_field($_GET[$nonce_field] ?? '');
		}

		if (!isset($field) || !wp_verify_nonce($field, $action)) {
			wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('invalid_nonce')));
			wp_die();
		}
	}

	/**
	 * Check if the current user has the required capabilities.
	 *
	 * @param array $caps Array of capabilities to check.
	 */
	protected function check_permissions(array $caps): void {
		foreach ($caps as $cap) {
			if (!current_user_can($cap)) {
				wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('permission_error')));
				wp_die();
			}
		}
	}

	protected function register_common_scripts(array $scripts): void {
		foreach ($scripts as $handle => $script) {
			wp_register_script(
				$handle,
				WPUBDP_PLUGIN_URL . $script['path'],
				$script['deps'] ?? array('jquery'),
				WPUBDP_PLUGIN_VERSION,
				true
			);
			wp_enqueue_script($handle);
		}
	}

	public function localize_scripts(string $script_handle, array $localizations): void {
		wp_localize_script($script_handle, 'localizedData', $localizations);
	}

}