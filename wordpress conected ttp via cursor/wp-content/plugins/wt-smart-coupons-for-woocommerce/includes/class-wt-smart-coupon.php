<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.thetoppercentile.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


if ( ! class_exists( 'Wt_Smart_Coupon' ) ) {
	/**
	 * The core plugin class.
	 *
	 * This is used to define internationalization, admin-specific hooks, and
	 * public-facing site hooks.
	 *
	 * Also maintains the unique identifier of this plugin as well as the current
	 * version of the plugin.
	 *
	 * @since      1.0.0
	 * @package    Wt_Smart_Coupon
	 * @subpackage Wt_Smart_Coupon/includes
	 */
	class Wt_Smart_Coupon {

		/**
		 * The loader that's responsible for maintaining and registering all hooks that power
		 * the plugin.
		 *
		 * @since    1.0.0
		 * @var      Wt_Smart_Coupon_Loader    $loader    Maintains and registers all hooks for the plugin.
		 */
		protected $loader;

		/**
		 * The unique identifier of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
		 */
		protected $plugin_name;

		/**
		 * The current version of the plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $version    The current version of the plugin.
		 */
		protected $version;

		/**
		 * The base name of the plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $plugin_base_name    The base name of the plugin.
		 */
		protected $plugin_base_name = WT_SMARTCOUPON_BASE_NAME;

		/**
		 * The stored options.
		 *
		 * @since    1.0.0
		 * @var $stored_options
		 */
		private static $stored_options = array();

		/**
		 * The admin instance.
		 *
		 * @since    1.0.0
		 * @var   object   $plugin_admin    The admin instance.
		 */
		public $plugin_admin = null;

		/**
		 * The common instance.
		 *
		 * @since    1.0.0
		 * @var   object   $plugin_common    The common instance.
		 */
		public $plugin_common = null;

		/**
		 * The public instance.
		 *
		 * @since    1.0.0
		 * @var   object   $plugin_public    The public instance.
		 */
		public $plugin_public = null;

		/**
		 * The instance.
		 *
		 * @since    1.0.0
		 * @var   object|null   $instance    The instance.
		 */
		private static $instance = null;

		/**
		 * Define the core functionality of the plugin.
		 *
		 * Set the plugin name and the plugin version that can be used throughout the plugin.
		 * Load the dependencies, define the locale, and set the hooks for the admin area and
		 * the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {

			if ( defined( 'WEBTOFFEE_SMARTCOUPON_VERSION' ) ) {
				$this->version = WEBTOFFEE_SMARTCOUPON_VERSION;
			} else {
				$this->version = '2.2.9';
			}
			$this->plugin_name = WT_SC_PLUGIN_NAME;

			$this->load_dependencies();
			$this->define_common_hooks();
			$this->define_admin_hooks();
			$this->define_public_hooks();
		}


		/**
		 * Get Instance
		 *
		 * @since 1.4.1
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new Wt_Smart_Coupon();
			}

			return self::$instance;
		}



		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Wt_Smart_Coupon_Loader. Orchestrates the hooks of the plugin.
		 * - Wt_Smart_Coupon_I18n. Defines internationalization functionality.
		 * - Wt_Smart_Coupon_Admin. Defines all hooks for the admin area.
		 * - Wt_Smart_Coupon_Public. Defines all hooks for the public side of the site.
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 */
		private function load_dependencies() {

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-wt-smart-coupon-loader.php';

			/**
			 * Webtoffee Security Library
			 * Includes Data sanitization, Access checking
			 */
			require_once plugin_dir_path( __DIR__ ) . 'includes/class-wt-smart-coupon-security-helper.php';

			/**
			 * Compatability to WPML
			 *
			 *  @since 1.4.5
			 *
			 * Webtoffee Language Functions
			 *
			 * Includes functions to manage translations
			 */

			require_once plugin_dir_path( __DIR__ ) . 'includes/class-wt-smart-coupon-multilanguage.php';

			/**
			 * The class responsible for defining all actions common to admin/public.
			 *
			 * @since 1.3.5
			 */
			require_once plugin_dir_path( __DIR__ ) . 'common/class-wt-smart-coupon-common.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			require_once plugin_dir_path( __DIR__ ) . 'admin/class-wt-smart-coupon-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			require_once plugin_dir_path( __DIR__ ) . 'public/class-wt-smart-coupon-public.php';

			/**
			 * This file is responsible for handling all the block related operations of the plugin.
			 *
			 * @since 1.6.0
			 */
			require_once plugin_dir_path( __DIR__ ) . 'blocks/class-wt-sc-free-blocks.php';

			$this->loader = new Wt_Smart_Coupon_Loader();
		}

		/**
		 * Register all of the hooks related to the admin/public area functionality
		 * of the plugin.
		 *
		 * @since    1.3.5
		 */
		private function define_common_hooks() {

			$this->plugin_common = Wt_Smart_Coupon_Common::get_instance( $this->get_plugin_name(), $this->get_version() );
			$this->plugin_common->register_modules();

			/**
			 * Coupon lookup table
			 *
			 * @since 1.4.3
			 */
			// Insert existing coupon data to lookup table.
			$this->loader->add_action( 'init', $this->plugin_common, 'update_existing_coupon_data_to_lookup_table', 10 );

			// Update lookup table on coupon object save.
			$this->loader->add_action( 'woocommerce_after_data_object_save', $this->plugin_common, 'update_coupon_lookup_on_object_save', 1000 );

			// Update lookup table on coupon meta data save.
			$this->loader->add_action( 'woocommerce_process_shop_coupon_meta', $this->plugin_common, 'update_coupon_lookup_on_meta_save', 1000 );

			// Update lookup table on coupon usage count change.
			$this->loader->add_action( 'woocommerce_increase_coupon_usage_count', $this->plugin_common, 'update_coupon_lookup_on_usage_count_change', 1000, 2 );
			$this->loader->add_action( 'woocommerce_decrease_coupon_usage_count', $this->plugin_common, 'update_coupon_lookup_on_usage_count_change', 1000, 2 );

			// Update lookup table on post meta update.
			$this->loader->add_action( 'updated_post_meta', $this->plugin_common, 'update_coupon_lookup_on_postmeta_change', 1000, 3 );
			$this->loader->add_action( 'added_post_meta', $this->plugin_common, 'update_coupon_lookup_on_postmeta_change', 1000, 3 );
			$this->loader->add_action( 'deleted_post_meta', $this->plugin_common, 'update_coupon_lookup_on_postmeta_change', 1000, 3 );

			// Update lookup table on post status update.
			$this->loader->add_action( 'transition_post_status', $this->plugin_common, 'update_coupon_lookup_on_post_status_change', 1000, 3 );

			/**
			 *  Check and update lookup table. Priority number must be lower, because data updation hook must fire after this one
			 *
			 *  @since 1.4.4
			 */
			$this->loader->add_action( 'init', $this->plugin_common, 'check_and_update_lookup_table', 1 );

			/**
			 *  Delete coupon row from coupon lookup table when coupon permenantly deleted.
			 *
			 *  @since 1.8.1
			 */
			$this->loader->add_action( 'delete_post', $this->plugin_common, 'coupon_delete_from_lookup_table_when_deleted' );
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 */
		private function define_admin_hooks() {

			$this->plugin_admin = Wt_Smart_Coupon_Admin::get_instance( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts' );
			$this->loader->add_action( 'admin_head', $this->plugin_admin, 'hide_free_shipping_coupon_field' );
			$this->loader->add_filter( 'plugin_action_links_' . $this->get_plugin_base_name(), $this->plugin_admin, 'add_plugin_links_wt_smartcoupon' );

			$this->loader->add_action( 'woocommerce_process_shop_coupon_meta', $this->plugin_admin, 'process_shop_coupon_meta' );

			$this->loader->add_action( 'woocommerce_coupon_options', $this->plugin_admin, 'add_new_coupon_options' );
			$this->loader->add_filter( 'woocommerce_coupon_data_tabs', $this->plugin_admin, 'remove_usage_restriction_tab', 100 );

			$this->loader->add_action( 'wp_ajax_wt_check_product_type', $this->plugin_admin, 'check_product_type' );

			/**
			*   Initiate admin modules
			 *
			*   @since 1.3.5
			*/
			$this->plugin_admin->register_modules();

			/**
			 *  Links under plugin description section of plugins page
			 *
			 *  @since 1.3.9
			 */
			$this->loader->add_filter( 'plugin_row_meta', $this->plugin_admin, 'plugin_row_meta', 10, 2 );

			/**
			 *  Saving new coupon count
			 *
			 *  @since 1.4.1
			 */
			$this->loader->add_action( 'wp_insert_post', $this->plugin_admin, 'save_created_coupon_count', 10, 3 );

			/**
			 *  Search coupons using email
			 *
			 *  @since 1.4.4
			 */
			$this->loader->add_action( 'parse_request', $this->plugin_admin, 'search_coupon_using_email' );

			/**
			*   Admin menu
			*
			*   @since 1.4.4
			*/
			$this->loader->add_action( 'admin_menu', $this->plugin_admin, 'admin_menu', 11 );

			/**
			* Smart coupon settings button on coupons page
			*
			* @since 1.4.4
			*/
			$this->loader->add_action( 'admin_head-edit.php', $this->plugin_admin, 'coupon_page_settings_button' );

			/**
			* Saving hook for debug tab
			*
			* @since 1.4.5
			*/
			$this->loader->add_action( 'admin_init', $this->plugin_admin, 'debug_save' );

			/**
			 *  Lookup table migration in progress message
			 *
			 *  @since 1.4.5
			 */
			$this->loader->add_action( 'admin_notices', $this->plugin_admin, 'lookup_table_migration_message' );

			/**
			 *  Tooltips
			 *
			 *  @since 1.7.0
			 */
			$this->loader->add_action( 'init', $this->plugin_admin, 'register_tooltips', 11 );

			/**
			 *  Include Design System file.
			 *
			 *  @since 2.0.0
			 */
			$this->loader->add_action( 'admin_init', $this->plugin_admin, 'include_design_system' );

			/**
			 *  Trigger after activation hook if not triggered
			 *
			 *  @since 2.0.0
			 */
			$this->loader->add_action( 'admin_init', $this->plugin_admin, 'check_and_trigger_activation_action_hook' );

			/**
			 *  Delete coupon in lookup table that doesn't exist in post table..
			 *
			 *  @since 2.0.0
			 */
			$this->loader->add_action( 'after_wt_smart_coupon_for_woocommerce_is_activated', $this->plugin_admin, 'delete_coupon_from_lookup_table' );

			$this->loader->add_filter( 'admin_footer_text', $this->plugin_admin, 'sc_review_request_footer' );

			$this->loader->add_filter( 'update_footer', $this->plugin_admin, 'sc_version_footer', 11 );

			$this->loader->add_action( 'wt_sc_module_settings_debug', $this->plugin_admin, 'render_reset_lookup_table_in_debug_tab' );

			$this->loader->add_action( 'wp_ajax_wbte_sc_reset_lookup_table', $this->plugin_admin, 'reset_lookup_table_ajax' );

		}

		/**
		 * Register all of the hooks related to the public-facing functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 */
		private function define_public_hooks() {

			$this->plugin_public = Wt_Smart_Coupon_Public::get_instance( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts' );

			$this->loader->add_action( 'woocommerce_before_checkout_form', $this->plugin_public, 'maybe_add_checkout_coupon_form', 9 );

			$this->loader->add_action( 'woocommerce_before_cart_table', $this->plugin_public, 'maybe_add_cart_coupon_form', 5 );
			$this->loader->add_filter( 'woocommerce_add_to_cart_validation', $this->plugin_public, 'validate_single_course_cart_rules', 10, 6 );
			$this->loader->add_filter( 'woocommerce_update_cart_validation', $this->plugin_public, 'validate_cart_item_quantity_update', 10, 4 );
			$this->loader->add_action( 'woocommerce_before_calculate_totals', $this->plugin_public, 'enforce_single_course_cart_rules', 1 );
			$this->loader->add_action( 'woocommerce_cart_loaded_from_session', $this->plugin_public, 'enforce_single_course_cart_rules', 1 );
			$this->loader->add_action( 'woocommerce_review_order_after_cart_contents', $this->plugin_public, 'render_checkout_remove_item_links', 20 );
			$this->loader->add_filter( 'woocommerce_checkout_cart_item_quantity', $this->plugin_public, 'add_remove_link_in_checkout_line_item', 10, 3 );
			$this->loader->add_action( 'woocommerce_after_checkout_validation', $this->plugin_public, 'validate_gateway_config_before_order', 10, 2 );
			$this->loader->add_action( 'wp_footer', $this->plugin_public, 'strip_emojis_from_order_received_page', 99 );

			/**
			*   Initiate public modules
			 *
			*   @since 1.4.0
			*/
			$this->plugin_public->register_modules();

			/**
			 *  Apply coupon on click
			 *
			 *  @since 1.4.8
			 */
			$this->loader->add_action( 'wc_ajax_apply_coupon_on_click', $this->plugin_public, 'apply_coupon' );

			/**
			 *  Set checkout values on block checkout
			 *
			 *  @since 1.6.0
			 */
			$this->loader->add_action( 'wc_ajax_wbte_sc_set_block_checkout_values', $this->plugin_public, 'set_block_checkout_values' );

			/**
			 *  Update payment method on session when payment method is changed
			 *
			 *  @since 2.2.0
			 */
			$this->loader->add_action( 'wc_ajax_wbte_sc_update_payment_method_on_session', $this->plugin_public, 'update_payment_method_on_session' );

			/**
			 *  Add 'available coupon in block cart/checkout' blocks data
			 *
			 *  @since 2.2.1
			 */
			$this->loader->add_filter( 'wbte_sc_alter_blocks_data', $this->plugin_public, 'add_coupon_blocks_data' );
		}

		/**
		 *  Registers modules
		 *
		 *  @since 1.3.5
		 *  @param   array  $modules The modules array.
		 *  @param   string $module_option_name The module option name.
		 *  @param   string $module_path The module path.
		 *  @param   array  $existing_modules The existing modules array.
		 */
		public static function register_modules( $modules, $module_option_name, $module_path, &$existing_modules ) {
			$wt_sc_modules = get_option( $module_option_name );
			if ( false === $wt_sc_modules ) {
				$wt_sc_modules = array();
			}
			foreach ( $modules as $module ) {
				$is_active = 1;
				if ( isset( $wt_sc_modules[ $module ] ) ) {
					$is_active = $wt_sc_modules[ $module ]; // checking module status.
				} else {
					$wt_sc_modules[ $module ] = 1; // default status is active.
				}
				$module_file = $module_path . "modules/$module/class-wt-smart-coupon-$module.php";
				if ( ! file_exists( $module_file ) ) {
					$module_file = $module_path . "modules/$module/class-wbte-smart-coupon-$module.php";
				}
				if ( file_exists( $module_file ) && 1 === $is_active ) {
					$existing_modules[] = $module; // this is for module_exits checking.
					require_once $module_file;
				} else {
					$wt_sc_modules[ $module ] = 0;
				}
			}
			$out = array();
			foreach ( $wt_sc_modules as $k => $m ) {
				if ( in_array( $k, $modules, true ) ) {
					$out[ $k ] = $m;
				}
			}
			update_option( $module_option_name, $out );
		}

		/**
		 *  Get module id from module base
		 *
		 *   @since 1.0.0
		 *
		 *   @param   string $module_base The module base.
		 *   @return  string The module id.
		 */
		public static function get_module_id( $module_base ) {
			return WT_SC_PLUGIN_NAME . '_' . $module_base;
		}


		/**
		 *  Get module base from module id
		 *
		 *   @since 1.3.5
		 *
		 *   @param   string $module_id The module id.
		 *   @return  string The module base.
		 */
		public static function get_module_base( $module_id ) {
			if ( strpos( $module_id, WT_SC_PLUGIN_NAME . '_' ) !== false ) {
				return str_replace( WT_SC_PLUGIN_NAME . '_', '', $module_id );
			}
			return false;
		}

		/**
		 * Run the loader to execute all of the hooks with WordPress.
		 *
		 * @since    1.0.0
		 */
		public function run() {
			$this->loader->run();
		}

		/**
		 *  Get plugin name
		 *
		 *  @since 1.0.0
		 *  @return string The plugin name.
		 */
		public function get_plugin_name() {
			return $this->plugin_name;
		}

		/**
		 * The reference to the class that orchestrates the hooks with the plugin.
		 *
		 * @since     1.0.0
		 * @return    Wt_Smart_Coupon_Loader    Orchestrates the hooks of the plugin.
		 */
		public function get_loader() {
			return $this->loader;
		}


		/**
		 *  Get plugin version
		 *
		 *  @since 1.0.0
		 *  @return string The plugin version.
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 *  Get plugin base name
		 *
		 *  @since 1.0.0
		 *  @return string The plugin base name.
		 */
		public function get_plugin_base_name() {
			return $this->plugin_base_name;
		}

		/**
		 *  Check if WooCommerce is prior to a given version
		 *
		 *  @since 1.0.0
		 *  @param   string $version The version to check.
		 *  @return  bool    Is WooCommerce prior to the given version or not.
		 */
		public static function wt_sc_is_woocommerce_prior_to( $version ) {
			$woocommerce_is_pre_version = ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $version, '<' ) ) ? true : false;
			return $woocommerce_is_pre_version;
		}


		/**
		 *  Install necessary tables
		 *
		 *  @since 1.4.3
		 */
		public static function install_tables() {
			global $wpdb;
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			if ( is_multisite() ) {
				// Get all blogs in the network and activate plugin on each one.
				$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::install_lookup_table();
					restore_current_blog();
				}
			} else {
				self::install_lookup_table();
			}
		}

		/**
		 *  Get lookup table name
		 *
		 *  @since 1.4.3
		 *  @return string The lookup table name.
		 */
		public static function get_lookup_table_name() {
			global $wpdb;
			return $wpdb->_escape( $wpdb->prefix . 'wt_sc_coupon_lookup' );
		}

		/**
		 *  Check if table exists
		 *
		 *  @since 1.4.3
		 *  @param   string $table_name The table name.
		 *  @return  bool    Is table exists or not.
		 */
		public static function is_table_exists( $table_name ) {
			global $wpdb;
			return $wpdb->get_results( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%' . $table_name . '%' ), ARRAY_N ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		/**
		 *  Create lookup table for saving coupon data
		 *
		 *  @since 1.4.3
		 */
		public static function install_lookup_table() {
			global $wpdb;
			$table_name = self::get_lookup_table_name();

			if ( ! self::is_table_exists( $table_name ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';

				$sql_qry = "CREATE TABLE IF NOT EXISTS {$table_name} (
	              `id` bigint(20) NOT NULL AUTO_INCREMENT,
	              `coupon_id` bigint(20) NOT NULL DEFAULT '0',
	              `is_auto_coupon` int(11) NOT NULL DEFAULT '0',
	              `auto_coupon_priority` bigint(20) NOT NULL DEFAULT '0',
	              `my_account_display` int(11) NOT NULL DEFAULT '0',
	              `cart_display` int(11) NOT NULL DEFAULT '0',
	              `checkout_display` int(11) NOT NULL DEFAULT '0',
				  `post_status` varchar(100) NOT NULL,
				  `email_restriction` text NOT NULL,
				  `user_roles` text NOT NULL,
				  `exclude_user_roles` text NOT NULL,
				  `expiry` varchar(100) NOT NULL,
				  `discount_type` varchar(100) NOT NULL,
				  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
				  `usage_limit` bigint(20) NOT NULL DEFAULT '0',
				  `usage_count` bigint(20) NOT NULL DEFAULT '0',
				  `usage_limit_per_user` int(11) NOT NULL DEFAULT '0',
				  `is_wt_gc_wallet_coupon` int(11) NOT NULL DEFAULT '0',
	              PRIMARY KEY(`id`),
	              INDEX `COUPON_ID`(`coupon_id`)
	            ) DEFAULT CHARSET=utf8;";

				dbDelta( $sql_qry );

			} elseif ( self::get_lookup_table_version() > self::get_installed_lookup_table_version() ) {

				if ( 2 > self::get_installed_lookup_table_version() ) {
					// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter

					// Update columns to BIGINT.
					$wpdb->query( "ALTER TABLE `{$table_name}` CHANGE `id` `id` BIGINT(20) NOT NULL AUTO_INCREMENT, CHANGE `coupon_id` `coupon_id` BIGINT(20) NOT NULL DEFAULT '0', CHANGE `usage_limit` `usage_limit` BIGINT(20) NOT NULL DEFAULT '0', CHANGE `usage_count` `usage_count` BIGINT(20) NOT NULL DEFAULT '0'" );

					// Add index to coupon_id column.
					$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX(`coupon_id`)" );

					// add new column for exclude user roles.
					$has_col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$table_name}` LIKE %s", 'exclude_user_roles' ), ARRAY_N );

					if ( ! $has_col ) {
						$wpdb->query( "ALTER TABLE `{$table_name}` ADD `exclude_user_roles` TEXT NOT NULL AFTER `user_roles`" );
					}

					// phpcs:enable
				}

				if ( 3 > self::get_installed_lookup_table_version() ) { /** Lesser than 3 so, add the new columns in version 3.  @since 1.7.0 */

					// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
					if ( ! $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$table_name}` LIKE %s", 'auto_coupon_priority' ), ARRAY_N ) ) {
						$wpdb->query( "ALTER TABLE `{$table_name}` ADD `auto_coupon_priority` bigint(20) NOT NULL DEFAULT '0' AFTER `is_auto_coupon`" );
					}

					// phpcs:enable
				}

					// Future version updates will come here.....

					// finally update the version number to latest.
					self::update_lookup_table_version();
			}

			if ( ! self::is_table_exists( $table_name ) ) {
				deactivate_plugins( WT_SMARTCOUPON_BASE_NAME );
				wp_die(
					// translators: %1$s: opening bold tag, %2$s: closing bold tag, %3$s: table name.
					sprintf( esc_html__( 'An error occurred while activating %1$s TheTopPercentile Coupons %2$s: Unable to create database table. %3$s', 'thetoppercentile-coupons' ), '<b>', '</b>', esc_html( $table_name ) ),
					'',
					array(
						'link_url'  => esc_url( admin_url( 'plugins.php' ) ),
						'link_text' => esc_html__( 'Go to plugins page', 'thetoppercentile-coupons' ),
					)
				);
			}
		}

		/**
		 *  Installed version of lookup table
		 *
		 *  @since 1.4.4
		 *  @return int     installed lookup table version
		 */
		public static function get_installed_lookup_table_version() {
			return absint( get_option( 'wt_sc_coupon_lookup_version', 1 ) );
		}

		/**
		 *  Update lookup table version to latest
		 *
		 *  @since 1.4.4
		 */
		public static function update_lookup_table_version() {
			update_option( 'wt_sc_coupon_lookup_version', self::get_lookup_table_version() );
		}


		/**
		 *  New lookup table version for the plugin
		 *
		 *  @since 1.4.4
		 *  @since 1.7.0    Lookup table version 3
		 *  @return int     new lookup table version
		 */
		public static function get_lookup_table_version() {
			return 3;
		}


		/**
		 *  Migrate old settings, If exists
		 *
		 *  @since 1.4.7
		 *  @param   array $settings The settings array.
		 */
		protected static function migrate_settings( $settings ) {
			$smart_coupon_option = get_option( 'wt_smart_coupon_options' );

			if (
				isset( $smart_coupon_option['wt_display_used_coupons'] )
				|| isset( $smart_coupon_option['wt_display_expired_coupons'] ) // old data exists.
			) {
				$settings                                       = array();
				$settings['display_used_coupons_my_account']    = isset( $smart_coupon_option['wt_display_used_coupons'] ) ? $smart_coupon_option['wt_display_used_coupons'] : true;
				$settings['display_expired_coupons_my_account'] = isset( $smart_coupon_option['wt_display_expired_coupons'] ) ? $smart_coupon_option['wt_display_expired_coupons'] : false;

				self::update_settings( $settings ); /* update old settings */

				// remove old option.
				unset( $smart_coupon_option['wt_display_used_coupons'], $smart_coupon_option['wt_display_expired_coupons'] );
				update_option( 'wt_smart_coupon_options', $smart_coupon_option );
			}
		}


		/**
		 *  Get default settings
		 *
		 *  @since     1.4.7
		 *  @param   string $base_id The base ID.
		 *  @return  array The settings array.
		 */
		public static function default_settings( $base_id = '' ) {
			$settings = array(
				'display_used_coupons_my_account'    => true,
				'display_expired_coupons_my_account' => false,
			);

			self::migrate_settings( $settings ); /* migrate old settings. If exists */

			if ( '' !== $base_id ) {
				/**
				 * To alter the module default settings.
				 *
				 * @since 1.4.7
				 * @param array $settings The settings array.
				 * @param string $base_id The base ID.
				 * @return array The filtered settings array.
				 */
				$settings = apply_filters( 'wt_sc_module_default_settings', $settings, $base_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			}
			return $settings;
		}


		/**
		 *  Get current settings.
		 *
		 *  @since     1.4.7
		 *  @param   string $base_id The base ID.
		 *  @return  array The settings array.
		 */
		public static function get_settings( $base_id = '' ) {
			$settings         = self::default_settings( $base_id );
			$option_name      = ( '' === $base_id ? WT_SC_SETTINGS_FIELD : $base_id );
			$option_id        = ( '' === $base_id ? 'main' : $base_id );
			$current_settings = get_option( $option_name, array() );

			if ( ! empty( $current_settings ) ) {
				foreach ( $settings as $setting_key => $setting ) {
					if ( isset( $current_settings[ $setting_key ] ) ) {
						if ( is_array( $setting ) && self::is_assoc_arr( $setting ) ) {
							$settings[ $setting_key ] = wp_parse_args( $current_settings[ $setting_key ], $settings[ $setting_key ] );

						} else {   /* assumes not a sub setting */
							$settings[ $setting_key ] = $current_settings[ $setting_key ];
						}
					}
				}
			}

			// stripping escape slashes.
			$settings = self::arr_stripslashes( $settings );
			/**
			 * To alter the settings.
			 *
			 * @since 1.4.7
			 * @param array $settings The settings array.
			 * @param string $base_id The base ID.
			 * @return array The filtered settings array.
			 */
			$settings = apply_filters( 'wt_sc_alter_settings', $settings, $base_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			return $settings;
		}


		/**
		 * Update current settings.
		 *
		 * @since   1.4.7
		 * @param   array  $the_options The options array.
		 * @param   string $base_id    Module id.
		 */
		public static function update_settings( $the_options, $base_id = '' ) {
			if ( '' !== $base_id && 'main' !== $base_id ) {
				self::$stored_options[ $base_id ] = $the_options;
				update_option( $base_id, $the_options );
			}

			if ( '' === $base_id ) {
				self::$stored_options['main'] = $the_options;
				update_option( WT_SC_SETTINGS_FIELD, $the_options );
			}
		}


		/**
		 * Update option value,
		 *
		 * @since   1.4.7
		 * @param   string $option_name The option name.
		 * @param   mixed  $value The value to update.
		 * @param   string $base The base ID.
		 * @return  mixed
		 */
		public static function update_option( $option_name, $value, $base = '' ) {
			$the_options                 = self::get_settings( $base );
			$the_options[ $option_name ] = $value;
			self::update_settings( $the_options, $base );
		}


		/**
		 * Get option value
		 *
		 * @since   1.4.7
		 * @param   string $option_name The option name.
		 * @param   string $base The base ID.
		 * @param   array  $the_options The options array.
		 * @return  mixed
		 */
		public static function get_option( $option_name, $base = '', $the_options = null ) {
			if ( is_null( $the_options ) ) {
				$the_options = self::get_settings( $base );
			}

			$vl = ( isset( $the_options[ $option_name ] ) ? $the_options[ $option_name ] : false );
			/**
			 * To alter the option value.
			 *
			 * @since 1.4.7
			 * @param mixed $vl Option value.
			 * @param array $the_options The options array.
			 * @param string $option_name The option name.
			 * @param string $base The base ID.
			 * @return mixed The filtered option value.
			 */
			$vl = apply_filters( 'wt_sc_alter_option', $vl, $the_options, $option_name, $base ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			return $vl;
		}


		/**
		 *  Is associative array
		 *
		 *  @since  1.4.7
		 *  @param  array $arr    Input array.
		 *  @return bool    Is associative array or not.
		 */
		public static function is_assoc_arr( $arr ) {
			return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
		}


		/**
		 *  Strip slashes from an array
		 *
		 *  @since  1.4.7
		 *  @param  array $arr      Input array.
		 *  @return array|string    Stripped array.
		 */
		protected static function arr_stripslashes( $arr ) {
			if ( is_array( $arr ) || is_object( $arr ) ) {
				foreach ( $arr as &$arrv ) {
					$arrv = self::arr_stripslashes( $arrv );
				}

				return $arr;

			} else {
				return stripslashes( $arr );
			}
		}
	}
}
