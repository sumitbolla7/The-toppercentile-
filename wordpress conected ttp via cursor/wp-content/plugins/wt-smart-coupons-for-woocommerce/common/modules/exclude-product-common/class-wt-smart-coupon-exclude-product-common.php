<?php
/**
 * Exclude Product from coupon common section
 *
 * @link http://www.thetoppercentile.com
 * @since 2.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Exclude_Product_Common' ) ) {

	/**
	 * Exclude Product Common Class
	 * Exclude product from coupon discount if exclude option is enabled in product edit page
	 *
	 * @since 2.0.0
	 */
	class Wt_Smart_Coupon_Exclude_Product_Common {

		/**
		 *  Module name
		 *
		 *  @var string $module_base module name
		 */
		public $module_base = 'exclude_product';

		/**
		 *  Module Id
		 *
		 *  @var string $module_id module id
		 */
		public $module_id = '';

		/**
		 *  Static Module Id
		 *
		 *  @var string $module_id_static module id
		 */
		public static $module_id_static = '';

		/**
		 *  Class instance
		 *
		 *  @var null|object $instance instance of class or null
		 */
		private static $instance = null;

		/**
		 *  Disabled products
		 *
		 *  @var array $disabled_products_option To store disabled products from option.
		 */
		protected static $disabled_products_option = array();

		/**
		 * Constructor function of the class
		 *
		 * @since 2.0.0
		 */
		public function __construct() {

			$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;
		}


		/**
		 * Get Instance
		 *
		 * @since 2.0.0
		 * @return object Class instance
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new Wt_Smart_Coupon_Exclude_Product_Common();
			}
			return self::$instance;
		}

		/**
		 * To get disabled products
		 *
		 * @since 2.0.0
		 * @return array Disabled products.
		 */
		public static function get_disabled_product() {
			if ( empty( self::$disabled_products_option ) ) {
				self::$disabled_products_option = get_option( 'wt_disabled_product_for_coupons', array() );
			}
			return self::$disabled_products_option;
		}

		/**
		 * Function for update all disabled product
		 *
		 * @since 1.1.1
		 * @since 2.0.0 Moved to common module.
		 * @param array $products Array of product ids to be disabled for coupons.
		 */
		public static function set_disabled_products( $products ) {

			update_option( 'wt_disabled_product_for_coupons', $products );
			self::$disabled_products_option = $products;
		}
	}
}

Wt_Smart_Coupon_Exclude_Product_Common::get_instance();
