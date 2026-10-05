<?php
/**
 * Validation class for UsersBulkDeleteWithPreview plugin.
 *
 * @package UsersBulkDeleteWithPreview\Utils
 */

namespace UsersBulkDeleteWithPreview\Utils;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for handling validation logic in the Users Bulk Delete With Preview plugin.
 */
class UbdwpValidation {

	/**
	 * Retrieve error messages based on the provided error code.
	 *
	 * @param string $code The error code.
	 *
	 * @return string The corresponding error message.
	 */
	public function get_error_message( string $code ): string {
		$messages = $this->get_error_messages();

		return $messages[ $code ] ?? esc_html__(
			'An unknown error occurred.',
			'users-bulk-delete-with-preview'
		);
	}

	/**
	 * Handle WP_Error responses and send a JSON error response.
	 *
	 * @param \WP_Error|array $results The WP_Error object or an array of results.
	 *
	 * @return void
	 */
	public function handle_wp_error( \WP_Error|array $results ): void {
		if ( ! is_wp_error( $results ) ) {
			return;
		}

		$this->send_error_response( $results->get_error_code() );
	}

	/**
	 * Get the validation error for a user search of the given type.
	 *
	 * Used by AJAX requests and WP-CLI commands, so it returns the error instead of sending a response.
	 *
	 * @param string $type    Search type.
	 * @param array  $request Sanitized request data.
	 *
	 * @return string|null Error code, or null when the request is valid.
	 */
	public function get_search_error( string $type, array $request ): ?string {
		switch ( $type ) {
			case 'select_existing':
				return $this->get_existing_users_error( $request );
			case 'find_users':
				return $this->get_find_user_form_error( $request );
			case 'find_users_by_woocommerce_filters':
				return $this->get_woocommerce_filters_error( $request );
			default:
				return 'select_type';
		}
	}

	/**
	 * Get the validation error for a search among existing users.
	 *
	 * @param array $request The request data.
	 *
	 * @return string|null Error code, or null when valid.
	 */
	public function get_existing_users_error( array $request ): ?string {
		$user_search = array_filter( array_unique( array_map( 'intval', (array) ( $request['user_search'] ?? array() ) ) ) );

		return empty( $user_search ) && empty( $request['all_users'] ) ? 'no_users_found' : null;
	}

	/**
	 * Get the validation error for the form for finding users based on various criteria.
	 *
	 * @param array $request The request data.
	 *
	 * @return string|null Error code, or null when valid.
	 */
	public function get_find_user_form_error( array $request ): ?string {
		$user_role         = array_unique( array_map( 'sanitize_text_field', (array) ( $request['user_role'] ?? [] ) ) );
		$user_email        = sanitize_text_field( $request['user_email'] ?? '' );
		$registration_date = sanitize_text_field( $request['registration_date'] ?? '' );
		$user_meta         = sanitize_text_field( $request['user_meta'] ?? '' );
		$user_meta_value   = sanitize_text_field( $request['user_meta_value'] ?? '' );
		$user_meta_equal   = sanitize_text_field( $request['user_meta_equal'] ?? '' );

		$has_meta_filter = ! empty( $user_meta ) && (
				$user_meta_equal === 'meta_not_exists' ||
				$user_meta_equal === 'meta_is_empty' ||
				! empty( $user_meta_value )
			);


		$without_content   = filter_var( $request['without_content'] ?? false, FILTER_VALIDATE_BOOLEAN );
		$without_wc_orders = filter_var( $request['without_wc_orders'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if (
			empty( $user_role ) &&
			empty( $user_email ) &&
			empty( $registration_date ) &&
			! $has_meta_filter &&
			! $without_content &&
			! $without_wc_orders
		) {
			return 'at_least_one_required';
		}

		$email_compare = sanitize_key( $request['user_email_equal'] ?? '' );

		if ( '' !== $user_email && ! in_array( $email_compare, array( '', 'equal_to_str', 'notequal_to_str', 'like_str', 'notlike_str', 'endswith_str' ), true ) ) {
			return 'invalid_email_compare';
		}

		// An unparsable value would silently drop the filter and widen the result to more users.
		if ( '' !== $registration_date ) {
			$date_compare = sanitize_key( $request['registration_date_compare'] ?? 'after' );
			$date_to      = sanitize_text_field( $request['registration_date_to'] ?? '' );

			if ( ! in_array( $date_compare, array( '', 'after', 'before', 'on', 'between' ), true ) ) {
				return 'invalid_date_compare';
			}

			if ( ! $this->is_valid_date( $registration_date ) ) {
				return 'invalid_date';
			}

			if ( 'between' === $date_compare && ( ! $this->is_valid_date( $date_to ) || $date_to < $registration_date ) ) {
				return 'invalid_date_range';
			}
		}

		if ( $has_meta_filter && '' !== $user_meta_value ) {
			if ( str_ends_with( $user_meta_equal, '_date' ) && ! $this->is_valid_date( $user_meta_value ) ) {
				return 'invalid_date';
			}

			if ( str_ends_with( $user_meta_equal, '_number' ) && ! is_numeric( $user_meta_value ) ) {
				return 'invalid_number';
			}
		}

		return null;
	}

	/**
	 * Check that a value is a real calendar date in YYYY-MM-DD format.
	 *
	 * @param string $value Date string.
	 *
	 * @return bool True for a valid date.
	 */
	private function is_valid_date( string $value ): bool {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) ) {
			return false;
		}

		return checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] );
	}

	/**
	 * Get the validation error for WooCommerce filters.
	 *
	 * @param array $request The request data.
	 *
	 * @return string|null Error code, or null when valid.
	 */
	public function get_woocommerce_filters_error( array $request ): ?string {
		$products = array_filter( array_unique( array_map( 'intval', (array) ( $request['products'] ?? array() ) ) ) );

		return empty( $products ) && empty( $request['all_products'] ) ? 'at_least_one_required' : null;
	}

	/**
	 * Validate and sanitize a positive integer value.
	 *
	 * @param mixed $value Input value to validate.
	 * @param int $default Default value if validation fails.
	 *
	 * @return int Validated positive integer.
	 */
	public function validate_positive_integer( $value, int $default ): int {
		$value = intval( $value );

		return $value > 0 ? $value : $default;
	}

	/**
	 * Return the error messages array.
	 *
	 * @return array<string, string> An associative array of error codes and their corresponding messages.
	 */
	private function get_error_messages(): array {
		return array(
			'permission_error'                  => esc_html__(
				'You do not have sufficient permissions to perform this action.',
				'users-bulk-delete-with-preview'
			),
			'invalid_nonce'                     => esc_html__(
				'Invalid nonce',
				'users-bulk-delete-with-preview'
			),
			'select_type'                       => esc_html__(
				'Select type',
				'users-bulk-delete-with-preview'
			),
			'security_error'                    => esc_html__(
				'Invalid security token',
				'users-bulk-delete-with-preview'
			),
			'generic_error'                     => esc_html__(
				'Something went wrong. Please try again.',
				'users-bulk-delete-with-preview'
			),
			'invalid_input'                     => esc_html__(
				'User IDs should be an array.',
				'users-bulk-delete-with-preview'
			),
			'at_least_one_required'             => esc_html__(
				'At least one required field.',
				'users-bulk-delete-with-preview'
			),
			'no_users_found_with_given_filters' => esc_html__(
				'No users found with the given filters',
				'users-bulk-delete-with-preview'
			),
			'no_users_found'                    => esc_html__(
				'No users found for the provided IDs.',
				'users-bulk-delete-with-preview'
			),
			'select_any_user'                   => esc_html__(
				'Please select at least one user for deletion.',
				'users-bulk-delete-with-preview'
			),
			'file_write_error'                  => esc_html__(
				'Failed to create the export file.',
				'users-bulk-delete-with-preview'
			),
			'invalid_date'                      => esc_html__(
				'Please enter a valid date in YYYY-MM-DD format.',
				'users-bulk-delete-with-preview'
			),
			'invalid_date_range'                => esc_html__(
				'Please enter a valid date range: both dates in YYYY-MM-DD format, and the end date not before the start date.',
				'users-bulk-delete-with-preview'
			),
			'invalid_date_compare'              => esc_html__(
				'Please choose a valid registration date comparison.',
				'users-bulk-delete-with-preview'
			),
			'invalid_number'                    => esc_html__(
				'Please enter a valid number for the selected comparison.',
				'users-bulk-delete-with-preview'
			),
			'invalid_email_compare'             => esc_html__(
				'Please choose a valid email comparison.',
				'users-bulk-delete-with-preview'
			),
			'preview_expired'                   => esc_html__(
				'The preview has expired. Please run the preview again.',
				'users-bulk-delete-with-preview'
			),
			'job_not_found'                     => esc_html__(
				'The deletion job was not found.',
				'users-bulk-delete-with-preview'
			),
			'job_invalid_state'                 => esc_html__(
				'The deletion job cannot do this in its current state.',
				'users-bulk-delete-with-preview'
			),
			'job_busy'                          => esc_html__(
				'The deletion job is being processed by another request. Please try again in a moment.',
				'users-bulk-delete-with-preview'
			),
			'confirm_mismatch'                  => esc_html__(
				'Please type the number of users to confirm the deletion.',
				'users-bulk-delete-with-preview'
			),
			'invalid_reassign'                  => esc_html__(
				'The user selected to receive the content is not valid.',
				'users-bulk-delete-with-preview'
			),
		);
	}

	/**
	 * Build a WP_Error with the translated message for an error code.
	 *
	 * @param string $error_code The error code.
	 *
	 * @return \WP_Error Error.
	 */
	public function to_wp_error( string $error_code ): \WP_Error {
		return new \WP_Error( $error_code, wp_specialchars_decode( $this->get_error_message( $error_code ), ENT_QUOTES ) );
	}

	/**
	 * Send JSON error response with the given error code.
	 *
	 * @param string $error_code The error code.
	 *
	 * @return void
	 */
	public function send_error_response( string $error_code ): void {
		wp_send_json_error( array(
			'message' => $this->get_error_message( $error_code ),
		) );
		wp_die();
	}
}
