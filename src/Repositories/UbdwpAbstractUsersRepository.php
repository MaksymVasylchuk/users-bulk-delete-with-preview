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

/**
 * Repository for managing user data and related operations.
 */
class UbdwpAbstractUsersRepository extends UbdwpAbstractBaseRepository {
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
		$query = "
            SELECT DISTINCT meta_key FROM {$this->wpdb->usermeta} WHERE meta_key LIKE %s LIMIT 10
        ";

		return $this->select( $query, [ '%' . $this->wpdb->esc_like( $search ) . '%' ] );
	}

	/**
	 * Get users by their IDs.
	 *
	 * @param array<int> $user_ids The user IDs to fetch.
	 *
	 * @return array<\WP_User> List of users.
	 */
	public function get_users_by_ids( array $user_ids ): array {
		return get_users( [ 'include' => $user_ids ] );
	}

	/**
	 * Get users by various filters.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @param array<string, mixed> $request Request parameters.
	 *
	 * @return \WP_User_Query List of users.
	 */
	public function get_users_by_filters( array $args, array $request ): \WP_User_Query {
		$this->apply_role_filter( $args, $request );
		$this->apply_registration_date_filter( $args, $request );
		$this->apply_usermeta_filter( $args, $request );
		$this->apply_email_filters( $args, $request['user_email'] ?? '', $request['user_email_equal'] ?? '' );

		return new \WP_User_Query( $args );
	}

	/**
	 * Get users excluding specified IDs.
	 *
	 * @param array<int> $exclude_ids IDs to exclude.
	 *
	 * @return array<\WP_User> List of users excluding the specified IDs.
	 */
	public function get_users_exclude_ids( array $exclude_ids ): array {
		return get_users( [
			'exclude' => array_unique( array_map( 'absint', $exclude_ids ) ),
			'number'  => - 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
		] );
	}

	/**
	 * Get users who purchased a specific WooCommerce product.
	 *
	 * @param array<int> $products_ids List of product IDs.
	 *
	 * @return array<int> List of user IDs.
	 */
	public function get_users_by_product_purchase( array $products_ids ): array {
		if ( empty( $products_ids ) ) {
			return [];
		}

		$products_ids = array_unique( array_map( 'absint', $products_ids ) );
		$placeholders = implode( ',', array_fill( 0, count( $products_ids ), '%d' ) );

		$query = "SELECT DISTINCT order_id 
            FROM {$this->wpdb->prefix}woocommerce_order_items
            WHERE order_item_id IN (
                SELECT order_item_id 
                FROM {$this->wpdb->prefix}woocommerce_order_itemmeta
                WHERE meta_key = '_product_id' AND meta_value IN ($placeholders))";

		$order_items = $this->get_col( $query, $products_ids );

		if ( empty( $order_items ) ) {
			return [];
		}

		$order_items            = array_map( 'intval', $order_items );
		$order_ids_placeholders = implode( ',', array_fill( 0, count( $order_items ), '%d' ) );

		$order_query = "SELECT DISTINCT customer_id 
            FROM {$this->wpdb->prefix}wc_orders 
            WHERE id IN ($order_ids_placeholders) AND status IN ('wc-completed', 'wc-processing', 'wc-on-hold')";

		return $this->get_col( $order_query, $order_items );
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

		if ( $email_compare ) {
			$compare = UbdwpHelperFacade::get_email_compare_operator( sanitize_text_field( $email_compare ) );

			if ( in_array( $compare, [ 'LIKE', 'NOT LIKE' ] ) ) {
				$email_search = '%' . $this->wpdb->esc_like( $email_search ) . '%';
			}

			$sql      = "
                SELECT ID 
                FROM {$this->wpdb->users} 
                WHERE user_email $compare %s 
                AND ID != %d
            ";
			$user_ids = $this->get_col( $sql, [ $email_search, $this->current_user_id ] );

			if ( ! empty( $user_ids ) ) {
				$args['include'] = $user_ids;
			} else {
				return new \WP_Error( 'no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message( 'no_users_found_with_given_filters' ) );
			}
		} else {
			$args['search']         = '*' . $email_search . '*';
			$args['search_columns'] = [ 'user_email' ];
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
			$args['role__in'] = array_map( 'sanitize_text_field', $request['user_role'] );
		}
	}

	/**
	 * Apply registration date filters to the query arguments.
	 *
	 * @param array<string, mixed> $args Current query arguments.
	 * @param array<string, mixed> $request Request parameters.
	 */
	private function apply_registration_date_filter( array &$args, array $request ): void {
		if ( ! empty( $request['registration_date'] ) ) {
			$args['date_query'][] = [
				'column'    => 'user_registered',
				'after'     => sanitize_text_field( $request['registration_date'] ),
				'inclusive' => true,
			];
		}
	}

	/**
	 * Apply usermeta filters to the query arguments.
	 *
	 * @param array<string, mixed> $args Current query arguments.
	 * @param array<string, mixed> $request Request parameters.
	 */
	private function apply_usermeta_filter( array &$args, array $request ): void {
		if ( ! empty( $request['user_meta'] ) && ! empty( $request['user_meta_value'] ) ) {
			$compare              = UbdwpHelperFacade::get_meta_compare_operator( sanitize_text_field( $request['user_meta_equal'] ) );
			$args['meta_query'][] = [
				'key'     => sanitize_text_field( $request['user_meta'] ),
				'value'   => sanitize_text_field( $request['user_meta_value'] ),
				'compare' => $compare,
			];
		}
	}
}