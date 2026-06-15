<?php
/**
 * Premium Upgrade Page
 *
 * @link
 * @since 1.4.4
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Premium_Upgrade' ) ) {
	/**
	 * Premium Upgrade class
	 *
	 * @since 1.4.4
	 */
	class Wt_Smart_Coupon_Premium_Upgrade {

		/**
		 * Module id
		 *
		 * @var string
		 */
		public $module_id = '';

		/**
		 * Module id static
		 *
		 * @var string
		 */
		public static $module_id_static = '';

		/**
		 * Module base
		 *
		 * @var string
		 */
		public $module_base = 'premium_upgrade';

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->module_id = $this->module_base;

			add_filter( 'wt_sc_admin_menu', array( $this, 'add_admin_pages' ), 11 );
			add_action( 'admin_footer', array( $this, 'highlight_admin_menu' ) );
		}

		/**
		 *  Admin menu
		 *
		 *  @since 1.4.4
		 *  @param array $menus Array of admin pages.
		 *  @return array Modified array of admin pages.
		 */
		public function add_admin_pages( $menus ) {
			$menus[] = array(
				'submenu',
				WT_SC_PLUGIN_NAME,
				__( 'Premium upgrade', 'thetoppercentile-coupons' ),
				__( 'Premium upgrade', 'thetoppercentile-coupons' ),
				'read',
				$this->module_id,
				array( $this, 'admin_settings_page' ),
			);
			return $menus;
		}


		/**
		 *  Admin settings page
		 *
		 *  @since 1.4.4
		 */
		public function admin_settings_page() {
			$module_img_path = plugin_dir_url( __FILE__ ) . 'assets/images/';
			$admin_img_path  = WT_SMARTCOUPON_MAIN_URL . 'admin/images/';

			include plugin_dir_path( __FILE__ ) . 'views/page-content.php';
		}

		/**
		 * Highlight premium upgrade menu
		 *
		 * @since 1.4.4
		 */
		public function highlight_admin_menu() {
			?>
			<style type="text/css">
			#toplevel_page_wt-smart-coupon-for-woo > ul.wp-submenu > li a[href="admin.php?page=premium_upgrade"]{ background:#45b680; color:#fff; }
			</style>
			<?php
		}
	}
	/* Premium upsell module disabled for The Top Percentile build. */
}