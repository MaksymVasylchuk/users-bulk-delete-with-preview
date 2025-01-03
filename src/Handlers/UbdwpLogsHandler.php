<?php
/**
 * Logs Handler
 *
 * @package     UsersBulkDeleteWithPreview\Handlers
 */

namespace UsersBulkDeleteWithPreview\Handlers;

use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;
use UsersBulkDeleteWithPreview\Repositories\UbdwpAbstractLogsRepository;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handler class for managing logs in the Users Bulk Delete With Preview plugin.
 */
class UbdwpLogsHandler {
	/**
	 * Repository for managing logs.
	 *
	 * @var UbdwpAbstractLogsRepository $repository
	 */
	private UbdwpAbstractLogsRepository $repository;

	/**
	 * Constructor to initialize the logs handler.
	 *
	 * @param int $current_user_id Current user ID.
	 */
	public function __construct( int $current_user_id ) {
		$this->repository = new UbdwpAbstractLogsRepository( $current_user_id );
	}

	/**
	 * Insert a log record into the logs table.
	 *
	 * @param array<string, mixed> $user_data Data of the user action to log.
	 */
	public function insert_log( array $user_data ): void {
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
		$limit        = UbdwpValidationFacade::validate_positive_integer( $request['length'] ?? 10, 10 );
		$offset       = UbdwpValidationFacade::validate_positive_integer( $request['start'] ?? 0, 0 );
		$search_value = sanitize_text_field( $request['search']['value'] ?? '' );

		$where = $this->repository->build_where_clause( $search_value );

		$logs             = $this->repository->get_logs( $where, $limit, $offset );
		$total_records    = $this->repository->get_total_record_count();
		$filtered_records = $this->repository->get_filtered_record_count( $where );

		return [
			'draw'            => UbdwpValidationFacade::validate_positive_integer( $request['draw'] ?? 0, 0 ),
			'recordsTotal'    => $total_records,
			'recordsFiltered' => $filtered_records,
			'data'            => $this->format_logs_data( $logs ),
		];
	}

	/**
	 * Format logs data for display in a DataTable.
	 *
	 * @param array<int, object> $logs Raw logs data from the repository.
	 *
	 * @return array<int, array<int, mixed>> Formatted logs data.
	 */
	private function format_logs_data( array $logs ): array {
		$data = [];

		foreach ( $logs as $log ) {
			$deleted_user_data = json_decode( $log->user_deleted_data, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				continue;
			}

			$data[] = [
				intval( $log->ID ),
				sanitize_text_field( $log->display_name ),
				intval( $deleted_user_data['user_delete_count'] ?? 0 ),
				implode(
					', ',
					array_map(
						fn( $entry ) => sanitize_text_field( $entry['email'] ),
						$deleted_user_data['user_delete_data'] ?? []
					)
				),
				sanitize_text_field( $log->deletion_time ),
			];
		}

		return $data;
	}
}