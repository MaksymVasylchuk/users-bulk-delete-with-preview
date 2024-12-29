<?php
/**
 * Base Page
 *
 * @package     UsersBulkDeleteWithPreview\Abstract
 */

namespace UsersBulkDeleteWithPreview\Abstract;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;

/**
 * Base class for admin pages in the Users Bulk Delete With Preview plugin.
 */
abstract class UbdwpAbstractBasePage {

	/**
	 * Capability required to manage options.
	 */
	public const MANAGE_OPTIONS_CAP = 'manage_options';

	/**
	 * Capability required to list users.
	 */
	public const LIST_USERS_CAP = 'list_users';

	/**
	 * ID of the current user.
	 *
	 * @var int|null
	 */
	public ?int $current_user_id = null;

	/**
	 * Render the page using the provided template and data.
	 *
	 * @param string $template_name Template file name.
	 * @param array  $data          Data to pass to the template.
	 * @return void
	 */
	protected function render_template(string $template_name, array $data = []): void {
		if (!current_user_can(self::MANAGE_OPTIONS_CAP)) {
			wp_die(UbdwpHelperFacade::get_error_message('permission_error'));
		}

		// Includes and renders the specified template securely.
		UbdwpViewsFacade::include_template($template_name, $data);
	}

	/**
	 * Get the current user's ID.
	 *
	 * @return int Current user ID.
	 */
	public function get_current_user_id(): int {
		if (is_null($this->current_user_id)) {
			$this->current_user_id = get_current_user_id();
		}
		return $this->current_user_id;
	}

	/**
	 * Register an AJAX call with the specified action and callback.
	 *
	 * @param string   $action   Action name.
	 * @param callable $callback Callback function.
	 * @return void
	 */
	protected function register_ajax_call(string $action, callable $callback): void {
		add_action("wp_ajax_{$action}", $callback);
	}

	/**
	 * Validate an AJAX request using a nonce field.
	 *
	 * @param string $nonce_field Nonce field to verify.
	 * @param string $action      Action to verify against.
	 * @param string $type        Request type (POST or GET).
	 * @return void
	 */
	protected function verify_nonce(string $nonce_field, string $action, string $type = 'POST'): void {
		$field = null;

		if (strtoupper($type) === 'POST') {
			$field = sanitize_text_field($_POST[$nonce_field] ?? '');
		} elseif (strtoupper($type) === 'GET') {
			$field = sanitize_text_field($_GET[$nonce_field] ?? '');
		}

		if (!isset($field) || !wp_verify_nonce($field, $action)) {
			wp_send_json_error(['message' => UbdwpHelperFacade::get_error_message('invalid_nonce')]);
			wp_die();
		}
	}

	/**
	 * Check if the current user has the required capabilities.
	 *
	 * @param array $caps Array of capabilities to check.
	 * @return void
	 */
	protected function check_permissions(array $caps): void {
		foreach ($caps as $cap) {
			if (!current_user_can($cap)) {
				wp_send_json_error(['message' => UbdwpHelperFacade::get_error_message('permission_error')]);
				wp_die();
			}
		}
	}

}