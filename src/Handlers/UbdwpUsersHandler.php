<?php
/**
 * Users Handler
 *
 * @package     UsersBulkDeleteWithPreview\Handlers
 */

namespace UsersBulkDeleteWithPreview\Handlers;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Repositories\UbdwpUsersRepository;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handler class for managing users in the Users Bulk Delete With Preview plugin.
 */
class UbdwpUsersHandler {

	/** @var UbdwpUsersRepository Repository for managing users. */
	private $repository;

	/** @var int Current user ID. */
	private $current_user_id;

	/**
	 * Constructor to initialize the Users Handler.
	 *
	 * @param int $current_user_id Current user ID.
	 */
	public function __construct(int $current_user_id) {
		$this->repository = new UbdwpUsersRepository($current_user_id);
		$this->current_user_id = $current_user_id;
	}

	/**
	 * Handle AJAX request to search users.
	 *
	 * @param array $request Request parameters.
	 *
	 * @return array List of matching users.
	 */
	public function search_users_ajax(array $request): array {
		$search_term = sanitize_text_field($request['q'] ?? '');
		$select_all = !empty($request['select_all']);

		$args = array(
			'search_columns' => array(
				'user_login',
				'user_email',
				'display_name',
			),
			'fields' => array('ID', 'display_name', 'user_email'),
		);

		if ($select_all) {
			$args['number'] = -1; // Fetch all users.
		} else {
			$args['search'] = '*' . esc_attr($search_term) . '*';
		}

		$user_query = $this->repository->search_users_ajax($args);
		$results = array();

		if (!empty($user_query->results)) {
			foreach ($user_query->results as $user) {
				if (intval($user->ID) !== intval($this->current_user_id)) {
					$results[] = array(
						'id' => intval($user->ID),
						'text' => sprintf('%s (%s)', sanitize_text_field($user->display_name), sanitize_email($user->user_email)),
					);
				}
			}
		}

		return $results;
	}

	/**
	 * Handle AJAX request to search user metadata.
	 *
	 * @param array $request Request parameters.
	 *
	 * @return array List of matching meta keys.
	 */
	public function search_usermeta_ajax(array $request): array {
		$search = sanitize_text_field($request['q'] ?? '');

		$results = $this->repository->search_usermeta_ajax($search);

		return array_map(function ($result) {
			return array(
				'id' => sanitize_key($result->meta_key),
				'text' => sanitize_text_field($result->meta_key),
			);
		}, $results);
	}

	/**
	 * Get users by their IDs.
	 *
	 * @param array $user_ids List of user IDs.
	 *
	 * @return array|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_ids(array $user_ids) {
		if (empty($user_ids) || !is_array($user_ids)) {
			return new \WP_Error('invalid_input', UbdwpHelperFacade::get_error_message('invalid_input'));
		}

		$user_ids = array_unique(array_map('intval', $user_ids));
		$users = $this->repository->get_users_by_ids($user_ids);

		if (!empty($users)) {
			return UbdwpHelperFacade::prepare_users_for_table($users, $this->repository);
		}

		return new \WP_Error('no_users_found', UbdwpHelperFacade::get_error_message('no_users_found'));
	}

	/**
	 * Get users by various filters.
	 *
	 * @param array $request Request parameters.
	 *
	 * @return array|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_filters(array $request) {
		$args = array(
			'exclude' => $this->current_user_id,
			'meta_query' => array(),
			'date_query' => array(),
		);

		$user_query = $this->repository->get_users_by_filters($args, $request);

		if (!empty($user_query->get_results())) {
			return UbdwpHelperFacade::prepare_users_for_table($user_query->get_results(), $this->repository);
		}

		return new \WP_Error('no_users_found_with_given_filters', UbdwpHelperFacade::get_error_message('no_users_found_with_given_filters'));
	}

	/**
	 * Get users who purchased specific WooCommerce products.
	 *
	 * @return array|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_woocommerce_filters() {
		$products = array_unique(array_map('absint', $_POST['products'] ?? array()));

		$user_ids = $this->repository->get_users_by_product_purchase($products);

		$user_ids = array_filter($user_ids, function ($value) {
			return $value !== 0 && $value !== '0';
		});

		if (!empty($user_ids)) {
			$user_ids = array_unique($user_ids);
			$users = $this->repository->get_users_by_ids($user_ids);

			return UbdwpHelperFacade::prepare_users_for_table($users, $this->repository);
		}

		return new \WP_Error('no_users_found_with_given_filters', UbdwpHelperFacade::get_error_message('no_users_found_with_given_filters'));
	}
}