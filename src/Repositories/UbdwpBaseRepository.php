<?php

namespace UsersBulkDeleteWithPreview\Repositories;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

abstract class UbdwpBaseRepository {
	protected $wpdb;
	protected $table_name;

	public function __construct(string $table_name) {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = esc_sql($wpdb->prefix . $table_name);
	}

	/**
	 * Insert a record into the table.
	 *
	 * @param array $data Key-value pairs for table columns and their values.
	 */
	protected function insert(array $data): void {
		$this->wpdb->insert($this->table_name, $data);
	}

	/**
	 * Execute a prepared SELECT query.
	 *
	 * @param string $query  SQL query with placeholders.
	 * @param array  $params Parameters to bind to the query.
	 *
	 * @return array Results as objects.
	 */
	protected function select(string $query, array $params = []): array {
		return $this->wpdb->get_results($this->wpdb->prepare($query, $params));
	}

	/**
	 * Get a single value result.
	 *
	 * @param string $query  SQL query with placeholders.
	 * @param array  $params Parameters to bind to the query.
	 *
	 * @return mixed Single value result.
	 */
	protected function get_var(string $query, array $params = []) {
		return $this->wpdb->get_var($this->wpdb->prepare($query, $params));
	}

	/**
	 * Count total rows in the table.
	 *
	 * @param string $where Optional WHERE clause for filtering.
	 *
	 * @return int Row count.
	 */
	protected function count(string $where = ''): int {
		return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} {$where}");
	}
}

