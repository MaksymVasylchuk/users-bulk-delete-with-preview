<?php

namespace UsersBulkDeleteWithPreview\Repositories;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Repositories\UbdwpBaseRepository;

class UbdwpLogsRepository extends UbdwpBaseRepository {

	public function __construct() {
		parent::__construct('ubdwp_logs');
	}


	public function insert_log(int $user_id, string $user_deleted_data, string $deletion_time): void {
		$this->insert([
			'user_id'           => $user_id,
			'user_deleted_data' => $user_deleted_data,
			'deletion_time'     => $deletion_time,
		]);
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

		global $wpdb;

		return $wpdb->prepare(
			'AND (u.display_name LIKE %s OR t.user_deleted_data LIKE %s)',
			'%' . $wpdb->esc_like($search_value) . '%',
			'%' . $wpdb->esc_like($search_value) . '%'
		);
	}

}