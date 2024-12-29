<?php
/**
 * Users Handler
 *
 * @package     UsersBulkDeleteWithPreview\Handlers
 */

namespace UsersBulkDeleteWithPreview\Handlers;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpViewsFacade;
use UsersBulkDeleteWithPreview\Repositories\UbdwpAbstractUsersRepository;
use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Handler class for managing users in the Users Bulk Delete With Preview plugin.
 */
class UbdwpUsersHandler {

	/** @var UbdwpAbstractUsersRepository Repository for managing users. */
	public $repository;

	/** @var int Current user ID. */
	private $current_user_id;

	/**
	 * Constructor to initialize the Users Handler.
	 *
	 * @param int $current_user_id Current user ID.
	 */
	public function __construct(int $current_user_id) {
		$this->repository = new UbdwpAbstractUsersRepository($current_user_id);
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
		$search_term = $request['q'] ?? '';
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
		$search = $request['q'] ?? '';

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
			return new \WP_Error('invalid_input', UbdwpValidationFacade::get_error_message('invalid_input'));
		}

		$user_ids = array_unique(array_map('intval', $user_ids));
		$users = $this->repository->get_users_by_ids($user_ids);

		if (!empty($users)) {
			return UbdwpHelperFacade::prepare_users_for_table($users, $this->repository);
		}

		return new \WP_Error('no_users_found', UbdwpValidationFacade::get_error_message('no_users_found'));
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

		return new \WP_Error('no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message('no_users_found_with_given_filters'));
	}

	/**
	 * Get users who purchased specific WooCommerce products.
	 *
	 * @return array|\WP_Error List of users or error on failure.
	 */
	public function get_users_by_woocommerce_filters(array $request) {
		$products = array_unique(array_map('absint', $request['products'] ?? array()));

		$user_ids = $this->repository->get_users_by_product_purchase($products);

		$user_ids = array_filter($user_ids, function ($value) {
			return $value !== 0 && $value !== '0';
		});

		if (!empty($user_ids)) {
			$user_ids = array_unique($user_ids);
			$users = $this->repository->get_users_by_ids($user_ids);

			return UbdwpHelperFacade::prepare_users_for_table($users, $this->repository);
		}

		return new \WP_Error('no_users_found_with_given_filters', UbdwpValidationFacade::get_error_message('no_users_found_with_given_filters'));
	}

	/**
	 * Generate CSV content from the user list.
	 *
	 * @param  array $users  List of user objects.
	 *
	 * @return string CSV content.
	 */
	public function generate_csv(array $users): string {
		$output = fopen('php://temp', 'w');
		fputcsv($output, ['ID', 'Username', 'Email', 'First Name', 'Last Name', 'Role']);

		foreach ($users as $user) {
			fputcsv($output, [
				intval($user->ID),
				sanitize_text_field($user->user_login),
				sanitize_email($user->user_email),
				sanitize_text_field($user->first_name),
				sanitize_text_field($user->last_name),
				implode(', ', $user->roles),
			]);
		}

		rewind($output);
		$csv_content = stream_get_contents($output);
		fclose($output);

		return $csv_content;
	}

	/**
	 * Save the generated CSV content to a file.
	 *
	 * @param  string $csv_output  The CSV content.
	 * @return string URL of the saved file.
	 */
	public function save_csv_file( $csv_output ) {
		// Initialize the WordPress filesystem API
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		// Get the upload directory
		$upload_dir = wp_upload_dir();
		$file_path  = $upload_dir['path'] . '/users_export_' . time() . '.csv';

		// Use WP_Filesystem's method to write the file
		if ( ! $wp_filesystem->put_contents( $file_path, $csv_output, FS_CHMOD_FILE ) ) {
			return new \WP_Error( 'file_write_error', __( 'Failed to write the CSV file.', 'users-bulk-delete-with-preview' ) );
		}

		// Return the URL of the saved file
		return $upload_dir['url'] . '/' . basename( $file_path );
	}

	public function delete_users(array $sanitized_users) {
		$deleted_users = array();
		foreach ($sanitized_users as $user) {
			if ( intval( $user['id'] ) > 0 ) {
				$deleted_users[ $user['id'] ] = array(
					'user_id'      => intval( $user['id'] ),
					'email'        => $user['email'],
					'display_name' => $user['display_name'],
					'reassign'     => $user['reassign'] ?? '',
				);

				// Check if related content should be deleted
				if (isset($user['reassign']) && $user['reassign'] === 'remove_all_related_content') {
					$user_id = intval($user['id']);

					// 1. Retrieve all posts by the user
					$user_posts = get_posts(array(
						'author'        => $user_id,
						'post_type'     => 'any',
						'post_status'   => 'any',
						'numberposts'   => -1,
						'fields'        => 'ids', // Retrieve only IDs for better performance
					));

					// Delete all posts by the user
					foreach ($user_posts as $post_id) {
						wp_delete_post($post_id, true); // Force delete
					}

					// 2. Delete comments by the user
					$user_comments = get_comments(array(
						'user_id' => $user_id,
					));
					foreach ($user_comments as $comment) {
						wp_delete_comment($comment->comment_ID, true); // Force delete
					}

					$user['reassign'] = null;
				}

				wp_delete_user( esc_attr( $user['id'] ), esc_attr( $user['reassign'] ) ?? null );
			}
		}


		$template
			= UbdwpViewsFacade::render_template(
			'partials/_success_user_delete.php',
			array(
				'user_delete_count' => count( $deleted_users ),
				'deleted_users'     => array_values( $deleted_users ),
			)
		);

		return [
			'deleted_users' => $deleted_users ?? array(),
			'template'      => $template ?? '',
		];
	}

	public function delete_csv_file($file_path) {
		if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
			wp_delete_file( $file_path ); // Delete file.
		}
	}

	public function search_users_for_delete_ajax($type, $request) {
		// Define the keys that need to be extracted from $_POST.
		$keys = [
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
			'user_meta'
		];
		$data_before_sanitize = array_intersect_key($request, array_flip($keys));
		$sanitized_data = UbdwpHelperFacade::sanitize_post_data($data_before_sanitize);

		switch ($type) {
			case 'select_existing':
				UbdwpValidationFacade::validate_user_search_for_existing_users($sanitized_data);
				$results = $this->get_users_by_ids(array_unique(array_map('intval', $request['user_search'] ?? array())));
				break;
			case 'find_users':
				UbdwpValidationFacade::validate_find_user_form($sanitized_data);
				$results = $this->get_users_by_filters($sanitized_data);
				break;
			case 'find_users_by_woocommerce_filters':
				UbdwpValidationFacade::validate_woocommerce_filters( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
				$results
					= $this->get_users_by_woocommerce_filters( $sanitized_data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked above, check method - "verify_nonce".
				break;
			default:
				wp_send_json_error(array('message' => UbdwpValidationFacade::get_error_message('select_type')));
				wp_die();
		}

		return $results;
	}
}