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

		$user_ids   = array_unique( array_map( 'intval', $user_ids ) );
		$user_query = $this->repository->query_users_by_ids( $user_ids, $this->get_preview_limit() );

		if ( ! empty( $user_query->get_results() ) ) {
			return $this->build_preview_response( $user_query );
		}

		return new \WP_Error( 'no_users_found', UbdwpValidationFacade::get_error_message( 'no_users_found' ) );
	}

	/**
	 * Format users for the preview table, including their post counts.
	 *
	 * @param array<\WP_User> $users Users.
	 *
	 * @return array<int, array<string, mixed>> Table rows.
	 */
	private function prepare_users_for_table( array $users ): array {
		$post_counts = $this->repository->count_posts_by_authors( array_map( static fn( $user ) => (int) $user->ID, $users ) );

		return UbdwpHelperFacade::prepare_users_for_table( $users, $post_counts );
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
			'meta_query'  => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query --  DB call is OK.
			'date_query'  => array(),
			'number'      => $this->get_preview_limit(),
			'count_total' => true,
		);

		$user_query = $this->repository->get_users_by_filters( $args, $request );

		if ( is_wp_error( $user_query ) ) {
			return $user_query;
		}

		if ( ! empty( $user_query->get_results() ) ) {
			return $this->build_preview_response( $user_query );
		}

		return new \WP_Error( 'no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message( 'no_users_found_with_given_filters' ) );
	}

	/**
	 * Maximum number of users loaded into one preview.
	 *
	 * Keeps memory use and response size bounded on sites with very many users.
	 *
	 * @return int Limit.
	 */
	private function get_preview_limit(): int {
		return max( 1, (int) apply_filters( 'ubdwp_preview_limit', 10000 ) );
	}

	/**
	 * Build the preview response with table rows and the total number of matching users.
	 *
	 * @param \WP_User_Query $user_query Executed query with count_total enabled.
	 *
	 * @return array<string, mixed> Preview data.
	 */
	private function build_preview_response( \WP_User_Query $user_query ): array {
		$rows  = $this->prepare_users_for_table( $user_query->get_results() );
		$total = max( count( $rows ), (int) $user_query->get_total() );

		return array(
			'rows'      => $rows,
			'total'     => $total,
			'truncated' => $total > count( $rows ),
			'message'   => $total > count( $rows )
				/* translators: 1: number of users shown, 2: number of matching users. */
				? sprintf( __( 'Showing the first %1$d of %2$d matching users. Delete them, then run the preview again for the rest, or narrow down the filters.', 'users-bulk-delete-with-preview' ), count( $rows ), $total )
				: '',
		);
	}

	/**
	 * Search WooCommerce products for the products filter.
	 *
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return array<int, array<string, string>> List of products for Select2.
	 */
	public function search_products_ajax( array $request ): array {
		if ( ! UbdwpHelperFacade::check_if_woocommerce_is_active() || ! function_exists( 'wc_get_products' ) ) {
			return array();
		}

		$search_term = $request['q'] ?? '';
		$limit       = 20;

		if ( $search_term !== '' ) {
			$product_ids = \WC_Data_Store::load( 'product' )->search_products( $search_term, '', false, true, $limit );
		} else {
			$product_ids = wc_get_products( array(
				'limit'   => $limit,
				'orderby' => 'title',
				'order'   => 'ASC',
				'return'  => 'ids',
			) );
		}

		$results = array();

		foreach ( array_filter( array_unique( array_map( 'absint', (array) $product_ids ) ) ) as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || $product->is_type( 'variation' ) || 'auto-draft' === $product->get_status() ) {
				continue;
			}

			$results[] = array(
				'id'   => (string) $product_id,
				'text' => sanitize_text_field( sprintf( '%s (#%d)', $product->get_name(), $product_id ) ),
			);
		}

		return $results;
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
		$user_ids = $this->repository->get_users_by_product_purchase( $products, ! empty( $request['all_products'] ) );

		if ( ! empty( $user_ids ) ) {
			$user_query = $this->repository->query_users_by_ids( array_unique( $user_ids ), $this->get_preview_limit() );

			if ( ! empty( $user_query->get_results() ) ) {
				return $this->build_preview_response( $user_query );
			}
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

		// Process each user once, even if the request repeats an ID.
		$unique_users = array();
		foreach ( $sanitized_users as $user ) {
			$unique_users[ (int) $user['id'] ] ??= $user;
		}
		$batch_ids = array_keys( $unique_users );

		foreach ( $unique_users as $user_id => $user ) {
			$block_reason = $this->get_block_reason( $user_id );

			if ( null !== $block_reason ) {
				$failed_users[ $user_id ] = $this->build_failed_entry( $user_id, $block_reason );
				continue;
			}

			// Log what is actually stored, never values sent by the browser.
			$user_data = get_userdata( $user_id );

			$reassign_raw           = (string) ( $user['reassign'] ?? '' );
			$remove_related_content = $reassign_raw === 'remove_all_related_content';
			$reassign               = null;

			if ( ! $remove_related_content && $reassign_raw !== '' ) {
				$reassign = $this->normalize_reassign_user_id( $reassign_raw, $user_id, $batch_ids );

				// Never fall back to deleting content when an explicit reassign target is invalid.
				if ( null === $reassign ) {
					$failed_users[ $user_id ] = $this->build_failed_entry( $user_id, 'invalid_reassign' );
					continue;
				}
			}

			if ( $remove_related_content ) {
				$this->delete_related_content( $user_id );
			}

			if ( ! $this->remove_or_delete_user( $user_id, $reassign ) ) {
				$failed_users[ $user_id ] = $this->build_failed_entry( $user_id, 'delete_failed' );
				continue;
			}

			$deleted_users[ $user_id ] = array(
				'user_id'      => $user_id,
				'email'        => sanitize_email( $user_data->user_email ),
				'display_name' => sanitize_text_field( $user_data->display_name ),
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

		$failed_template = UbdwpViewsFacade::render_template(
			'partials/_failed_user_delete.php',
			array( 'failed_users' => array_values( $failed_users ) )
		);

		return array(
			'deleted_users'   => $deleted_users,
			'failed_users'    => $failed_users,
			'template'        => $template,
			'failed_template' => $failed_template,
		);
	}

	/**
	 * Summarize what a deletion request will do, without changing anything.
	 *
	 * @param array<int, array<string, mixed>> $sanitized_users Users selected for deletion.
	 *
	 * @return array<string, mixed> Summary for the confirmation dialog.
	 */
	public function get_delete_summary( array $sanitized_users ): array {
		$unique_users = array();
		foreach ( $sanitized_users as $user ) {
			$unique_users[ (int) $user['id'] ] ??= $user;
		}
		$batch_ids = array_keys( $unique_users );

		$summary = array(
			'selected'         => count( $unique_users ),
			'deletable'        => 0,
			'skipped'          => array(),
			'reassign_users'   => 0,
			'reassign_posts'   => 0,
			'reassign_targets' => array(),
			'default_users'    => 0,
			'default_posts'    => 0,
			'remove_users'     => 0,
			'remove_posts'     => 0,
			'remove_comments'  => 0,
			'multisite'        => is_multisite(),
		);

		foreach ( $unique_users as $user_id => $user ) {
			$block_reason = $this->get_block_reason( $user_id );
			$reassign_raw = (string) ( $user['reassign'] ?? '' );
			$reassign     = null;

			if ( null === $block_reason && '' !== $reassign_raw && 'remove_all_related_content' !== $reassign_raw ) {
				$reassign = $this->normalize_reassign_user_id( $reassign_raw, $user_id, $batch_ids );
				if ( null === $reassign ) {
					$block_reason = 'invalid_reassign';
				}
			}

			if ( null !== $block_reason ) {
				$summary['skipped'][] = $this->build_failed_entry( $user_id, $block_reason );
				continue;
			}

			++$summary['deletable'];

			if ( 'remove_all_related_content' === $reassign_raw ) {
				++$summary['remove_users'];
				$summary['remove_posts']    += count( $this->get_related_post_ids( $user_id ) );
				$summary['remove_comments'] += (int) get_comments( array( 'user_id' => $user_id, 'status' => 'any', 'count' => true ) );
			} elseif ( null !== $reassign ) {
				++$summary['reassign_users'];
				$summary['reassign_posts'] += $this->count_default_content( $user_id, true );
				$summary['reassign_targets'][ $reassign ] = sanitize_user( get_userdata( $reassign )->user_login );
			} else {
				++$summary['default_users'];
				$summary['default_posts'] += $this->count_default_content( $user_id, false );
			}
		}

		$summary['reassign_targets'] = array_values( $summary['reassign_targets'] );
		$summary['confirm_required'] = $summary['deletable'] >= (int) apply_filters( 'ubdwp_confirmation_threshold', 20 );

		return $summary;
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
	 * Explain why a user cannot be deleted or removed in the current site context.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string|null Reason code, or null when the user can be deleted.
	 */
	private function get_block_reason( int $user_id ): ?string {
		$user = $user_id > 0 ? get_userdata( $user_id ) : false;

		if ( ! $user ) {
			return 'not_found';
		}

		if ( $user_id === $this->current_user_id ) {
			return 'self';
		}

		if ( is_multisite() && ! is_user_member_of_blog( $user_id, get_current_blog_id() ) ) {
			return 'not_member';
		}

		if ( UbdwpHelperFacade::is_protected_user( $user, $this->current_user_id ) ) {
			return 'protected';
		}

		$capability = is_multisite() ? 'remove_user' : 'delete_user';

		return current_user_can( $capability, $user_id ) ? null : 'no_permission';
	}

	/**
	 * Build a failed user entry with a translated reason.
	 *
	 * @param int    $user_id User ID.
	 * @param string $reason  Reason code.
	 *
	 * @return array<string, mixed> Failed entry.
	 */
	private function build_failed_entry( int $user_id, string $reason ): array {
		$user = $user_id > 0 ? get_userdata( $user_id ) : false;

		return array(
			'user_id' => $user_id,
			'login'   => $user ? sanitize_user( $user->user_login ) : '',
			'email'   => $user ? sanitize_email( $user->user_email ) : '',
			'reason'  => $reason,
			'message' => UbdwpHelperFacade::get_delete_block_reason_message( $reason ),
		);
	}

	/**
	 * Count content affected when a user is deleted without "remove all related content".
	 *
	 * Mirrors wp_delete_user(): posts of types that are deleted with the user.
	 * On multisite, removing a user from a site keeps the content unless it is reassigned.
	 *
	 * @param int  $user_id  User ID.
	 * @param bool $reassign Whether the content is reassigned instead of deleted.
	 *
	 * @return int Number of posts.
	 */
	private function count_default_content( int $user_id, bool $reassign ): int {
		global $wpdb;

		if ( is_multisite() && ! $reassign ) {
			return 0;
		}

		if ( $reassign ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d", $user_id ) );
		}

		$post_types = array();
		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
			if ( $post_type->delete_with_user || ( null === $post_type->delete_with_user && post_type_supports( $post_type->name, 'author' ) ) ) {
				$post_types[] = $post_type->name;
			}
		}

		if ( empty( $post_types ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type IN ($placeholders)", array_merge( array( $user_id ), $post_types ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are generated for each post type.
	}

	/**
	 * Get IDs of posts removed by "remove all related content".
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array<int> Post IDs.
	 */
	private function get_related_post_ids( int $user_id ): array {
		// Post type "any" intentionally skips internal types (e.g. WooCommerce orders, scheduled actions),
		// but status "any" would skip trashed posts and auto-drafts, so all statuses are listed explicitly.
		return get_posts( array(
			'author'           => $user_id,
			'post_type'        => 'any',
			'post_status'      => array_keys( get_post_stati() ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );
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
		$user_posts = $this->get_related_post_ids( $user_id );

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
			'registration_date_compare',
			'registration_date_to',
			'without_content',
			'user_meta_value',
			'user_email',
			'filter_type',
			'action',
			'user_email_equal',
			'user_meta_equal',
			'user_search',
			'products',
			'all_products',
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
