<?php

namespace UsersBulkDeleteWithPreview\Handlers;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Repositories\UbdwpUsersRepository;

class UbdwpUsersHandler {
	private $repository;
	private $current_user_id;

	public function __construct($current_user_id) {
		$this->repository = new UbdwpUsersRepository($current_user_id);
	}

	public function search_users_ajax(array $request) {
		$search_term = sanitize_text_field( $request['q'] );
		$select_all  = ! empty( $request['select_all'] );

		$args = array(
			'search_columns' => array(
				'user_login',
				'user_email',
				'display_name',
			),
			'fields'         => array( 'ID', 'display_name', 'user_email' ),
			// Remove 'exclude' parameter
		);

		if ( $select_all ) {
			$args['number'] = -1; // Fetch all users.
		} else {
			$args['search'] = '*' . esc_attr( $search_term ) . '*';
		}

		$user_query = $this->repository->search_users_ajax($args);
		$results    = array();

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				// Manually exclude the current user
				if ( intval( $user->ID ) !== intval( $this->current_user_id ) ) {
					$results[] = array(
						'id'   => intval( $user->ID ),
						'text' => sprintf( '%s (%s)', $user->display_name, $user->user_email ),
					);
				}
			}
		}

		return $results;

	}

	public function search_usermeta_ajax( $request ) {
		$search  = sanitize_text_field( $request['q'] );

		$results = $this->repository->search_usermeta_ajax( $search );

		return array_map( function ( $result ) {
			return array(
				'id'   => $result->meta_key,
				'text' => $result->meta_key,
			);
		}, $results );
	}


	public function get_users_by_ids(  array $user_ids ) {
		if ( empty( $user_ids ) || ! is_array( $user_ids ) ) {
			return new \WP_Error( 'invalid_input', UbdwpHelperFacade::get_error_message( 'invalid_input' ) );
		}

		$user_ids = array_unique( array_map('intval', $user_ids ) );

		$this->repository->get_users_by_ids($user_ids);

		if ( ! empty( $users ) ) {
			return UbdwpHelperFacade::prepare_users_for_table( $users , $this->repository);
		}

		return new \WP_Error( 'no_users_found', UbdwpHelperFacade::get_error_message( 'no_users_found' ) );
	}

	public function get_users_by_filters( $request ) {
		$args = array(
			'exclude'    => $this->current_user_id, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude --  In this case we need to exclude current user.
			'meta_query' => array(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query --  DB call is OK.
			'date_query' => array(),
		);

		$user_query = $this->repository->get_users_by_filters( $args, $request );

		if ( ! empty( $user_query->get_results() ) ) {
			return UbdwpHelperFacade::prepare_users_for_table( $user_query->get_results(), $this->repository );
		}

		return new \WP_Error( 'no_users_found_with_given_filters', UbdwpHelperFacade::get_error_message( 'no_users_found_with_given_filters' ) );
	}

	public function get_users_by_woocommerce_filters( ) {
		$products = array_unique( array_map('absint', $_POST['products'] ?? array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing --  "nonce" is checked in search_users_for_delete_ajax method

		$user_ids = array_merge(
			$this->repository->get_users_by_product_purchase( $products )
		);

		$user_ids = array_filter($user_ids, function($value) {
			return $value !== 0 && $value !== '0';
		});

		if ( ! empty( $user_ids ) ) {
			$user_ids = array_unique( $user_ids );
			$users    = $this->repository->get_users_by_ids( $user_ids );

			return UbdwpHelperFacade::prepare_users_for_table( $users , $this->repository);
		}

		return new \WP_Error( 'no_users_found_with_given_filters', UbdwpHelperFacade::get_error_message( 'no_users_found_with_given_filters' ) );
	}
}

