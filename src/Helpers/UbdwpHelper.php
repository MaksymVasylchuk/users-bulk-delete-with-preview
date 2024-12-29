<?php
/**
 * Views facade
 *
 * @package     UsersBulkDeleteWithPreview\Helpers
 */

namespace UsersBulkDeleteWithPreview\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for handling helper function in the Users Bulk Delete With Preview plugin.
 */
class UbdwpHelper {

	/**
	 * Get available user search types.
	 *
	 * @return array<string, string> List of user search types with their labels.
	 */
	public function get_types_of_user_search(): array {
		$types = array(
			'select_existing' => __( 'Choose from existing users', 'users-bulk-delete-with-preview' ),
			'find_users'      => __( 'Find users according to certain criteria', 'users-bulk-delete-with-preview' ),
		);

		if ( $this->check_if_woocommerce_is_active() ) {
			$types['find_users_by_woocommerce_filters'] = __( 'Find users using WooCommerce filters', 'users-bulk-delete-with-preview' );
		}

		return $types;
	}

	/**
	 * Check if the WooCommerce plugin is active.
	 *
	 * @return bool True if WooCommerce is active, false otherwise.
	 */
	public function check_if_woocommerce_is_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'woocommerce/woocommerce.php' ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}


	/**
	 * Prepare user data for displaying in a table.
	 *
	 * @param  array $users      List of WP_User objects.
	 * @param  mixed $repository The user repository for retrieving additional data.
	 *
	 * @return array Formatted user data for table display.
	 */
	public function prepare_users_for_table( array $users, $repository ): array {
		if ( empty( $users ) ) {
			return array();
		}

		$user_ids       = array_unique( array_map( 'intval', array_map( fn( $user ) => $user->ID, $users ) ) );
		$all_users      = $repository->get_users_exclude_ids( $user_ids );
		$select_options = $this->build_select_options( $all_users );

		return array_map( fn( $user ) => $this->format_user_data_for_table( $user, $select_options ), $users );
	}

	/**
	 * Build HTML options for the select dropdown.
	 *
	 * @param  array $all_users  List of all users.
	 *
	 * @return string HTML string of select options.
	 */
	private function build_select_options( array $all_users ): string {
		$options = '<option value="">' . __( 'Select a user', 'users-bulk-delete-with-preview' ) . '</option>';

		foreach ( $all_users as $user ) {
			$options .= '<option value="' . esc_attr( $user->ID ) . '">' . esc_html( $user->user_login ) . '</option>';
		}

		return $options;
	}

	/**
	 * Format a single user's data for table display.
	 *
	 * @param  \WP_User $user            The user object.
	 * @param  string   $select_options  The HTML options for the select dropdown.
	 *
	 * @return array Formatted user data.
	 */
	private function format_user_data_for_table( \WP_User $user, string $select_options ): array {
		return array(
			'checkbox'        => '<input type="checkbox" class="user-checkbox" name="users[' . esc_attr( $user->ID ) . '][id]" value="' . esc_attr( $user->ID ) . '">',
			'ID'              => intval( $user->ID ),
			'user_login'      => sanitize_text_field( $user->user_login ),
			'user_email'      => sanitize_email( $user->user_email ),
			'user_registered' => sanitize_text_field( $user->user_registered ),
			'user_role'       => implode( ', ', $user->roles ),
			'select'          => $this->build_user_select_html( $user, $select_options ),
		);
	}

	/**
	 * Build HTML for the user select dropdown.
	 *
	 * @param  \WP_User $user            The user object.
	 * @param  string   $select_options  The HTML options for the select dropdown.
	 *
	 * @return string The HTML string for the user select dropdown.
	 */
	private function build_user_select_html( \WP_User $user, string $select_options ): string {
		return '<select class="user-select" name="users[' . esc_attr( $user->ID ) . '][reassign]">' . $select_options . '</select>' .
		       '<input type="hidden" name="users[' . esc_attr( $user->ID ) . '][email]" value="' . esc_attr( $user->user_email ) . '">' .
		       '<input type="hidden" name="users[' . esc_attr( $user->ID ) . '][display_name]" value="' . esc_attr( $user->display_name ) . '">';
	}

	/**
	 * Return SVG icon for plugin.
	 *
	 * @return string Base64-encoded SVG icon.
	 */
	public function get_icon(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="gray"><path d="M19 13v-2h-6v2h6m-10-2V9H5v2h4m-4 4v-2H3v2h2M17 1H7c-1.1 0-2 .9-2 2v16h2v4h12v-4h2V3c0-1.1-.9-2-2-2zm0 18H7v-1h10v1zm2-4H5V3h14v12zM9 11H7V9h2v2zm6 0h-2v-2h2v2z"/></svg>';

		$encoded_logo = base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Used for data URI.

		return 'data:image/svg+xml;base64,' . $encoded_logo;
	}

	/**
	 * Custom sanitization function for $_POST data.
	 *
	 * @param array<string, mixed> $data The data to sanitize.
	 *
	 * @return array<string, mixed> The sanitized data.
	 */
	public function sanitize_post_data( array $data ): array {
		$sanitized_data = array();
		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'find_users_nonce':
				case 'search_user_existing_nonce':
				case 'search_user_meta_nonce':
				case 'registration_date':
				case 'user_meta_value':
				case 'user_email':
					$sanitized_data[ $key ] = sanitize_text_field( $value );
					break;

				case 'filter_type':
				case 'action':
				case 'user_email_equal':
				case 'user_meta_equal':
				case 'user_meta':
					$sanitized_data[ $key ] = sanitize_key( $value );
					break;

				case 'user_search':
				case 'products': // Added 'products[]' sanitization
					$sanitized_data[ $key ] = array_unique( array_map( 'absint', $value ) );
					break;

				case 'user_role':
					$sanitized_data[ $key ] = array_unique( array_map( 'sanitize_text_field', $value ) );
					break;

				default:
					// Fallback for unexpected keys
					$sanitized_data[ $key ] = sanitize_text_field( $value );
					break;
			}
		}
		return $sanitized_data;
	}

	/**
	 * Custom sanitization function for $_GET data.
	 *
	 * @param array<string, mixed> $data The data to sanitize.
	 *
	 * @return array<string, mixed> The sanitized data.
	 */
	public function sanitize_get_data( array $data ): array {
		$sanitized_data = array();

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'draw':
				case 'start':
				case 'length':
				case '_':
					$sanitized_data[ $key ] = absint( $value );
					break;

				case 'action':
				case 'logs_datatable_nonce':
					$sanitized_data[ $key ] = sanitize_text_field( $value );
					break;

				case 'search':
					$sanitized_data[ $key ] = array(
						'value' => sanitize_text_field( $value['value'] ?? '' ),
						'regex' => filter_var( $value['regex'] ?? 'false', FILTER_VALIDATE_BOOLEAN ),
					);
					break;

				case 'columns':
					$sanitized_data[ $key ] = array_map( function ( $column ) {
						return array(
							'data'       => absint( $column['data'] ?? 0 ),
							'name'       => sanitize_text_field( $column['name'] ?? '' ),
							'searchable' => filter_var( $column['searchable'] ?? 'false', FILTER_VALIDATE_BOOLEAN ),
							'orderable'  => filter_var( $column['orderable'] ?? 'false', FILTER_VALIDATE_BOOLEAN ),
							'search'     => array(
								'value' => sanitize_text_field( $column['search']['value'] ?? '' ),
								'regex' => filter_var( $column['search']['regex'] ?? 'false', FILTER_VALIDATE_BOOLEAN ),
							),
						);
					}, $value );
					break;

				default:
					// Fallback for unexpected keys
					$sanitized_data[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized_data;
	}

	/**
	 * Get translation for DataTables.
	 *
	 * @return array<string, string> Translation strings for DataTables.
	 */
	public function getDataTableTranslation(): array {
		return array(
			'emptyTable'     => __( 'No data available in table', 'users-bulk-delete-with-preview' ),
			'info'           => __( 'Showing _START_ to _END_ of _TOTAL_ entries', 'users-bulk-delete-with-preview' ),
			'infoEmpty'      => __( 'Showing 0 to 0 of 0 entries', 'users-bulk-delete-with-preview' ),
			'infoFiltered'   => __( '(filtered from _MAX_ total entries)', 'users-bulk-delete-with-preview' ),
			'lengthMenu'     => __( 'Show _MENU_ entries', 'users-bulk-delete-with-preview' ),
			'loadingRecords' => __( 'Loading...', 'users-bulk-delete-with-preview' ),
			'processing'     => __( 'Processing...', 'users-bulk-delete-with-preview' ),
			'search'         => __( 'Search', 'users-bulk-delete-with-preview' ),
			'zeroRecords'    => __( 'No matching records found', 'users-bulk-delete-with-preview' ),
		);
	}

	/**
	 * Get translation for User table.
	 *
	 * @return array<string, string> Translation strings for the User table.
	 */
	public function getUserTableTranslation(): array {
		return array(
			'id'             => __( 'ID', 'users-bulk-delete-with-preview' ),
			'username'       => __( 'Username', 'users-bulk-delete-with-preview' ),
			'email'          => __( 'Email', 'users-bulk-delete-with-preview' ),
			'registered'     => __( 'Registered', 'users-bulk-delete-with-preview' ),
			'role'           => __( 'Role', 'users-bulk-delete-with-preview' ),
			'assignContent'  => __( 'Assign related content to user', 'users-bulk-delete-with-preview' ),
		);
	}

	/**
	 * Check if the current page is a plugin-specific page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return bool True if the page is a plugin-specific page, otherwise false.
	 */
	public function is_plugin_page( string $hook_suffix ): bool {
		return $hook_suffix === 'toplevel_page_ubdwp_admin' || ( isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'ubdwp_admin', 'ubdwp_admin_logs' ), true ) );
	}

	/**
	 * Map user meta comparison operator from request.
	 *
	 * @param string $comparison Comparison type from request.
	 *
	 * @return string Comparison operator.
	 */
	public function get_meta_compare_operator( string $comparison ): string {
		$map = array(
			'notequal_to_str'         => '!=',
			'like_str'                => 'LIKE',
			'notlike_str'             => 'NOT LIKE',
			'equal_to_date'           => '=',
			'equal_to_number'         => '=',
			'notequal_to_date'        => '!=',
			'notequal_to_number'      => '!=',
			'lessthen_date'           => '<',
			'lessthen_number'         => '<',
			'lessthenequal_date'      => '<=',
			'lessthenequal_number'    => '<=',
			'greaterthen_date'        => '>',
			'greaterthen_number'      => '>',
			'greaterthenequal_date'   => '>=',
			'greaterthenequal_number' => '>=',
		);

		return $map[ $comparison ] ?? '=';
	}

	/**
	 * Map email comparison operator from request.
	 *
	 * @param string $comparison Comparison type from request.
	 *
	 * @return string Comparison operator.
	 */
	public function get_email_compare_operator( string $comparison ): string {
		$map = array(
			'notequal_to_str' => '!=',
			'like_str'        => 'LIKE',
			'notlike_str'     => 'NOT LIKE',
		);

		return $map[ $comparison ] ?? '=';
	}


	/**
	 * Register and enqueue common scripts.
	 *
	 * @param array<string, array<string, mixed>> $scripts Array of scripts to register and enqueue.
	 *
	 * @return void
	 */
	public function register_common_scripts( array $scripts ): void {
		foreach ( $scripts as $handle => $script ) {
			wp_register_script(
				$handle,
				WPUBDP_PLUGIN_URL . $script['path'],
				$script['deps'] ?? array( 'jquery' ),
				WPUBDP_PLUGIN_VERSION,
				true
			);
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Register and enqueue common styles.
	 *
	 * @param array<string, array<string, mixed>> $styles Array of styles to register and enqueue.
	 *
	 * @return void
	 */
	public function register_common_styles( array $styles ): void {
		foreach ( $styles as $handle => $style ) {
			wp_enqueue_style(
				$handle,
				WPUBDP_PLUGIN_URL . $style['path'],
				$style['deps'] ?? array(),
				WPUBDP_PLUGIN_VERSION
			);
		}
	}

	/**
	 * Localize scripts with provided data.
	 *
	 * @param string                $script_handle Script handle to localize.
	 * @param array<string, mixed> $localizations Data to localize the script with.
	 *
	 * @return void
	 */
	public function localize_scripts( string $script_handle, array $localizations ): void {
		wp_localize_script( $script_handle, 'localizedData', $localizations );
	}
}