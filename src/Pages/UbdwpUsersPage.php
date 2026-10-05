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
use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;
use UsersBulkDeleteWithPreview\Handlers\UbdwpUsersHandler;
use UsersBulkDeleteWithPreview\Services\UbdwpDeletionJobs;
use UsersBulkDeleteWithPreview\Services\UbdwpPreviewSession;
use UsersBulkDeleteWithPreview\Services\UbdwpSelection;
use UsersBulkDeleteWithPreview\Services\UbdwpUserFinder;

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
	 * Constructor to initialize the Users Page.
	 */
	public function __construct() {
		$this->handler = new UbdwpUsersHandler( $this->get_current_user_id() );

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
			'batch_size'         => UbdwpDeletionJobs::get_batch_size(),
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
				'wpubdp-select2-js' => array(
					'path' => 'assets/select2/select2.min.js',
					'deps' => array( 'jquery' )
				),
			) );

			UbdwpHelperFacade::register_build_script( 'wpubdp-admin-js', 'users', array( 'wpubdp-select2-js', 'wpubdp-dataTables-js' ) );

			$running_job = ( new UbdwpDeletionJobs( get_current_user_id() ) )->get_running_background_job();

			UbdwpHelperFacade::localize_scripts( 'wpubdp-admin-js', array(
				'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				'reassignUsersNonce' => wp_create_nonce( 'ubdwp_search_reassign_users' ),
				'previewNonce'       => wp_create_nonce( 'ubdwp_preview' ),
				'logsUrl'            => admin_url( 'admin.php?page=ubdwp_admin_logs' ),
				'jobsUrl'            => admin_url( 'admin.php?page=ubdwp_admin_jobs' ),
				'runningJobId'       => $running_job ? (int) $running_job->ID : 0,
				'resultsLimit'       => 1000,
				'translations'       => array_merge(
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
		$this->handle_ajax_request( 'nonce', 'ubdwp_search_users', $this->get_view_capabilities(), function () {
			$search_data = array(
				'q' => sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			);

			return array( 'results' => $this->handler->search_users_ajax( $search_data ) );
		} );
	}

	/**
	 * Handle AJAX request to search user metadata.
	 *
	 * @return void
	 */
	public function search_usermeta_ajax(): void {
		$this->handle_ajax_request( 'nonce', 'ubdwp_search_usermeta', $this->get_view_capabilities(), function () {
			$sanitized_data = array(
				'q' => sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			);

			return $this->handler->search_usermeta_ajax( $sanitized_data );
		} );
	}

	/**
	 * Handle AJAX request to search users that can receive reassigned content.
	 *
	 * Users of the preview are excluded, because they may be deleted.
	 *
	 * @return void
	 */
	public function search_reassign_users_ajax(): void {
		$this->handle_ajax_request( 'nonce', 'ubdwp_search_reassign_users', $this->get_view_capabilities(), function () {
			$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.

			$search_data = array(
				'q'           => sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
				'preview_ids' => UbdwpPreviewSession::get_user_ids( $token, get_current_user_id() ) ?? array(),
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
		$this->handle_ajax_request( 'nonce', 'ubdwp_search_products', $this->get_view_capabilities(), function () {
			$search_data = array(
				'q' => sanitize_text_field( wp_unslash( $_POST['q'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			);

			return array( 'results' => $this->handler->search_products_ajax( $search_data ) );
		} );
	}

	/**
	 * Handle AJAX request to find users for deletion: stores the matching users as a preview.
	 *
	 * @return void
	 */
	public function search_users_for_delete_ajax(): void {
		$this->handle_ajax_request( 'find_users_nonce', 'ubdwp_find_users', $this->get_view_capabilities(), function () {
			$type    = sanitize_key( wp_unslash( $_POST['filter_type'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			$request = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, fields are sanitized by the finder.

			$user_ids = ( new UbdwpUserFinder( get_current_user_id() ) )->find( $type, is_array( $request ) ? $request : array() );

			if ( is_wp_error( $user_ids ) ) {
				wp_send_json_error( array( 'message' => $user_ids->get_error_message() ) );
				wp_die();
			}

			return array(
				'token' => UbdwpPreviewSession::create( get_current_user_id(), $user_ids ),
				'total' => count( $user_ids ),
			);
		} );
	}

	/**
	 * Handle AJAX request for one page of the preview table (DataTables server-side processing).
	 *
	 * @return void
	 */
	public function preview_page_ajax(): void {
		$this->handle_ajax_request( 'nonce', 'ubdwp_preview', $this->get_view_capabilities(), function () {
			$user_ids = $this->get_preview_user_ids();
			$page     = $this->handler->get_preview_page(
				$user_ids,
				absint( wp_unslash( $_POST['start'] ?? 0 ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, cast to integer.
				absint( wp_unslash( $_POST['length'] ?? 10 ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, cast to integer.
				sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
				absint( wp_unslash( $_POST['order_column'] ?? 1 ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, cast to integer.
				sanitize_key( wp_unslash( $_POST['order_dir'] ?? 'asc' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			);

			return array(
				'draw'            => absint( wp_unslash( $_POST['draw'] ?? 0 ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, cast to integer.
				'recordsTotal'    => count( $user_ids ),
				'recordsFiltered' => $page['filtered'],
				'data'            => $page['rows'],
			);
		} );
	}

	/**
	 * Handle AJAX request for user export to CSV.
	 *
	 * @return void
	 */
	public function custom_export_users_action(): void {
		$this->handle_ajax_request( 'export_users_nonce', 'ubdwp_export_users', $this->get_view_capabilities(), function () {
			$selection = $this->get_requested_selection();

			return array(
				'file_name' => $this->handler->get_csv_file_name(),
				'content'   => $this->handler->generate_csv_for_ids( $selection['ids'] ),
			);
		} );
	}

	/**
	 * Handle AJAX request that creates a deletion job for the selected users.
	 *
	 * @return void
	 */
	public function create_job_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$job = $this->get_jobs()->create( $this->get_requested_selection() );

			return $this->job_response( $job );
		} );
	}

	/**
	 * Handle AJAX request that summarizes the next chunk of a deletion job.
	 *
	 * @return void
	 */
	public function job_summary_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$jobs = $this->get_jobs();
			$job  = $jobs->get_owned( $this->get_requested_job_id() );

			return $this->job_response( is_wp_error( $job ) ? $job : $jobs->summarize( $job ) );
		} );
	}

	/**
	 * Handle AJAX request that starts a summarized deletion job.
	 *
	 * @return void
	 */
	public function job_start_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$jobs = $this->get_jobs();
			$job  = $jobs->get_owned( $this->get_requested_job_id() );
			$mode = sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.

			// WP-CLI jobs are only started by WP-CLI.
			if ( ! in_array( $mode, array( UbdwpDeletionJobs::MODE_BROWSER, UbdwpDeletionJobs::MODE_BACKGROUND ), true ) ) {
				$mode = UbdwpDeletionJobs::MODE_BROWSER;
			}

			if ( ! is_wp_error( $job ) ) {
				$job = $jobs->start( $job, $mode, sanitize_text_field( wp_unslash( $_POST['confirm'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			}

			return $this->job_response( $job );
		} );
	}

	/**
	 * Handle AJAX request that deletes the next batch of a job run in the browser.
	 *
	 * @return void
	 */
	public function job_process_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$jobs = $this->get_jobs();
			$job  = $jobs->get_owned( $this->get_requested_job_id() );

			if ( is_wp_error( $job ) ) {
				return $this->job_response( $job );
			}

			if ( UbdwpDeletionJobs::MODE_BROWSER !== $job->mode ) {
				return $this->job_response( UbdwpValidationFacade::to_wp_error( 'job_invalid_state' ) );
			}

			$result = $jobs->process( $job );

			if ( is_wp_error( $result ) ) {
				return $this->job_response( $result );
			}

			return array_merge( $this->job_response( $result['job'] ), array(
				'template'        => $result['template'],
				'failed_template' => $result['failed_template'],
			) );
		} );
	}

	/**
	 * Handle AJAX request for the status of a job.
	 *
	 * When a background job has not moved for 30 seconds (for example when WP-Cron or Action Scheduler
	 * cannot run on the server), its owner's status request deletes batches for a few seconds,
	 * so the job keeps going while the page is open.
	 *
	 * @return void
	 */
	public function job_status_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$jobs = $this->get_jobs();
			$job  = $jobs->get_owned( $this->get_requested_job_id() );

			if (
				! is_wp_error( $job ) &&
				UbdwpDeletionJobs::MODE_BACKGROUND === $job->mode &&
				UbdwpDeletionJobs::STATUS_RUNNING === $job->status &&
				strtotime( $job->updated_at . ' UTC' ) < time() - 30
			) {
				$deadline = microtime( true ) + 10;

				do {
					$result = $jobs->process( $job );
					$job    = is_wp_error( $result ) ? $jobs->get( (int) $job->ID ) : $result['job'];
				} while ( ! is_wp_error( $result ) && UbdwpDeletionJobs::STATUS_RUNNING === $job->status && microtime( true ) < $deadline );
			}

			return $this->job_response( $job );
		} );
	}

	/**
	 * Handle AJAX request that cancels a job. Any user who can delete users may cancel a job of the site.
	 *
	 * @return void
	 */
	public function job_cancel_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$jobs = $this->get_jobs();
			$job  = $jobs->get( $this->get_requested_job_id() );

			return $this->job_response( $job ? $jobs->cancel( $job ) : UbdwpValidationFacade::to_wp_error( 'job_not_found' ) );
		} );
	}

	/**
	 * Handle AJAX request that removes a job the user did not confirm.
	 *
	 * @return void
	 */
	public function job_discard_action(): void {
		$this->handle_ajax_request( 'delete_users_nonce', 'ubdwp_delete_users', $this->get_delete_capabilities(), function () {
			$jobs = $this->get_jobs();
			$job  = $jobs->get_owned( $this->get_requested_job_id() );

			return array( 'discarded' => ! is_wp_error( $job ) && $jobs->discard( $job ) );
		} );
	}

	/**
	 * Capabilities required to preview and export users.
	 *
	 * @return array<string> Capabilities.
	 */
	private function get_view_capabilities(): array {
		return array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
		);
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
	 * Deletion jobs of the current user.
	 *
	 * @return UbdwpDeletionJobs Jobs service.
	 */
	private function get_jobs(): UbdwpDeletionJobs {
		return new UbdwpDeletionJobs( get_current_user_id() );
	}

	/**
	 * Read the job ID from the request.
	 *
	 * @return int Job ID.
	 */
	private function get_requested_job_id(): int {
		return absint( wp_unslash( $_POST['job_id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, cast to integer.
	}

	/**
	 * Read the users of the preview named in the request.
	 *
	 * Sends a JSON error and stops when the preview does not exist, expired or belongs to someone else.
	 *
	 * @return array<int> User IDs.
	 */
	private function get_preview_user_ids(): array {
		$token    = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
		$user_ids = UbdwpPreviewSession::get_user_ids( $token, get_current_user_id() );

		if ( null === $user_ids ) {
			UbdwpValidationFacade::send_error_response( 'preview_expired' );
		}

		return $user_ids;
	}

	/**
	 * Read the selection sent with the request and resolve it against the preview.
	 *
	 * The selection is one JSON field, so PHP's max_input_vars cannot cut large selections short.
	 * Sends a JSON error and stops when no user is selected.
	 *
	 * @return array{ids: array<int>, default: string, reassign: array<int, string>} Selection.
	 */
	private function get_requested_selection(): array {
		$preview_ids = $this->get_preview_user_ids();
		$selection   = UbdwpSelection::resolve(
			$preview_ids,
			UbdwpSelection::decode( wp_unslash( $_POST['selection_json'] ?? '' ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce is checked in "handle_ajax_request" method, values are sanitized by UbdwpSelection.
		);

		if ( empty( $selection['ids'] ) ) {
			UbdwpValidationFacade::send_error_response( 'select_any_user' );
		}

		return $selection;
	}

	/**
	 * Build the JSON response for a job, or send the error.
	 *
	 * @param object|\WP_Error|null $job Job.
	 *
	 * @return array<string, mixed> Job status with rendered user lists.
	 */
	private function job_response( $job ): array {
		if ( is_wp_error( $job ) || ! $job ) {
			wp_send_json_error( array( 'message' => is_wp_error( $job ) ? $job->get_error_message() : UbdwpValidationFacade::get_error_message( 'job_not_found' ) ) );
			wp_die();
		}

		$status = $this->get_jobs()->to_status( $job );

		$status['skipped_template'] = UbdwpViewsFacade::render_template( 'partials/_failed_user_delete.php', array( 'failed_users' => $status['summary']['skipped'] ) );
		$status['failed_users_template'] = UbdwpViewsFacade::render_template( 'partials/_failed_user_delete.php', array( 'failed_users' => $status['failed_users'] ) );

		unset( $status['summary']['skipped'], $status['failed_users'] );

		return $status;
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
			'ubdwp_preview_page'            => 'preview_page_ajax',
			'ubdwp_search_reassign_users'   => 'search_reassign_users_ajax',
			'ubdwp_search_products'         => 'search_products_ajax',
			'ubdwp_export_users'            => 'custom_export_users_action',
			'ubdwp_create_job'              => 'create_job_action',
			'ubdwp_job_summary'             => 'job_summary_action',
			'ubdwp_job_start'               => 'job_start_action',
			'ubdwp_job_process'             => 'job_process_action',
			'ubdwp_job_status'              => 'job_status_action',
			'ubdwp_job_cancel'              => 'job_cancel_action',
			'ubdwp_job_discard'             => 'job_discard_action',
		);

		foreach ( $ajax_calls as $action => $method ) {
			$this->register_ajax_call( $action, array( $this, $method ) );
		}
	}
}
