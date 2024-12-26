<?php

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Repositories\UbdwpLogsRepository;
use UsersBulkDeleteWithPreview\Handlers\UbdwpLogsHandler;

class UbdwpLogsPage extends UbdwpBasePage {

	private $handler;

	public function __construct() {
		$this->handler = new UbdwpLogsHandler();
		$this->register_ajax_call('logs_datatables', [$this, 'handle_ajax_requests']);
	}

	/**
	 * Render the logs page.
	 */
	public function render(): void {
		$data = [
			'title' => __('Logs Page', 'users-bulk-delete-with-preview'),
		];
		$this->render_template('logs-page.php', $data);
	}

	/**
	 * Handle AJAX requests for logs.
	 */
	public function handle_ajax_requests(): void {
		$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );
		$this->verify_nonce(
			'logs_datatable_nonce',
			'logs_datatable_nonce',
			'GET'
		);

		// Fetch and format logs data via the handler
		$response = $this->handler->prepare_logs_data($_GET);

		// Send JSON response
		wp_send_json($response);
		wp_die();
	}
}