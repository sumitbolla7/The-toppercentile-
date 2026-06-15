<?php
/**
 * Option for excluding a product from applying coupon
 *
 * @link       http://www.thetoppercentile.com
 * @since      1.0.0
 * @since      2.0.0 Converted to module
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/admin/exclude-product
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Exclude_Product_Common' ) ) {
	return;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Exclude_Product_Admin' ) ) {


	/**
	 * Exclude Product Admin Class
	 * Exclude product from coupon discount if exclude option is enabled in product edit page.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Moved to module.
	 */
	class Wt_Smart_Coupon_Exclude_Product_Admin extends Wt_Smart_Coupon_Exclude_Product_Common {

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
		 * Constructor function of the class
		 *
		 * @since 1.1.1
		 */
		public function __construct() {

			$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_exclude_product_check_box' ) );

			add_action( 'woocommerce_process_product_meta', array( $this, 'save_exclude_product_data' ), 10, 1 );
		}

		/**
		 * Get Instance
		 *
		 * @since 2.0.0
		 * @return object Class instance
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new Wt_Smart_Coupon_Exclude_Product_Admin();
			}
			return self::$instance;
		}

		/**
		 * Add disabled product checkbox
		 *
		 * @since 1.1.1
		 */
		public static function add_exclude_product_check_box() {

			echo '<div class="wt-exclude-product-from-coupon">';
			woocommerce_wp_checkbox(
				array(
					'id'          => '_wt_disabled_for_coupons',
					'label'       => __( 'Exclude from coupons', 'thetoppercentile-coupons' ),
					'description' => __( 'Exclude this product from coupon discounts', 'thetoppercentile-coupons' ),
					'desc_tip'    => 'true',
				)
			);

			echo '</div>';
		}

		/**
		 * Save Disabled Product meta
		 *
		 * @since 1.1.1
		 * @param int $post_id Product id.
		 */
		public static function save_exclude_product_data( $post_id ) {
			if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
				return;
			}

			$meta_disabled    = get_post_meta( $post_id, '_wt_disabled_for_coupons', true );
			$current_disabled = isset( $_POST['_wt_disabled_for_coupons'] ) ? 'yes' : 'no';

			if ( empty( $meta_disabled ) && 'no' === $current_disabled ) {
				return;
			}

			$disabled_products = self::get_disabled_product();
			if ( empty( $disabled_products ) ) {
				$disabled_products = array();
			}

			if ( 'yes' === $current_disabled ) {
				$disabled_products[] = $post_id;
				$disabled_products   = array_unique( $disabled_products );
			} else {
				// Remove product ID if it exists in the list.
				$key = array_search( $post_id, $disabled_products, true );
				if ( false !== $key ) {
					unset( $disabled_products[ $key ] );
				}
			}

			update_post_meta( $post_id, '_wt_disabled_for_coupons', $current_disabled );
			self::set_disabled_products( $disabled_products );
		}
	}
}

Wt_Smart_Coupon_Exclude_Product_Admin::get_instance();
