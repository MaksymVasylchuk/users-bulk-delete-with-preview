<?php
/**
 * User finder
 *
 * @package     UsersBulkDeleteWithPreview\Services
 */

namespace UsersBulkDeleteWithPreview\Services;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;
use UsersBulkDeleteWithPreview\Repositories\UbdwpUsersRepository;

/**
 * Finds the IDs of all users matching the filters of the admin page or a WP-CLI command.
 */
class UbdwpUserFinder {
	/**
	 * Filter types.
	 */
	public const TYPES = array( 'select_existing', 'find_users', 'find_users_by_woocommerce_filters' );

	/**
	 * Request fields used by the filters.
	 */
	private const FILTER_KEYS = array(
		'registration_date',
		'registration_date_compare',
		'registration_date_to',
		'without_content',
		'without_wc_orders',
		'user_meta_value',
		'user_email',
		'user_email_equal',
		'user_meta_equal',
		'user_search',
		'all_users',
		'products',
		'all_products',
		'user_role',
		'user_meta',
	);

	/**
	 * Users repository.
	 *
	 * @var UbdwpUsersRepository
	 */
	private UbdwpUsersRepository $repository;

	/**
	 * User running the search; never part of the result.
	 *
	 * @var int
	 */
	private int $current_user_id;

	/**
	 * Constructor.
	 *
	 * @param int $current_user_id User running the search.
	 */
	public function __construct( int $current_user_id ) {
		$this->current_user_id = $current_user_id;
		$this->repository      = new UbdwpUsersRepository( $current_user_id );
	}

	/**
	 * Find the IDs of all users of the current site that match the filters.
	 *
	 * @param string               $type    Filter type.
	 * @param array<string, mixed> $request Raw request data (unslashed).
	 *
	 * @return array<int>|\WP_Error User IDs in ascending order, or an error.
	 */
	public function find( string $type, array $request ) {
		if ( ! in_array( $type, self::TYPES, true ) ) {
			return UbdwpValidationFacade::to_wp_error( 'select_type' );
		}

		$data  = UbdwpHelperFacade::sanitize_post_data( array_intersect_key( $request, array_flip( self::FILTER_KEYS ) ) );
		$error = UbdwpValidationFacade::get_search_error( $type, $data );

		if ( null !== $error ) {
			return UbdwpValidationFacade::to_wp_error( $error );
		}

		switch ( $type ) {
			case 'select_existing':
				$user_ids = ! empty( $data['all_users'] )
					? $this->repository->get_all_site_user_ids()
					: $this->repository->filter_site_user_ids( $data['user_search'] ?? array() );
				break;

			case 'find_users_by_woocommerce_filters':
				$user_ids = $this->repository->filter_site_user_ids(
					$this->repository->get_users_by_product_purchase( $data['products'] ?? array(), ! empty( $data['all_products'] ) )
				);
				break;

			default:
				$query = $this->repository->get_users_by_filters(
					array(
						'exclude'    => $this->current_user_id, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- The current user is never deleted.
						'meta_query' => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- DB call is OK.
						'date_query' => array(),
						'fields'     => 'ID',
						'number'     => -1,
						'orderby'    => 'ID',
						'order'      => 'ASC',
					),
					$data
				);

				if ( is_wp_error( $query ) ) {
					return $query;
				}

				$user_ids = array_map( 'intval', $query->get_results() );
				break;
		}

		/**
		 * Filters the users found by the filters, for example to apply extra filters of another plugin.
		 *
		 * The result can only be narrowed: IDs that were not found are ignored.
		 *
		 * @since 2.4.0
		 *
		 * @param array<int>           $user_ids User IDs found.
		 * @param string               $type     Filter type.
		 * @param array<string, mixed> $request  Raw request data (unslashed, not sanitized).
		 */
		$filtered = apply_filters( 'ubdwp_found_user_ids', $user_ids, $type, $request );

		if ( is_array( $filtered ) ) {
			$user_ids = array_values( array_intersect( $user_ids, array_map( 'intval', $filtered ) ) );
		}

		$user_ids = array_values( array_diff( $user_ids, array( $this->current_user_id ) ) );

		/**
		 * Filters the maximum number of users one preview can contain (0 means no limit).
		 *
		 * @param int $limit Maximum number of users.
		 */
		$limit = (int) apply_filters( 'ubdwp_preview_limit', 0 );

		if ( $limit > 0 ) {
			$user_ids = array_slice( $user_ids, 0, $limit );
		}

		if ( empty( $user_ids ) ) {
			return UbdwpValidationFacade::to_wp_error( 'select_existing' === $type ? 'no_users_found' : 'no_users_found_with_given_filters' );
		}

		return $user_ids;
	}
}
