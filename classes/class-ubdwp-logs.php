<?php
/**
 * Logs class
 *
 * @package     UsersBulkDeleteWithPreview\Classes
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! class_exists( 'UBDWPLogs' ) ) {
	/**
	 * Class for handling log records in the Users Bulk Delete With Preview plugin.
	 */
	class UBDWPLogs {
		/**
		 * Current user ID
		 *
		 * @var int.
		 */
		private $current_user_id;
		/**
		 * Custom table name
		 *
		 * @var string.
		 */
		private $table_name;

		/**
		 * Constructor initializes the class with current user ID and log table name.
		 */
		public function __construct() {
			global $wpdb;
			$this->current_user_id = get_current_user_id();
			$this->table_name      = "{$wpdb->prefix}ubdwp_logs";
		}

		/**
		 * Insert a new log record into the logs table.
		 *
		 * @param  array $user_data  Data about the users that were deleted.
		 */
		public function insert_log_record( array $user_data ): void {
			global $wpdb;

			// Convert the user data array to JSON format.
			$user_data_json = wp_json_encode( $user_data );

			// Prepare and execute the SQL query to insert a new log record.
			$wpdb->insert(
				$this->table_name,
				array(
					'user_id'           => $this->current_user_id,
					'user_deleted_data' => $user_data_json,
					'deletion_time'     => current_time( 'mysql' ),
				)
			);
		}

		/**
		 * Retrieve log records and prepare them for display in a data table.
		 *
		 * @param  array $request  The request data, including pagination and search parameters.
		 */
		public function logs_data_table( array $request ): void {

			// Prepare the parameters.
			$limit        = intval( sanitize_text_field( $request['length'] ) );
			$offset       = intval( sanitize_text_field( $request['start'] ) );
			$search_value = sanitize_text_field( $request['search']['value'] ) ?? '';

			// Build the WHERE clause for search functionality.
			$where = $this->build_where_clause( $search_value );

			// Retrieve the log records.
			$rows = $this->get_log_records( $where, $limit, $offset );

			// Retrieve the total and filtered record counts.
			$total_data    = $this->get_total_record_count();
			$filtered_data = $this->get_filtered_record_count( $where );

			// Prepare the data for the response.
			$data = $this->prepare_log_data( $rows );

			// Create the response array.
			$response = array(
				'draw'            => intval( sanitize_text_field( $request['draw'] ) ),
				'recordsTotal'    => intval( $total_data ),
				'recordsFiltered' => intval( $filtered_data ),
				'data'            => $data,
			);

			// Output the JSON response and exit.
			wp_send_json( $response );
			wp_die();
		}

		/**
		 * Build the WHERE clause for the search functionality.
		 *
		 * @param  string $search_value  The search term.
		 *
		 * @return string The constructed WHERE clause.
		 */
		private function build_where_clause( string $search_value ): string {
			global $wpdb;

			if ( empty( $search_value ) ) {
				return '';
			}

			return $wpdb->prepare(
				'AND (u.display_name LIKE %s OR t.user_deleted_data LIKE %s)',
				'%' . $wpdb->esc_like( $search_value ) . '%',
				'%' . $wpdb->esc_like( $search_value ) . '%'
			);
		}

		/**
		 * Retrieve log records from the database.
		 *
		 * @param  string $where   The WHERE clause for filtering.
		 * @param  int    $limit   The number of records to retrieve.
		 * @param  int    $offset  The offset for pagination.
		 *
		 * @return array The log records.
		 */
		private function get_log_records( string $where, int $limit, int $offset ) {
			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT t.ID, t.user_id, u.display_name, t.user_deleted_data, t.deletion_time 
                FROM $this->table_name t
                INNER JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
                WHERE 1=1 {$where}
                LIMIT %d OFFSET %d",
				$limit,
				$offset
			);

			return $wpdb->get_results( $query ); // db call ok; no-cache ok.
		}

		/**
		 * Get the total number of records in the logs table.
		 *
		 * @return int The total number of records.
		 */
		private function get_total_record_count(): int {
			global $wpdb;

			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $this->table_name" ) ); // db call ok; no-cache ok.
		}

		/**
		 * Get the total number of filtered records in the logs table.
		 *
		 * @param  string $where  The WHERE clause for filtering.
		 *
		 * @return int The total number of filtered records.
		 */
		private function get_filtered_record_count( string $where ): int {
			global $wpdb;

			$query = "SELECT COUNT(*) 
                      FROM $this->table_name t
                      INNER JOIN {$wpdb->prefix}users u ON t.user_id = u.ID
                      WHERE 1=1 {$where}";

			return (int) $wpdb->get_var( $wpdb->prepare( $query ) ); // db call ok; no-cache ok.
		}

		/**
		 * Prepare the log data for the DataTables response.
		 *
		 * @param  array $rows  The log records retrieved from the database.
		 *
		 * @return array The formatted log data.
		 */
		private function prepare_log_data( array $rows ): array {
			$data = array();

			foreach ( $rows as $row ) {
				$deleted_user_data = json_decode(
					$row->user_deleted_data,
					true
				);
				$data[]            = array(
					intval( $row->ID ),
					sanitize_text_field( $row->display_name ),
					intval( $deleted_user_data['user_delete_count'] ) ?? 0,
					implode(
						', ',
						array_map(
							function ( $entry ) {
								return $entry['email'];
							},
							$deleted_user_data['user_delete_data'] ?? array()
						)
					),
					$row->deletion_time,
				);
			}

			return $data;
		}
	}
}
