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
use UsersBulkDeleteWithPreview\Activators\UbdwpActivate;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;
use UsersBulkDeleteWithPreview\Services\UbdwpBackgroundRunner;

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
		'users'    => 'UsersBulkDeleteWithPreview\Pages\UbdwpUsersPage',
		'jobs'     => 'UsersBulkDeleteWithPreview\Pages\UbdwpJobsPage',
		'logs'     => 'UsersBulkDeleteWithPreview\Pages\UbdwpLogsPage',
		'settings' => 'UsersBulkDeleteWithPreview\Pages\UbdwpSettingsPage',
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
		add_action( 'admin_init', array( UbdwpActivate::class, 'ubdwp_maybe_upgrade_current_site' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles' ) );
		add_action( 'in_admin_header', array( $this, 'remove_foreign_notices' ), PHP_INT_MAX );

		// Add action links to the plugin list.
		add_filter( 'plugin_action_links_' . WPUBDP_BASE_NAME, array( $this, 'action_links' ) );

		if ( is_multisite() ) {
			add_action( 'wp_initialize_site', array( UbdwpActivate::class, 'ubdwp_initialize_new_site' ) );
			add_filter( 'wpmu_drop_tables', array( UbdwpActivate::class, 'ubdwp_drop_site_tables' ), 10, 2 );
		}

		// Background deletion jobs run outside of admin requests (WP-Cron or Action Scheduler).
		UbdwpBackgroundRunner::register();

		// Log privacy: retention cleanup, personal data export and erasure, privacy policy text.
		\UsersBulkDeleteWithPreview\Services\UbdwpPrivacy::register();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'ubdwp', \UsersBulkDeleteWithPreview\Cli\UbdwpCliCommand::class );
		}

		// Initialize page objects.
		$this->init_pages();
	}

	/**
	 * Keep the pages of the plugin free of the notices of other plugins and of WordPress: long lists of banners push
	 * the form down. The notices stay on every other page of the admin area.
	 *
	 * @return void
	 */
	public function remove_foreign_notices(): void {
		global $hook_suffix, $wp_filter;

		if ( ! is_string( $hook_suffix ) || ! UbdwpHelperFacade::is_plugin_page( $hook_suffix ) ) {
			return;
		}

		foreach ( array( 'admin_notices', 'all_admin_notices', 'network_admin_notices', 'user_admin_notices' ) as $hook ) {
			if ( ! isset( $wp_filter[ $hook ] ) ) {
				continue;
			}

			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					if ( ! $this->is_own_notice( $callback['function'] ) ) {
						remove_action( $hook, $callback['function'], $priority );
					}
				}
			}
		}
	}

	/**
	 * Whether a notice callback belongs to this plugin or an add-on of it.
	 *
	 * @param mixed $callback Callback.
	 *
	 * @return bool True when it does.
	 */
	private function is_own_notice( $callback ): bool {
		$own = false;

		if ( $callback instanceof \Closure ) {
			$own = str_contains( wp_normalize_path( (string) ( new \ReflectionFunction( $callback ) )->getFileName() ), '/users-bulk-delete-with-preview' );
		} else {
			$target = is_array( $callback ) ? $callback[0] : $callback;
			$class  = is_object( $target ) ? get_class( $target ) : (string) $target;
			$own    = str_starts_with( $class, 'UsersBulkDeleteWithPreview' );
		}

		/**
		 * Filters whether a notice stays on the pages of the plugin, where notices of other plugins are removed.
		 *
		 * @since 2.5.0
		 *
		 * @param bool  $own      Whether the notice belongs to the plugin or an add-on of it.
		 * @param mixed $callback Callback of the notice.
		 */
		return (bool) apply_filters( 'ubdwp_keep_admin_notice', $own, $callback );
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
			__( 'Deletion Jobs', 'users-bulk-delete-with-preview' ),
			__( 'Deletion Jobs', 'users-bulk-delete-with-preview' ),
			'manage_options',
			'ubdwp_admin_jobs',
			array( $this->pages['jobs'], 'render' )
		);

		add_submenu_page(
			'ubdwp_admin',
			__( 'Bulk Users Delete Logs', 'users-bulk-delete-with-preview' ),
			__( 'Logs', 'users-bulk-delete-with-preview' ),
			'manage_options',
			'ubdwp_admin_logs',
			array( $this->pages['logs'], 'render' )
		);

		add_submenu_page(
			'ubdwp_admin',
			__( 'Bulk Users Delete Settings', 'users-bulk-delete-with-preview' ),
			__( 'Settings', 'users-bulk-delete-with-preview' ),
			'manage_options',
			'ubdwp_admin_settings',
			array( $this->pages['settings'], 'render' )
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
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=ubdwp_admin' ) ) . '">' . esc_html__( 'Delete users', 'users-bulk-delete-with-preview' ) . '</a>';
		$logs_link     = '<a href="' . esc_url( admin_url( 'admin.php?page=ubdwp_admin_logs' ) ) . '">' . esc_html__( 'Logs', 'users-bulk-delete-with-preview' ) . '</a>';
		$options_link  = '<a href="' . esc_url( admin_url( 'admin.php?page=ubdwp_admin_settings' ) ) . '">' . esc_html__( 'Settings', 'users-bulk-delete-with-preview' ) . '</a>';
		array_unshift( $links, $settings_link, $logs_link, $options_link );

		return $links;
	}

	/**
	 * Load text domain for translations.
	 *
	 * @return void
	 */
	public function load_text_domain(): void {
		$domain = 'users-bulk-delete-with-preview';
		$locale = apply_filters( 'plugin_locale', determine_locale(), $domain ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.

		unload_textdomain( $domain );

		// The translations bundled in /languages first: they always match this version. The language pack from
		// translate.wordpress.org adds the languages and strings the plugin does not bundle.
		load_textdomain( $domain, WPUBDP_PLUGIN_DIR . "languages/{$domain}-{$locale}.mo", $locale );
		load_textdomain( $domain, WP_LANG_DIR . "/plugins/{$domain}-{$locale}.mo", $locale );
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
					'wpubdp-select2-css'    => array( 'path' => 'assets/select2/select2.min.css' ),
					'wpubdp-dataTables-css' => array( 'path' => 'assets/dataTables/datatables.min.css' ),
			) );

			// Plugin styles come last, so they can adjust the library styles.
			UbdwpHelperFacade::register_build_style( 'wpubdp-admin-css', 'users', array( 'wpubdp-select2-css', 'wpubdp-dataTables-css' ) );
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
		defined( 'WPUBDP_PLUGIN_VERSION' ) || define( 'WPUBDP_PLUGIN_VERSION', '2.4.1' );
		defined( 'WPUBDP_BASE_NAME' ) || define( 'WPUBDP_BASE_NAME', plugin_basename( WPUBDP_PLUGIN_FILE ) );
	}
}
