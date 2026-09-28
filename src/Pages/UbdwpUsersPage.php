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
use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;

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
		$data = array(
			'title'              => __( 'Users Management', 'users-bulk-delete-with-preview' ),
			'roles'              => wp_roles()->roles,
			'types'              => UbdwpHelperFacade::get_types_of_user_search(),
			'woocommerce_active' => UbdwpHelperFacade::check_if_woocommerce_is_active(),
		);

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
			UbdwpHelperFacade::register_common_scripts( array(
				'wpubdp-bootstrap-js'  => array(
					'path' => 'assets/bootstrap/bootstrap.min.js',
					'deps' => array( 'jquery' )
				),
				'wpubdp-select2-js'    => array(
					'path' => 'assets/select2/select2.min.js',
					'deps' => array( 'jquery' )
				),
					'wpubdp-admin-js'      => array(
						'path' => 'assets/admin/admin.min.js',
						'deps' => array(
							'jquery',
							'wpubdp-bootstrap-js',
							'wpubdp-select2-js',
							'wpubdp-dataTables-js',
							'jquery-ui-datepicker',
							'wp-i18n'
						)
					),
			) );

			UbdwpHelperFacade::localize_scripts( 'wpubdp-admin-js', array(
				'ajaxurl'                => admin_url( 'admin-ajax.php' ),
				'reassignUsersNonce'     => wp_create_nonce( 'ubdwp_search_reassign_users' ),
				'translations'           => array_merge(
					UbdwpHelperFacade::get_data_table_translation(),
					UbdwpHelperFacade::get_user_table_translation()
				),
			) );

			wp_set_script_translations( 'wpubdp-admin-js', 'users-bulk-delete-with-preview', WPUBDP_PLUGIN_DIR . 'languages' );
		}
	}

	/**
	 * Handle AJAX request to search for existing users.
	 *
	 * @return void
	 */
	public function search_existing_users_ajax(): void {
		$capabilities = array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		);

		$this->handle_ajax_request( 'nonce', 'ubdwp_search_users', $capabilities, function () {
			$search_data = array(
				'q'          => sanitize_text_field( $_POST['q'] ?? '' ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.
				'select_all' => filter_var( $_POST['select_all'] ?? false, FILTER_VALIDATE_BOOLEAN ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.
			);

			$results = $this->handler->search_users_ajax( $search_data );

			return array( 'results' => $results );
		} );
	}

	/**
	 * Handle AJAX request to search user metadata.
	 *
	 * @return void
	 */
	public function search_usermeta_ajax(): void {
		$capabilities = array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		);

		$this->handle_ajax_request( 'nonce', 'ubdwp_search_usermeta', $capabilities, function () {
			$sanitized_data = array(
				'q' => sanitize_text_field( $_POST['q'] ?? '' ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.
			);

			return $this->handler->search_usermeta_ajax( $sanitized_data );
		} );
	}

	/**
	 * Handle AJAX request to search users that can receive reassigned content.
	 *
	 * @return void
	 */
	public function search_reassign_users_ajax(): void {
		$capabilities = array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		);

		$this->handle_ajax_request( 'nonce', 'ubdwp_search_reassign_users', $capabilities, function () {
			$exclude = wp_unslash( $_POST['exclude'] ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, values are cast to integers below.

			$search_data = array(
				'q'       => sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
				'exclude' => is_array( $exclude ) ? array_map( 'absint', $exclude ) : array(),
			);

			return array( 'results' => $this->handler->search_reassign_users_ajax( $search_data ) );
		} );
	}

	/**
	 * Handle AJAX request to search WooCommerce products for the products filter.
	 *
	 * @return void
	 */
	public function search_products_ajax(): void {
		$capabilities = array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		);

		$this->handle_ajax_request( 'nonce', 'ubdwp_search_products', $capabilities, function () {
			$search_data = array(
				'q' => sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			);

			return array( 'results' => $this->handler->search_products_ajax( $search_data ) );
		} );
	}

	/**
	 * Handle AJAX request to search users for deletion.
	 *
	 * @return void
	 */
	public function search_users_for_delete_ajax(): void {
		$capabilities = array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		);

		$this->handle_ajax_request( 'find_users_nonce', 'ubdwp_find_users', $capabilities, function () {
			$type = sanitize_text_field( $_POST['filter_type'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.

			if ( empty( $type ) ) {
				wp_send_json_error( array( 'message' => UbdwpValidationFacade::get_error_message( 'select_type' ) ) );
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
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$sanitized_users = $this->get_requested_users();

			$response      = $this->handler->delete_users( $sanitized_users );
			$deleted_users = $response['deleted_users'] ?? array();
			$failed_users  = $response['failed_users'] ?? array();

			if ( ! empty( $deleted_users ) ) {
				$this->logs_handler->insert_log( array(
					'user_delete_count' => count( $deleted_users ),
					'user_delete_data'  => array_values( $deleted_users ),
				) );
			}

			return array(
				'template'        => $response['template'] ?? '',
				'failed_template' => $response['failed_template'] ?? '',
				'deleted_count'   => count( $deleted_users ),
				'failed_count'    => count( $failed_users ),
			);
		} );
	}

	/**
	 * Handle AJAX request that summarizes a deletion before it is confirmed.
	 *
	 * @return void
	 */
	public function delete_summary_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$summary = $this->handler->get_delete_summary( $this->get_requested_users() );

			$summary['skipped_template'] = UbdwpViewsFacade::render_template(
				'partials/_failed_user_delete.php',
				array( 'failed_users' => $summary['skipped'] )
			);
			$summary['skipped'] = count( $summary['skipped'] );

			return $summary;
		} );
	}

	/**
	 * Capabilities required to delete users or remove them from a site.
	 *
	 * @return array<string> Capabilities.
	 */
	private function get_delete_capabilities(): array {
		return array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
			is_multisite() ? self::REMOVE_USERS_CAP : self::DELETE_USERS_CAP,
		);
	}

	/**
	 * Read users selected for deletion from the request.
	 *
	 * Sends a JSON error and stops when no valid user ID was sent.
	 *
	 * @return array<int, array<string, mixed>> Sanitized users.
	 */
	private function get_requested_users(): array {
		$users = wp_unslash( $_POST['users'] ?? array() ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, values are sanitized below.

		$sanitized_users = array_values( array_filter( array_map( static function ( $user ) {
			return is_array( $user ) && ! empty( $user['id'] ) ? array(
				'id'       => (int) $user['id'],
				'reassign' => sanitize_text_field( $user['reassign'] ?? '' ),
			) : null;
		}, is_array( $users ) ? $users : array() ) ) );

		if ( empty( array_filter( array_column( $sanitized_users, 'id' ) ) ) ) {
			wp_send_json_error( array( 'message' => UbdwpValidationFacade::get_error_message( 'select_any_user' ) ) );
			wp_die();
		}

		return $sanitized_users;
	}

	/**
	 * Handle AJAX request for custom user export to CSV.
	 *
	 * @return void
	 */
	public function custom_export_users_action(): void {
		$capabilities = array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		);

		$this->handle_ajax_request( 'export_users_nonce', 'ubdwp_export_users', $capabilities, function () {
			$sanitized_users = array_filter( array_map( function ( $user ) {
				return is_array( $user ) && ! empty( $user['value'] ) ? array(
					'id'    => (int) ( $user['value'] ?? 0 ),
					'name'  => sanitize_text_field( $user['name'] ?? '' ),
					'email' => sanitize_email( $user['email'] ?? '' ),
				) : null;
			}, $_POST['users'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --  Nonce is checked in "handle_ajax_request" method, variable already sanitized.

			$user_ids = array_unique( array_map( 'absint', array_column( $sanitized_users, 'id' ) ) );

			if ( empty( $user_ids ) ) {
				wp_send_json_error( array( 'message' => UbdwpValidationFacade::get_error_message( 'select_any_user' ) ) );
				wp_die();
			}

			$user_list = $this->handler->repository->get_users_by_ids( $user_ids );

			return array(
				'file_name' => $this->handler->get_csv_file_name(),
				'content'   => $this->handler->generate_csv( $user_list ),
			);
		} );
	}

	/**
	 * Register AJAX calls.
	 *
	 * @return void
	 */
	private function register_ajax_calls(): void {
		$ajax_calls = array(
			'ubdwp_search_users'            => 'search_existing_users_ajax',
			'ubdwp_search_usermeta'         => 'search_usermeta_ajax',
			'ubdwp_search_users_for_delete' => 'search_users_for_delete_ajax',
			'ubdwp_delete_users'            => 'delete_users_action',
			'ubdwp_delete_summary'          => 'delete_summary_action',
			'ubdwp_search_reassign_users'   => 'search_reassign_users_ajax',
			'ubdwp_search_products'         => 'search_products_ajax',
			'ubdwp_export_users'            => 'custom_export_users_action',
		);

		foreach ( $ajax_calls as $action => $method ) {
			$this->register_ajax_call( $action, array( $this, $method ) );
		}
	}
}
