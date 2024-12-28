<?php

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
abstract class UbdwpBasePage {

	const MANAGE_OPTIONS_CAP = 'manage_options';
	const LIST_USERS_CAP     = 'list_users';

	protected $current_user_id;

	public function __construct() {
		$this->current_user_id = get_current_user_id();
	}

	/**
	 * Render the page using the provided template and data.
	 *
	 * @param string $template_name Template file name.
	 * @param array  $data          Data to pass to the template.
	 */
	protected function render_template(string $template_name, array $data = []): void {
		if (!current_user_can('manage_options')) {
			wp_die(__('You do not have permission to access this page.', 'users-bulk-delete-with-preview'));
		}

		UbdwpViewsFacade::include_template($template_name, $data); // Ensures secure template rendering.
	}

	/**
	 * Register an AJAX call.
	 *
	 * @param string   $action Action name.
	 * @param callable $callback Callback function.
	 */
	protected function register_ajax_call(string $action, callable $callback): void {
		add_action("wp_ajax_{$action}", $callback);
	}

	/**
	 * Validate an AJAX request.
	 *
	 * @param string $nonce_key Nonce key to verify.
	 */
	protected function verify_nonce(string $nonce_field, string $action, string $type = 'POST'): void {
		$field = sanitize_text_field( $_POST[ $nonce_field ] ?? '' ) ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Variable already sanitized

		if( strtoupper($type) === 'GET' ) {
			$field = sanitize_text_field( $_GET[ $nonce_field ] ?? '' ) ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Variable already sanitized
		}

		if ( ! isset( $field )
		     || ! wp_verify_nonce( $field, $action )
		) {
			wp_send_json_error( array( 'message' => UbdwpHelperFacade::get_error_message( 'invalid_nonce' ) ) );
			wp_die();
		}
	}

	protected function check_permissions( array $caps ): void {
		foreach ( $caps as $cap ) {
			if ( ! current_user_can( $cap ) ) {
				wp_send_json_error( array( 'message' => UbdwpHelperFacade::get_error_message( 'permission_error' ) ) );
				wp_die();
			}
		}
	}
}
