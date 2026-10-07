<?php
/**
 * Jobs Repository
 *
 * @package     UsersBulkDeleteWithPreview\Repositories
 */

namespace UsersBulkDeleteWithPreview\Repositories;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractBaseRepository;

/**
 * Stores deletion jobs of the current site.
 */
class UbdwpJobsRepository extends UbdwpAbstractBaseRepository {
	/**
	 * Columns returned in job lists (without the large ID and reassign lists).
	 */
	private const LIST_COLUMNS = 'ID, user_id, status, mode, total, summary_position, position, deleted_count, failed_count, message, created_at, updated_at';

	/**
	 * Constructor.
	 *
	 * @param int $current_user_id Current user ID.
	 */
	public function __construct( int $current_user_id ) {
		parent::__construct( 'ubdwp_jobs', $current_user_id );
	}

	/**
	 * Create a job.
	 *
	 * @param array<string, mixed> $fields Column values.
	 *
	 * @return int Job ID, 0 on failure.
	 */
	public function create( array $fields ): int {
		$now = gmdate( 'Y-m-d H:i:s' );

		$this->insert( array_merge(
			array(
				'status'       => 'draft',
				'mode'         => 'browser',
				'user_ids'     => '',
				'reassign'     => '{}',
				'summary'      => '{}',
				'failed_users' => '[]',
				'message'      => '',
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			$fields
		) );

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Get a job with all columns.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return object|null Job row.
	 */
	public function find( int $job_id ): ?object {
		$rows = $this->select( "SELECT * FROM {$this->table_name} WHERE ID = %d", array( $job_id ) );

		return $rows[0] ?? null;
	}

	/**
	 * Update a job.
	 *
	 * @param int                  $job_id Job ID.
	 * @param array<string, mixed> $fields Column values.
	 *
	 * @return bool True on success.
	 */
	public function update( int $job_id, array $fields ): bool {
		$fields['updated_at'] = gmdate( 'Y-m-d H:i:s' );

		return false !== $this->wpdb->update( $this->table_name, $fields, array( 'ID' => $job_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
	}

	/**
	 * Lock a job for one processing request.
	 *
	 * The update only succeeds when no other request holds a valid lock, so two requests
	 * (for example a background run and the browser) never process the same batch.
	 *
	 * @param int $job_id  Job ID.
	 * @param int $seconds Lock lifetime.
	 *
	 * @return string|null Lock token, or null when the job is locked by another request.
	 */
	public function acquire_lock( int $job_id, int $seconds ): ?string {
		$token = wp_generate_password( 32, false, false );
		$now   = gmdate( 'Y-m-d H:i:s' );

		$updated = $this->execute(
			"UPDATE {$this->table_name} SET lock_token = %s, lock_until = %s WHERE ID = %d AND ( lock_until IS NULL OR lock_until < %s )",
			array( $token, gmdate( 'Y-m-d H:i:s', time() + $seconds ), $job_id, $now )
		);

		return 1 === $updated ? $token : null;
	}

	/**
	 * Release a lock held by the given token.
	 *
	 * @param int    $job_id Job ID.
	 * @param string $token  Lock token.
	 *
	 * @return void
	 */
	public function release_lock( int $job_id, string $token ): void {
		$this->execute(
			"UPDATE {$this->table_name} SET lock_token = '', lock_until = NULL WHERE ID = %d AND lock_token = %s",
			array( $job_id, $token )
		);
	}

	/**
	 * Delete a job.
	 *
	 * @param int $job_id Job ID.
	 *
	 * @return void
	 */
	public function delete( int $job_id ): void {
		$this->wpdb->delete( $this->table_name, array( 'ID' => $job_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
	}

	/**
	 * Get the latest jobs of the site, without their user lists.
	 *
	 * @param int $limit  Number of jobs.
	 * @param int $offset Number of newer jobs to skip.
	 *
	 * @return array<object> Jobs.
	 */
	public function get_recent( int $limit, int $offset = 0 ): array {
		return $this->select( 'SELECT ' . self::LIST_COLUMNS . " FROM {$this->table_name} ORDER BY ID DESC LIMIT %d OFFSET %d", array( $limit, max( 0, $offset ) ) );
	}

	/**
	 * Count all jobs of the current site.
	 *
	 * @return int Number of jobs.
	 */
	public function count_all(): int {
		return $this->count();
	}

	/**
	 * Get the latest unfinished background job started by a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return object|null Job row without the user list.
	 */
	public function get_running_background_job( int $user_id ): ?object {
		$rows = $this->select(
			'SELECT ' . self::LIST_COLUMNS . " FROM {$this->table_name} WHERE user_id = %d AND mode = %s AND status = %s ORDER BY ID DESC LIMIT 1",
			array( $user_id, 'background', 'running' )
		);

		return $rows[0] ?? null;
	}

	/**
	 * Delete old jobs: finished jobs after 30 days, jobs that were never started after one day.
	 *
	 * @return void
	 */
	public function prune(): void {
		$this->execute(
			"DELETE FROM {$this->table_name} WHERE ( status IN ('done', 'cancelled', 'failed') AND updated_at < %s ) OR ( status IN ('draft', 'summarizing', 'ready') AND updated_at < %s )",
			array( gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) )
		);
	}

	/**
	 * Get jobs whose stored user lists mention a text (for the personal data eraser).
	 *
	 * @param string $needle Text as it is encoded in the stored JSON.
	 *
	 * @return array<object> Jobs with ID, user_id, status, summary and failed_users.
	 */
	public function find_mentioning( string $needle ): array {
		$like = '%' . $this->wpdb->esc_like( $needle ) . '%';

		return $this->select(
			"SELECT ID, user_id, status, summary, failed_users FROM {$this->table_name} WHERE summary LIKE %s OR failed_users LIKE %s",
			array( $like, $like )
		);
	}

	/**
	 * Remove the owner of finished jobs started by a user (for the personal data eraser).
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int Number of updated jobs.
	 */
	public function forget_owner( int $user_id ): int {
		return $this->execute(
			"UPDATE {$this->table_name} SET user_id = 0 WHERE user_id = %d AND status IN ('done', 'cancelled', 'failed')",
			array( $user_id )
		);
	}

	/**
	 * Delete finished jobs older than a number of days.
	 *
	 * @param int $days Age in days.
	 *
	 * @return int Number of deleted jobs.
	 */
	public function delete_finished_older_than( int $days ): int {
		return $this->execute(
			"DELETE FROM {$this->table_name} WHERE status IN ('done', 'cancelled', 'failed') AND updated_at < %s",
			array( gmdate( 'Y-m-d H:i:s', time() - max( 0, $days ) * DAY_IN_SECONDS ) )
		);
	}
}
