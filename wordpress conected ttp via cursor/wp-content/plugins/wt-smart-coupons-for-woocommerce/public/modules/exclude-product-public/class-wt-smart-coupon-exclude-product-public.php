<?php
/**
 * Exclude Product from coupon public section
 *
 * @link http://www.thetoppercentile.com
 *
 * @since 1.1.1
 * @since 2.0.0 Moved from admin module to public module.
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Exclude_Product_Common' ) ) {
	return;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Exclude_Product_Public' ) ) {

	/**
	 * Exclude Product Public Class
	 * Exclude product from coupon discount if exclude option is enabled in product edit page.
	 *
	 * @since 1.1.1
	 * @since 2.0.0 Moved from admin module to public module.
	 */
	class Wt_Smart_Coupon_Exclude_Product_Public extends Wt_Smart_Coupon_Exclude_Product_Common {


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
		 * @since 2.0.0
		 */
		public function __construct() {
			$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'set_coupon_validity_for_excluded_products' ), 12, 2 );
			add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'zero_discount_for_excluded_products' ), 12, 5 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'set_fixed_cart_not_valid_for_excluded_products' ), 10, 2 );
		}

		/**
		 * Get Instance
		 *
		 * @since 2.0.0
		 * @return object Class instance
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new Wt_Smart_Coupon_Exclude_Product_Public();
			}
			return self::$instance;
		}

		/**
		 * Set coupon validity for excluded products.
		 * If product is excluded from coupon then set coupon validity for that product to false.
		 *
		 * @since 2.0.0
		 * @param  bool   $valid            Coupon validity for product.
		 * @param  object $product          Product object.
		 * @return bool                     Coupon validity.
		 */
		public static function set_coupon_validity_for_excluded_products( $valid, $product ) {
			if ( ! $valid ) {
				return $valid;
			}

			$disabled_products = self::get_disabled_product();

			if ( ! is_array( $disabled_products ) || 0 >= count( $disabled_products ) ) {
				return $valid;
			}

			$parent_id = $product->get_parent_id();

			if ( in_array( $product->get_id(), $disabled_products, true ) || in_array( $parent_id, $disabled_products, true ) ) {
				$valid = false;
			}

			return $valid;
		}

		/**
		 * Add zero discount for excluded products
		 *
		 * @since 2.0.0
		 * @param  float $discount           Discount amount.
		 * @param  float $discounting_amount Discounting amount.
		 * @param  array $cart_item          Cart item.
		 * @return float                      Discount amount.
		 */
		public static function zero_discount_for_excluded_products( $discount, $discounting_amount, $cart_item ) {
			$disabled_products = self::get_disabled_product();

			if ( ! is_array( $disabled_products ) || 0 >= count( $disabled_products ) ) {
				return $discount;
			}

			$product_id = 0 < $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$parent_id  = 0 < $cart_item['variation_id'] ? $cart_item['product_id'] : 0;

			if ( in_array( $product_id, $disabled_products, true ) || in_array( $parent_id, $disabled_products, true ) ) {
				return 0;
			}
			return $discount;
		}

		/**
		 * Set fixed cart not valid for excluded products.
		 * If any product is excluded from coupon then set fixed cart not valid.
		 *
		 * @since 2.0.0
		 * @param  bool   $valid            Coupon validity.
		 * @param  object $coupon           Coupon object.
		 * @return bool                     Coupon validity.
		 * @throws Exception                Throw exception if exclude product in cart and coupon is fixed cart.
		 */
		public static function set_fixed_cart_not_valid_for_excluded_products( $valid, $coupon ) {

			if ( ! $valid ) {
				return $valid;
			}

			/**
			 * Filter to alter coupon validity on cart discount coupons.
			 *
			 * @since 2.0.0
			 * @param bool   $valid    Coupon validity.
			 * @param object $coupon   Coupon object.
			 * @return bool            Coupon validity.
			 */
			if ( 'fixed_cart' === $coupon->get_discount_type() && apply_filters( 'wbte_sc_alter_coupon_valid_on_cart_discount_coupons', true, $coupon->get_id() ) ) {
				$disabled_products = self::get_disabled_product();
				if ( is_array( $disabled_products ) && 0 < count( $disabled_products ) ) {
					$cart = WC()->cart->get_cart();
					foreach ( $cart as $cart_item ) {
						$product_id = 0 < $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
						$parent_id  = 0 < $cart_item['variation_id'] ? $cart_item['product_id'] : 0;
						if ( in_array( $product_id, $disabled_products, true ) || in_array( $parent_id, $disabled_products, true ) ) {
							$valid = false;
							break;
						}
					}
				}
			}

			if ( ! $valid ) {
				throw new Exception( esc_html__( 'Sorry, this coupon is not applicable for selected products.', 'thetoppercentile-coupons' ), 109 );
			}

			return $valid;
		}
	}
}
Wt_Smart_Coupon_Exclude_Product_Public::get_instance();
