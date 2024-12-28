<?php
/**
 * Users Page
 *
 * @package     UsersBulkDeleteWithPreview\Pages
 */

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Handlers\UbdwpLogsHandler;
use UsersBulkDeleteWithPreview\Handlers\UbdwpUsersHandler;
use UsersBulkDeleteWithPreview\Repositories\UbdwpLogsRepository;
use UsersBulkDeleteWithPreview\Repositories\UbdwpUsersRepository;
use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;

/**
 * Class for managing the Users Page.
 */
class UbdwpUsersPage extends UbdwpBasePage {

	/** @var UbdwpUsersHandler Handler for user actions. */
	private $handler;

	/** @var UbdwpUsersRepository Repository for user data. */
	private $repository;

	/** @var UbdwpLogsRepository Repository for logs data. */
	private $logs_repository;
	private $logs_handler;
	public $current_user_id;
	/**
	 * Constructor to initialize the Users Page.
	 */
	public function __construct() {
		$current_user_id = $this->get_current_user_id();

		$this->handler = new UbdwpUsersHandler($current_user_id);
		$this->repository = new UbdwpUsersRepository($current_user_id);
		$this->logs_repository = new UbdwpLogsRepository($current_user_id);
		$this->logs_handler = new UbdwpLogsHandler($current_user_id);

		$this->register_ajax_call('search_users', array($this, 'search_existing_users_ajax'));
		$this->register_ajax_call('search_usermeta', array($this, 'search_usermeta_ajax'));
		$this->register_ajax_call('search_users_for_delete', array($this, 'search_users_for_delete_ajax'));
		$this->register_ajax_call('delete_users_action', array($this, 'delete_users_action'));
		$this->register_ajax_call('custom_export_users', array($this, 'custom_export_users_action'));
		$this->register_ajax_call('delete_exported_file', array($this, 'delete_exported_files_action'));
	}

	/**
	 * Render the Users Page.
	 */
	public function render(): void {
		$all_roles = wp_roles()->roles;
		$types = UbdwpHelperFacade::get_types_of_user_search();
		$products = array();

		if (is_plugin_active('woocommerce/woocommerce.php') && function_exists('wc_get_products')) {
			$products = wc_get_products(array('limit' => -1));
		}

		$data = array(
			'title' => __('Users Management', 'users-bulk-delete-with-preview'),
			'roles' => $all_roles,
			'types' => $types,
			'products' => $products,
		);

		$this->render_template('admin-page.php', $data);
	}

	/**
	 * Register admin scripts for the Users Page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	public function register_admin_scripts(string $hook_suffix): void {
		if ($hook_suffix === 'toplevel_page_ubdwp_admin') {

			$this->register_common_scripts([
				'wpubdp-bootstrap-js' => ['path' => 'assets/bootstrap/bootstrap.min.js', 'deps' => ['jquery']],
				'wpubdp-select2-js' => ['path' => 'assets/select2/select2.min.js', 'deps' => ['jquery']],
				'wpubdp-datepicker-js' => ['path' => 'assets/jquery-ui-datepicker/jquery-ui-timepicker-addon.min.js', 'deps' => ['jquery', 'jquery-ui-core', 'jquery-ui-datepicker']],
				'wpubdp-admin-js' => ['path' => 'assets/admin/admin.min.js', 'deps' => ['jquery', 'wpubdp-bootstrap-js', 'wpubdp-select2-js', 'wpubdp-dataTables-js', 'wp-i18n']],
			]);

			$this->localize_scripts('wpubdp-admin-js', [
				'ajaxurl' => admin_url('admin-ajax.php'),
				'translations' => array_merge(
					UbdwpHelperFacade::getDataTableTranslation(),
					UbdwpHelperFacade::getUserTableTranslation()
				),
			]);
		}
	}

	/**
	 * Handle AJAX request to search for existing users.
	 */
	public function search_existing_users_ajax(): void {
		$this->check_permissions(array(self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP));
		$this->verify_nonce('nonce', 'search_user_existing_nonce');

		$search_data = array(
			'q' => sanitize_text_field($_POST['q'] ?? ''),
			'select_all' => sanitize_text_field($_POST['select_all'] ?? false),
		);

		$results = $this->handler->search_users_ajax($search_data);
		wp_send_json_success(array('results' => $results));
		wp_die();
	}

	/**
	 * Handle AJAX request to search user metadata.
	 */
	public function search_usermeta_ajax(): void {
		$this->check_permissions(array(self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP));
		$this->verify_nonce('nonce', 'search_user_meta_nonce');

		$sanitized_data = array(
			'q' => sanitize_text_field($_POST['q'] ?? ''),
		);

		$results = $this->handler->search_usermeta_ajax($sanitized_data);
		wp_send_json_success($results);
		wp_die();
	}

	/**
	 * Handle AJAX request to search users for deletion.
	 */
	public function search_users_for_delete_ajax(): void {
		try {
			$this->check_permissions(array(self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP));
			$this->verify_nonce('find_users_nonce', 'find_users_nonce');

			$type = sanitize_text_field($_POST['filter_type'] ?? '');

			if (empty($type)) {
				wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('select_type')));
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
			$data_before_sanitize = array_intersect_key($_POST, array_flip($keys));
			$sanitized_data = UbdwpHelperFacade::sanitize_post_data($data_before_sanitize);

			switch ($type) {
				case 'select_existing':
					UbdwpHelperFacade::validate_user_search_for_existing_users($sanitized_data);
					$results = $this->handler->get_users_by_ids(array_unique(array_map('intval', $_POST['user_search'] ?? array())));
					break;
				case 'find_users':
					UbdwpHelperFacade::validate_find_user_form($sanitized_data);
					$results = $this->handler->get_users_by_filters($sanitized_data);
					break;
				default:
					wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('select_type')));
					wp_die();
			}

			UbdwpHelperFacade::handle_wp_error( $results );
			wp_send_json_success($results);
			wp_die();
		} catch (\Exception $e) {
			wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('generic_error')));
			wp_die();
		}
	}

	/**
	 * Handle AJAX request to delete users.
	 */
	public function delete_users_action(): void {
		try {
			$this->check_permissions(array(self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP));
			$this->verify_nonce('delete_users_nonce', 'delete_users_nonce');

			$sanitized_users = array_filter(array_map(function ($user) {
				return is_array($user) && !empty($user['id']) ? array(
					'id' => intval($user['id']),
					'reassign' => sanitize_text_field($user['reassign'] ?? ''),
					'email' => sanitize_email($user['email'] ?? ''),
					'display_name' => sanitize_text_field($user['display_name'] ?? ''),
				) : null;
			}, $_POST['users'] ?? array()));

			$user_ids = array_unique(array_column($sanitized_users, 'id'));

			if (empty($user_ids)) {
				wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('select_any_user')));
				wp_die();
			}

			$deleted_users = array();
			foreach ($sanitized_users as $user) {
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
				= UbdwpViewsFacade::render_template(
				'partials/_success_user_delete.php',
				array(
					'user_delete_count' => count( $deleted_users ),
					'deleted_users'     => array_values( $deleted_users ),
				)
			);

			$this->logs_handler->insert_log(
				array(
					'user_delete_count' => count( $deleted_users ),
					'user_delete_data'  => array_values( $deleted_users ),
				)
			);

			wp_send_json_success( array( 'template' => $template ) );

			wp_die();
		} catch (\Exception $e) {
			wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('generic_error')));
			wp_die();
		}
	}

	/**
	 * Handle AJAX request for custom user export to CSV.
	 */
	public function custom_export_users_action(): void {
		try {
			$this->check_permissions( array(self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP) );
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
	private function generate_csv(array $users): string {
		$output = fopen('php://temp', 'w');
		fputcsv($output, ['ID', 'Username', 'Email', 'First Name', 'Last Name', 'Role']);

		foreach ($users as $user) {
			fputcsv($output, [
				intval($user->ID),
				sanitize_text_field($user->user_login),
				sanitize_email($user->user_email),
				sanitize_text_field($user->first_name),
				sanitize_text_field($user->last_name),
				implode(', ', $user->roles),
			]);
		}

		rewind($output);
		$csv_content = stream_get_contents($output);
		fclose($output);

		return $csv_content;
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
		$this->check_permissions( array(self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP) );

		$file_path = isset( $_POST['file_path'] ) ? sanitize_text_field( $_POST['file_path'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing -- Filed is sanitizing here, nonce is checked above, check method - "verify_nonce".

		if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
			wp_delete_file( $file_path ); // Delete file.
		}

		wp_send_json_success();
	}
}