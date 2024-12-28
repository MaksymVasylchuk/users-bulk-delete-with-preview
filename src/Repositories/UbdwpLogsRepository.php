<?php

namespace UsersBulkDeleteWithPreview\Repositories;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Repositories\UbdwpBaseRepository;

class UbdwpLogsRepository extends UbdwpBaseRepository {

	public function __construct($current_user_id) {
		parent::__construct('ubdwp_logs', $current_user_id);
	}


	public function insert_log($user_data): void {
		// Convert the user data array to JSON format.
		$user_data_json = wp_json_encode( $user_data );

		// Prepare and execute the SQL query to insert a new log record.
		$this->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- It is OK, it is a custom table
			array(
				'user_id'           => $this->current_user_id,
				'user_deleted_data' => $user_data_json,
				'deletion_time'     => current_time( 'mysql' ),
			)
		);
	}

	public function get_logs(string $where, int $limit, int $offset): array {
		$query = "
            SELECT t.ID, t.user_id, u.display_name, t.user_deleted_data, t.deletion_time
            FROM {$this->table_name} t
            INNER JOIN {$this->wpdb->users} u ON t.user_id = u.ID
            WHERE 1=1 {$where}
            LIMIT %d OFFSET %d
        ";

		return $this->select($query, [$limit, $offset]);
	}

	public function get_total_record_count(): int {
		return $this->count();
	}

	public function get_filtered_record_count(string $where): int {
		$query = "
            SELECT COUNT(*)
            FROM {$this->table_name} t
            INNER JOIN {$this->wpdb->users} u ON t.user_id = u.ID
            WHERE 1=1 {$where}
        ";

		return (int) $this->get_var($query);
	}

	public function build_where_clause(string $search_value): string {
		if (empty($search_value)) {
			return '';
		}

		return $this->wpdb->prepare(
			'AND (u.display_name LIKE %s OR t.user_deleted_data LIKE %s)',
			'%' . $this->wpdb->esc_like($search_value) . '%',
			'%' . $this->wpdb->esc_like($search_value) . '%'
		);
	}

}