<?php
/**
 * Main class loader
 *
 * @package     UsersBulkDeleteWithPreview\Core
 */

namespace UsersBulkDeleteWithPreview\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Traits\UbdwpTraitSingleton;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;

/**
 * Main loader class for the plugin.
 * Handles initialization, hooks, and page rendering.
 */
class UbdwpLoader {
	use UbdwpTraitSingleton;

	/**
	 * List of initialized page objects.
	 *
	 * @var array<string, object>
	 */
	private array $pages = array();

	/**
	 * List of page classes to initialize.
	 *
	 * @var array<string, string>
	 */
	private array $pages_for_init = array(
		'users' => 'UsersBulkDeleteWithPreview\Pages\UbdwpUsersPage',
		'logs'  => 'UsersBulkDeleteWithPreview\Pages\UbdwpLogsPage',
	);

	/**
	 * Initialize the plugin by defining constants, setting up hooks, and loading pages.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->define_constants();

		// Load plugin translations.
		add_action( 'init', array( $this, 'load_text_domain' ) );

		// Register admin menu and enqueue assets.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles' ) );

		// Add action links to the plugin list.
		add_filter( 'plugin_action_links_' . WPUBDP_BASE_NAME, array( $this, 'action_links' ) );

		// Initialize page objects.
		$this->init_pages();
	}

	/**
	 * Add admin menu and submenu pages.
	 *
	 * @return void
	 */
	public function admin_menu(): void {
		add_menu_page(
			__( 'Bulk Users Delete', 'users-bulk-delete-with-preview' ),
			__( 'Bulk Users Delete', 'users-bulk-delete-with-preview' ),
			'manage_options',
			'ubdwp_admin',
			array( $this->pages['users'], 'render' ),
			UbdwpHelperFacade::get_icon()
		);

		add_submenu_page(
			'ubdwp_admin',
			__( 'Bulk Users Delete Logs', 'users-bulk-delete-with-preview' ),
			__( 'Bulk Users Delete Logs', 'users-bulk-delete-with-preview' ),
			'manage_options',
			'ubdwp_admin_logs',
			array( $this->pages['logs'], 'render' )
		);
	}

	/**
	 * Enqueue admin styles for plugin pages.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	public function register_admin_styles( string $hook_suffix ): void {
		$this->register_common_admin_styles( $hook_suffix );
	}

	/**
	 * Enqueue admin scripts for plugin pages.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	public function register_admin_scripts( string $hook_suffix ): void {
		$this->register_common_admin_scripts( $hook_suffix );

		foreach ( $this->pages as $page ) {
			$page->register_admin_scripts( $hook_suffix );
		}
	}

	/**
	 * Add action links to the plugin list in the admin area.
	 *
	 * @param array<string> $links Existing action links.
	 *
	 * @return array<string> Modified action links.
	 */
	public function action_links( array $links ): array {
		$settings_link = '<a href="admin.php?page=ubdwp_admin">' . __( 'Delete users', 'users-bulk-delete-with-preview' ) . '</a>';
		$logs_link     = '<a href="admin.php?page=ubdwp_admin_logs">' . __( 'Logs', 'users-bulk-delete-with-preview' ) . '</a>';
		array_unshift( $links, $settings_link, $logs_link );

		return $links;
	}

	/**
	 * Load text domain for translations.
	 *
	 * @return void
	 */
	public function load_text_domain(): void {
		unload_textdomain( 'users-bulk-delete-with-preview' );
		load_plugin_textdomain( 'users-bulk-delete-with-preview', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Enqueue common admin styles based on the current page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	protected function register_common_admin_styles( string $hook_suffix ): void {
		if ( UbdwpHelperFacade::is_plugin_page( $hook_suffix ) ) {
			UbdwpHelperFacade::register_common_styles( array(
				'wpubdp-bootstrap-css' => array( 'path' => 'assets/bootstrap/bootstrap.min.css' ),
				'wpubdp-select2-css' => array( 'path' => 'assets/select2/select2.min.css' ),
				'wpubdp-jquery-ui-css' => array( 'path' => 'assets/jquery-ui/jquery-ui.css' ),
				'wpubdp-jquery-ui-timepicker-addon-css' => array(
					'path' => 'assets/jquery-ui-datepicker/jquery-ui-timepicker-addon.min.css',
					'deps' => array( 'wpubdp-jquery-ui-css' )
				),
				'wpubdp-dataTables-css' => array( 'path' => 'assets/dataTables/datatables.min.css' ),
				'wpubdp-admin-css' => array( 'path' => 'assets/admin/admin.min.css' ),
			) );
		}
	}

	/**
	 * Enqueue common admin scripts based on the current page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 *
	 * @return void
	 */
	protected function register_common_admin_scripts( string $hook_suffix ): void {
		if ( UbdwpHelperFacade::is_plugin_page( $hook_suffix ) ) {
			UbdwpHelperFacade::register_common_scripts( array(
				'wpubdp-dataTables-js' => array(
					'path' => 'assets/dataTables/datatables.min.js',
					'deps' => array( 'jquery' )
				),
			) );
		}
	}

	/**
	 * Initialize page objects from the list of page classes.
	 *
	 * @return void
	 */
	private function init_pages(): void {
		if ( ! empty( $this->pages_for_init ) && empty( $this->pages ) ) {
			foreach ( $this->pages_for_init as $page => $page_class ) {
				$this->pages[ $page ] = new $page_class();
			}
		}
	}

	/**
	 * Define plugin constants.
	 *
	 * @return void
	 */
	private function define_constants(): void {
		// Root directory of the plugin.
		$plugin_root = dirname( __DIR__, 2 );

		// Define constants if not already defined.
		defined( 'WPUBDP_PLUGIN_DIR' ) || define( 'WPUBDP_PLUGIN_DIR', $plugin_root . '/' );
		defined( 'WPUBDP_PLUGIN_FILE' ) || define( 'WPUBDP_PLUGIN_FILE', $plugin_root . '/ubdwp-users-bulk-delete-with-preview.php' );
		defined( 'WPUBDP_PLUGIN_URL' ) || define( 'WPUBDP_PLUGIN_URL', plugin_dir_url( WPUBDP_PLUGIN_FILE ) );
		defined( 'WPUBDP_PLUGIN_VERSION' ) || define( 'WPUBDP_PLUGIN_VERSION', '2.1.0' );
		defined( 'WPUBDP_BASE_NAME' ) || define( 'WPUBDP_BASE_NAME', plugin_basename( WPUBDP_PLUGIN_FILE ) );
	}
}