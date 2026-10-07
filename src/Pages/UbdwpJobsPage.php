<?php
/**
 * Deletion Jobs Page
 *
 * @package     UsersBulkDeleteWithPreview\Pages
 */

namespace UsersBulkDeleteWithPreview\Pages;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractBasePage;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Services\UbdwpDeletionJobs;

/**
 * Lists the deletion jobs of the site, with their progress and a Cancel button.
 *
 * Cancelling uses the "ubdwp_job_cancel" AJAX action of the Users page.
 */
class UbdwpJobsPage extends UbdwpAbstractBasePage {
	/**
	 * Admin page slug.
	 */
	public const SLUG = 'ubdwp_admin_jobs';

	/**
	 * Jobs shown on one page of the list.
	 */
	public const PER_PAGE = 20;

	/**
	 * Render the deletion jobs page.
	 *
	 * @return void
	 */
	public function render(): void {
		$jobs  = new UbdwpDeletionJobs( get_current_user_id() );
		$total = $jobs->count();
		$pages = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$page  = min( $pages, max( 1, (int) wp_unslash( $_GET['paged'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Page number of a read-only list, cast to integer.

		$this->render_template( 'jobs-page.php', array(
			'title'      => __( 'Deletion Jobs', 'users-bulk-delete-with-preview' ),
			'jobs'       => array_map( array( $jobs, 'to_status' ), $jobs->get_recent( self::PER_PAGE, ( $page - 1 ) * self::PER_PAGE ) ),
			'can_cancel' => current_user_can( is_multisite() ? self::REMOVE_USERS_CAP : self::DELETE_USERS_CAP ),
			'total'      => $total,
			'pagination' => paginate_links( array(
				'base'      => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=' . self::SLUG ) ),
				'format'    => '',
				'current'   => $page,
				'total'     => $pages,
				'prev_text' => '&lsaquo;',
				'next_text' => '&rsaquo;',
			) ),
		) );
	}

	/**
	 * Register admin scripts for the deletion jobs page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	public function register_admin_scripts( string $hook_suffix ): void {
		if ( isset( $_GET['page'] ) && self::SLUG === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not required here.
			UbdwpHelperFacade::register_build_script( 'wpubdp-jobs-js', 'jobs' );

			UbdwpHelperFacade::localize_scripts( 'wpubdp-jobs-js', array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'jobsNonce' => wp_create_nonce( 'ubdwp_delete_users' ),
			) );

			wp_set_script_translations( 'wpubdp-jobs-js', 'users-bulk-delete-with-preview', WPUBDP_PLUGIN_DIR . 'languages' );
		}
	}
}
