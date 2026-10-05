<?php
/**
 * Logs Handler
 *
 * @package     UsersBulkDeleteWithPreview\Handlers
 */

namespace UsersBulkDeleteWithPreview\Handlers;

use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;
use UsersBulkDeleteWithPreview\Repositories\UbdwpLogsRepository;
use UsersBulkDeleteWithPreview\Services\UbdwpPrivacy;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handler class for managing logs in the Users Bulk Delete With Preview plugin.
 */
class UbdwpLogsHandler {
	/**
	 * Repository for managing logs.
	 *
	 * @var UbdwpLogsRepository $repository
	 */
	private UbdwpLogsRepository $repository;

	/**
	 * Constructor to initialize the logs handler.
	 *
	 * @param int $current_user_id Current user ID.
	 */
	public function __construct( int $current_user_id ) {
		$this->repository = new UbdwpLogsRepository( $current_user_id );
	}

	/**
	 * Insert a log record into the logs table.
	 *
	 * @param array<string, mixed> $user_data Data of the user action to log.
	 */
	public function insert_log( array $user_data ): void {
		// Store emails and names as the privacy settings say (in full, masked or not at all).
		if ( isset( $user_data['user_delete_data'] ) && is_array( $user_data['user_delete_data'] ) ) {
			$user_data['user_delete_data'] = UbdwpPrivacy::prepare_log_entries( $user_data['user_delete_data'] );
		}

		$user_data_json = wp_json_encode( $user_data );

		if ( false === $user_data_json ) {
			return;
		}

		$this->repository->insert_log( $user_data_json );
	}

	/**
	 * Prepare logs data for display in a DataTable.
	 *
	 * @param array<string, mixed> $request Request parameters for fetching logs.
	 *
	 * @return array<string, mixed> Prepared logs data including metadata for DataTables.
	 */
	public function prepare_logs_data( array $request ): array {
		$limit        = min( 100, UbdwpValidationFacade::validate_positive_integer( $request['length'] ?? 10, 10 ) );
		$offset       = UbdwpValidationFacade::validate_positive_integer( $request['start'] ?? 0, 0 );
		$search_value = sanitize_text_field( $request['search']['value'] ?? '' );

		$where = $this->repository->build_where_clause( $search_value );

		$logs             = $this->repository->get_logs( $where, $limit, $offset );
		$total_records    = $this->repository->get_total_record_count();
		$filtered_records = $this->repository->get_filtered_record_count( $where );

		return array(
			'draw'            => UbdwpValidationFacade::validate_positive_integer( $request['draw'] ?? 0, 0 ),
			'recordsTotal'    => $total_records,
			'recordsFiltered' => $filtered_records,
			'data'            => $this->format_logs_data( $logs ),
		);
	}

	/**
	 * Format logs data for display in a DataTable.
	 *
	 * @param array<int, object> $logs Raw logs data from the repository.
	 *
	 * @return array<int, array<int, mixed>> Formatted logs data.
	 */
	private function format_logs_data( array $logs ): array {
		$data = array();

		foreach ( $logs as $log ) {
			$deleted_user_data = json_decode( $log->user_deleted_data, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				continue;
			}

			$data[] = array(
				intval( $log->ID ),
				$this->format_performer( $log ),
				intval( $deleted_user_data['user_delete_count'] ?? 0 ),
				implode(
					', ',
					array_map(
						// Entries stored without an email (privacy settings or erasure) show the user ID.
						fn( $entry ) => '' !== (string) ( $entry['email'] ?? '' ) ? sanitize_text_field( $entry['email'] ) : '#' . absint( $entry['user_id'] ?? 0 ),
						$deleted_user_data['user_delete_data'] ?? []
					)
				),
				sanitize_text_field( $log->deletion_time ),
			);
		}

		return $data;
	}

	/**
	 * Name of the user who performed a deletion.
	 *
	 * @param object $log Log record.
	 *
	 * @return string Name.
	 */
	private function format_performer( object $log ): string {
		if ( null !== $log->display_name ) {
			return sanitize_text_field( $log->display_name );
		}

		if ( 0 === (int) $log->user_id ) {
			return __( 'Anonymized user', 'users-bulk-delete-with-preview' );
		}

		/* translators: %d: ID of the deleted administrator who performed the deletion. */
		return sprintf( __( 'Deleted user #%d', 'users-bulk-delete-with-preview' ), (int) $log->user_id );
	}
}
