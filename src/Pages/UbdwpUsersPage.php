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
use UsersBulkDeleteWithPreview\Handlers\UbdwpUsersHandler;
use UsersBulkDeleteWithPreview\Repositories\UbdwpLogsRepository;
use UsersBulkDeleteWithPreview\Repositories\UbdwpUsersRepository;

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

	/**
	 * Constructor to initialize the Users Page.
	 */
	public function __construct() {
		parent::__construct();

		$this->handler = new UbdwpUsersHandler($this->current_user_id);
		$this->repository = new UbdwpUsersRepository($this->current_user_id);
		$this->logs_repository = new UbdwpLogsRepository($this->current_user_id);

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
	public function register_admin_scripts($hook_suffix): void {
		if ($hook_suffix === 'toplevel_page_ubdwp_admin') {
			wp_register_script('wpubdp-bootstrap-js', WPUBDP_PLUGIN_URL . 'assets/bootstrap/bootstrap.min.js', array('jquery'), WPUBDP_PLUGIN_VERSION, true);
			wp_register_script('wpubdp-select2-js', WPUBDP_PLUGIN_URL . 'assets/select2/select2.min.js', array('jquery'), WPUBDP_PLUGIN_VERSION, true);
			wp_register_script('wpubdp-datepicker-js', WPUBDP_PLUGIN_URL . 'assets/jquery-ui-datepicker/jquery-ui-timepicker-addon.min.js', array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'), WPUBDP_PLUGIN_VERSION, true);
			wp_register_script('wpubdp-admin-js', WPUBDP_PLUGIN_URL . 'assets/admin/admin.min.js', array('jquery', 'wpubdp-bootstrap-js', 'wpubdp-select2-js', 'wp-i18n'), WPUBDP_PLUGIN_VERSION, true);
			wp_register_script('wpubdp-dataTables-js', WPUBDP_PLUGIN_URL . 'assets/dataTables/datatables.min.js', array('jquery'), WPUBDP_PLUGIN_VERSION, true);

			wp_enqueue_script('wpubdp-bootstrap-js');
			wp_enqueue_script('wpubdp-select2-js');
			wp_enqueue_script('wpubdp-datepicker-js');
			wp_enqueue_script('wpubdp-dataTables-js');
			wp_enqueue_script('wpubdp-admin-js');

			wp_localize_script('wpubdp-admin-js', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));

			$translation = array_merge(UbdwpHelperFacade::getDataTableTranslation(), UbdwpHelperFacade::getUserTableTranslation());
			wp_localize_script('wpubdp-admin-js', 'dataTableLang', $translation);
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
		$this->check_permissions(array(self::MANAGE_OPTIONS_CAP));
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
			$this->check_permissions(array(self::MANAGE_OPTIONS_CAP));
			$this->verify_nonce('find_users_nonce', 'find_users_nonce');

			$type = sanitize_text_field($_POST['filter_type'] ?? '');

			if (empty($type)) {
				wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('select_type')));
				wp_die();
			}

			$keys = array('find_users_nonce', 'filter_type', 'user_search', 'products', 'user_role');
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
			$this->check_permissions(array(self::MANAGE_OPTIONS_CAP));
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

			foreach ($sanitized_users as $user) {
				wp_delete_user($user['id'], $user['reassign']);
			}

			wp_send_json_success();
			wp_die();
		} catch (\Exception $e) {
			wp_send_json_error(array('message' => UbdwpHelperFacade::get_error_message('generic_error')));
			wp_die();
		}
	}
}