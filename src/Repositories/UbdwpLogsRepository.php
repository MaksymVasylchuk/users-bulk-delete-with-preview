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
class UbdwpLogsRepository extends UbdwpAbstractBaseRepository {
	/**
	 * Constructor to initialize the Logs Repository.
	 *
	 * @param int $current_user_id Current user ID.
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
		$this->insert( array(
			'user_id'           => $this->current_user_id,
			'user_deleted_data' => $user_data,
			'deletion_time'     => current_time( 'mysql' ),
		) );
	}

	/**
	 * Retrieve logs with optional filters, pagination, and sorting.
	 *
	 * @param string $where WHERE clause for filtering log records.
	 * @param int $limit Maximum number of records to retrieve.
	 * @param int $offset Number of records to skip.
	 *
	 * @return array<int, object> Logs as an array of result objects.
	 */
	public function get_logs( string $where, int $limit, int $offset ): array {
		$query = "
            SELECT t.ID, t.user_id, u.display_name, t.user_deleted_data, t.deletion_time
            FROM {$this->table_name} t
            LEFT JOIN {$this->wpdb->users} u ON t.user_id = u.ID
            WHERE 1=1 {$where}
            ORDER BY t.deletion_time DESC, t.ID DESC
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
            LEFT JOIN {$this->wpdb->users} u ON t.user_id = u.ID
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

	/**
	 * Delete log records older than a number of days.
	 *
	 * @param int $days Age in days.
	 *
	 * @return int Number of deleted records.
	 */
	public function delete_older_than( int $days ): int {
		// Deletion times are stored in the site's timezone (current_time( 'mysql' )).
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - max( 0, $days ) * DAY_IN_SECONDS );

		return $this->execute( "DELETE FROM {$this->table_name} WHERE deletion_time < %s", array( $cutoff ) );
	}

	/**
	 * Delete all log records.
	 *
	 * @return int Number of deleted records.
	 */
	public function delete_all(): int {
		return $this->execute( "DELETE FROM {$this->table_name}" );
	}

	/**
	 * Get log records after an ID, for processing all records in batches.
	 *
	 * @param int $after_id Last processed ID.
	 * @param int $limit    Batch size.
	 *
	 * @return array<object> Records with ID, user_id and user_deleted_data.
	 */
	public function get_rows_after( int $after_id, int $limit ): array {
		return $this->select(
			"SELECT ID, user_id, user_deleted_data FROM {$this->table_name} WHERE ID > %d ORDER BY ID ASC LIMIT %d",
			array( $after_id, $limit )
		);
	}

	/**
	 * Get log records about a person: deletions of their account or deletions they performed.
	 *
	 * @param string $json_email Email as it is encoded in the stored JSON (without quotes).
	 * @param int    $user_id    Their user ID, 0 when the account no longer exists.
	 * @param int    $limit      Number of records.
	 * @param int    $offset     Offset.
	 *
	 * @return array<object> Records.
	 */
	public function get_rows_about_person( string $json_email, int $user_id, int $limit, int $offset ): array {
		return $this->select(
			"SELECT ID, user_id, user_deleted_data, deletion_time FROM {$this->table_name}
            WHERE user_deleted_data LIKE %s OR ( %d > 0 AND user_id = %d )
            ORDER BY ID ASC LIMIT %d OFFSET %d",
			array( '%' . $this->wpdb->esc_like( '"' . $json_email . '"' ) . '%', $user_id, $user_id, $limit, $offset )
		);
	}

	/**
	 * Update a log record.
	 *
	 * @param int                  $log_id Record ID.
	 * @param array<string, mixed> $fields Column values.
	 *
	 * @return void
	 */
	public function update_row( int $log_id, array $fields ): void {
		$this->wpdb->update( $this->table_name, $fields, array( 'ID' => $log_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
	}
}
