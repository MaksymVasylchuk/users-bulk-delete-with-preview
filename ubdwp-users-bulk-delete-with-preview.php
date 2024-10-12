<?php
/**
 * Plugin Name: Users Bulk Delete With Preview
 * Plugin URI: https://google.com
 * Description: Effortlessly delete multiple WordPress users with our Users Bulk Delete With Preview plugin. View and confirm user details before removal to ensure accuracy and avoid mistakes. Streamline your user management process with ease and confidence!
 * Version: 1.0.0
 * Requires at least 5.6
 * Tested up to: 6.6.2
 * Author: maksymvasylchuk
 * Author URI: https://github.com/MaksymVasylchuk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: users-bulk-delete-with-preview
 * Domain Path: /languages
 *
 * @package UsersBulkDeleteWithPreview
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Main plugin class.
if ( ! class_exists( 'UBDWP_Users_Bulk_Delete_With_Preview' ) ) {
	/**
	 * UBDWP_Users_Bulk_Delete_With_Preview class.
	 */
	class UBDWP_Users_Bulk_Delete_With_Preview {
		/**
		 * Define constants, add classes, includes, load translations and init hooks and filters.
		 */
		public function __construct() {
			// Initialize constants.
			$this->define_constants();
			// Include necessary classes.
			$this->add_classes();
			// Include additional files.
			$this->add_includes();
			// Languages.
			$this->load_text_domain();

			// Hook functions.
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action(
				'admin_enqueue_scripts',
				array( $this, 'register_admin_scripts' )
			);
			add_action(
				'admin_enqueue_scripts',
				array( $this, 'register_admin_styles' )
			);

			// Filter functions.
			add_filter(
				'plugin_action_links_' . WPUBDP_BASE_NAME,
				array( $this, 'action_links' )
			);
		}

		/**
		 * Define plugin constants.
		 */
		private function define_constants(): void {
			if ( ! defined( 'WPUBDP_PLUGIN_DIR' ) ) {
				define( 'WPUBDP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}
			if ( ! defined( 'WPUBDP_PLUGIN_FILE' ) ) {
				define( 'WPUBDP_PLUGIN_FILE', __FILE__ );
			}
			if ( ! defined( 'WPUBDP_PLUGIN_URL' ) ) {
				define( 'WPUBDP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}
			if ( ! defined( 'WPUBDP_PLUGIN_VERSION' ) ) {
				define( 'WPUBDP_PLUGIN_VERSION', time() );
			}
			if ( ! defined( 'WPUBDP_BASE_NAME' ) ) {
				define(
					'WPUBDP_BASE_NAME',
					plugin_basename( WPUBDP_PLUGIN_FILE )
				);
			}
		}

		/**
		 * Include additional files from the includes directory.
		 */
		private function add_includes(): void {
			if ( defined( 'WPUBDP_PLUGIN_DIR' ) ) {
				$root_file         = glob(
					WPUBDP_PLUGIN_DIR
											. 'includes/*.php'
				);
				$subdirectory_file = glob(
					WPUBDP_PLUGIN_DIR
											. 'includes/**/*.php'
				);
				$all_files         = array_merge( $subdirectory_file, $root_file );

				foreach ( $all_files as $file ) {
					include_once $file;
				}
			}
		}

		/**
		 * Include additional classes from the classes directory.
		 */
		private function add_classes(): void {
			$classes              = glob( WPUBDP_PLUGIN_DIR . 'classes/*.php' );
			$subdirectory_classes = glob(
				WPUBDP_PLUGIN_DIR
										. 'classes/**/*.php'
			);
			$all_classes          = array_merge(
				$classes,
				$subdirectory_classes
			);

			foreach ( $all_classes as $file ) {
				require_once $file;
			}
		}

		/**
		 * Add admin menu and submenu pages.
		 */
		public function admin_menu(): void {
			add_menu_page(
				__( 'Bulk Users Delete', 'users-bulk-delete-with-preview' ),
				__( 'Bulk Users Delete', 'users-bulk-delete-with-preview' ),
				'manage_options',
				'ubdwp_admin',
				array( $this, 'ubdwp_settings_page' ),
				UBDWPHelperFacade::get_icon()
			);

			add_submenu_page(
				'ubdwp_admin',
				__(
					'Bulk Users Delete Logs',
					'users-bulk-delete-with-preview'
				),
				__(
					'Bulk Users Delete Logs',
					'users-bulk-delete-with-preview'
				),
				'manage_options',
				'ubdwp_admin_logs',
				array( $this, 'ubdwp_logs_page' )
			);
		}

		/**
		 * Render the settings page.
		 */
		public function ubdwp_settings_page(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( UBDWPHelperFacade::get_error_message( 'permission_error' ) ); // UBDWPHelperFacade will return escaped and translated string.
			}

			$template_name = 'admin-page.php';

			// Retrieve user roles and other data for the settings page.
			$all_roles     = wp_roles()->roles;
			$types         = UBDWPHelperFacade::get_types_of_user_search();
			$products      = array();
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && function_exists( 'wc_get_products' ) ) {
				$products = wc_get_products( array( 'limit' => - 1 ) );
			}

			$data = array(
				'title'         => __(
					'Users Bulk Delete With Preview',
					'users-bulk-delete-with-preview'
				),
				'roles'         => $all_roles,
				'types'         => $types,
				'products'      => $products,
			);

			UBDWPViewsFacade::include_template( $template_name, $data ); // WPCS: XSS ok.
		}

		/**
		 * Render the logs page.
		 */
		public function ubdwp_logs_page(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( UBDWPHelperFacade::get_error_message( 'permission_error' ) ); // UBDWPHelperFacade will return escaped and translated string.
			}

			$template_name = 'logs-page.php';

			$data = array(
				'title' => __(
					'Logs page',
					'users-bulk-delete-with-preview'
				),
			);

			UBDWPViewsFacade::include_template( $template_name, $data ); // WPCS: XSS ok.
		}

		/**
		 * Enqueue admin styles.
		 *
		 * @param  string $hook_suffix  The hook suffix for the current admin page.
		 */
		public function register_admin_styles( string $hook_suffix ): void {
			if ( $hook_suffix === 'toplevel_page_ubdwp_admin'
				|| ( isset($_GET['page']) && $_GET['page'] == 'ubdwp_admin_logs' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce verification is not required here.
			) {
				wp_enqueue_style(
					'wpubdp-bootstrap-css',
					WPUBDP_PLUGIN_URL . 'assets/bootstrap/bootstrap.min.css',
					array(),
					WPUBDP_PLUGIN_VERSION
				);

				wp_enqueue_style(
					'wpubdp-select2-css',
					WPUBDP_PLUGIN_URL . 'assets/select2/select2.min.css',
					array(),
					WPUBDP_PLUGIN_VERSION
				);

				wp_enqueue_style(
					'wpubdp-jquery-ui-css',
					WPUBDP_PLUGIN_URL . 'assets/jquery-ui/jquery-ui.css',
					array(),
					WPUBDP_PLUGIN_VERSION
				);

				wp_enqueue_style(
					'wpubdp-jquery-ui-timepicker-addon-css',
					WPUBDP_PLUGIN_URL
					. 'assets/jquery-ui-datepicker/jquery-ui-timepicker-addon.min.css',
					array( 'wpubdp-jquery-ui-css' ),
					WPUBDP_PLUGIN_VERSION
				);

				wp_enqueue_style(
					'wpubdp-dataTables-css',
					WPUBDP_PLUGIN_URL
					. 'assets/dataTables/datatables.min.css',
					array(),
					WPUBDP_PLUGIN_VERSION
				);

				wp_enqueue_style(
					'wpubdp-admin-css',
					WPUBDP_PLUGIN_URL . 'assets/admin/admin.min.css',
					array(),
					WPUBDP_PLUGIN_VERSION
				);
			}
		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @param  string $hook_suffix  The hook suffix for the current admin page.
		 */
		public function register_admin_scripts( string $hook_suffix ): void {
			if ( $hook_suffix === 'toplevel_page_ubdwp_admin' ) {
				wp_register_script(
					'wpubdp-bootstrap-js',
					WPUBDP_PLUGIN_URL . 'assets/bootstrap/bootstrap.min.js',
					array( 'jquery' ),
					WPUBDP_PLUGIN_VERSION,
					true
				);

				wp_register_script(
					'wpubdp-select2-js',
					WPUBDP_PLUGIN_URL . 'assets/select2/select2.min.js',
					array( 'jquery' ),
					WPUBDP_PLUGIN_VERSION,
					true
				);

				wp_register_script(
					'wpubdp-datepicker-js',
					WPUBDP_PLUGIN_URL
					. 'assets/jquery-ui-datepicker/jquery-ui-timepicker-addon.min.js',
					array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ),
					WPUBDP_PLUGIN_VERSION,
					true
				);

				wp_register_script(
					'wpubdp-admin-js',
					WPUBDP_PLUGIN_URL . 'assets/admin/admin.min.js',
					array(
						'jquery',
						'wpubdp-bootstrap-js',
						'wpubdp-select2-js',
						'wp-i18n',
					),
					WPUBDP_PLUGIN_VERSION,
					true
				);

				wp_register_script(
					'wpubdp-dataTables-js',
					WPUBDP_PLUGIN_URL
					. 'assets/dataTables/datatables.min.js',
					array( 'jquery' ),
					WPUBDP_PLUGIN_VERSION,
					true
				);

				wp_enqueue_script( 'wpubdp-bootstrap-js' );
				wp_enqueue_script( 'wpubdp-select2-js' );
				wp_enqueue_script( 'wpubdp-datepicker-js' );
				wp_enqueue_script( 'wpubdp-dataTables-js' );
				wp_enqueue_script( 'wpubdp-admin-js' );

				wp_localize_script(
					'wpubdp-admin-js',
					'myAjax',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
					)
				);
			}

			if (isset($_GET['page']) && $_GET['page'] == 'ubdwp_admin_logs') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The nonce verification is not required here.

				wp_register_script(
					'wpubdp-dataTables-js',
					WPUBDP_PLUGIN_URL
					. 'assets/dataTables/datatables.min.js',
					array( 'jquery' ),
					WPUBDP_PLUGIN_VERSION,
					true
				);

				wp_register_script(
					'wpubdp-logs-js',
					WPUBDP_PLUGIN_URL . 'assets/admin/logs.min.js',
					array( 'jquery', 'wpubdp-dataTables-js' ),
					WPUBDP_PLUGIN_VERSION,
					true
				);

				wp_enqueue_script( 'wpubdp-dataTables-js' );
				wp_enqueue_script( 'wpubdp-logs-js' );

				wp_localize_script(
					'wpubdp-logs-js',
					'myAjax',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
					)
				);
			}
		}

		/**
		 * Add action links to the plugin list.
		 *
		 * @param  array $links  Existing action links.
		 *
		 * @return array Modified action links.
		 */
		public function action_links( array $links ): array {
			$settings_link = '<a href="admin.php?page=ubdwp_admin">'
							. __( 'Delete users' , 'users-bulk-delete-with-preview') . '</a>';
			$logs_link     = '<a href="admin.php?page=ubdwp_admin_logs">'
							. __( 'Logs' , 'users-bulk-delete-with-preview') . '</a>';
			array_unshift( $links, $settings_link );
			array_unshift( $links, $logs_link );

			return $links;
		}

		/**
		 * Load text domain (translations).
		 */
		public function load_text_domain(): void {
			load_plugin_textdomain(
				'users-bulk-delete-with-preview',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}
	}
}

// Initialize the plugin if the class exists.
if ( class_exists( 'UBDWP_Users_Bulk_Delete_With_Preview' ) ) {
	$users_bulk_delete_with_preview = new UBDWP_Users_Bulk_Delete_With_Preview();

	// Register activation hook.
	register_activation_hook( __FILE__, 'ubdwp_activate_plugin' );
}
