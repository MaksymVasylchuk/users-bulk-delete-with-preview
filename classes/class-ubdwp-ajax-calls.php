<?php
/**
 * Ajax calls class
 *
 * @package     UsersBulkDeleteWithPreview\Classes
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! class_exists( 'WPUBDPAjaxCalls' ) ) {
	/**
	 * Class for handling ajax calls in the Users Bulk Delete With Preview plugin.
	 */
	class WPUBDPAjaxCalls {
		const MANAGE_OPTIONS_CAP = 'manage_options';
		const LIST_USERS_CAP     = 'list_users';

		/**
		 * Constructor initializes the class with ajax actions.
		 */
		public function __construct() {
			// Register AJAX actions for various operations.
			add_action(
				'wp_ajax_search_users',
				array( $this, 'search_existing_users_ajax' )
			);
			add_action(
				'wp_ajax_search_usermeta',
				array( $this, 'search_usermeta_ajax' )
			);
			add_action(
				'wp_ajax_search_users_for_delete',
				array( $this, 'search_users_for_delete_ajax' )
			);
			add_action(
				'wp_ajax_delete_users_action',
				array( $this, 'delete_users_action' )
			);
			add_action(
				'wp_ajax_logs_datatables',
				array( $this, 'logs_datatables_action' )
			);
			add_action(
				'wp_ajax_custom_export_users',
				array( $this, 'custom_export_users_action' )
			);
			add_action(
				'wp_ajax_delete_exported_file',
				array( $this, 'delete_exported_files_action' )
			);
		}

		/**
		 * Generic method to check user permissions.
		 *
		 * @param  array $caps  List of capabilities to check.
		 */
		private function check_permissions( array $caps ): void {
			foreach ( $caps as $cap ) {
				if ( ! current_user_can( $cap ) ) {
					wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'permission_error' ) ) );
					wp_die();
				}
			}
		}

		/**
		 * Generic method to verify nonce.
		 *
		 * @param  string $nonce_field  Name of the nonce field.
		 * @param  string $action       Name of the nonce action.
		 * @param  string $type         Type of request.
		 */
		private function verify_nonce( string $nonce_field, string $action, string $type = 'POST'): void {
			$field = sanitize_text_field( $_POST[ $nonce_field ] ?? '' ) ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Variable already sanitized

			if( strtoupper($type) === 'GET' ) {
				$field = sanitize_text_field( $_GET[ $nonce_field ] ?? '' ) ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Variable already sanitized
			}

			if ( ! isset( $field )
				|| ! wp_verify_nonce( $field, $action )
			) {
				wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'invalid_nonce' ) ) );
				wp_die();
			}
		}

		/**
		 * Handle AJAX request to search for existing users.
		 */
		public function search_existing_users_ajax(): void {
			$this->check_permissions(
				array(
					self::MANAGE_OPTIONS_CAP,
					self::LIST_USERS_CAP,
				)
			);
			$this->verify_nonce( 'nonce', 'search_user_existing_nonce' );

			// Process the search request.
			$search_data = array(
				'q' => sanitize_text_field( $_POST['q'] ?? '' ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce is checked above in "verify_nonce" method, variable already sanitized
				'select_all' => sanitize_text_field( $_POST['select_all'] ?? false ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Nonce is checked above in "verify_nonce" method, variable already sanitized
			);
			$results = UBDWPUsersFacade::search_users_ajax( $search_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is checked in method above.
			wp_send_json_success( array( 'results' => $results ) );
			wp_die();
		}

		/**
		 * Handle AJAX request to search user metadata.
		 */
		public function search_usermeta_ajax(): void {
			$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );
			$this->verify_nonce( 'nonce', 'search_user_meta_nonce' );

			// Process the metadata search request.
			$sanitized_data = array(
				'q' => sanitize_text_field( $_POST['q'] ?? ''), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Missing -- Input is already checked for empty or null and sanitized here and the nonce is checked in method above
			);
			$results = UBDWPUsersFacade::search_usermeta_ajax( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The nonce is checked in method above.
			wp_send_json_success( $results );
			wp_die();
		}

		/**
		 * Handle AJAX request to search users for deletion.
		 */
		public function search_users_for_delete_ajax():void {
			try {
				$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );
				$this->verify_nonce( 'find_users_nonce', 'find_users_nonce' );

				$type = sanitize_text_field( $_POST['filter_type'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- The nonce is checked in method above, variable sanitizing here.
				if ( empty( $type ) ) {
					wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'select_type' ) ) );
					wp_die();
				}

				// Define the keys that need to be extracted from $_POST.
				$keys = [
					'find_users_nonce',
					'search_user_existing_nonce',
					'search_user_meta_nonce',
					'registration_date',
					'user_meta_value',
					'user_email',
					'filter_type',
					'action',
					'user_email_equal',
					'user_meta_equal',
					'user_search',
					'products',
					'user_role',
					'user_meta'
				];

				$data_before_sanitize = array_intersect_key( $_POST, array_flip( $keys ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".

				$sanitized_data = UBDWPHelperFacade::sanitize_post_data( $data_before_sanitize ); //POST data sanitized in this method

				switch ( $type ) {
					case 'select_existing':
						UBDWPHelperFacade::validate_user_search_for_existing_users( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
						$results
							= UBDWPUsersFacade::get_users_by_ids( array_unique( array_map('intval',$_POST['user_search'] ?? array() ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
						break;
					case 'find_users':
						UBDWPHelperFacade::validate_find_user_form( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
						$results
							= UBDWPUsersFacade::get_users_by_filters( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
						break;
					case 'find_users_by_woocommerce_filters':
						UBDWPHelperFacade::validate_woocommerce_filters( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
						$results
							= UBDWPUsersFacade::get_users_by_woocommerce_filters( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
						break;
					default:
						wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'select_type' ) ) );
						wp_die();
				}

				UBDWPHelperFacade::handle_wp_error( $results );
				wp_send_json_success( $results );
				wp_die();
			} catch ( \Exception $e ) {
				wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'generic_error' ) ) );
				wp_die();
			}
		}

		/**
		 * Handle AJAX request to delete users.
		 */
		public function delete_users_action(): void {
			try {
				$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );
				$this->verify_nonce(
					'delete_users_nonce',
					'delete_users_nonce'
				);

				$sanitized_users = array_filter( array_map( function ( $user ) {
					return (is_array( $user ) && !empty($user['id']))  ? array(
						'id'            => intval( $user['id'] ?? 0 ),
						'reassign'      => sanitize_text_field( $user['reassign'] ?? '' ),
						'email'         => sanitize_email( $user['email'] ?? '' ),
						'display_name'  => sanitize_text_field( $user['display_name'] ?? '' )
					) : null;
				}, $_POST['users'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", this filter is sanitizing the $_POST array.


				$user_ids = array_unique( array_map('intval', array_column( $sanitized_users, 'id' ) ) );

				if ( empty( $user_ids ) ) {
					wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'select_any_user' ) ) );
					wp_die();
				}

				$deleted_users = array();
				foreach ( $sanitized_users as $user ) {
					if ( intval( $user['id'] ) > 0 ) {
						$deleted_users[ $user['id'] ] = array(
							'user_id'      => intval( $user['id'] ),
							'email'        => $user['email'],
							'display_name' => $user['display_name'],
							'reassign'     => $user['reassign'] ?? '',
						);
						wp_delete_user( esc_attr( $user['id'] ), esc_attr( $user['reassign'] ) ?? null );
					}
				}


				$template
					= UBDWPViewsFacade::render_template(
						'partials/_success_user_delete.php',
						array(
							'user_delete_count' => count( $deleted_users ),
							'deleted_users'     => array_values( $deleted_users ),
						)
					);

				UBDWPLogsFacade::insert_log_record(
					array(
						'user_delete_count' => count( $deleted_users ),
						'user_delete_data'  => array_values( $deleted_users ),
					)
				);

				wp_send_json_success( array( 'template' => $template ) );
				wp_die();
			} catch ( \Exception $e ) {
				wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'generic_error' ) ) );
				wp_die();
			}
		}

		/**
		 * Handle AJAX request to retrieve logs for DataTables.
		 */
		public function logs_datatables_action(): void {
			$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );
			$this->verify_nonce(
				'logs_datatable_nonce',
				'logs_datatable_nonce',
				'GET'
			);

			// Create a custom array from $_GET with specific keys
			$custom_data = array(
				'draw' => $_GET['draw'] ?? 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
				'start' => $_GET['start'] ?? 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
				'length' => $_GET['length'] ?? 10, // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
				'action' => $_GET['action'] ?? '', // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
				'logs_datatable_nonce' => $_GET['logs_datatable_nonce'] ?? '', // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
				'search' => isset($_GET['search']) ? array( // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
					'value' => $_GET['search']['value'] ?? '', // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
					'regex' => $_GET['search']['regex'] ?? false, // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
				) : array('value' => '', 'regex' => false),
				'columns' => isset($_GET['columns']) ? $_GET['columns'] : array(), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", data will sanitize later in "UBDWPHelperFacade::sanitize_get_data".
			);

			$sanitized_data = UBDWPHelperFacade::sanitize_get_data( $custom_data );
			
			UBDWPLogsFacade::logs_data_table( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce is checked in method above.
		}

		/**
		 * Handle AJAX request for custom user export to CSV.
		 */
		public function custom_export_users_action(): void {
			try {
				$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );
				$this->verify_nonce(
					'export_users_nonce',
					'export_users_nonce'
				);

				$sanitized_users = array_filter( array_map( function ( $user ) {
					return ( is_array( $user ) && !empty( $user['value'] ) ) ? array(
						'id' => intval( $user['value'] ?? 0 ),
						'name'  => sanitize_text_field( $user['name'] ?? '' ),
						'email' => sanitize_email( $user['email'] ?? '' ),
					) : null;
				}, $_POST['users'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked above, check method - "verify_nonce", this filter is sanitizing the $_POST array.

				$user_ids  = array_column( $sanitized_users, 'id' );
				$user_ids  = array_map( 'esc_attr', $user_ids );
				$user_list = get_users( array( 'include' => $user_ids ) );

				if ( empty( $user_list ) ) {
					wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'select_any_user' ) ) );
					wp_die();
				}

				$csv_output = $this->generate_csv( $user_list );

				// Save CSV file to wp-content/uploads.
				$file_url = $this->save_csv_file( $csv_output );

				wp_send_json_success(
					array(
						'file_url'  => $file_url,
						'file_path' => $file_url,
					)
				);
			} catch ( \Exception $e ) {
				wp_send_json_error( array( 'message' => UBDWPHelperFacade::get_error_message( 'generic_error' ) ) );
				wp_die();
			}
		}

		/**
		 * Generate CSV content from the user list.
		 *
		 * @param  array $users  List of user objects.
		 *
		 * @return string CSV content.
		 */
		private function generate_csv( array $users ): string {
			$csv_output = 'ID,Username,Email,First Name,Last Name,Role' . "\n";
			foreach ( $users as $user ) {
				$csv_output .= implode(
					',',
					array(
						intval( $user->ID ),
						sanitize_text_field( $user->user_login ),
						sanitize_email( $user->user_email ),
						sanitize_text_field( $user->first_name ),
						sanitize_text_field( $user->last_name ),
						implode( ', ', $user->roles ),
					)
				) . "\n";
			}

			return $csv_output;
		}

		/**
		 * Save the generated CSV content to a file.
		 *
		 * @param  string $csv_output  The CSV content.
		 * @return string URL of the saved file.
		 */
		private function save_csv_file( $csv_output ) {
			// Initialize the WordPress filesystem API
			global $wp_filesystem;
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			WP_Filesystem();

			// Get the upload directory
			$upload_dir = wp_upload_dir();
			$file_path  = $upload_dir['path'] . '/users_export_' . time() . '.csv';

			// Use WP_Filesystem's method to write the file
			if ( ! $wp_filesystem->put_contents( $file_path, $csv_output, FS_CHMOD_FILE ) ) {
				return new WP_Error( 'file_write_error', __( 'Failed to write the CSV file.', 'users-bulk-delete-with-preview' ) );
			}

			// Return the URL of the saved file
			return $upload_dir['url'] . '/' . basename( $file_path );
		}

		/**
		 * Handle AJAX request to delete exported files.
		 */
		public function delete_exported_files_action() {
			$this->verify_nonce( 'nonce', 'custom_export_users_nonce' );
			$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );

			$file_path = isset( $_POST['file_path'] ) ? sanitize_text_field( $_POST['file_path'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing -- Filed is sanitizing here, nonce is checked above, check method - "verify_nonce".

			if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
				wp_delete_file( $file_path ); // Delete file.
			}

			wp_send_json_success();
		}
	}
}

// Initialize the AJAX handler if the class exists.
if ( class_exists( 'WPUBDPAjaxCalls' ) ) {
	$users_bulk_delete_with_preview_ajax_calls = new WPUBDPAjaxCalls();
}
