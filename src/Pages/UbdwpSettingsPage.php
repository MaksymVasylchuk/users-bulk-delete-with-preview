<?php
/**
 * Settings Page
 *
 * @package     UsersBulkDeleteWithPreview\Pages
 */

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractBasePage;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Services\UbdwpPrivacy;

/**
 * Plugin settings: privacy of the deletion log and log maintenance.
 */
class UbdwpSettingsPage extends UbdwpAbstractBasePage {
	/**
	 * Admin page slug.
	 */
	public const SLUG = 'ubdwp_admin_settings';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_ajax_call( 'ubdwp_privacy_save', array( $this, 'save_privacy_settings_action' ) );
		$this->register_ajax_call( 'ubdwp_logs_anonymize', array( $this, 'anonymize_logs_action' ) );
		$this->register_ajax_call( 'ubdwp_logs_purge', array( $this, 'purge_logs_action' ) );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render(): void {
		$tabs    = $this->get_tabs();
		$current = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only selects the tab that is shown.

		$this->render_template( 'settings-page.php', array(
			'title'           => __( 'Settings', 'users-bulk-delete-with-preview' ),
			'tabs'            => $tabs,
			'current_tab'     => isset( $tabs[ $current ] ) ? $current : 'general',
			'page_url'        => admin_url( 'admin.php?page=' . self::SLUG ),
			'privacy'         => UbdwpPrivacy::get_settings(),
			'can_maintain'    => current_user_can( is_multisite() ? self::REMOVE_USERS_CAP : self::DELETE_USERS_CAP ),
			'logs_url'        => admin_url( 'admin.php?page=ubdwp_admin_logs' ),
		) );
	}

	/**
	 * Tabs of the settings page: "General" of the plugin, then the tabs of other plugins.
	 *
	 * @return array<string, string> Tab labels keyed by tab.
	 */
	private function get_tabs(): array {
		$tabs = array( 'general' => __( 'General', 'users-bulk-delete-with-preview' ) );

		/**
		 * Filters the tabs of the Settings page. Print the content of a tab with the "ubdwp_settings_tab" action.
		 *
		 * @since 2.5.0
		 *
		 * @param array<string, string> $tabs Tab labels keyed by tab ("general" is the plugin's own tab).
		 */
		$filtered = apply_filters( 'ubdwp_settings_tabs', $tabs );

		if ( is_array( $filtered ) ) {
			foreach ( $filtered as $tab => $label ) {
				$tab = sanitize_key( (string) $tab );

				if ( '' !== $tab && ! isset( $tabs[ $tab ] ) && is_string( $label ) ) {
					$tabs[ $tab ] = $label;
				}
			}
		}

		return $tabs;
	}

	/**
	 * Register admin scripts for the settings page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	public function register_admin_scripts( string $hook_suffix ): void {
		if ( isset( $_GET['page'] ) && self::SLUG === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not required here.
			UbdwpHelperFacade::register_build_script( 'wpubdp-settings-js', 'settings' );

			UbdwpHelperFacade::localize_scripts( 'wpubdp-settings-js', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			) );

			wp_set_script_translations( 'wpubdp-settings-js', 'users-bulk-delete-with-preview', WPUBDP_PLUGIN_DIR . 'languages' );
		}
	}

	/**
	 * Handle AJAX request that saves the privacy settings of the log.
	 *
	 * @return void
	 */
	public function save_privacy_settings_action(): void {
		$this->handle_ajax_request( 'privacy_nonce', 'ubdwp_privacy', array( self::MANAGE_OPTIONS_CAP, self::LIST_USERS_CAP ), function () {
			$days = trim( sanitize_text_field( wp_unslash( $_POST['retention_days'] ?? '0' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.

			// absint() would turn -5 into 5 and start deleting the log after 5 days.
			if ( '' === $days || ! ctype_digit( $days ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a number of days.', 'users-bulk-delete-with-preview' ) ) );
			}

			return UbdwpPrivacy::save_settings( array(
				'retention_days' => (int) $days,
				'log_email'      => sanitize_key( wp_unslash( $_POST['log_email'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			) );
		} );
	}

	/**
	 * Handle AJAX request that applies the email setting to existing log records.
	 *
	 * @return void
	 */
	public function anonymize_logs_action(): void {
		$this->handle_ajax_request( 'privacy_nonce', 'ubdwp_privacy', $this->get_log_maintenance_capabilities(), function () {
			return array( 'updated' => UbdwpPrivacy::anonymize_existing_logs() );
		} );
	}

	/**
	 * Handle AJAX request that deletes log records older than a number of days, or all of them.
	 *
	 * Deleting the whole log requires "all" as confirmation, so a missing number never empties the log.
	 *
	 * @return void
	 */
	public function purge_logs_action(): void {
		$this->handle_ajax_request( 'privacy_nonce', 'ubdwp_privacy', $this->get_log_maintenance_capabilities(), function () {
			$raw  = trim( sanitize_text_field( wp_unslash( $_POST['older_than_days'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.
			$days = ctype_digit( $raw ) ? (int) $raw : 0;
			$all  = 'all' === sanitize_key( wp_unslash( $_POST['confirm'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in "handle_ajax_request" method.

			if ( $days < 1 && ! $all ) {
				wp_send_json_error( array( 'message' => __( 'Please enter a number of days.', 'users-bulk-delete-with-preview' ) ) );
				wp_die();
			}

			return array( 'deleted' => UbdwpPrivacy::delete_logs( $all ? 0 : $days ) );
		} );
	}

	/**
	 * Capabilities required to change or delete log records.
	 *
	 * @return array<string> Capabilities.
	 */
	private function get_log_maintenance_capabilities(): array {
		return array(
			self::MANAGE_OPTIONS_CAP,
			self::LIST_USERS_CAP,
			is_multisite() ? self::REMOVE_USERS_CAP : self::DELETE_USERS_CAP,
		);
	}
}
