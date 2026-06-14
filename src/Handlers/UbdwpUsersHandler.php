<?php
/**
 * Users Handler
 *
 * @package     UsersBulkDeleteWithPreview\Handlers
 */

namespace UsersBulkDeleteWithPreview\Handlers;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;
use UsersBulkDeleteWithPreview\Repositories\UbdwpUsersRepository;
use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handler class for managing users in the Users Bulk Delete With Preview plugin.
 */
class UbdwpUsersHandler {
	/**
	 * Repository for managing users.
	 *
	 * @var UbdwpUsersRepository
	 */
	public UbdwpUsersRepository $repository;

	/**
	 * Current user ID.
	 *
	 * @var int
	 */
	private int $current_user_id;

	/**
	 * Constructor to initialize the Users Handler.
	 *
	 * @param int $current_user_id Current user ID.
	 */
	public function __construct( int $current_user_id ) {
		$this->repository      = new UbdwpUsersRepository( $current_user_id );
		$this->current_user_id = $current_user_id;
	}

	/**
	 * Handle AJAX request to search users.
	 *
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return array<int, array<string, string>> List of matching users.
	 */
	public function search_users_ajax( array $request ): array {
		$search_term = $request['q'] ?? '';
		$select_all  = ! empty( $request['select_all'] );

		$args = array(
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
			'fields'         => array( 'ID', 'display_name', 'user_email' ),
		);

		if ( $select_all ) {
			$args['number'] = - 1; // Fetch all users.
		} else {
			$args['search'] = '*' . esc_attr( $search_term ) . '*';
		}

		$user_query = $this->repository->search_users_ajax( $args );
		$results    = array();

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				if ( (int) $user->ID !== $this->current_user_id ) {
					$results[] = array(
						'id'   => (string) $user->ID,
						'text' => sprintf( '%s (%s)', sanitize_text_field( $user->display_name ), sanitize_email( $user->user_email ) ),
					);
				}
			}
		}

		return $results;
	}

	/**
	 * Handle AJAX request to search user metadata.
	 *
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return array<int, array<string, string>> List of matching meta keys.
	 */
	public function search_usermeta_ajax( array $request ): array {
		$search  = $request['q'] ?? '';
		$results = $this->repository->search_usermeta_ajax( $search );

		return array_map( static function ( $result ) {
			return array(
				'id'   => sanitize_key( $result->meta_key ),
				'text' => sanitize_text_field( $result->meta_key ),
			);
		}, $results );
	}

	/**
	 * Get users by their IDs.
	 *
	 * @param array<int> $user_ids List of user IDs.
	 *
	 * @return array|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_ids( array $user_ids ) {
		if ( empty( $user_ids ) || ! is_array( $user_ids ) ) {
			return new \WP_Error( 'invalid_input', UbdwpValidationFacade::get_error_message( 'invalid_input' ) );
		}

		$user_ids = array_unique( array_map( 'intval', $user_ids ) );
		$users    = $this->repository->get_users_by_ids( $user_ids );

		if ( ! empty( $users ) ) {
			return UbdwpHelperFacade::prepare_users_for_table( $users, $this->repository );
		}

		return new \WP_Error( 'no_users_found', UbdwpValidationFacade::get_error_message( 'no_users_found' ) );
	}

	/**
	 * Get users by various filters.
	 *
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return array|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_filters( array $request ) {
		$args = array(
			'exclude'    => $this->current_user_id, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude --  In this case we need to exclude current user.
			'meta_query' => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query --  DB call is OK.
			'date_query' => array(),
		);

		$user_query = $this->repository->get_users_by_filters( $args, $request );

		if ( ! empty( $user_query->get_results() ) ) {
			return UbdwpHelperFacade::prepare_users_for_table( $user_query->get_results(), $this->repository );
		}

		return new \WP_Error( 'no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message( 'no_users_found_with_given_filters' ) );
	}

	/**
	 * Get users who purchased specific WooCommerce products.
	 *
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return array|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_woocommerce_filters( array $request ) {
		$products = array_unique( array_map( 'absint', $request['products'] ?? array() ) );
		$user_ids = $this->repository->get_users_by_product_purchase( $products );

		$user_ids = array_filter( $user_ids, static fn( $value ) => $value !== 0 && $value !== '0' );

		if ( ! empty( $user_ids ) ) {
			$user_ids = array_unique( $user_ids );
			$users    = $this->repository->get_users_by_ids( $user_ids );

			return UbdwpHelperFacade::prepare_users_for_table( $users, $this->repository );
		}

		return new \WP_Error( 'no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message( 'no_users_found_with_given_filters' ) );
	}

	/**
	 * Generate CSV content from the user list.
	 *
	 * @param array<object> $users List of user objects.
	 *
	 * @return string CSV content.
	 */
	public function generate_csv( array $users ): string {
		// Create a temporary file object in memory.
		$temp_file = new \SplTempFileObject();

		// Add CSV header.
		$temp_file->fputcsv( array( 'ID', 'Username', 'Email', 'First Name', 'Last Name', 'Role' ) );

		// Add user data.
		foreach ( $users as $user ) {
			$temp_file->fputcsv( array(
				(int) $user->ID,
				$this->escape_csv_cell( sanitize_text_field( $user->user_login ) ),
				$this->escape_csv_cell( sanitize_email( $user->user_email ) ),
				$this->escape_csv_cell( sanitize_text_field( $user->first_name ) ),
				$this->escape_csv_cell( sanitize_text_field( $user->last_name ) ),
				$this->escape_csv_cell( implode( ', ', array_map( 'sanitize_text_field', $user->roles ) ) ),
			) );
		}

		// Rewind the file pointer and retrieve the content.
		$temp_file->rewind();
		$csv_content = '';
		while ( ! $temp_file->eof() ) {
			$csv_content .= $temp_file->fgets();
		}

		return $csv_content;
	}

	/**
	 * Save the generated CSV content to a file.
	 *
	 * @param string $csv_output The CSV content.
	 *
	 * @return array<string, string>|\WP_Error URL and path of the saved file or error on failure.
	 */
	public function save_csv_file( string $csv_output ) {
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		$upload_dir = wp_upload_dir();
		$export_dir = $this->get_export_dir();

		if ( ! wp_mkdir_p( $export_dir ) ) {
			return new \WP_Error( 'file_write_error', __( 'Failed to create the CSV export directory.', 'users-bulk-delete-with-preview' ) );
		}

		$index_file = trailingslashit( $export_dir ) . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			$wp_filesystem->put_contents( $index_file, "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}

		$file_name = wp_unique_filename( $export_dir, 'users_export_' . time() . '.csv' );
		$file_path = trailingslashit( $export_dir ) . $file_name;

		if ( ! $wp_filesystem->put_contents( $file_path, $csv_output, FS_CHMOD_FILE ) ) {
			return new \WP_Error( 'file_write_error', __( 'Failed to write the CSV file.', 'users-bulk-delete-with-preview' ) );
		}

		return array(
			'file_url'  => trailingslashit( $upload_dir['baseurl'] ) . 'ubdwp-exports/' . basename( $file_path ),
			'file_path' => $file_path,
		);
	}

	/**
	 * Delete users and their related data.
	 *
	 * @param array<int, array<string, mixed>> $sanitized_users List of users to delete.
	 *
	 * @return array<string, mixed> Result of the deletion process.
	 */
	public function delete_users( array $sanitized_users ): array {
		$deleted_users = array();

		foreach ( $sanitized_users as $user ) {
			$user_id = (int) $user['id'];

			if ( $user_id <= 0 || $user_id === $this->current_user_id || ! get_userdata( $user_id ) ) {
				continue;
			}

			$reassign = $this->normalize_reassign_user_id( $user['reassign'] ?? null, $user_id );

			if ( $user_id > 0 ) {
				$deleted_users[ $user_id ] = array(
					'user_id'      => $user_id,
					'email'        => $user['email'],
					'display_name' => $user['display_name'],
					'reassign'     => $reassign ?? '',
				);

				if ( isset( $user['reassign'] ) && $user['reassign'] === 'remove_all_related_content' ) {
					$user_posts = get_posts( array(
						'author'      => $user_id,
						'post_type'   => 'any',
						'post_status' => 'any',
						'numberposts' => - 1,
						'fields'      => 'ids',
					) );

					foreach ( $user_posts as $post_id ) {
						wp_delete_post( $post_id, true );
					}

					$user_comments = get_comments( array( 'user_id' => $user_id ) );
					foreach ( $user_comments as $comment ) {
						wp_delete_comment( $comment->comment_ID, true );
					}

					$reassign = null;
				}

				wp_delete_user( $user_id, $reassign );
			}
		}

		$template = UbdwpViewsFacade::render_template(
			'partials/_success_user_delete.php',
			array(
				'user_delete_count' => count( $deleted_users ),
				'deleted_users'     => array_values( $deleted_users ),
			)
		);

		return array(
			'deleted_users' => $deleted_users,
			'template'      => $template,
		);
	}

	/**
	 * Delete a CSV file from the server.
	 *
	 * @param string $file_path Path to the file to delete.
	 */
	public function delete_csv_file( string $file_path ): void {
		$real_file_path = realpath( $file_path );
		$real_export_dir = realpath( $this->get_export_dir() );

		if (
			$real_file_path &&
			$real_export_dir &&
			str_starts_with( $real_file_path, trailingslashit( $real_export_dir ) ) &&
			str_ends_with( $real_file_path, '.csv' ) &&
			file_exists( $real_file_path )
		) {
			wp_delete_file( $real_file_path );
		}
	}

	/**
	 * Escape a CSV value against spreadsheet formula injection.
	 *
	 * @param string $value CSV cell value.
	 *
	 * @return string Safe CSV cell value.
	 */
	private function escape_csv_cell( string $value ): string {
		return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
	}

	/**
	 * Get the plugin export directory inside uploads.
	 *
	 * @return string Export directory path.
	 */
	private function get_export_dir(): string {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'ubdwp-exports';
	}

	/**
	 * Normalize the reassign target for wp_delete_user().
	 *
	 * @param mixed $reassign Reassign value from request.
	 * @param int   $deleted_user_id User being deleted.
	 *
	 * @return int|null Reassign user ID or null.
	 */
	private function normalize_reassign_user_id( mixed $reassign, int $deleted_user_id ): ?int {
		$reassign_id = absint( $reassign );

		if ( $reassign_id <= 0 || $reassign_id === $deleted_user_id || ! get_userdata( $reassign_id ) ) {
			return null;
		}

		return $reassign_id;
	}

	/**
	 * Handle AJAX request for searching users to delete.
	 *
	 * @param string $type Type of search.
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return array|\WP_Error Result of the search.
	 */
	public function search_users_for_delete_ajax( string $type, array $request ) {
		$keys = array(
			'find_users_nonce',
			'search_user_existing_nonce',
			'search_user_meta_nonce',
			'registration_date',
			'user_meta_value',
			'user_email',
			'filter_type',
			'action',
			'user_email_equal',
			'user_meta_equal',
			'user_search',
			'products',
			'user_role',
			'user_meta',
		);

		$data_before_sanitize = array_intersect_key( $request, array_flip( $keys ) );
		$sanitized_data       = UbdwpHelperFacade::sanitize_post_data( $data_before_sanitize );

		switch ( $type ) {
			case 'select_existing':
				UbdwpValidationFacade::validate_user_search_for_existing_users( $sanitized_data );
				$results = $this->get_users_by_ids( array_unique( array_map( 'intval', $request['user_search'] ?? array() ) ) );
				break;
			case 'find_users':
				UbdwpValidationFacade::validate_find_user_form( $sanitized_data );
				$results = $this->get_users_by_filters( $sanitized_data );
				break;
			case 'find_users_by_woocommerce_filters':
				UbdwpValidationFacade::validate_woocommerce_filters( $sanitized_data );
				$results = $this->get_users_by_woocommerce_filters( $sanitized_data );
				break;
			default:
				wp_send_json_error( array( 'message' => UbdwpValidationFacade::get_error_message( 'select_type' ) ) );
				wp_die();
		}

		return $results;
	}
}
