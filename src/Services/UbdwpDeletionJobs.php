<?php
/**
 * Deletion jobs
 *
 * @package     UsersBulkDeleteWithPreview\Services
 */

namespace UsersBulkDeleteWithPreview\Services;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Facades\UbdwpValidationFacade;
use UsersBulkDeleteWithPreview\Handlers\UbdwpLogsHandler;
use UsersBulkDeleteWithPreview\Handlers\UbdwpUsersHandler;
use UsersBulkDeleteWithPreview\Repositories\UbdwpJobsRepository;

/**
 * Deletes a selection of users in batches.
 *
 * A job is created from a selection, summarized in chunks (status "summarizing"), confirmed ("ready"),
 * then processed batch by batch ("running") in the browser, in the background or by WP-CLI,
 * until it is "done", "cancelled" or "failed".
 *
 * Every batch is deleted by UbdwpUsersHandler as the user who created the job, with the same
 * permission checks and protected users as a direct deletion.
 */
class UbdwpDeletionJobs {
	public const STATUS_SUMMARIZING = 'summarizing';
	public const STATUS_READY       = 'ready';
	public const STATUS_RUNNING     = 'running';
	public const STATUS_DONE        = 'done';
	public const STATUS_CANCELLED   = 'cancelled';
	public const STATUS_FAILED      = 'failed';

	public const MODE_BROWSER    = 'browser';
	public const MODE_BACKGROUND = 'background';
	public const MODE_CLI        = 'cli';

	/**
	 * Users summarized per request.
	 */
	public const SUMMARY_CHUNK = 2000;

	/**
	 * Skipped and failed users kept for display.
	 */
	private const MAX_LISTED_USERS = 500;

	/**
	 * Lifetime of a processing lock in seconds.
	 */
	private const LOCK_SECONDS = 120;

	/**
	 * Jobs repository.
	 *
	 * @var UbdwpJobsRepository
	 */
	private UbdwpJobsRepository $repository;

	/**
	 * User acting on the jobs.
	 *
	 * @var int
	 */
	private int $user_id;

	/**
	 * Constructor.
	 *
	 * @param int $user_id User acting on the jobs.
	 */
	public function __construct( int $user_id ) {
		$this->user_id    = $user_id;
		$this->repository = new UbdwpJobsRepository( $user_id );
	}

	/**
	 * Number of users deleted per batch.
	 *
	 * @return int Batch size between 1 and 500.
	 */
	public static function get_batch_size(): int {
		/**
		 * Filters how many users are deleted per batch (per request in the browser).
		 *
		 * @param int $batch_size Batch size.
		 */
		return max( 1, min( 500, (int) apply_filters( 'ubdwp_delete_batch_size', 50 ) ) );
	}

	/**
	 * Create a job for the selected users and start summarizing it.
	 *
	 * @param array{ids: array<int>, default: string, reassign: array<int, string>} $selection Resolved selection.
	 * @param string                                                                 $mode      Planned mode.
	 *
	 * @return object|\WP_Error Job.
	 */
	public function create( array $selection, string $mode = self::MODE_BROWSER ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $selection['ids'] ?? array() ) ) ) );

		if ( empty( $ids ) ) {
			return UbdwpValidationFacade::to_wp_error( 'select_any_user' );
		}

		$this->repository->prune();

		$job_id = $this->repository->create( array(
			'user_id'  => $this->user_id,
			'status'   => self::STATUS_SUMMARIZING,
			'mode'     => $mode,
			'total'    => count( $ids ),
			'user_ids' => implode( ',', $ids ),
			'reassign' => wp_json_encode( array(
				'default' => UbdwpSelection::sanitize_reassign( $selection['default'] ?? '' ),
				'map'     => array_map( array( UbdwpSelection::class, 'sanitize_reassign' ), (array) ( $selection['reassign'] ?? array() ) ),
			) ),
			'summary'  => wp_json_encode( $this->empty_summary() ),
		) );

		$job = $job_id ? $this->repository->find( $job_id ) : null;

		if ( ! $job ) {
			return UbdwpValidationFacade::to_wp_error( 'generic_error' );
		}

		/**
		 * Fires after a deletion job was created, before it is summarized and confirmed.
		 *
		 * @since 2.4.0
		 *
		 * @param object $job Job row (ID, user_id, status, mode, total, user_ids, ...).
		 */
		do_action( 'ubdwp_job_created', $job );

		return $job;
	}

	/**
	 * Get a job of the current site.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return object|null Job.
	 */
	public function get( int $job_id ): ?object {
		return $job_id > 0 ? $this->repository->find( $job_id ) : null;
	}

	/**
	 * Get a job created by the acting user.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return object|\WP_Error Job.
	 */
	public function get_owned( int $job_id ) {
		$job = $this->get( $job_id );

		if ( ! $job || (int) $job->user_id !== $this->user_id ) {
			return UbdwpValidationFacade::to_wp_error( 'job_not_found' );
		}

		return $job;
	}

	/**
	 * Summarize the next chunk of a job. The job is "ready" once all users are summarized.
	 *
	 * @param object $job        Job.
	 * @param int    $chunk_size Users per chunk.
	 *
	 * @return object|\WP_Error Updated job.
	 */
	public function summarize( object $job, int $chunk_size = self::SUMMARY_CHUNK ) {
		if ( self::STATUS_SUMMARIZING !== $job->status ) {
			return self::STATUS_READY === $job->status ? $job : UbdwpValidationFacade::to_wp_error( 'job_invalid_state' );
		}

		$ids      = $this->get_job_user_ids( $job );
		$start    = (int) $job->summary_position;
		$chunk    = array_slice( $ids, $start, max( 1, $chunk_size ) );
		$summary  = $this->decode( $job->summary, $this->empty_summary() );
		$handler  = new UbdwpUsersHandler( (int) $job->user_id );
		$partial  = $handler->get_delete_summary( $this->build_entries( $job, $chunk ), array_flip( $ids ) );
		$position = $start + count( $chunk );

		foreach ( array( 'selected', 'deletable', 'reassign_users', 'reassign_posts', 'default_users', 'default_posts', 'remove_users', 'remove_posts', 'remove_comments' ) as $key ) {
			$summary[ $key ] += (int) $partial[ $key ];
		}

		$summary['skipped_count'] += count( $partial['skipped'] );
		$summary['skipped']        = array_slice( array_merge( $summary['skipped'], $partial['skipped'] ), 0, self::MAX_LISTED_USERS );
		$summary['reassign_targets'] = array_slice( array_values( array_unique( array_merge( $summary['reassign_targets'], $partial['reassign_targets'] ) ) ), 0, 20 );

		$fields = array(
			'summary_position' => $position,
		);

		if ( $position >= count( $ids ) ) {
			$summary['confirm_required'] = $summary['deletable'] >= (int) apply_filters( 'ubdwp_confirmation_threshold', 20 );
			$fields['status']            = self::STATUS_READY;
		}

		$fields['summary'] = wp_json_encode( $summary );
		$this->repository->update( (int) $job->ID, $fields );
		UbdwpHelperFacade::flush_runtime_cache();

		return $this->repository->find( (int) $job->ID );
	}

	/**
	 * Start a summarized job.
	 *
	 * @param object $job     Job.
	 * @param string $mode    Browser, background or CLI.
	 * @param string $confirm Number of users typed by the user, required for large deletions.
	 *
	 * @return object|\WP_Error Updated job.
	 */
	public function start( object $job, string $mode, string $confirm = '' ) {
		if ( self::STATUS_READY !== $job->status || ! in_array( $mode, array( self::MODE_BROWSER, self::MODE_BACKGROUND, self::MODE_CLI ), true ) ) {
			return UbdwpValidationFacade::to_wp_error( 'job_invalid_state' );
		}

		$summary = $this->decode( $job->summary, $this->empty_summary() );

		if ( (int) $summary['deletable'] < 1 ) {
			return UbdwpValidationFacade::to_wp_error( 'select_any_user' );
		}

		// The typed confirmation is checked here too, not only in the browser.
		if ( ! empty( $summary['confirm_required'] ) && trim( $confirm ) !== (string) (int) $summary['deletable'] ) {
			return UbdwpValidationFacade::to_wp_error( 'confirm_mismatch' );
		}

		$this->repository->update( (int) $job->ID, array(
			'status' => self::STATUS_RUNNING,
			'mode'   => $mode,
		) );

		if ( self::MODE_BACKGROUND === $mode ) {
			UbdwpBackgroundRunner::schedule( (int) $job->ID );
		}

		return $this->repository->find( (int) $job->ID );
	}

	/**
	 * Delete the next batch of a running job.
	 *
	 * Must run as the user who created the job: permissions are checked for the current user.
	 *
	 * @param object   $job        Job.
	 * @param int|null $batch_size Users per batch, the filtered default when null.
	 *
	 * @return array{job: object, template: string, failed_template: string}|\WP_Error Updated job and rendered rows of this batch.
	 */
	public function process( object $job, ?int $batch_size = null ) {
		$job_id = (int) $job->ID;
		$lock   = $this->repository->acquire_lock( $job_id, self::LOCK_SECONDS );

		if ( null === $lock ) {
			return UbdwpValidationFacade::to_wp_error( 'job_busy' );
		}

		try {
			// Read the job again: another request may have processed a batch before the lock was taken.
			$job = $this->repository->find( $job_id );

			if ( ! $job || self::STATUS_RUNNING !== $job->status ) {
				return $job
					? array( 'job' => $job, 'template' => '', 'failed_template' => '' )
					: UbdwpValidationFacade::to_wp_error( 'job_not_found' );
			}

			$ids      = $this->get_job_user_ids( $job );
			$position = (int) $job->position;
			$batch    = array_slice( $ids, $position, $batch_size ?? self::get_batch_size() );
			$owner_id = (int) $job->user_id;
			$result   = ( new UbdwpUsersHandler( $owner_id ) )->delete_users( $this->build_entries( $job, $batch ), array_flip( $ids ) );
			$deleted  = $result['deleted_users'] ?? array();
			$failed   = array_values( $result['failed_users'] ?? array() );

			if ( ! empty( $deleted ) ) {
				( new UbdwpLogsHandler( $owner_id ) )->insert_log( array(
					'user_delete_count' => count( $deleted ),
					'user_delete_data'  => array_values( $deleted ),
					'job_id'            => $job_id,
				) );
			}

			$position += count( $batch );
			$fields    = array(
				'position'      => $position,
				'deleted_count' => (int) $job->deleted_count + count( $deleted ),
				'failed_count'  => (int) $job->failed_count + count( $failed ),
				'failed_users'  => wp_json_encode( array_slice( array_merge( $this->decode( $job->failed_users, array() ), $failed ), 0, self::MAX_LISTED_USERS ) ),
			);

			if ( $position >= count( $ids ) ) {
				$fields = array_merge( $fields, $this->finished_fields( self::STATUS_DONE ) );
			}

			$this->repository->update( $job_id, $fields );
			UbdwpHelperFacade::flush_runtime_cache();

			$job = $this->repository->find( $job_id );

			if ( self::STATUS_DONE === ( $fields['status'] ?? '' ) ) {
				$this->fire_finished( $job );
			}

			return array(
				'job'             => $job,
				'template'        => (string) ( $result['template'] ?? '' ),
				'failed_template' => (string) ( $result['failed_template'] ?? '' ),
			);
		} finally {
			$this->repository->release_lock( $job_id, $lock );
		}
	}

	/**
	 * Cancel a job that is not finished. Users already deleted stay deleted.
	 *
	 * @param object $job Job.
	 *
	 * @return object|\WP_Error Updated job.
	 */
	public function cancel( object $job ) {
		if ( in_array( $job->status, array( self::STATUS_DONE, self::STATUS_CANCELLED, self::STATUS_FAILED ), true ) ) {
			return UbdwpValidationFacade::to_wp_error( 'job_invalid_state' );
		}

		$this->repository->update( (int) $job->ID, $this->finished_fields( self::STATUS_CANCELLED ) );

		$job = $this->repository->find( (int) $job->ID );
		$this->fire_finished( $job );

		return $job;
	}

	/**
	 * Remove a job that was never started (a dry run, or a summary that was not confirmed).
	 *
	 * @param object $job Job.
	 *
	 * @return bool True when the job was removed.
	 */
	public function discard( object $job ): bool {
		if ( ! in_array( $job->status, array( self::STATUS_SUMMARIZING, self::STATUS_READY ), true ) ) {
			return false;
		}

		$this->repository->delete( (int) $job->ID );

		return true;
	}

	/**
	 * Mark a job as failed.
	 *
	 * @param object $job     Job.
	 * @param string $message Reason.
	 *
	 * @return void
	 */
	public function fail( object $job, string $message ): void {
		$this->repository->update( (int) $job->ID, array_merge( $this->finished_fields( self::STATUS_FAILED ), array( 'message' => $message ) ) );
		$this->fire_finished( $this->repository->find( (int) $job->ID ) );
	}

	/**
	 * Announce that a job has finished.
	 *
	 * @param object|null $job Finished job.
	 *
	 * @return void
	 */
	private function fire_finished( ?object $job ): void {
		if ( ! $job ) {
			return;
		}

		/**
		 * Fires when a deletion job is completed, cancelled or failed.
		 *
		 * @since 2.4.0
		 *
		 * @param array<string, mixed> $status Job status, the same data as the job status of the admin page
		 *                                     (job_id, owner_id, status, mode, total, deleted, failed, summary, failed_users, ...).
		 * @param object               $job    Job row.
		 */
		do_action( 'ubdwp_job_finished', $this->to_status( $job ), $job );
	}

	/**
	 * Latest jobs of the current site.
	 *
	 * @param int $limit Number of jobs.
	 *
	 * @return array<object> Jobs without user lists.
	 */
	public function get_recent( int $limit = 20 ): array {
		return $this->repository->get_recent( $limit );
	}

	/**
	 * Latest running background job of the acting user.
	 *
	 * @return object|null Job.
	 */
	public function get_running_background_job(): ?object {
		return $this->repository->get_running_background_job( $this->user_id );
	}

	/**
	 * Describe a job for the admin page and WP-CLI.
	 *
	 * @param object $job Job.
	 *
	 * @return array<string, mixed> Job status.
	 */
	public function to_status( object $job ): array {
		$total    = (int) $job->total;
		$summary  = isset( $job->summary ) ? $this->decode( $job->summary, $this->empty_summary() ) : $this->empty_summary();
		$position = self::STATUS_SUMMARIZING === $job->status ? (int) $job->summary_position : (int) $job->position;

		return array(
			'job_id'       => (int) $job->ID,
			'owner_id'     => (int) $job->user_id,
			'created_at'   => (string) $job->created_at,
			'status'       => (string) $job->status,
			'status_label' => self::get_status_label( (string) $job->status ),
			'mode'         => (string) $job->mode,
			'total'        => $total,
			'position'     => $position,
			'percent'      => $total > 0 ? (int) floor( min( $position, $total ) * 100 / $total ) : 100,
			'deleted'      => (int) $job->deleted_count,
			'failed'       => (int) $job->failed_count,
			'finished'     => in_array( $job->status, array( self::STATUS_DONE, self::STATUS_CANCELLED, self::STATUS_FAILED ), true ),
			'summary'      => $summary,
			'failed_users' => isset( $job->failed_users ) ? $this->decode( $job->failed_users, array() ) : array(),
			'message'      => (string) $job->message,
			'multisite'    => is_multisite(),
		);
	}

	/**
	 * Translated label of a job status.
	 *
	 * @param string $status Status.
	 *
	 * @return string Label.
	 */
	public static function get_status_label( string $status ): string {
		$labels = array(
			self::STATUS_SUMMARIZING => __( 'Preparing', 'users-bulk-delete-with-preview' ),
			self::STATUS_READY       => __( 'Waiting for confirmation', 'users-bulk-delete-with-preview' ),
			self::STATUS_RUNNING     => __( 'Running', 'users-bulk-delete-with-preview' ),
			self::STATUS_DONE        => __( 'Completed', 'users-bulk-delete-with-preview' ),
			self::STATUS_CANCELLED   => __( 'Cancelled', 'users-bulk-delete-with-preview' ),
			self::STATUS_FAILED      => __( 'Failed', 'users-bulk-delete-with-preview' ),
		);

		return $labels[ $status ] ?? $status;
	}

	/**
	 * User IDs of a job.
	 *
	 * @param object $job Job.
	 *
	 * @return array<int> User IDs.
	 */
	private function get_job_user_ids( object $job ): array {
		return '' === (string) $job->user_ids ? array() : array_map( 'intval', explode( ',', (string) $job->user_ids ) );
	}

	/**
	 * Build deletion entries (ID and reassign value) for users of a job.
	 *
	 * @param object     $job      Job.
	 * @param array<int> $user_ids User IDs.
	 *
	 * @return array<int, array{id: int, reassign: string}> Entries.
	 */
	private function build_entries( object $job, array $user_ids ): array {
		$reassign = $this->decode( $job->reassign, array() );
		$default  = (string) ( $reassign['default'] ?? '' );
		$map      = (array) ( $reassign['map'] ?? array() );

		return array_map( static fn( int $user_id ): array => array(
			'id'       => $user_id,
			'reassign' => (string) ( $map[ $user_id ] ?? $default ),
		), $user_ids );
	}

	/**
	 * Fields of a finished job: the large lists are no longer needed.
	 *
	 * @param string $status Final status.
	 *
	 * @return array<string, string> Fields.
	 */
	private function finished_fields( string $status ): array {
		return array(
			'status'   => $status,
			'user_ids' => '',
			'reassign' => '{}',
		);
	}

	/**
	 * Summary of a job before any user was summarized.
	 *
	 * @return array<string, mixed> Summary.
	 */
	private function empty_summary(): array {
		return array(
			'selected'         => 0,
			'deletable'        => 0,
			'skipped_count'    => 0,
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
			'confirm_required' => false,
		);
	}

	/**
	 * Decode a JSON column.
	 *
	 * @param mixed $json    JSON.
	 * @param array $default Value when the JSON is invalid.
	 *
	 * @return array Decoded value merged over the default.
	 */
	private function decode( $json, array $default ): array {
		$value = is_string( $json ) ? json_decode( $json, true ) : null;

		return is_array( $value ) ? array_merge( $default, $value ) : $default;
	}
}
