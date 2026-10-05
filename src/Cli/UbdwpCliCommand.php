<?php
/**
 * WP-CLI commands
 *
 * @package     UsersBulkDeleteWithPreview\Cli
 */

namespace UsersBulkDeleteWithPreview\Cli;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Activators\UbdwpActivate;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Handlers\UbdwpUsersHandler;
use UsersBulkDeleteWithPreview\Services\UbdwpBackgroundRunner;
use UsersBulkDeleteWithPreview\Services\UbdwpDeletionJobs;
use UsersBulkDeleteWithPreview\Services\UbdwpPrivacy;
use UsersBulkDeleteWithPreview\Services\UbdwpSelection;
use UsersBulkDeleteWithPreview\Services\UbdwpUserFinder;

/**
 * Find and delete users with the filters of Users Bulk Delete With Preview.
 *
 * Commands run as the WordPress user given with the global --user parameter. That user needs the same
 * capabilities as on the admin page, and protected users (administrators and other privileged users)
 * are never deleted. On multisite, use --url to choose the site: users are removed from that site only.
 *
 * ## EXAMPLES
 *
 *     # Count subscribers without a first name registered before 2025.
 *     $ wp ubdwp find --role=subscriber --meta-key=first_name --meta-compare=empty --registered=2025-01-01 --registered-compare=before --format=count --user=admin
 *
 *     # Show what would be deleted, without deleting anything.
 *     $ wp ubdwp delete --role=subscriber --meta-key=first_name --meta-compare=empty --dry-run --user=admin
 *
 *     # Delete them and reassign their posts to user 1.
 *     $ wp ubdwp delete --role=subscriber --meta-key=first_name --meta-compare=empty --reassign=1 --yes --user=admin
 */
class UbdwpCliCommand {
	/**
	 * Map of --email-compare values to the plugin's comparison types.
	 */
	private const EMAIL_COMPARE = array(
		'equal'     => 'equal_to_str',
		'not-equal' => 'notequal_to_str',
		'like'      => 'like_str',
		'not-like'  => 'notlike_str',
		'ends-with' => 'endswith_str',
	);

	/**
	 * Map of --meta-compare values to the plugin's comparison types.
	 */
	private const META_COMPARE = array(
		'not-exists'          => 'meta_not_exists',
		'empty'               => 'meta_is_empty',
		'equal'               => 'equal_to_str',
		'not-equal'           => 'notequal_to_str',
		'like'                => 'like_str',
		'not-like'            => 'notlike_str',
		'equal-date'          => 'equal_to_date',
		'not-equal-date'      => 'notequal_to_date',
		'less-date'           => 'lessthen_date',
		'less-equal-date'     => 'lessthenequal_date',
		'greater-date'        => 'greaterthen_date',
		'greater-equal-date'  => 'greaterthenequal_date',
		'equal-number'        => 'equal_to_number',
		'not-equal-number'    => 'notequal_to_number',
		'less-number'         => 'lessthen_number',
		'less-equal-number'   => 'lessthenequal_number',
		'greater-number'      => 'greaterthen_number',
		'greater-equal-number' => 'greaterthenequal_number',
	);

	/**
	 * Lists the users that match the filters.
	 *
	 * ## OPTIONS
	 *
	 * [--ids=<ids>]
	 * : Comma-separated user IDs. Use "all" for all users of the site.
	 *
	 * [--role=<roles>]
	 * : Comma-separated roles.
	 *
	 * [--email=<value>]
	 * : Email value to compare.
	 *
	 * [--email-compare=<compare>]
	 * : How to compare the email.
	 * ---
	 * default: equal
	 * options:
	 *   - equal
	 *   - not-equal
	 *   - like
	 *   - not-like
	 *   - ends-with
	 * ---
	 *
	 * [--registered=<date>]
	 * : Registration date (YYYY-MM-DD, site timezone).
	 *
	 * [--registered-compare=<compare>]
	 * : How to compare the registration date.
	 * ---
	 * default: after
	 * options:
	 *   - after
	 *   - before
	 *   - on
	 *   - between
	 * ---
	 *
	 * [--registered-to=<date>]
	 * : End date for --registered-compare=between.
	 *
	 * [--meta-key=<key>]
	 * : User meta key.
	 *
	 * [--meta-compare=<compare>]
	 * : How to compare the user meta: not-exists, empty, equal, not-equal, like, not-like,
	 * equal-date, not-equal-date, less-date, less-equal-date, greater-date, greater-equal-date,
	 * equal-number, not-equal-number, less-number, less-equal-number, greater-number, greater-equal-number.
	 * ---
	 * default: equal
	 * ---
	 *
	 * [--meta-value=<value>]
	 * : User meta value to compare.
	 *
	 * [--without-content]
	 * : Only users without posts or comments on the site.
	 *
	 * [--without-orders]
	 * : Only users without WooCommerce orders.
	 *
	 * [--products=<ids>]
	 * : Customers who bought one of these WooCommerce products (comma-separated product IDs).
	 *
	 * [--any-product]
	 * : Customers who bought any WooCommerce product.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - ids
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ubdwp find --email=@example.com --email-compare=ends-with --user=admin
	 *     $ wp ubdwp find --products=12,15 --format=count --user=admin
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Options.
	 *
	 * @return void
	 */
	public function find( array $args, array $assoc_args ): void {
		$this->prepare();

		$user_ids = $this->find_user_ids( $assoc_args );
		$format   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( 'count' === $format ) {
			\WP_CLI::line( (string) count( $user_ids ) );

			return;
		}

		if ( 'ids' === $format ) {
			\WP_CLI::line( implode( ' ', $user_ids ) );

			return;
		}

		$handler = new UbdwpUsersHandler( get_current_user_id() );
		$items   = array();

		foreach ( array_chunk( $user_ids, 500 ) as $chunk ) {
			$page = $handler->get_preview_page( $chunk, 0, count( $chunk ), '', 1, 'asc' );

			foreach ( $page['rows'] as $row ) {
				$items[] = array(
					'ID'         => $row['ID'],
					'user_login' => $row['user_login'],
					'user_email' => $row['user_email'],
					'registered' => $row['user_registered'],
					'roles'      => $row['user_role'],
					'posts'      => $row['post_count'],
					'protected'  => $row['protected'] ? 'yes' : 'no',
				);
			}

			UbdwpHelperFacade::flush_runtime_cache();
		}

		\WP_CLI\Utils\format_items( $format, $items, array( 'ID', 'user_login', 'user_email', 'registered', 'roles', 'posts', 'protected' ) );
	}

	/**
	 * Deletes the users that match the filters (on multisite: removes them from the site).
	 *
	 * Shows a summary first. Protected users and users the current user cannot delete are skipped.
	 * Every batch is recorded on the plugin's Logs page.
	 *
	 * ## OPTIONS
	 *
	 * [--ids=<ids>]
	 * : Comma-separated user IDs. Use "all" for all users of the site.
	 *
	 * [--role=<roles>]
	 * : Comma-separated roles.
	 *
	 * [--email=<value>]
	 * : Email value to compare.
	 *
	 * [--email-compare=<compare>]
	 * : How to compare the email: equal, not-equal, like, not-like, ends-with.
	 * ---
	 * default: equal
	 * ---
	 *
	 * [--registered=<date>]
	 * : Registration date (YYYY-MM-DD, site timezone).
	 *
	 * [--registered-compare=<compare>]
	 * : How to compare the registration date: after, before, on, between.
	 * ---
	 * default: after
	 * ---
	 *
	 * [--registered-to=<date>]
	 * : End date for --registered-compare=between.
	 *
	 * [--meta-key=<key>]
	 * : User meta key.
	 *
	 * [--meta-compare=<compare>]
	 * : How to compare the user meta (see "wp help ubdwp find").
	 * ---
	 * default: equal
	 * ---
	 *
	 * [--meta-value=<value>]
	 * : User meta value to compare.
	 *
	 * [--without-content]
	 * : Only users without posts or comments on the site.
	 *
	 * [--without-orders]
	 * : Only users without WooCommerce orders.
	 *
	 * [--products=<ids>]
	 * : Customers who bought one of these WooCommerce products (comma-separated product IDs).
	 *
	 * [--any-product]
	 * : Customers who bought any WooCommerce product.
	 *
	 * [--reassign=<user>]
	 * : User ID that receives the content of deleted users, or "remove-content" to permanently delete
	 * their posts and comments. Without it, WordPress core behavior applies.
	 *
	 * [--batch-size=<number>]
	 * : Users deleted per batch.
	 *
	 * [--dry-run]
	 * : Only show the summary.
	 *
	 * [--yes]
	 * : Do not ask for confirmation.
	 *
	 * [--background]
	 * : Create a background job instead of deleting now. Follow it with "wp ubdwp jobs".
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ubdwp delete --role=subscriber --meta-key=first_name --meta-compare=empty --dry-run --user=admin
	 *     $ wp ubdwp delete --ids=12,15 --reassign=remove-content --yes --user=admin
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Options.
	 *
	 * @return void
	 */
	public function delete( array $args, array $assoc_args ): void {
		$this->prepare( true );

		$user_ids = $this->find_user_ids( $assoc_args );
		$reassign = $this->parse_reassign( (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'reassign', '' ) );
		$jobs     = new UbdwpDeletionJobs( get_current_user_id() );
		$job      = $jobs->create(
			array(
				'ids'      => $user_ids,
				'default'  => $reassign,
				'reassign' => array(),
			),
			UbdwpDeletionJobs::MODE_CLI
		);

		if ( is_wp_error( $job ) ) {
			\WP_CLI::error( $job->get_error_message() );
		}

		$progress = \WP_CLI\Utils\make_progress_bar( __( 'Preparing the summary', 'users-bulk-delete-with-preview' ), count( $user_ids ) );

		while ( UbdwpDeletionJobs::STATUS_SUMMARIZING === $job->status ) {
			$before = (int) $job->summary_position;
			$job    = $jobs->summarize( $job );

			if ( is_wp_error( $job ) ) {
				\WP_CLI::error( $job->get_error_message() );
			}

			$progress->tick( (int) $job->summary_position - $before );
		}

		$progress->finish();

		$status = $jobs->to_status( $job );
		$this->print_summary( $status['summary'] );

		if ( (int) $status['summary']['deletable'] < 1 || \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false ) ) {
			$jobs->discard( $job );
			\WP_CLI::success( __( 'Dry run: no users were deleted.', 'users-bulk-delete-with-preview' ) );

			return;
		}

		/* translators: %d: number of users. */
		\WP_CLI::confirm( sprintf( __( 'Delete %d users? This cannot be undone.', 'users-bulk-delete-with-preview' ), (int) $status['summary']['deletable'] ), $assoc_args );

		$background = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'background', false );
		$job        = $jobs->start( $job, $background ? UbdwpDeletionJobs::MODE_BACKGROUND : UbdwpDeletionJobs::MODE_CLI, (string) (int) $status['summary']['deletable'] );

		if ( is_wp_error( $job ) ) {
			\WP_CLI::error( $job->get_error_message() );
		}

		if ( $background ) {
			/* translators: %d: job ID. */
			\WP_CLI::success( sprintf( __( 'Background job %d created. Follow it with "wp ubdwp jobs".', 'users-bulk-delete-with-preview' ), (int) $job->ID ) );

			return;
		}

		$this->run_job( $jobs, $job, $this->get_batch_size( $assoc_args ) );
	}

	/**
	 * Lists, cancels or runs deletion jobs of the site.
	 *
	 * ## OPTIONS
	 *
	 * [<action>]
	 * : What to do.
	 * ---
	 * default: list
	 * options:
	 *   - list
	 *   - cancel
	 *   - run
	 * ---
	 *
	 * [<id>]
	 * : Job ID for "cancel" and "run".
	 *
	 * [--format=<format>]
	 * : Output format of the list.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ubdwp jobs --user=admin
	 *     $ wp ubdwp jobs run 12 --user=admin
	 *     $ wp ubdwp jobs cancel 12 --user=admin
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Options.
	 *
	 * @return void
	 */
	public function jobs( array $args, array $assoc_args ): void {
		$this->prepare( true );

		$action = $args[0] ?? 'list';
		$jobs   = new UbdwpDeletionJobs( get_current_user_id() );

		if ( 'list' === $action ) {
			$items = array();

			foreach ( $jobs->get_recent( 50 ) as $job ) {
				$status  = $jobs->to_status( $job );
				$owner   = get_userdata( (int) $job->user_id );
				$items[] = array(
					'ID'       => $status['job_id'],
					'status'   => $status['status'],
					'mode'     => $status['mode'],
					'user'     => $owner ? $owner->user_login : (string) $job->user_id,
					'progress' => $status['position'] . '/' . $status['total'],
					'deleted'  => $status['deleted'],
					'failed'   => $status['failed'],
					'created'  => get_date_from_gmt( $job->created_at, 'Y-m-d H:i' ),
					'message'  => $status['message'],
				);
			}

			\WP_CLI\Utils\format_items( \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' ), $items, array( 'ID', 'status', 'mode', 'user', 'progress', 'deleted', 'failed', 'created', 'message' ) );

			return;
		}

		$job = $jobs->get( absint( $args[1] ?? 0 ) );

		if ( ! $job ) {
			\WP_CLI::error( __( 'The deletion job was not found.', 'users-bulk-delete-with-preview' ) );
		}

		if ( 'cancel' === $action ) {
			$result = $jobs->cancel( $job );

			if ( is_wp_error( $result ) ) {
				\WP_CLI::error( $result->get_error_message() );
			}

			/* translators: %d: job ID. */
			\WP_CLI::success( sprintf( __( 'Job %d cancelled.', 'users-bulk-delete-with-preview' ), (int) $job->ID ) );

			return;
		}

		// Running a job deletes users as the user who started it, never as someone else.
		if ( UbdwpDeletionJobs::STATUS_RUNNING !== $job->status || (int) $job->user_id !== get_current_user_id() ) {
			\WP_CLI::error( __( 'Only running jobs that you started can be run.', 'users-bulk-delete-with-preview' ) );
		}

		$this->run_job( $jobs, $job, $this->get_batch_size( $assoc_args ) );
	}

	/**
	 * Deletes old log entries or applies the email privacy setting to existing entries.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : What to do.
	 * ---
	 * options:
	 *   - purge
	 *   - anonymize
	 * ---
	 *
	 * [--older-than=<days>]
	 * : For "purge": delete entries older than this many days.
	 *
	 * [--all]
	 * : For "purge": delete the whole log.
	 *
	 * [--yes]
	 * : Do not ask for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp ubdwp logs purge --older-than=90 --user=admin
	 *     $ wp ubdwp logs anonymize --yes --user=admin
	 *
	 * @param array<int, string>    $args       Positional arguments.
	 * @param array<string, string> $assoc_args Options.
	 *
	 * @return void
	 */
	public function logs( array $args, array $assoc_args ): void {
		$this->prepare( true );

		if ( 'anonymize' === ( $args[0] ?? '' ) ) {
			\WP_CLI::confirm( __( 'Apply the saved email setting to all existing log entries? Masked or removed data cannot be restored.', 'users-bulk-delete-with-preview' ), $assoc_args );

			/* translators: %d: number of log entries. */
			\WP_CLI::success( sprintf( __( 'Updated log entries: %d.', 'users-bulk-delete-with-preview' ), UbdwpPrivacy::anonymize_existing_logs() ) );

			return;
		}

		$all  = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );
		$days = absint( \WP_CLI\Utils\get_flag_value( $assoc_args, 'older-than', 0 ) );

		if ( ! $all && $days < 1 ) {
			\WP_CLI::error( __( 'Use --older-than=<days> or --all.', 'users-bulk-delete-with-preview' ) );
		}

		\WP_CLI::confirm(
			$all
				? __( 'Delete the whole log? This cannot be undone.', 'users-bulk-delete-with-preview' )
				/* translators: %d: number of days. */
				: sprintf( __( 'Delete log entries older than %d days? This cannot be undone.', 'users-bulk-delete-with-preview' ), $days ),
			$assoc_args
		);

		/* translators: %d: number of log entries. */
		\WP_CLI::success( sprintf( __( 'Deleted log entries: %d.', 'users-bulk-delete-with-preview' ), UbdwpPrivacy::delete_logs( $all ? 0 : $days ) ) );
	}

	/**
	 * Load the plugin context and check the current user.
	 *
	 * @param bool $delete Whether the command deletes users.
	 *
	 * @return void
	 */
	private function prepare( bool $delete = false ): void {
		UbdwpActivate::ubdwp_maybe_upgrade_current_site();

		$can_view = current_user_can( 'manage_options' ) && current_user_can( 'list_users' );

		if ( ! get_current_user_id() || ! $can_view || ( $delete && ! UbdwpBackgroundRunner::can_delete_users() ) ) {
			\WP_CLI::error( __( 'Run this command as an administrator who can delete users, for example with --user=admin.', 'users-bulk-delete-with-preview' ) );
		}
	}

	/**
	 * Find users with the filters given as options.
	 *
	 * @param array<string, string> $assoc_args Options.
	 *
	 * @return array<int> User IDs.
	 */
	private function find_user_ids( array $assoc_args ): array {
		$list = static fn( string $value ): array => array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) );
		$get  = static fn( string $key, $default = '' ) => \WP_CLI\Utils\get_flag_value( $assoc_args, $key, $default );

		if ( '' !== (string) $get( 'ids' ) ) {
			$type    = 'select_existing';
			$request = 'all' === $get( 'ids' ) ? array( 'all_users' => true ) : array( 'user_search' => $list( (string) $get( 'ids' ) ) );
		} elseif ( '' !== (string) $get( 'products' ) || $get( 'any-product', false ) ) {
			$type    = 'find_users_by_woocommerce_filters';
			$request = array(
				'products'     => $list( (string) $get( 'products' ) ),
				'all_products' => (bool) $get( 'any-product', false ),
			);
		} else {
			$email_compare = (string) $get( 'email-compare', 'equal' );
			$meta_compare  = (string) $get( 'meta-compare', 'equal' );

			if ( ! isset( self::EMAIL_COMPARE[ $email_compare ] ) ) {
				\WP_CLI::error( __( 'Please choose a valid email comparison.', 'users-bulk-delete-with-preview' ) );
			}

			if ( ! isset( self::META_COMPARE[ $meta_compare ] ) ) {
				\WP_CLI::error( __( 'Please choose a valid user meta comparison.', 'users-bulk-delete-with-preview' ) );
			}

			$type    = 'find_users';
			$request = array(
				'user_role'                 => $list( (string) $get( 'role' ) ),
				'user_email'                => (string) $get( 'email' ),
				'user_email_equal'          => self::EMAIL_COMPARE[ $email_compare ],
				'registration_date'         => (string) $get( 'registered' ),
				'registration_date_compare' => (string) $get( 'registered-compare', 'after' ),
				'registration_date_to'      => (string) $get( 'registered-to' ),
				'user_meta'                 => (string) $get( 'meta-key' ),
				'user_meta_equal'           => self::META_COMPARE[ $meta_compare ],
				'user_meta_value'           => (string) $get( 'meta-value' ),
				'without_content'           => (bool) $get( 'without-content', false ),
				'without_wc_orders'         => (bool) $get( 'without-orders', false ),
			);
		}

		$user_ids = ( new UbdwpUserFinder( get_current_user_id() ) )->find( $type, $request );

		if ( is_wp_error( $user_ids ) ) {
			\WP_CLI::error( $user_ids->get_error_message() );
		}

		return $user_ids;
	}

	/**
	 * Convert the --reassign option to a reassign value.
	 *
	 * @param string $value Option value.
	 *
	 * @return string Reassign value.
	 */
	private function parse_reassign( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		if ( 'remove-content' === $value ) {
			return UbdwpSelection::REMOVE_CONTENT;
		}

		$reassign = UbdwpSelection::sanitize_reassign( $value );

		if ( '' === $reassign || ! get_userdata( (int) $reassign ) || ( is_multisite() && ! is_user_member_of_blog( (int) $reassign ) ) ) {
			\WP_CLI::error( __( 'The user selected to receive the content is not valid.', 'users-bulk-delete-with-preview' ) );
		}

		return $reassign;
	}

	/**
	 * Read the --batch-size option.
	 *
	 * @param array<string, string> $assoc_args Options.
	 *
	 * @return int Batch size.
	 */
	private function get_batch_size( array $assoc_args ): int {
		$batch_size = absint( \WP_CLI\Utils\get_flag_value( $assoc_args, 'batch-size', 0 ) );

		return $batch_size > 0 ? min( 500, $batch_size ) : UbdwpDeletionJobs::get_batch_size();
	}

	/**
	 * Delete all remaining batches of a running job, with a progress bar.
	 *
	 * @param UbdwpDeletionJobs $jobs       Jobs service of the current user.
	 * @param object            $job        Job.
	 * @param int               $batch_size Users per batch.
	 *
	 * @return void
	 */
	private function run_job( UbdwpDeletionJobs $jobs, object $job, int $batch_size ): void {
		wp_raise_memory_limit( 'admin' );

		$progress = \WP_CLI\Utils\make_progress_bar( __( 'Deleting users', 'users-bulk-delete-with-preview' ), (int) $job->total );
		$progress->tick( (int) $job->position );

		while ( UbdwpDeletionJobs::STATUS_RUNNING === $job->status ) {
			$before = (int) $job->position;
			$result = $jobs->process( $job, $batch_size );

			if ( is_wp_error( $result ) ) {
				$progress->finish();
				\WP_CLI::error( $result->get_error_message() );
			}

			$job = $result['job'];
			$progress->tick( (int) $job->position - $before );
		}

		$progress->finish();

		$status = $jobs->to_status( $job );

		if ( ! empty( $status['failed_users'] ) ) {
			\WP_CLI\Utils\format_items( 'table', $status['failed_users'], array( 'user_id', 'login', 'email', 'message' ) );
		}

		if ( UbdwpDeletionJobs::STATUS_DONE !== $status['status'] ) {
			/* translators: %s: job status. */
			\WP_CLI::warning( sprintf( __( 'The job stopped with status: %s', 'users-bulk-delete-with-preview' ), $status['status_label'] ) );
		}

		/* translators: 1: number of deleted users, 2: number of users that were not deleted. */
		\WP_CLI::success( sprintf( __( 'Deleted users: %1$d. Not deleted: %2$d.', 'users-bulk-delete-with-preview' ), $status['deleted'], $status['failed'] ) );
	}

	/**
	 * Print the summary of a job.
	 *
	 * @param array<string, mixed> $summary Summary.
	 *
	 * @return void
	 */
	private function print_summary( array $summary ): void {
		$translations = UbdwpHelperFacade::get_user_table_translation();

		\WP_CLI::line( sprintf( $translations['summarySelected'], $summary['selected'] ) );
		\WP_CLI::line( sprintf( is_multisite() ? $translations['summaryRemovable'] : $translations['summaryDeletable'], $summary['deletable'] ) );

		if ( $summary['reassign_users'] > 0 ) {
			\WP_CLI::line( sprintf( $translations['summaryReassign'], $summary['reassign_users'], $summary['reassign_posts'], implode( ', ', $summary['reassign_targets'] ) ) );
		}

		if ( $summary['default_users'] > 0 ) {
			\WP_CLI::line( is_multisite() ? sprintf( $translations['summaryKeep'], $summary['default_users'] ) : sprintf( $translations['summaryTrash'], $summary['default_users'], $summary['default_posts'] ) );
		}

		if ( $summary['remove_users'] > 0 ) {
			\WP_CLI::line( sprintf( $translations['summaryRemove'], $summary['remove_users'], $summary['remove_posts'], $summary['remove_comments'] ) );
		}

		if ( $summary['skipped_count'] > 0 ) {
			\WP_CLI::line( sprintf( $translations['summarySkipped'], $summary['skipped_count'] ) );
			\WP_CLI\Utils\format_items( 'table', array_slice( $summary['skipped'], 0, 50 ), array( 'user_id', 'login', 'email', 'message' ) );
		}
	}
}
