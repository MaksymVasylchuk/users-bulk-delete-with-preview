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
	 * Validate the user search request for existing users.
	 *
	 * @param array $request The request data.
	 *
	 * @return void
	 */
	public function validate_user_search_for_existing_users( array $request ): void {
		$user_search = array_filter( array_unique( array_map( 'intval', (array) ( $request['user_search'] ?? array() ) ) ) );

		if ( empty( $user_search ) ) {
			$this->send_error_response( 'no_users_found' );
		}
	}

	/**
	 * Validate the form for finding users based on various criteria.
	 *
	 * @param array $request The request data.
	 *
	 * @return void
	 */
	public function validate_find_user_form( array $request ): void {
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


		$without_content = filter_var( $request['without_content'] ?? false, FILTER_VALIDATE_BOOLEAN );

		if (
			empty( $user_role ) &&
			empty( $user_email ) &&
			empty( $registration_date ) &&
			! $has_meta_filter &&
			! $without_content
		) {
			$this->send_error_response( 'at_least_one_required' );
		}

		// An unparsable value would silently drop the filter and widen the result to more users.
		if ( '' !== $registration_date ) {
			$date_compare = sanitize_key( $request['registration_date_compare'] ?? 'after' );
			$date_to      = sanitize_text_field( $request['registration_date_to'] ?? '' );

			if ( ! in_array( $date_compare, array( '', 'after', 'before', 'on', 'between' ), true ) ) {
				$this->send_error_response( 'invalid_date_compare' );
			}

			if ( ! $this->is_valid_date( $registration_date ) ) {
				$this->send_error_response( 'invalid_date' );
			}

			if ( 'between' === $date_compare && ( ! $this->is_valid_date( $date_to ) || $date_to < $registration_date ) ) {
				$this->send_error_response( 'invalid_date_range' );
			}
		}

		if ( $has_meta_filter && '' !== $user_meta_value ) {
			if ( str_ends_with( $user_meta_equal, '_date' ) && ! $this->is_valid_date( $user_meta_value ) ) {
				$this->send_error_response( 'invalid_date' );
			}

			if ( str_ends_with( $user_meta_equal, '_number' ) && ! is_numeric( $user_meta_value ) ) {
				$this->send_error_response( 'invalid_number' );
			}
		}
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
	 * Validate WooCommerce filters in the request.
	 *
	 * @param array $request The request data.
	 *
	 * @return void
	 */
	public function validate_woocommerce_filters( array $request ): void {
		$products = array_filter( array_unique( array_map( 'intval', (array) ( $request['products'] ?? array() ) ) ) );

		if ( empty( $products ) && empty( $request['all_products'] ) ) {
			$this->send_error_response( 'at_least_one_required' );
		}
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
		);
	}

	/**
	 * Send JSON error response with the given error code.
	 *
	 * @param string $error_code The error code.
	 *
	 * @return void
	 */
	private function send_error_response( string $error_code ): void {
		wp_send_json_error( array(
			'message' => $this->get_error_message( $error_code ),
		) );
		wp_die();
	}
}
