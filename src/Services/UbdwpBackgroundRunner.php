<?php
/**
 * Background runner
 *
 * @package     UsersBulkDeleteWithPreview\Services
 */

namespace UsersBulkDeleteWithPreview\Services;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Runs deletion jobs in the background with Action Scheduler when it is available
 * (for example with WooCommerce), otherwise with WP-Cron.
 *
 * Each run deletes batches for a limited time as the user who started the job, then schedules
 * the next run until the job is finished.
 */
class UbdwpBackgroundRunner {
	/**
	 * Hook that runs a job.
	 */
	public const HOOK = 'ubdwp_run_deletion_job';

	/**
	 * Action Scheduler group.
	 */
	public const GROUP = 'ubdwp';

	/**
	 * Register the hook that processes background jobs.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( self::HOOK, array( self::class, 'run' ), 10, 1 );
	}

	/**
	 * Schedule the next run of a job, unless one is already waiting.
	 *
	 * @param int $job_id Job ID.
	 * @param int $delay  Seconds before the run.
	 *
	 * @return void
	 */
	public static function schedule( int $job_id, int $delay = 0 ): void {
		$args = array( $job_id );

		if ( function_exists( 'as_enqueue_async_action' ) && function_exists( 'as_has_scheduled_action' ) && did_action( 'action_scheduler_init' ) ) {
			if ( ! as_has_scheduled_action( self::HOOK, $args, self::GROUP ) ) {
				if ( $delay > 0 ) {
					as_schedule_single_action( time() + $delay, self::HOOK, $args, self::GROUP );
				} else {
					as_enqueue_async_action( self::HOOK, $args, self::GROUP );
				}
			}

			return;
		}

		if ( ! wp_next_scheduled( self::HOOK, $args ) ) {
			wp_schedule_single_event( time() + $delay, self::HOOK, $args );
		}

		if ( 0 === $delay && ! wp_doing_cron() ) {
			spawn_cron();
		}
	}

	/**
	 * Process a background job for a limited time.
	 *
	 * @param mixed $job_id Job ID.
	 *
	 * @return void
	 */
	public static function run( $job_id ): void {
		$job_id = absint( $job_id );
		$jobs   = new UbdwpDeletionJobs( 0 );
		$job    = $jobs->get( $job_id );

		if ( ! $job || UbdwpDeletionJobs::STATUS_RUNNING !== $job->status || UbdwpDeletionJobs::MODE_BACKGROUND !== $job->mode ) {
			return;
		}

		$owner_id      = (int) $job->user_id;
		$previous_user = get_current_user_id();
		$owner_jobs    = new UbdwpDeletionJobs( $owner_id );

		// Delete as the user who started the job, so their permissions and protected users apply.
		wp_set_current_user( $owner_id );

		if ( ! self::can_delete_users() ) {
			$owner_jobs->fail( $job, __( 'The user who started this job can no longer delete users.', 'users-bulk-delete-with-preview' ) );
			wp_set_current_user( $previous_user );

			return;
		}

		wp_raise_memory_limit( 'admin' );

		$deadline = microtime( true ) + self::get_time_budget();

		do {
			$result = $owner_jobs->process( $job );

			if ( is_wp_error( $result ) ) {
				// Another request holds the lock: check again later in case it stops.
				self::schedule( $job_id, 60 );
				break;
			}

			$job = $result['job'];
		} while ( $job && UbdwpDeletionJobs::STATUS_RUNNING === $job->status && microtime( true ) < $deadline );

		if ( $job && UbdwpDeletionJobs::STATUS_RUNNING === $job->status && ! is_wp_error( $result ) ) {
			self::schedule( $job_id );
		}

		wp_set_current_user( $previous_user );
	}

	/**
	 * Check the capabilities needed to delete users in the current site context.
	 *
	 * @return bool True when the current user can run deletions.
	 */
	public static function can_delete_users(): bool {
		return current_user_can( 'manage_options' )
			&& current_user_can( 'list_users' )
			&& current_user_can( is_multisite() ? 'remove_users' : 'delete_users' );
	}

	/**
	 * Seconds one background run may spend deleting users.
	 *
	 * @return int Seconds.
	 */
	private static function get_time_budget(): int {
		$max_execution_time = (int) ini_get( 'max_execution_time' );
		$budget             = $max_execution_time > 0 ? (int) floor( $max_execution_time / 2 ) : 20;

		/**
		 * Filters how many seconds one background run may spend deleting users.
		 *
		 * @param int $budget Seconds.
		 */
		return max( 5, min( 120, (int) apply_filters( 'ubdwp_background_time_budget', min( 20, $budget ) ) ) );
	}
}
