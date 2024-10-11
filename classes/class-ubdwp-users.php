<?php
/**
 * Users class
 *
 * @package     UsersBulkDeleteWithPreview\Classes
 */

// Ensure this file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	echo "Hi there! I'm just a plugin, not much I can do when called directly.";
	exit;
}
if ( ! class_exists( 'UBDWPUsers' ) ) {
	/**
	 * Class UBDWPUsers
	 * Handles user-related operations for the Users Bulk Delete With Preview plugin.
	 */
	class UBDWPUsers {
		/**
		 * Current user_id
		 *
		 * @var int.
		 */
		private int $current_user_id;

		/**
		 * Constructor initializes the class with the current user ID.
		 */
		public function __construct() {
			$this->current_user_id = get_current_user_id();
		}

		/**
		 * Handle AJAX request to search users.
		 *
		 * @param  array $request  The request parameters.
		 *
		 * @return array List of users matching the search term.
		 */
		public function search_users_ajax( array $request ): array {
			$search_term = sanitize_text_field( $request['q'] );
			$select_all  = ! empty( $request['select_all'] );

			$args = array(
				'search_columns' => array(
					'user_login',
					'user_email',
					'display_name',
				),
				'fields'         => array( 'ID', 'display_name', 'user_email' ),
				'exclude'        => $this->current_user_id,
			);

			if ( $select_all ) {
				$args['number'] = - 1; // Fetch all users.
			} else {
				$args['search'] = '*' . esc_attr( $search_term ) . '*';
			}

			$user_query = new WP_User_Query( $args );
			$results    = array();

			if ( ! empty( $user_query->results ) ) {
				foreach ( $user_query->results as $user ) {
					$results[] = array(
						'id'   => intval( $user->ID ),
						'text' => sprintf( '%s (%s)', $user->display_name, $user->user_email ),
					);
				}
			}

			return $results;
		}

		/**
		 * Handle AJAX request to search user metadata.
		 *
		 * @param  array $request  The request parameters.
		 *
		 * @return array List of meta keys matching the search term.
		 */
		public function search_usermeta_ajax( array $request ): array {
			global $wpdb;

			$search  = sanitize_text_field( $request['q'] );
			$query   = $wpdb->prepare(
				"SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE %s LIMIT 10",
				'%' . $wpdb->esc_like( $search ) . '%'
			);
			$results = $wpdb->get_results( $query ); // db call ok; no-cache ok.

			return array_map( function ( $result ) {
				return array(
					'id'   => $result->meta_key,
					'text' => $result->meta_key,
				);
			}, $results );
		}

		/**
		 * Get users by their IDs.
		 *
		 * @param  array $user_ids  The user IDs to fetch.
		 *
		 * @return array|WP_Error List of users or WP_Error on failure.
		 */
		public function get_users_by_ids( array $user_ids ): mixed {
			if ( empty( $user_ids ) || ! is_array( $user_ids ) ) {
				return new WP_Error( 'invalid_input', UBDWPHelperFacade::get_error_message( 'invalid_input' ) );
			}

			$user_ids = array_unique( array_map('absint', $user_ids ) );

			$users = get_users( array( 'include' => $user_ids ) );

			if ( ! empty( $users ) ) {
				return UBDWPHelperFacade::prepare_users_for_table( $users );
			}

			return new WP_Error( 'no_users_found', UBDWPHelperFacade::get_error_message( 'no_users_found' ) );
		}

		/**
		 * Get users by various filters.
		 *
		 * @param  array $request  The request parameters.
		 *
		 * @return array|WP_Error List of users or WP_Error on failure.
		 */
		public function get_users_by_filters( array $request ): mixed {
			$args = array(
				'exclude'    => $this->current_user_id,
				'meta_query' => array(),
				'date_query' => array(),
			);

			$this->apply_role_filter( $args, $request ); //Request sanitized in apply_role_filter
			$this->apply_registration_date_filter( $args, $request ); //Request sanitized in apply_registration_date_filter
			$this->apply_usermeta_filter( $args, $request ); //Request sanitized in apply_usermeta_filter
			$this->apply_email_filters( $args, $request['user_email'] ?? '', $request['user_email_equal'] ?? '' ); //Request sanitized in apply_email_filters

			$user_query = new WP_User_Query( $args );

			if ( ! empty( $user_query->get_results() ) ) {
				return UBDWPHelperFacade::prepare_users_for_table( $user_query->get_results() );
			}

			return new WP_Error( 'no_users_found_with_given_filters', UBDWPHelperFacade::get_error_message( 'no_users_found_with_given_filters' ) );
		}

		/**
		 * Get users by WooCommerce filters.
		 *
		 * @return array|WP_Error List of users or WP_Error on failure.
		 */
		public function get_users_by_woocommerce_filters(): mixed {

			$products = array_unique( array_map('absint', $_POST['products'] ?? array() ) );

			$user_ids = array_merge(
				$this->get_users_by_product_purchase( $products )
			);

			if ( ! empty( $user_ids ) ) {
				$user_ids = array_unique( $user_ids );
				$users    = get_users( array( 'include' => $user_ids ) );

				return UBDWPHelperFacade::prepare_users_for_table( $users );
			}

			return new WP_Error( 'no_users_found_with_given_filters', UBDWPHelperFacade::get_error_message( 'no_users_found_with_given_filters' ) );
		}

		/**
		 * Get users excluding specified IDs.
		 *
		 * @param  array $exclude_ids  IDs to exclude.
		 *
		 * @return array List of users excluding the specified IDs.
		 */
		public function get_users_exclude_ids( array $exclude_ids ): array {
			return get_users( array(
				'exclude' => array_unique( array_map('absint', $exclude_ids ) ),
				'number'  => -1,
				'orderby' => 'ID',
				'order'   => 'ASC',
			) );
		}

		/**
		 * Map user meta comparison operator from request.
		 *
		 * @param  string $comparison  Comparison type from request.
		 *
		 * @return string Comparison operator.
		 */
		private function get_meta_compare_operator( string $comparison ): string {
			$map = array(
				'notequal_to_str'         => '!=',
				'like_str'                => 'LIKE',
				'notlike_str'             => 'NOT LIKE',
				'equal_to_date'           => '=',
				'equal_to_number'         => '=',
				'notequal_to_date'        => '!=',
				'notequal_to_number'      => '!=',
				'lessthen_date'           => '<',
				'lessthen_number'         => '<',
				'lessthenequal_date'      => '<=',
				'lessthenequal_number'    => '<=',
				'greaterthen_date'        => '>',
				'greaterthen_number'      => '>',
				'greaterthenequal_date'   => '>=',
				'greaterthenequal_number' => '>=',
			);

			return $map[ $comparison ] ?? '=';
		}

		/**
		 * Map email comparison operator from request.
		 *
		 * @param  string $comparison  Comparison type from request.
		 *
		 * @return string Comparison operator.
		 */
		private function get_email_compare_operator( string $comparison ): string {
			$map = array(
				'notequal_to_str' => '!=',
				'like_str'        => 'LIKE',
				'notlike_str'     => 'NOT LIKE',
			);

			return $map[ $comparison ] ?? '=';
		}

		/**
		 * Apply email filters to the query arguments.
		 *
		 * @param  array  $args           Current query arguments.
		 * @param  string $email_search   Email search term.
		 * @param  string $email_compare  Comparison operator.
		 *
		 * @return array|WP_Error Modified query arguments.
		 */
		private function apply_email_filters(
			array &$args,
			string $email_search,
			string $email_compare
		): mixed {
			global $wpdb;

			$email_search = sanitize_text_field( $email_search );
			if ( $email_compare ) {
				$compare = $this->get_email_compare_operator( sanitize_text_field( $email_compare ) );

				if ( in_array( $compare, array( 'LIKE', 'NOT LIKE' ) ) ) {
					$email_search = '%' . $wpdb->esc_like( $email_search ) . '%';
				}

				$sql      = "
                    SELECT ID 
                    FROM {$wpdb->users} 
                    WHERE user_email $compare %s 
                    AND ID != %d
                ";
				$user_ids = $wpdb->get_col( $wpdb->prepare( $sql,
					$email_search,
					$this->current_user_id ) );

				if ( ! empty( $user_ids ) ) {
					$args['include'] = $user_ids;
				} else {
					return new WP_Error( 'no_users_found_with_given_filters', UBDWPHelperFacade::get_error_message( 'no_users_found_with_given_filters' ) );
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
		 * @param  array $args     Current query arguments.
		 * @param  array $request  The request parameters.
		 */
		private function apply_role_filter( array &$args, array $request ) {
			if ( ! empty( $request['user_role'] ) ) {
				$args['role__in'] = array_map( 'sanitize_text_field', $request['user_role'] );
			}
		}

		/**
		 * Apply registration date filters to the query arguments.
		 *
		 * @param  array $args     Current query arguments.
		 * @param  array $request  The request parameters.
		 */
		private function apply_registration_date_filter(
			array &$args,
			array $request
		): void {
			if ( ! empty( $request['registration_date'] ) ) {
				$args['date_query'][] = array(
					'column' => 'user_registered',
					'after'  => sanitize_text_field( $request['registration_date'] ),
				);
			}
		}

		/**
		 * Apply usermeta filters to the query arguments.
		 *
		 * @param  array $args     Current query arguments.
		 * @param  array $request  The request parameters.
		 */
		private function apply_usermeta_filter( array &$args, array $request ): void {
			if ( ! empty( $request['user_meta'] ) && ! empty( $request['user_meta_value'] ) ) {
				$compare              = $this->get_meta_compare_operator( sanitize_text_field( $request['user_meta_equal'] ) );
				$args['meta_query'][] = array(
					'key'     => sanitize_text_field( $request['user_meta'] ),
					'value'   => sanitize_text_field( $request['user_meta_value'] ),
					'compare' => $compare,
				);
			}
		}

		/**
		 * Get users who purchased a specific WooCommerce product.
		 *
		 * @param array $products_ids List of products IDs.
		 *
		 * @return array List of user IDs.
		 */
		private function get_users_by_product_purchase( array $products_ids ): array {
			global $wpdb;

			// Check if the product IDs array is empty.
			if ( empty( $products_ids ) ) {
				return array();
			}

			// Sanitize product IDs: convert all IDs to integers.
			$products_ids = array_unique( array_map('absint', $products_ids ) );

			// Create placeholders for the prepared SQL statement.
			$placeholders = implode( ',', array_fill( 0, count( $products_ids ), '%d' ) );

			// Get order IDs that contain any of the products.
			$order_items = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT order_id 
             FROM {$wpdb->prefix}woocommerce_order_items 
             WHERE order_item_id IN (
                 SELECT order_item_id 
                 FROM {$wpdb->prefix}woocommerce_order_itemmeta 
                 WHERE meta_key = '_product_id' AND meta_value IN ($placeholders)
             )",
					...$products_ids
				)
			);


			if ( empty( $order_items ) ) {
				return array();
			}

			// Sanitize order IDs.
			$order_items = array_map( 'intval', $order_items );

			// Create placeholders for order IDs.
			$order_ids_placeholders = implode( ',', array_fill( 0, count( $order_items ), '%d' ) );


			// Get user IDs who purchased the products.
			return $wpdb->get_col(
				$wpdb->prepare(
					"
            SELECT DISTINCT posts.post_author 
            FROM {$wpdb->prefix}posts AS posts
            WHERE posts.ID IN ($order_ids_placeholders)
            AND posts.post_type IN ('shop_order', 'shop_order_placehold')
            AND posts.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
            ",
					...$order_items
				)
			);
		}
	}
}