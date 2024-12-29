<?php
/**
 * Logs Repository
 *
 * @package     UsersBulkDeleteWithPreview\Repositories
 */

namespace UsersBulkDeleteWithPreview\Repositories;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractBaseRepository;

/**
 * Repository for managing log records in the Users Bulk Delete With Preview plugin.
 */
class UbdwpAbstractLogsRepository extends UbdwpAbstractBaseRepository {

	/**
	 * Constructor to initialize the Logs Repository.
	 *
	 * @param int $current_user_id Current user ID.
	 *
	 * @return void
	 */
	public function __construct( int $current_user_id ) {
		parent::__construct( 'ubdwp_logs', $current_user_id );
	}

	/**
	 * Insert a new log record into the logs table.
	 *
	 * @param string $user_data User data to log in JSON format.
	 *
	 * @return void
	 */
	public function insert_log( string $user_data ): void {
		// Prepare and execute the SQL query to insert a new log record.
		$this->insert( array(
			'user_id'           => $this->current_user_id,
			'user_deleted_data' => $user_data,
			'deletion_time'     => current_time( 'mysql' ),
		) );
	}

	/**
	 * Retrieve logs with optional filters, pagination, and sorting.
	 *
	 * @param string $where  WHERE clause for filtering log records.
	 * @param int    $limit  Maximum number of records to retrieve.
	 * @param int    $offset Number of records to skip.
	 *
	 * @return array<int, object> Logs as an array of result objects.
	 */
	public function get_logs( string $where, int $limit, int $offset ): array {
		$query = "
            SELECT t.ID, t.user_id, u.display_name, t.user_deleted_data, t.deletion_time
            FROM {$this->table_name} t
            INNER JOIN {$this->wpdb->users} u ON t.user_id = u.ID
            WHERE 1=1 {$where}
            ORDER BY t.deletion_time DESC
            LIMIT %d OFFSET %d
        ";

		return $this->select( $query, array( $limit, $offset ) );
	}

	/**
	 * Get the total number of log records.
	 *
	 * @return int Total count of log records.
	 */
	public function get_total_record_count(): int {
		return $this->count();
	}

	/**
	 * Get the number of filtered log records based on a WHERE clause.
	 *
	 * @param string $where WHERE clause for filtering log records.
	 *
	 * @return int Filtered count of log records.
	 */
	public function get_filtered_record_count( string $where ): int {
		$query = "
            SELECT COUNT(*)
            FROM {$this->table_name} t
            INNER JOIN {$this->wpdb->users} u ON t.user_id = u.ID
            WHERE 1=1 {$where}
        ";

		return (int) $this->get_var( $query );
	}

	/**
	 * Build a WHERE clause for filtering log records based on a search value.
	 *
	 * @param string $search_value Search term to filter log records.
	 *
	 * @return string WHERE clause for the search term.
	 */
	public function build_where_clause( string $search_value ): string {
		if ( empty( $search_value ) ) {
			return '';
		}

		return $this->wpdb->prepare(
			'AND (u.display_name LIKE %s OR t.user_deleted_data LIKE %s)',
			'%' . $this->wpdb->esc_like( $search_value ) . '%',
			'%' . $this->wpdb->esc_like( $search_value ) . '%'
		);
	}
}