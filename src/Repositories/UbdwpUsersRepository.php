<?php
/**
 * Users Repository
 *
 * @package     UsersBulkDeleteWithPreview\Repositories
 */

namespace UsersBulkDeleteWithPreview\Repositories;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractBaseRepository;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;

/**
 * Repository for managing user data and related operations.
 */
class UbdwpUsersRepository extends UbdwpAbstractBaseRepository {
	/**
	 * Constructor to initialize the Users Repository.
	 *
	 * @param int $current_user_id Current user ID.
	 */
	public function __construct( int $current_user_id ) {
		parent::__construct( 'users', $current_user_id );
	}

	/**
	 * Handle AJAX request to search users.
	 *
	 * @param array<string, mixed> $args The request parameters.
	 *
	 * @return \WP_User_Query List of users matching the search term.
	 */
	public function search_users_ajax( array $args ): \WP_User_Query {
		$args['blog_id'] = get_current_blog_id();

		return new \WP_User_Query( $args );
	}

	/**
	 * Handle AJAX request to search user metadata.
	 *
	 * @param string $search The search term.
	 *
	 * @return array<int, string> List of meta keys matching the search term.
	 */
	public function search_usermeta_ajax( string $search ): array {
		$params = array( '%' . $this->wpdb->esc_like( $search ) . '%' );

		if ( is_multisite() ) {
			// Only offer meta keys of users that belong to the current site.
			$query    = "
                SELECT DISTINCT um.meta_key
                FROM {$this->wpdb->usermeta} um
                INNER JOIN {$this->wpdb->usermeta} cap ON cap.user_id = um.user_id AND cap.meta_key = %s
                WHERE um.meta_key LIKE %s
                LIMIT 10
            ";
			$params = array( $this->wpdb->get_blog_prefix() . 'capabilities', $params[0] );

			return $this->select( $query, $params );
		}

		$query = "
            SELECT DISTINCT meta_key FROM {$this->wpdb->usermeta} WHERE meta_key LIKE %s LIMIT 10
        ";

		return $this->select( $query, $params );
	}

	/**
	 * Get users by their IDs.
	 *
	 * @param array<int> $user_ids The user IDs to fetch.
	 *
	 * @return array<\WP_User> List of users.
	 */
	public function get_users_by_ids( array $user_ids ): array {
		return get_users( array(
			'blog_id' => get_current_blog_id(),
			'include' => $user_ids,
		) );
	}

	/**
	 * Keep only IDs of existing users that belong to the current site, in ascending order.
	 *
	 * @param array<int> $user_ids User IDs.
	 *
	 * @return array<int> User IDs.
	 */
	public function filter_site_user_ids( array $user_ids ): array {
		$user_ids = array_values( array_filter( array_unique( array_map( 'absint', $user_ids ) ) ) );

		if ( empty( $user_ids ) ) {
			return array();
		}

		return $this->query_user_ids( array( 'include' => $user_ids ) );
	}

	/**
	 * Get IDs of all users of the current site, in ascending order.
	 *
	 * @return array<int> User IDs.
	 */
	public function get_all_site_user_ids(): array {
		return $this->query_user_ids( array() );
	}

	/**
	 * Run a user query that returns only IDs, without loading user objects.
	 *
	 * @param array<string, mixed> $args WP_User_Query arguments.
	 *
	 * @return array<int> User IDs in ascending order.
	 */
	public function query_user_ids( array $args ): array {
		$query = new \WP_User_Query( array_merge( $args, array(
			'blog_id'     => get_current_blog_id(),
			'fields'      => 'ID',
			'number'      => -1,
			'orderby'     => 'ID',
			'order'       => 'ASC',
			'count_total' => false,
		) ) );

		return array_map( 'intval', $query->get_results() );
	}

	/**
	 * Load one page of preview users from a fixed set of user IDs.
	 *
	 * @param array<int> $user_ids User IDs of the preview.
	 * @param int        $offset   Offset.
	 * @param int        $limit    Page size.
	 * @param string     $search   Search term for login, email and display name.
	 * @param string     $orderby  Order by field (ID, login, email or registered).
	 * @param string     $order    ASC or DESC.
	 *
	 * @return \WP_User_Query Query with results and the total of the filtered set.
	 */
	public function query_preview_page( array $user_ids, int $offset, int $limit, string $search, string $orderby, string $order ): \WP_User_Query {
		$args = array(
			'blog_id'     => get_current_blog_id(),
			'include'     => $user_ids,
			'number'      => $limit,
			'offset'      => $offset,
			'orderby'     => $orderby,
			'order'       => $order,
			'count_total' => true,
		);

		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
		}

		return new \WP_User_Query( $args );
	}

	/**
	 * Get users by various filters.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return \WP_User_Query|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_filters( array $args, array $request ): \WP_User_Query|\WP_Error {
		$args['blog_id'] = get_current_blog_id();

		$this->apply_role_filter( $args, $request );
		$this->apply_registration_date_filter( $args, $request );
		$this->apply_usermeta_filter( $args, $request );
		$email_filter_result = $this->apply_email_filters( $args, $request['user_email'] ?? '', $request['user_email_equal'] ?? '' );

		if ( is_wp_error( $email_filter_result ) ) {
			return $email_filter_result;
		}

		// Run last, because they have to narrow down an "include" list set by the email filter.
		if ( ! empty( $request['without_content'] ) ) {
			$content_filter_result = $this->exclude_user_ids( $args, $this->get_user_ids_with_content() );

			if ( is_wp_error( $content_filter_result ) ) {
				return $content_filter_result;
			}
		}

		if ( ! empty( $request['without_wc_orders'] ) ) {
			$orders_filter_result = $this->exclude_user_ids( $args, $this->get_customer_ids_with_orders() );

			if ( is_wp_error( $orders_filter_result ) ) {
				return $orders_filter_result;
			}
		}

		return new \WP_User_Query( $args );
	}

	/**
	 * Post types that count as a user's content.
	 *
	 * Same set as WP_Query's post type "any" used by "remove all related content":
	 * internal types such as revisions or WooCommerce orders are not included.
	 *
	 * @return array<string> Post type names.
	 */
	public function get_content_post_types(): array {
		return array_values( get_post_types( array( 'exclude_from_search' => false ) ) );
	}

	/**
	 * Count posts in any status for several users with one query.
	 *
	 * @param array<int> $user_ids User IDs.
	 *
	 * @return array<int, int> Post count by user ID (users without posts are omitted).
	 */
	public function count_posts_by_authors( array $user_ids ): array {
		$user_ids   = array_values( array_filter( array_unique( array_map( 'absint', $user_ids ) ) ) );
		$post_types = $this->get_content_post_types();

		if ( empty( $user_ids ) || empty( $post_types ) ) {
			return array();
		}

		$user_placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$type_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
		$rows              = $this->select(
			"SELECT post_author, COUNT(*) AS post_count FROM {$this->wpdb->posts} WHERE post_author IN ($user_placeholders) AND post_type IN ($type_placeholders) GROUP BY post_author",
			array_merge( $user_ids, $post_types )
		);

		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ (int) $row->post_author ] = (int) $row->post_count;
		}

		return $counts;
	}

	/**
	 * Get IDs of users who have posts (in any status) or comments on the current site.
	 *
	 * @return array<int> User IDs.
	 */
	public function get_user_ids_with_content(): array {
		$post_types = $this->get_content_post_types();
		$authors    = array();

		if ( ! empty( $post_types ) ) {
			$type_placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
			$authors           = $this->get_col(
				"SELECT DISTINCT post_author FROM {$this->wpdb->posts} WHERE post_author > %d AND post_type IN ($type_placeholders)",
				array_merge( array( 0 ), $post_types )
			);
		}

		$commenters = $this->get_col( "SELECT DISTINCT user_id FROM {$this->wpdb->comments} WHERE user_id > %d", array( 0 ) );

		return array_values( array_unique( array_map( 'absint', array_merge( $authors, $commenters ) ) ) );
	}

	/**
	 * Get IDs of customers who have at least one WooCommerce order in any status on the current site.
	 *
	 * Orders in the trash and checkout drafts are not counted. Returns no IDs when WooCommerce is not active.
	 *
	 * @return array<int> User IDs.
	 */
	public function get_customer_ids_with_orders(): array {
		if ( ! UbdwpHelperFacade::check_if_woocommerce_is_active() ) {
			return array();
		}

		if ( $this->is_woocommerce_hpos_enabled() ) {
			$query = "SELECT DISTINCT customer_id FROM {$this->wpdb->prefix}wc_orders
                WHERE type = 'shop_order' AND customer_id > 0 AND status NOT IN ('trash', 'wc-checkout-draft', 'auto-draft')";
		} else {
			$query = "SELECT DISTINCT pm.meta_value FROM {$this->wpdb->posts} o
                INNER JOIN {$this->wpdb->postmeta} pm ON pm.post_id = o.ID AND pm.meta_key = '_customer_user'
                WHERE o.post_type = 'shop_order' AND o.post_status NOT IN ('trash', 'wc-checkout-draft', 'auto-draft')";
		}

		return array_values( array_filter( array_map( 'absint', $this->get_col( $query ) ) ) );
	}

	/**
	 * Remove the given users from a query.
	 *
	 * @param array<string, mixed> $args     Current query arguments.
	 * @param array<int>           $user_ids Users to remove.
	 *
	 * @return \WP_Error|null Error when no user can match.
	 */
	private function exclude_user_ids( array &$args, array $user_ids ): ?\WP_Error {
		if ( empty( $user_ids ) ) {
			return null;
		}

		// WP_User_Query ignores "exclude" when "include" is set, so narrow the include list instead.
		if ( ! empty( $args['include'] ) ) {
			$args['include'] = array_values( array_diff( array_map( 'absint', (array) $args['include'] ), $user_ids ) );

			if ( empty( $args['include'] ) ) {
				return new \WP_Error( 'no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message( 'no_users_found_with_given_filters' ) );
			}

			return null;
		}

		$args['exclude'] = array_values( array_unique( array_merge( array_map( 'absint', (array) ( $args['exclude'] ?? array() ) ), $user_ids ) ) ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- These users must be excluded.

		return null;
	}

	/**
	 * Get users who purchased specific WooCommerce products.
	 *
	 * Only paid orders (completed, processing, on-hold) are counted; refunds and guest orders are ignored.
	 *
	 * @param array<int> $products_ids List of product IDs.
	 * @param bool       $any_product  Match customers who purchased any product instead of the given ones.
	 *
	 * @return array<int> List of user IDs.
	 */
	public function get_users_by_product_purchase( array $products_ids, bool $any_product = false ): array {
		$products_ids = array_values( array_filter( array_unique( array_map( 'absint', $products_ids ) ) ) );

		if ( ! $any_product && empty( $products_ids ) ) {
			return array();
		}

		$product_where = "AND oim.meta_value <> '0'";

		if ( ! $any_product ) {
			$product_where = 'AND oim.meta_value IN (' . implode( ',', array_fill( 0, count( $products_ids ), '%d' ) ) . ')';
		}

		$items_join = "INNER JOIN {$this->wpdb->prefix}woocommerce_order_items oi ON oi.order_id = o.id AND oi.order_item_type = 'line_item'
                INNER JOIN {$this->wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id AND oim.meta_key = '_product_id'";

		if ( $this->is_woocommerce_hpos_enabled() ) {
			$query = "SELECT DISTINCT o.customer_id
                FROM {$this->wpdb->prefix}wc_orders o
                {$items_join}
                WHERE o.type = 'shop_order' AND o.status IN ('wc-completed', 'wc-processing', 'wc-on-hold') AND o.customer_id > 0 {$product_where}";
		} else {
			// Legacy order storage keeps orders in the posts table and the customer in post meta.
			$items_join = str_replace( 'oi.order_id = o.id', 'oi.order_id = o.ID', $items_join );
			$query      = "SELECT DISTINCT pm.meta_value
                FROM {$this->wpdb->posts} o
                INNER JOIN {$this->wpdb->postmeta} pm ON pm.post_id = o.ID AND pm.meta_key = '_customer_user'
                {$items_join}
                WHERE o.post_type = 'shop_order' AND o.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold') {$product_where}";
		}

		return array_values( array_filter( array_map( 'intval', $this->get_col( $query, $any_product ? array() : $products_ids ) ) ) );
	}

	/**
	 * Check whether WooCommerce stores orders in its custom (HPOS) tables.
	 *
	 * @return bool True when HPOS is the authoritative order storage.
	 */
	public function is_woocommerce_hpos_enabled(): bool {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
		}

		return false;
	}

	/**
	 * Apply email filters to the query arguments.
	 *
	 * @param array<string, mixed> $args Current query arguments.
	 * @param string $email_search Email search term.
	 * @param string $email_compare Comparison operator.
	 *
	 * @return array<string, mixed>|\WP_Error Modified query arguments or error.
	 */
	private function apply_email_filters( array &$args, string $email_search, string $email_compare ): mixed {
		$email_search = sanitize_text_field( $email_search );

		if ( $email_search === '' ) {
			return $args;
		}

		if ( $email_compare ) {
			$compare = UbdwpHelperFacade::get_email_compare_operator( sanitize_text_field( $email_compare ) );

			if ( 'endswith_str' === $email_compare ) {
				// "Ends with" finds a domain, e.g. "@example.com".
				$email_search = '%' . $this->wpdb->esc_like( $email_search );
			} elseif ( in_array( $compare, array( 'LIKE', 'NOT LIKE' ), true ) ) {
				$email_search = '%' . $this->wpdb->esc_like( $email_search ) . '%';
			}

			$sql      = "
                SELECT ID 
                FROM {$this->wpdb->users} 
                WHERE user_email $compare %s 
                AND ID != %d
            ";
			$user_ids = $this->get_col( $sql, array( $email_search, $this->current_user_id ) );
			$user_ids = array_filter(
				array_map( 'absint', $user_ids ),
				static fn( int $user_id ): bool => ! is_multisite() || is_user_member_of_blog( $user_id, get_current_blog_id() )
			);

			if ( ! empty( $user_ids ) ) {
				$args['include'] = $user_ids;
			} else {
				return new \WP_Error( 'no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message( 'no_users_found_with_given_filters' ) );
			}
		} else {
			$args['search']         = '*' . $email_search . '*';
			$args['search_columns'] = array( 'user_email' );
		}

		return $args;
	}

	/**
	 * Apply role filters to the query arguments.
	 *
	 * @param array<string, mixed> $args Current query arguments.
	 * @param array<string, mixed> $request Request parameters.
	 */
	private function apply_role_filter( array &$args, array $request ): void {
		if ( ! empty( $request['user_role'] ) ) {
			$args['role__in'] = array_map( 'sanitize_text_field', (array) $request['user_role'] );
		}
	}

	/**
	 * Apply registration date filters to the query arguments.
	 *
	 * @param array<string, mixed> $args Current query arguments.
	 * @param array<string, mixed> $request Request parameters.
	 */
	private function apply_registration_date_filter( array &$args, array $request ): void {
		$from = sanitize_text_field( $request['registration_date'] ?? '' );

		if ( '' === $from ) {
			return;
		}

		$to      = sanitize_text_field( $request['registration_date_to'] ?? '' );
		$compare = sanitize_key( $request['registration_date_compare'] ?? '' );

		// Dates are entered in the site's timezone, while user_registered is stored in UTC.
		$day_start = static fn( string $date ): string => get_gmt_from_date( $date . ' 00:00:00' );
		$day_end   = static fn( string $date ): string => get_gmt_from_date( $date . ' 23:59:59' );
		$clause    = array(
			'column'    => 'user_registered',
			'inclusive' => true,
		);

		switch ( $compare ) {
			case 'before':
				$clause['before'] = $day_end( $from );
				break;
			case 'on':
				$clause['after']  = $day_start( $from );
				$clause['before'] = $day_end( $from );
				break;
			case 'between':
				$clause['after']  = $day_start( $from );
				$clause['before'] = $day_end( $to );
				break;
			default:
				// Requests without an operator keep the behavior of earlier versions.
				$clause['after'] = $day_start( $from );
				break;
		}

		$args['date_query'][] = $clause;
	}

	/**
	 * Apply usermeta filters to the query arguments.
	 *
	 * @param array<string, mixed> $args Current query arguments.
	 * @param array<string, mixed> $request Request parameters.
	 */
	private function apply_usermeta_filter( array &$args, array $request ): void {
		$key          = sanitize_text_field( $request['user_meta'] ?? '' );
		$compare_type = sanitize_text_field( $request['user_meta_equal'] ?? '' );

		if ( $key === '' ) {
			return;
		}

		switch ( $compare_type ) {
			case 'meta_not_exists':
				$args['meta_query'][] = [
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				];
				break;

			case 'meta_is_empty':
				// WordPress stores empty profile fields (e.g. first_name) for every user, while other plugins may
				// not store the key at all. Both mean "no value", so match either.
				$args['meta_query'][] = [
					'relation' => 'OR',
					[
						'key'     => $key,
						'value'   => '',
						'compare' => '=',
					],
					[
						'key'     => $key,
						'compare' => 'NOT EXISTS',
					],
				];
				break;

			default:
				$value = sanitize_text_field( $request['user_meta_value'] ?? '' );
				if ( $value === '' ) {
					return;
				}

				$args['meta_query'][] = [
					'key'     => $key,
					'value'   => $value,
					'compare' => UbdwpHelperFacade::get_meta_compare_operator( $compare_type ),
					'type'    => UbdwpHelperFacade::get_meta_compare_type( $compare_type ),
				];
				break;
		}
	}
}
