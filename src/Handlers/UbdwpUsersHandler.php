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
			$args['search'] = '*' . $search_term . '*';
			$args['number'] = 50; // Limit autocomplete results on large sites.
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
				'id'   => sanitize_text_field( $result->meta_key ),
				'text' => sanitize_text_field( $result->meta_key ),
			);
		}, $results );
	}

	/**
	 * Handle AJAX request to search users that can receive reassigned content.
	 *
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return array<int, array<string, string>> List of matching users.
	 */
	public function search_reassign_users_ajax( array $request ): array {
		$search_term = $request['q'] ?? '';
		$exclude     = array_filter( array_unique( array_map( 'absint', $request['exclude'] ?? array() ) ) );

		$args = array(
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
			'fields'         => array( 'ID', 'user_login', 'display_name' ),
			'exclude'        => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Users selected for deletion must not receive reassigned content.
			'number'         => 20,
			'orderby'        => 'user_login',
			'order'          => 'ASC',
		);

		if ( $search_term !== '' ) {
			$args['search'] = '*' . $search_term . '*';
		}

		$user_query = $this->repository->search_users_ajax( $args );
		$results    = array();

		foreach ( $user_query->get_results() as $user ) {
			$results[] = array(
				'id'   => (string) $user->ID,
				'text' => sprintf( '%s (%s)', sanitize_user( $user->user_login ), sanitize_text_field( $user->display_name ) ),
			);
		}

		return $results;
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
			return UbdwpHelperFacade::prepare_users_for_table( $users );
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

		if ( is_wp_error( $user_query ) ) {
			return $user_query;
		}

		if ( ! empty( $user_query->get_results() ) ) {
			return UbdwpHelperFacade::prepare_users_for_table( $user_query->get_results() );
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
		$products = array_unique( array_map( 'absint', (array) ( $request['products'] ?? array() ) ) );
		$user_ids = $this->repository->get_users_by_product_purchase( $products );

		$user_ids = array_filter( $user_ids, static fn( $value ) => $value !== 0 && $value !== '0' );

		if ( ! empty( $user_ids ) ) {
			$user_ids = array_unique( $user_ids );
			$users    = $this->repository->get_users_by_ids( $user_ids );

			return UbdwpHelperFacade::prepare_users_for_table( $users );
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

		// Explicit CSV control avoids the PHP 8.4 fputcsv() $escape deprecation.
		$temp_file->setCsvControl( ',', '"', '' );

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
	 * Build a file name for the CSV export download.
	 *
	 * @return string CSV file name.
	 */
	public function get_csv_file_name(): string {
		return 'users_export_' . gmdate( 'Y-m-d_H-i-s' ) . '.csv';
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
		$failed_users  = array();
		$batch_ids     = array_map( 'intval', array_column( $sanitized_users, 'id' ) );

		foreach ( $sanitized_users as $user ) {
			$user_id = (int) $user['id'];

			if ( ! $this->can_manage_user_for_current_site( $user_id ) ) {
				$failed_users[] = $user_id;
				continue;
			}

			$reassign_raw           = (string) ( $user['reassign'] ?? '' );
			$remove_related_content = $reassign_raw === 'remove_all_related_content';
			$reassign               = null;

			if ( ! $remove_related_content && $reassign_raw !== '' ) {
				$reassign = $this->normalize_reassign_user_id( $reassign_raw, $user_id, $batch_ids );

				// Never fall back to deleting content when an explicit reassign target is invalid.
				if ( null === $reassign ) {
					$failed_users[] = $user_id;
					continue;
				}
			}

			if ( $remove_related_content ) {
				$this->delete_related_content( $user_id );
			}

			if ( ! $this->remove_or_delete_user( $user_id, $reassign ) ) {
				$failed_users[] = $user_id;
				continue;
			}

			$deleted_users[ $user_id ] = array(
				'user_id'      => $user_id,
				'email'        => $user['email'],
				'display_name' => $user['display_name'],
				'reassign'     => $remove_related_content ? 'remove_all_related_content' : ( $reassign ?? '' ),
				'action'       => is_multisite() ? 'removed_from_site' : 'deleted',
			);
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
			'failed_users'  => $failed_users,
			'template'      => $template,
		);
	}

	/**
	 * Escape a CSV value against spreadsheet formula injection.
	 *
	 * @param string $value CSV cell value.
	 *
	 * @return string Safe CSV cell value.
	 */
	private function escape_csv_cell( string $value ): string {
		return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
	}

	/**
	 * Normalize the reassign target for wp_delete_user().
	 *
	 * @param mixed      $reassign Reassign value from request.
	 * @param int        $deleted_user_id User being deleted.
	 * @param array<int> $batch_ids Users being deleted in the same request.
	 *
	 * @return int|null Reassign user ID or null.
	 */
	private function normalize_reassign_user_id( mixed $reassign, int $deleted_user_id, array $batch_ids = array() ): ?int {
		$reassign_id = absint( $reassign );

		if (
			$reassign_id <= 0 ||
			$reassign_id === $deleted_user_id ||
			in_array( $reassign_id, $batch_ids, true ) ||
			! get_userdata( $reassign_id )
		) {
			return null;
		}

		if ( is_multisite() && ! is_user_member_of_blog( $reassign_id, get_current_blog_id() ) ) {
			return null;
		}

		return $reassign_id;
	}

	/**
	 * Check whether a user can be managed in the current site context.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool True when the user can be managed.
	 */
	private function can_manage_user_for_current_site( int $user_id ): bool {
		if ( $user_id <= 0 || $user_id === $this->current_user_id || ! get_userdata( $user_id ) ) {
			return false;
		}

		if ( ! is_multisite() ) {
			return current_user_can( 'delete_user', $user_id );
		}

		if ( ! is_user_member_of_blog( $user_id, get_current_blog_id() ) || ! current_user_can( 'remove_user', $user_id ) ) {
			return false;
		}

		return ! is_super_admin( $user_id ) || is_super_admin( $this->current_user_id );
	}

	/**
	 * Remove a user from the current site in multisite or delete it on single-site installs.
	 *
	 * @param int      $user_id User ID.
	 * @param int|null $reassign User ID to reassign content to.
	 *
	 * @return bool True on success.
	 */
	private function remove_or_delete_user( int $user_id, ?int $reassign ): bool {
		if ( is_multisite() ) {
			$result = remove_user_from_blog( $user_id, get_current_blog_id(), $reassign );

			return ! is_wp_error( $result );
		}

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		return (bool) wp_delete_user( $user_id, $reassign );
	}

	/**
	 * Delete posts and comments owned by a user in the current site context.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return void
	 */
	private function delete_related_content( int $user_id ): void {
		// Post type "any" intentionally skips internal types (e.g. WooCommerce orders, scheduled actions),
		// but status "any" would skip trashed posts and auto-drafts, so all statuses are listed explicitly.
		$user_posts = get_posts( array(
			'author'           => $user_id,
			'post_type'        => 'any',
			'post_status'      => array_keys( get_post_stati() ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );

		foreach ( $user_posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}

		$user_comments = get_comments( array(
			'user_id' => $user_id,
			'status'  => 'any',
		) );

		foreach ( $user_comments as $comment ) {
			wp_delete_comment( $comment->comment_ID, true );
		}
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
				$results = $this->get_users_by_ids( $sanitized_data['user_search'] ?? array() );
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
