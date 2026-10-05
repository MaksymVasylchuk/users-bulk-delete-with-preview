<?php
/**
 * Privacy
 *
 * @package     UsersBulkDeleteWithPreview\Services
 */

namespace UsersBulkDeleteWithPreview\Services;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Repositories\UbdwpJobsRepository;
use UsersBulkDeleteWithPreview\Repositories\UbdwpLogsRepository;

/**
 * Personal data kept by the plugin (the deletion log and deletion jobs) and the tools to limit it:
 * how emails are stored in the log, how long the log is kept, WordPress personal data export and erasure,
 * and the suggested privacy policy text.
 */
class UbdwpPrivacy {
	/**
	 * Option with the privacy settings of a site.
	 */
	public const OPTION = 'ubdwp_privacy';

	/**
	 * Daily cleanup hook.
	 */
	public const CRON_HOOK = 'ubdwp_daily_cleanup';

	/**
	 * Store emails and names in full.
	 */
	public const EMAIL_FULL = 'full';

	/**
	 * Store masked emails and names (j***@example.com).
	 */
	public const EMAIL_MASKED = 'masked';

	/**
	 * Store only user IDs.
	 */
	public const EMAIL_NONE = 'none';

	/**
	 * Records processed per request by the exporter, the eraser and the anonymizer.
	 */
	private const BATCH = 100;

	/**
	 * Register the privacy hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'register_eraser' ) );
		add_action( 'admin_init', array( self::class, 'add_privacy_policy_content' ) );
		add_action( 'admin_init', array( self::class, 'schedule_cleanup' ) );
		add_action( self::CRON_HOOK, array( self::class, 'run_cleanup' ) );
	}

	/**
	 * Privacy settings of the current site.
	 *
	 * @return array{retention_days: int, log_email: string} Settings.
	 */
	public static function get_settings(): array {
		$settings = get_option( self::OPTION, array() );

		return self::sanitize_settings( is_array( $settings ) ? $settings : array() );
	}

	/**
	 * Save the privacy settings of the current site.
	 *
	 * @param array<string, mixed> $settings Raw settings.
	 *
	 * @return array{retention_days: int, log_email: string} Saved settings.
	 */
	public static function save_settings( array $settings ): array {
		// Fields that are not sent keep their saved value.
		$settings = self::sanitize_settings( array_merge( self::get_settings(), $settings ) );
		update_option( self::OPTION, $settings );

		return $settings;
	}

	/**
	 * Normalize settings.
	 *
	 * @param array<string, mixed> $settings Raw settings.
	 *
	 * @return array{retention_days: int, log_email: string} Settings.
	 */
	private static function sanitize_settings( array $settings ): array {
		$mode = isset( $settings['log_email'] ) && is_string( $settings['log_email'] ) ? $settings['log_email'] : self::EMAIL_MASKED;
		$days = $settings['retention_days'] ?? 0;

		return array(
			// 0 keeps the log until it is deleted manually.
			'retention_days' => min( 3650, is_scalar( $days ) ? absint( $days ) : 0 ),
			'log_email'      => in_array( $mode, array( self::EMAIL_FULL, self::EMAIL_MASKED, self::EMAIL_NONE ), true ) ? $mode : self::EMAIL_MASKED,
		);
	}

	/**
	 * Mask an email: keep the first character and the domain (j***@example.com).
	 *
	 * @param string $email Email.
	 *
	 * @return string Masked email.
	 */
	public static function mask_email( string $email ): string {
		$at = strrpos( $email, '@' );

		if ( false === $at ) {
			return self::mask_name( $email );
		}

		$local = substr( $email, 0, $at );

		return ( '' === $local ? '' : mb_substr( $local, 0, 1 ) ) . '***' . substr( $email, $at );
	}

	/**
	 * Mask a name: keep the first character.
	 *
	 * @param string $name Name.
	 *
	 * @return string Masked name.
	 */
	public static function mask_name( string $name ): string {
		return '' === $name ? '' : mb_substr( $name, 0, 1 ) . '***';
	}

	/**
	 * Apply the email setting to the deleted users of a new log record.
	 *
	 * @param array<int, array<string, mixed>> $entries Deleted users.
	 * @param string|null                      $mode    Email mode, the site setting when null.
	 *
	 * @return array<int, array<string, mixed>> Entries to store.
	 */
	public static function prepare_log_entries( array $entries, ?string $mode = null ): array {
		$mode = $mode ?? self::get_settings()['log_email'];

		return array_map( static function ( $entry ) use ( $mode ) {
			if ( ! is_array( $entry ) || self::EMAIL_FULL === $mode ) {
				return $entry;
			}

			if ( self::EMAIL_NONE === $mode ) {
				$entry['email']        = '';
				$entry['display_name'] = '';
			} else {
				$entry['email']        = self::mask_email( (string) ( $entry['email'] ?? '' ) );
				$entry['display_name'] = self::mask_name( (string) ( $entry['display_name'] ?? '' ) );
			}

			return $entry;
		}, $entries );
	}

	/**
	 * Apply the current email setting to log records that are already stored.
	 *
	 * Masking and removal cannot be undone; with "full", nothing changes.
	 *
	 * @return int Number of updated records.
	 */
	public static function anonymize_existing_logs(): int {
		$mode = self::get_settings()['log_email'];

		if ( self::EMAIL_FULL === $mode ) {
			return 0;
		}

		$logs    = new UbdwpLogsRepository( 0 );
		$updated = 0;
		$last_id = 0;

		do {
			$rows = $logs->get_rows_after( $last_id, self::BATCH );

			foreach ( $rows as $row ) {
				$last_id = (int) $row->ID;
				$data    = json_decode( (string) $row->user_deleted_data, true );

				if ( ! is_array( $data ) || empty( $data['user_delete_data'] ) || ! is_array( $data['user_delete_data'] ) ) {
					continue;
				}

				$new = self::prepare_log_entries( $data['user_delete_data'], $mode );

				if ( $new !== $data['user_delete_data'] ) {
					$data['user_delete_data'] = $new;
					$logs->update_row( $last_id, array( 'user_deleted_data' => wp_json_encode( $data ) ) );
					++$updated;
				}
			}
		} while ( count( $rows ) === self::BATCH );

		return $updated;
	}

	/**
	 * Delete log records.
	 *
	 * @param int $older_than_days Delete records older than this many days; 0 deletes all records.
	 *
	 * @return int Number of deleted records.
	 */
	public static function delete_logs( int $older_than_days ): int {
		$logs = new UbdwpLogsRepository( 0 );

		return $older_than_days > 0 ? $logs->delete_older_than( $older_than_days ) : $logs->delete_all();
	}

	/**
	 * Schedule the daily cleanup on the current site.
	 *
	 * @return void
	 */
	public static function schedule_cleanup(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Daily cleanup: delete log records and finished jobs older than the retention period.
	 *
	 * @return void
	 */
	public static function run_cleanup(): void {
		$days = self::get_settings()['retention_days'];

		if ( $days > 0 ) {
			self::delete_logs( $days );
			// Finished jobs also list skipped users, so they are not kept longer than the log.
			( new UbdwpJobsRepository( 0 ) )->delete_finished_older_than( $days );
		}

		( new UbdwpJobsRepository( 0 ) )->prune();
	}

	/**
	 * Register the personal data exporter.
	 *
	 * @param array<string, array<string, mixed>> $exporters Exporters.
	 *
	 * @return array<string, array<string, mixed>> Exporters.
	 */
	public static function register_exporter( array $exporters ): array {
		$exporters['users-bulk-delete-with-preview'] = array(
			'exporter_friendly_name' => __( 'Users Bulk Delete With Preview log', 'users-bulk-delete-with-preview' ),
			'callback'               => array( self::class, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Register the personal data eraser.
	 *
	 * @param array<string, array<string, mixed>> $erasers Erasers.
	 *
	 * @return array<string, array<string, mixed>> Erasers.
	 */
	public static function register_eraser( array $erasers ): array {
		$erasers['users-bulk-delete-with-preview'] = array(
			'eraser_friendly_name' => __( 'Users Bulk Delete With Preview log', 'users-bulk-delete-with-preview' ),
			'callback'             => array( self::class, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Export log records about a person: the deletion of their account and the deletions they performed.
	 *
	 * @param string $email Email of the person.
	 * @param int    $page  Page, starting at 1.
	 *
	 * @return array{data: array<int, array<string, mixed>>, done: bool} Export data.
	 */
	public static function export_personal_data( string $email, int $page = 1 ): array {
		$user  = get_user_by( 'email', $email );
		$rows  = ( new UbdwpLogsRepository( 0 ) )->get_rows_about_person( self::json_fragment( $email ), $user ? (int) $user->ID : 0, self::BATCH, max( 0, $page - 1 ) * self::BATCH );
		$items = array();

		foreach ( $rows as $row ) {
			$data = json_decode( (string) $row->user_deleted_data, true );

			foreach ( is_array( $data['user_delete_data'] ?? null ) ? $data['user_delete_data'] : array() as $entry ) {
				if ( is_array( $entry ) && strtolower( (string) ( $entry['email'] ?? '' ) ) === strtolower( $email ) ) {
					$items[] = array(
						'group_id'    => 'ubdwp-log',
						'group_label' => __( 'Bulk user deletion log', 'users-bulk-delete-with-preview' ),
						'item_id'     => 'ubdwp-log-' . $row->ID . '-' . absint( $entry['user_id'] ?? 0 ),
						'data'        => array(
							array( 'name' => __( 'Event', 'users-bulk-delete-with-preview' ), 'value' => __( 'Your account was deleted', 'users-bulk-delete-with-preview' ) ),
							array( 'name' => __( 'Date', 'users-bulk-delete-with-preview' ), 'value' => (string) $row->deletion_time ),
							array( 'name' => __( 'Email', 'users-bulk-delete-with-preview' ), 'value' => (string) $entry['email'] ),
							array( 'name' => __( 'Display name', 'users-bulk-delete-with-preview' ), 'value' => (string) ( $entry['display_name'] ?? '' ) ),
						),
					);
				}
			}

			if ( $user && (int) $row->user_id === (int) $user->ID ) {
				$items[] = array(
					'group_id'    => 'ubdwp-log',
					'group_label' => __( 'Bulk user deletion log', 'users-bulk-delete-with-preview' ),
					'item_id'     => 'ubdwp-log-' . $row->ID,
					'data'        => array(
						array( 'name' => __( 'Event', 'users-bulk-delete-with-preview' ), 'value' => __( 'You deleted users', 'users-bulk-delete-with-preview' ) ),
						array( 'name' => __( 'Date', 'users-bulk-delete-with-preview' ), 'value' => (string) $row->deletion_time ),
						array( 'name' => __( 'Deleted users count', 'users-bulk-delete-with-preview' ), 'value' => (string) absint( $data['user_delete_count'] ?? 0 ) ),
					),
				);
			}
		}

		return array(
			'data' => $items,
			'done' => count( $rows ) < self::BATCH,
		);
	}

	/**
	 * Erase personal data of a person from the log and from deletion jobs.
	 *
	 * Their email and name are removed from records about the deletion of their account,
	 * and they are removed as the performer of deletions. The records stay as anonymous history.
	 *
	 * @param string $email Email of the person.
	 * @param int    $page  Page, starting at 1.
	 *
	 * @return array{items_removed: bool, items_retained: bool, messages: array<int, string>, done: bool} Result.
	 */
	public static function erase_personal_data( string $email, int $page = 1 ): array {
		$user    = get_user_by( 'email', $email );
		$user_id = $user ? (int) $user->ID : 0;
		$needle  = self::json_fragment( $email );
		$logs    = new UbdwpLogsRepository( 0 );
		// Erased records no longer match, so every request reads the first batch again.
		$rows    = $logs->get_rows_about_person( $needle, $user_id, self::BATCH, 0 );
		$removed = false;

		foreach ( $rows as $row ) {
			$data   = json_decode( (string) $row->user_deleted_data, true );
			$fields = array();

			if ( is_array( $data ) && is_array( $data['user_delete_data'] ?? null ) ) {
				$data['user_delete_data'] = self::erase_from_entries( $data['user_delete_data'], $email );
				$fields['user_deleted_data'] = wp_json_encode( $data );
			}

			if ( $user_id && (int) $row->user_id === $user_id ) {
				$fields['user_id'] = 0;
			}

			if ( ! empty( $fields ) ) {
				$logs->update_row( (int) $row->ID, $fields );
				$removed = true;
			}
		}

		if ( 1 === $page ) {
			$jobs = new UbdwpJobsRepository( 0 );

			foreach ( $jobs->find_mentioning( '"' . $needle . '"' ) as $job ) {
				$summary = json_decode( (string) $job->summary, true );
				$failed  = json_decode( (string) $job->failed_users, true );

				if ( is_array( $summary ) && is_array( $summary['skipped'] ?? null ) ) {
					$summary['skipped'] = self::erase_from_entries( $summary['skipped'], $email );
				}

				$jobs->update( (int) $job->ID, array(
					'summary'      => wp_json_encode( is_array( $summary ) ? $summary : array() ),
					'failed_users' => wp_json_encode( is_array( $failed ) ? self::erase_from_entries( $failed, $email ) : array() ),
				) );
				$removed = true;
			}

			if ( $user_id && $jobs->forget_owner( $user_id ) > 0 ) {
				$removed = true;
			}
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => count( $rows ) < self::BATCH,
		);
	}

	/**
	 * Add the suggested privacy policy text.
	 *
	 * @return void
	 */
	public static function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p class="privacy-policy-tutorial">' . esc_html__( 'Users Bulk Delete With Preview keeps a log of bulk user deletions made by administrators. You can change what is stored and for how long on the plugin\'s Settings page.', 'users-bulk-delete-with-preview' ) . '</p>'
			. '<strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'users-bulk-delete-with-preview' ) . ' </strong>'
			. '<p>' . esc_html__( 'When administrators delete user accounts in bulk, we keep a log of the deletion for security and audit purposes. The log contains the ID of the deleted account, the date of the deletion and, depending on our settings, the email address and display name, which can be stored in full, masked or not at all. The log also records which administrator performed the deletion. Log entries are kept until they are deleted by an administrator or, if a retention period is set, until that period ends. You can request an export or erasure of this data.', 'users-bulk-delete-with-preview' ) . '</p>';

		wp_add_privacy_policy_content( __( 'Users Bulk Delete With Preview', 'users-bulk-delete-with-preview' ), wp_kses_post( $content ) );
	}

	/**
	 * Remove the email and name of a person from deletion entries.
	 *
	 * @param array<int, mixed> $entries Entries with email, display_name and login.
	 * @param string            $email   Email of the person.
	 *
	 * @return array<int, mixed> Entries.
	 */
	private static function erase_from_entries( array $entries, string $email ): array {
		return array_map( static function ( $entry ) use ( $email ) {
			if ( is_array( $entry ) && strtolower( (string) ( $entry['email'] ?? '' ) ) === strtolower( $email ) ) {
				$entry['email']        = '';
				$entry['display_name'] = '';
				$entry['login']        = '';
				$entry['anonymized']   = true;
			}

			return $entry;
		}, $entries );
	}

	/**
	 * An email as it appears inside the stored JSON (wp_json_encode escapes some characters).
	 *
	 * @param string $email Email.
	 *
	 * @return string JSON-encoded email without the quotes.
	 */
	private static function json_fragment( string $email ): string {
		return substr( (string) wp_json_encode( $email ), 1, -1 );
	}
}
