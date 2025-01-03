<?php
/**
 * Users Page
 *
 * @package     UsersBulkDeleteWithPreview\Pages
 */

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractBasePage;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Handlers\UbdwpLogsHandler;
use UsersBulkDeleteWithPreview\Handlers\UbdwpUsersHandler;
use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;

/**
 * Class for managing the Users Page.
 */
class UbdwpUsersPage extends UbdwpAbstractBasePage {
	/**
	 * Handler for user actions.
	 *
	 * @var UbdwpUsersHandler
	 */
	private UbdwpUsersHandler $handler;

	/**
	 * Handler for managing logs.
	 *
	 * @var UbdwpLogsHandler
	 */
	private UbdwpLogsHandler $logs_handler;

	/**
	 * Constructor to initialize the Users Page.
	 */
	public function __construct() {
		$current_user_id = $this->get_current_user_id();

		$this->handler      = new UbdwpUsersHandler( $current_user_id );
		$this->logs_handler = new UbdwpLogsHandler( $current_user_id );

		$this->register_ajax_calls();
	}

	/**
	 * Render the Users Page.
	 *
	 * @return void
	 */
	public function render(): void {
		$products = [];

		if ( UbdwpHelperFacade::check_if_woocommerce_is_active() ) {
			$products = wc_get_products( [ 'limit' => - 1 ] );
		}

		$data = [
			'title'    => __( 'Users Management', 'users-bulk-delete-with-preview' ),
			'roles'    => wp_roles()->roles,
			'types'    => UbdwpHelperFacade::get_types_of_user_search(),
			'products' => $products,
		];

		$this->render_template( 'admin-page.php', $data );
	}

	/**
	 * Register admin scripts for the Users Page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	public function register_admin_scripts( string $hook_suffix ): void {
		if ( $hook_suffix === 'toplevel_page_ubdwp_admin' ) {
			UbdwpHelperFacade::register_common_scripts( [
				'wpubdp-bootstrap-js'  => [ 'path' => 'assets/bootstrap/bootstrap.min.js', 'deps' => [ 'jquery' ] ],
				'wpubdp-select2-js'    => [ 'path' => 'assets/select2/select2.min.js', 'deps' => [ 'jquery' ] ],
				'wpubdp-datepicker-js' => [
					'path' => 'assets/jquery-ui-datepicker/jquery-ui-timepicker-addon.min.js',
					'deps' => [ 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ]
				],
				'wpubdp-admin-js'      => [
					'path' => 'assets/admin/admin.min.js',
					'deps' => [
						'jquery',
						'wpubdp-bootstrap-js',
						'wpubdp-select2-js',
						'wpubdp-dataTables-js',
						'wp-i18n'
					]
				],
			] );

			UbdwpHelperFacade::localize_scripts( 'wpubdp-admin-js', [
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'translations' => array_merge(
					UbdwpHelperFacade::get_data_table_translation(),
					UbdwpHelperFacade::get_user_table_translation()
				),
			] );
		}
	}

	/**
	 * Handle AJAX request to search for existing users.
	 *
	 * @return void
	 */
	public function search_existing_users_ajax(): void {
		$capabilities = [
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		];

		$this->handle_ajax_request( 'nonce', 'search_user_existing_nonce', $capabilities, function () {
			$search_data = [
				'q'          => sanitize_text_field( $_POST['q'] ?? '' ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.
				'select_all' => filter_var( $_POST['select_all'] ?? false, FILTER_VALIDATE_BOOLEAN ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.
			];

			$results = $this->handler->search_users_ajax( $search_data );

			return [ 'results' => $results ];
		} );
	}

	/**
	 * Handle AJAX request to search user metadata.
	 *
	 * @return void
	 */
	public function search_usermeta_ajax(): void {
		$capabilities = [
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		];

		$this->handle_ajax_request( 'nonce', 'search_user_meta_nonce', $capabilities, function () {
			$sanitized_data = [
				'q' => sanitize_text_field( $_POST['q'] ?? '' ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.
			];

			return $this->handler->search_usermeta_ajax( $sanitized_data );
		} );
	}

	/**
	 * Handle AJAX request to search users for deletion.
	 *
	 * @return void
	 */
	public function search_users_for_delete_ajax(): void {
		$capabilities = [
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		];

		$this->handle_ajax_request( 'find_users_nonce', 'find_users_nonce', $capabilities, function () {
			$type = sanitize_text_field( $_POST['filter_type'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.

			if ( empty( $type ) ) {
				wp_send_json_error( [ 'message' => UbdwpValidationFacade::get_error_message( 'select_type' ) ] );
				wp_die();
			}

			$results = $this->handler->search_users_for_delete_ajax( $type, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable will be sanitize in the "search_users_for_delete_ajax" method.

			UbdwpValidationFacade::handle_wp_error( $results );

			return $results;
		} );
	}

	/**
	 * Handle AJAX request to delete users.
	 *
	 * @return void
	 */
	public function delete_users_action(): void {
		$capabilities = [
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
			self::DELETE_USERS_CAP,
		];

		$this->handle_ajax_request( 'delete_users_nonce', 'delete_users_nonce', $capabilities, function () {
			$sanitized_users = array_filter( array_map( function ( $user ) {
				return is_array( $user ) && ! empty( $user['id'] ) ? [
					'id'           => (int) $user['id'],
					'reassign'     => sanitize_text_field( $user['reassign'] ?? '' ),
					'email'        => sanitize_email( $user['email'] ?? '' ),
					'display_name' => sanitize_text_field( $user['display_name'] ?? '' ),
				] : null;
			}, $_POST['users'] ?? [] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.

			$user_ids = array_unique( array_column( $sanitized_users, 'id' ) );

			if ( empty( $user_ids ) ) {
				wp_send_json_error( [ 'message' => UbdwpValidationFacade::get_error_message( 'select_any_user' ) ] );
				wp_die();
			}

			$response = $this->handler->delete_users( $sanitized_users );

			extract( $response );

			$this->logs_handler->insert_log( [
				'user_delete_count' => count( $deleted_users ),
				'user_delete_data'  => array_values( $deleted_users ),
			] );

			return [ 'template' => $template ];
		} );
	}

	/**
	 * Handle AJAX request for custom user export to CSV.
	 *
	 * @return void
	 */
	public function custom_export_users_action(): void {
		$capabilities = [
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		];

		$this->handle_ajax_request( 'export_users_nonce', 'export_users_nonce', $capabilities, function () {
			$sanitized_users = array_filter( array_map( function ( $user ) {
				return is_array( $user ) && ! empty( $user['value'] ) ? [
					'id'    => (int) ( $user['value'] ?? 0 ),
					'name'  => sanitize_text_field( $user['name'] ?? '' ),
					'email' => sanitize_email( $user['email'] ?? '' ),
				] : null;
			}, $_POST['users'] ?? [] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.

			$user_ids = array_column( $sanitized_users, 'id' );
			$user_ids = array_map( 'esc_attr', $user_ids );

			if ( empty( $user_ids ) ) {
				wp_send_json_error( [ 'message' => UbdwpValidationFacade::get_error_message( 'select_any_user' ) ] );
				wp_die();
			}

			$user_list = $this->handler->repository->get_users_by_ids( $user_ids );

			$csv_output = $this->handler->generate_csv( $user_list );

			$file_url = $this->handler->save_csv_file( $csv_output );

			return [
				'file_url'  => $file_url,
				'file_path' => $file_url,
			];
		} );
	}

	/**
	 * Handle AJAX request to delete exported files.
	 *
	 * @return void
	 */
	public function delete_exported_files_action(): void {
		$capabilities = [ self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP ];

		$this->handle_ajax_request( 'nonce', 'custom_export_users_nonce', $capabilities, function () {
			$file_path = isset( $_POST['file_path'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
				? sanitize_text_field( $_POST['file_path'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method, variable already sanitized.
				: '';

			$this->handler->delete_csv_file( $file_path );

			return [];
		} );
	}

	/**
	 * Register AJAX calls.
	 *
	 * @return void
	 */
	private function register_ajax_calls(): void {
		$ajax_calls = [
			'search_users'            => 'search_existing_users_ajax',
			'search_usermeta'         => 'search_usermeta_ajax',
			'search_users_for_delete' => 'search_users_for_delete_ajax',
			'delete_users_action'     => 'delete_users_action',
			'custom_export_users'     => 'custom_export_users_action',
			'delete_exported_file'    => 'delete_exported_files_action',
		];

		foreach ( $ajax_calls as $action => $method ) {
			$this->register_ajax_call( $action, [ $this, $method ] );
		}
	}
}