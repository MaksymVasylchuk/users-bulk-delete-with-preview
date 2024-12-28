<?php
/**
 * Main class loader
 *
 * @package     UsersBulkDeleteWithPreview\Core
 */

namespace UsersBulkDeleteWithPreview\Core;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Pages\UbdwpUsersPage;
use UsersBulkDeleteWithPreview\Pages\UbdwpLogsPage;
use UsersBulkDeleteWithPreview\Traits\UbdwpTraitSingleton;
use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;

/**
 * Main loader class for the plugin.
 * Handles initialization, hooks, and page rendering.
 */
class UbdwpLoader
{
	use UbdwpTraitSingleton;

	/** @var array List of page objects. */
	private array $pages = array();

	/**
	 * Initialize the plugin by defining constants, setting up hooks, and loading pages.
	 */
	public function initialize(): void
	{
		$this->define_constants();

		// Load plugin translations.
		add_action('init', array($this, 'load_text_domain'));

		// Register admin menu and enqueue assets.
		add_action('admin_menu', array($this, 'admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'register_admin_styles'));

		// Add action links to the plugin list.
		add_filter('plugin_action_links_' . WPUBDP_BASE_NAME, array($this, 'action_links'));

		// Initialize page objects.
		$this->pages['logs'] = new UbdwpLogsPage();
		$this->pages['users'] = new UbdwpUsersPage();
	}

	/**
	 * Define plugin constants.
	 */
	private function define_constants(): void
	{
		// Root directory of the plugin.
		$plugin_root = dirname(__DIR__, 2); // Navigate two levels up from 'src/Core'.

		// Define constants if not already defined.
		if (!defined('WPUBDP_PLUGIN_DIR')) {
			define('WPUBDP_PLUGIN_DIR', $plugin_root . '/');
		}
		if (!defined('WPUBDP_PLUGIN_FILE')) {
			define('WPUBDP_PLUGIN_FILE', $plugin_root . '/ubdwp-users-bulk-delete-with-preview.php');
		}
		if (!defined('WPUBDP_PLUGIN_URL')) {
			define('WPUBDP_PLUGIN_URL', plugin_dir_url(WPUBDP_PLUGIN_FILE));
		}
		if (!defined('WPUBDP_PLUGIN_VERSION')) {
			define('WPUBDP_PLUGIN_VERSION', '1.1.1');
		}
		if (!defined('WPUBDP_BASE_NAME')) {
			define('WPUBDP_BASE_NAME', plugin_basename(WPUBDP_PLUGIN_FILE));
		}
	}

	/**
	 * Add admin menu and submenu pages.
	 */
	public function admin_menu(): void
	{
		add_menu_page(
			__('Bulk Users Delete', 'users-bulk-delete-with-preview'),
			__('Bulk Users Delete', 'users-bulk-delete-with-preview'),
			'manage_options',
			'ubdwp_admin',
			array($this->pages['users'], 'render'),
			UbdwpHelperFacade::get_icon()
		);

		add_submenu_page(
			'ubdwp_admin',
			__('Bulk Users Delete Logs', 'users-bulk-delete-with-preview'),
			__('Bulk Users Delete Logs', 'users-bulk-delete-with-preview'),
			'manage_options',
			'ubdwp_admin_logs',
			array($this->pages['logs'], 'render')
		);
	}

	/**
	 * Enqueue admin styles for plugin pages.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	public function register_admin_styles(string $hook_suffix): void
	{
		$this->register_common_admin_styles($hook_suffix);
	}

	/**
	 * Enqueue admin scripts for plugin pages.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	public function register_admin_scripts(string $hook_suffix): void
	{
		$this->register_common_admin_scripts($hook_suffix);

		foreach ($this->pages as $page) {
			$page->register_admin_scripts($hook_suffix);
		}
	}

	/**
	 * Add action links to the plugin list in the admin area.
	 *
	 * @param array $links Existing action links.
	 * @return array Modified action links.
	 */
	public function action_links(array $links): array
	{
		$settings_link = '<a href="admin.php?page=ubdwp_admin">' . __('Delete users', 'users-bulk-delete-with-preview') . '</a>';
		$logs_link = '<a href="admin.php?page=ubdwp_admin_logs">' . __('Logs', 'users-bulk-delete-with-preview') . '</a>';
		array_unshift($links, $settings_link, $logs_link);

		return $links;
	}

	/**
	 * Load text domain for translations.
	 */
	public function load_text_domain(): void
	{
		unload_textdomain('users-bulk-delete-with-preview');
		load_plugin_textdomain('users-bulk-delete-with-preview', false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	/**
	 * Enqueue common admin styles based on the current page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	protected function register_common_admin_styles(string $hook_suffix): void
	{
		if (UbdwpHelperFacade::is_plugin_page($hook_suffix)) {
			wp_enqueue_style('wpubdp-bootstrap-css', WPUBDP_PLUGIN_URL . 'assets/bootstrap/bootstrap.min.css', array(), WPUBDP_PLUGIN_VERSION);
			wp_enqueue_style('wpubdp-select2-css', WPUBDP_PLUGIN_URL . 'assets/select2/select2.min.css', array(), WPUBDP_PLUGIN_VERSION);
			wp_enqueue_style('wpubdp-admin-css', WPUBDP_PLUGIN_URL . 'assets/admin/admin.min.css', array(), WPUBDP_PLUGIN_VERSION);
		}
	}

	/**
	 * Enqueue common admin scripts based on the current page.
	 *
	 * @param string $hook_suffix The hook suffix for the current admin page.
	 */
	protected function register_common_admin_scripts(string $hook_suffix): void
	{
		if (UbdwpHelperFacade::is_plugin_page($hook_suffix)) {
			wp_register_script('wpubdp-dataTables-js', WPUBDP_PLUGIN_URL . 'assets/dataTables/datatables.min.js', array('jquery'), WPUBDP_PLUGIN_VERSION, true);
			wp_enqueue_script('wpubdp-dataTables-js');
		}
	}
}