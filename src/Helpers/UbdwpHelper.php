<?php

namespace UsersBulkDeleteWithPreview\Helpers;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for handling helper functions in the Users Bulk Delete With Preview plugin.
 */
class UbdwpHelper {

	/**
	 * Retrieve error messages based on the provided error code.
	 *
	 * @param string $code The error code.
	 *
	 * @return string The corresponding error message.
	 */
	public function get_error_message(string $code): string {
		$messages = $this->get_error_messages();

		return $messages[$code] ?? esc_html__('An unknown error occurred.', 'users-bulk-delete-with-preview');
	}

	/**
	 * Return the error messages array.
	 *
	 * @return array
	 */
	private function get_error_messages(): array {
		return array(
			'permission_error'                  => esc_html__('You do not have sufficient permissions to perform this action.', 'users-bulk-delete-with-preview'),
			'invalid_nonce'                     => esc_html__('Invalid nonce', 'users-bulk-delete-with-preview'),
			'select_type'                       => esc_html__('Select type', 'users-bulk-delete-with-preview'),
			'security_error'                    => esc_html__('Invalid security token', 'users-bulk-delete-with-preview'),
			'generic_error'                     => esc_html__('Something went wrong. Please try again.', 'users-bulk-delete-with-preview'),
			'invalid_input'                     => esc_html__('User IDs should be an array.', 'users-bulk-delete-with-preview'),
			'at_least_one_required'             => esc_html__('At least one required field.', 'users-bulk-delete-with-preview'),
			'no_users_found_with_given_filters' => esc_html__('No users found with the given filters', 'users-bulk-delete-with-preview'),
			'no_users_found'                    => esc_html__('No users found for the provided IDs.', 'users-bulk-delete-with-preview'),
			'select_any_user'                   => esc_html__('Please select at least one user for deletion.', 'users-bulk-delete-with-preview'),
		);
	}

	/**
	 * Handle WP_Error responses and send a JSON error response.
	 *
	 * @param WP_Error|array $results The WP_Error object.
	 */
	public function handle_wp_error(WP_Error|array $results): void {
		if (!is_wp_error($results)) {
			return;
		}

		$this->send_error_response($results->get_error_code());
	}

	/**
	 * Get available user search types.
	 *
	 * @return array List of user search types.
	 */
	public function get_types_of_user_search(): array {
		$types = array(
			'select_existing' => __('Choose from existing users', 'users-bulk-delete-with-preview'),
			'find_users'      => __('Find users according to certain criteria', 'users-bulk-delete-with-preview'),
		);

		if ($this->check_if_woocommerce_is_active()) {
			$types['find_users_by_woocommerce_filters'] = __('Find users using WooCommerce filters', 'users-bulk-delete-with-preview');
		}

		return $types;
	}

	/**
	 * Check if the WooCommerce plugin is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	public function check_if_woocommerce_is_active(): bool {
		if (!function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active('woocommerce/woocommerce.php') || in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
	}

	/**
	 * Validate the user search request for existing users.
	 *
	 * @param array $request The request data.
	 */
	public function validate_user_search_for_existing_users(array $request): void {
		$user_search = array_unique(array_map('intval', $request['user_search'] ?? array()));

		if (empty($user_search)) {
			$this->send_error_response('no_users_found');
		}
	}

	/**
	 * Validate the form for finding users based on various criteria.
	 *
	 * @param array $request The request data.
	 */
	public function validate_find_user_form(array $request): void {
		$user_role = array_unique(array_map('sanitize_text_field', $request['user_role'] ?? array()));
		$user_email = sanitize_text_field($request['user_email'] ?? '');
		$registration_date = sanitize_text_field($request['registration_date'] ?? '');
		$user_meta = sanitize_text_field($request['user_meta'] ?? '');
		$user_meta_value = sanitize_text_field($request['user_meta_value'] ?? '');

		if (empty($user_role) && empty($user_email) && empty($registration_date) && (empty($user_meta) || empty($user_meta_value))) {
			$this->send_error_response('at_least_one_required');
		}
	}

	/**
	 * Send JSON error response with the given error code.
	 *
	 * @param string $error_code The error code.
	 */
	private function send_error_response(string $error_code): void {
		wp_send_json_error(array(
			'message' => $this->get_error_message($error_code),
		));
		wp_die();
	}

	/**
	 * Check if the current page is a plugin-specific page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return bool True if the page is a plugin-specific page, otherwise false.
	 */
	public function is_plugin_page(string $hook_suffix): bool {
		return $hook_suffix === 'toplevel_page_ubdwp_admin' || (isset($_GET['page']) && in_array($_GET['page'], array('ubdwp_admin', 'ubdwp_admin_logs'), true));
	}
}