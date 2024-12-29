<?php
/**
 * Logs Page
 *
 * @package     UsersBulkDeleteWithPreview\Pages
 */

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractBasePage;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Handlers\UbdwpLogsHandler;

/**
 * Class for managing the Logs Page.
 */
class UbdwpLogsPage extends UbdwpAbstractBasePage {

	/** @var UbdwpLogsHandler Handler for managing logs data. */
	private $handler;

	/**
	 * Constructor to initialize the Logs Page.
	 */
	public function __construct() {
		$this->handler = new UbdwpLogsHandler( $this->get_current_user_id() );
		$this->register_ajax_call( 'logs_datatables',
			array( $this, 'handle_ajax_requests' ) );
	}

	/**
	 * Render the logs page.
	 */
	public function render(): void {
		$data = array(
			'title' => __( 'Logs Page', 'users-bulk-delete-with-preview' ),
		);
		$this->render_template( 'logs-page.php', $data );
	}

	/**
	 * Register admin scripts for the logs page.
	 *
	 * @param  string  $hook_suffix  The hook suffix for the current admin page.
	 */
	public function register_admin_scripts( string $hook_suffix ): void {
		if ( isset( $_GET['page'] )
		     && $_GET['page'] === 'ubdwp_admin_logs'
		) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce verification is not required here.
			UbdwpHelperFacade::register_common_scripts( [
				'wpubdp-logs-js' => [
					'path' => 'assets/admin/logs.min.js',
					'deps' => [
						'jquery',
						'wpubdp-dataTables-js',
						'wp-i18n'
					],
				],
			] );

			UbdwpHelperFacade::localize_scripts( 'wpubdp-logs-js', [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'translations' => array_merge(
					UbdwpHelperFacade::getDataTableTranslation(),
					UbdwpHelperFacade::getUserTableTranslation()
				),
			] );
		}
	}

	/**
	 * Handle AJAX requests for logs.
	 */
	public function handle_ajax_requests(): void {
		try {
			$this->check_permissions( array( self::MANAGE_OPTIONS_CAP ) );
			$this->verify_nonce( 'logs_datatable_nonce',
				'logs_datatable_nonce',
				'GET' );

			// Fetch and format logs data via the handler
			$response = $this->handler->prepare_logs_data( $_GET );

			// Send JSON response
			wp_send_json( $response );
			wp_die();
		} catch ( \Exception $e ) {
			wp_send_json_error( array( 'message' => UbdwpHelperFacade::get_error_message( 'generic_error' ) ) );
			wp_die();
		}
	}
}
