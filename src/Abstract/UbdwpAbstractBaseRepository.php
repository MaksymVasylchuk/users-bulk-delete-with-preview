<?php
/**
 * Base Repository
 *
 * @package     UsersBulkDeleteWithPreview\Abstract
 */

namespace UsersBulkDeleteWithPreview\Abstract;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base repository class for database operations.
 */
abstract class UbdwpAbstractBaseRepository {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	protected \wpdb $wpdb;

	/**
	 * Table name with the WordPress prefix.
	 *
	 * @var string
	 */
	protected string $table_name;

	/**
	 * Current user ID.
	 *
	 * @var int
	 */
	protected int $current_user_id;

	/**
	 * Constructor to initialize the repository.
	 *
	 * @param string $table_name The table name without the WordPress prefix.
	 * @param int $current_user_id The current user ID.
	 */
	public function __construct( string $table_name, int $current_user_id ) {
		global $wpdb;
		$this->wpdb            = $wpdb;
		$this->table_name      = esc_sql( $wpdb->prefix . $table_name );
		$this->current_user_id = $current_user_id;
	}

	/**
	 * Insert a record into the table.
	 *
	 * @param array $data Key-value pairs for table columns and their values.
	 *
	 * @return void
	 */
	protected function insert( array $data ): void {
		$this->wpdb->insert( $this->table_name, $data );
	}

	/**
	 * Execute a prepared SELECT query.
	 *
	 * @param string $query SQL query with placeholders.
	 * @param array $params Parameters to bind to the query.
	 *
	 * @return array Results as objects.
	 */
	protected function select( string $query, array $params = array() ): array {
		return $this->wpdb->get_results( $this->wpdb->prepare( $query, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --  "prepare" is used here.
	}

	/**
	 * Execute a prepared query to fetch a single column.
	 *
	 * @param string $query SQL query with placeholders.
	 * @param array $params Parameters to bind to the query.
	 *
	 * @return array Results as a single column array.
	 */
	protected function get_col( string $query, array $params = array() ): array {
		return $this->wpdb->get_col( $this->wpdb->prepare( $query, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --  "prepare" is used here.
	}

	/**
	 * Get a single value result.
	 *
	 * @param string $query SQL query with placeholders.
	 * @param array $params Parameters to bind to the query.
	 *
	 * @return mixed Single value result.
	 */
	protected function get_var( string $query, array $params = array() ): mixed {
		return $this->wpdb->get_var( $this->wpdb->prepare( $query, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --  "prepare" is used here.
	}

	/**
	 * Count total rows in the table.
	 *
	 * @param string $where Optional WHERE clause for filtering.
	 *
	 * @return int Row count.
	 */
	protected function count( string $where = '' ): int {
		return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name already escaped, and where clause also.
	}
}