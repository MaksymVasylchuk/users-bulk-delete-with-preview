<?php
/**
 * Logs Page
 *
 * @package     UsersBulkDeleteWithPreview\Pages
 */

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Handlers\UbdwpLogsHandler;

/**
 * Class for managing the Logs Page.
 */
class UbdwpLogsPage extends UbdwpBasePage {

	/** @var UbdwpLogsHandler Handler for managing logs data. */
	private $handler;

	/**
	 * Constructor to initialize the Logs Page.
	 */
	public function __construct() {
		parent::__construct();
		$this->handler = new UbdwpLogsHandler($this->current_user_id);
		$this->register_ajax_call('logs_datatables', array($this, 'handle_ajax_requests'));
	}

	/**
	 * Render the logs page.
	 */
	public function render(): void {
		$data = array(
			'title' => __('Logs Page', 'users-bulk-delete-with-preview'),
		);
		$this->render_template('logs-page.php', $data);
	}

	/**
	 * Register admin scripts for the logs page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	public function register_admin_scripts($hook_suffix): void {

		if (isset($_GET['page']) && $_GET['page'] === 'ubdwp_admin_logs') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce verification is not required here.

			wp_register_script(
				'wpubdp-dataTables-js',
				WPUBDP_PLUGIN_URL . 'assets/dataTables/datatables.min.js',
				array('jquery'),
				WPUBDP_PLUGIN_VERSION,
				true
			);

			wp_register_script(
				'wpubdp-logs-js',
				WPUBDP_PLUGIN_URL . 'assets/admin/logs.min.js',
				array('jquery', 'wpubdp-dataTables-js'),
				WPUBDP_PLUGIN_VERSION,
				true
			);

			wp_enqueue_script('wpubdp-dataTables-js');
			wp_enqueue_script('wpubdp-logs-js');

			wp_localize_script(
				'wpubdp-logs-js',
				'myAjax',
				array(
					'ajaxurl' => admin_url('admin-ajax.php'),
				)
			);

			// Localize DataTable language strings
			$translation = UbdwpHelperFacade::getDataTableTranslation();
			wp_localize_script('wpubdp-logs-js', 'dataTableLang', $translation);
		}
	}

	/**
	 * Handle AJAX requests for logs.
	 */
	public function handle_ajax_requests(): void {
		$this->check_permissions(array(self::MANAGE_OPTIONS_CAP));
		$this->verify_nonce('logs_datatable_nonce', 'logs_datatable_nonce', 'GET');

		// Fetch and format logs data via the handler
		$response = $this->handler->prepare_logs_data($_GET);

		// Send JSON response
		wp_send_json($response);
		wp_die();
	}
}
