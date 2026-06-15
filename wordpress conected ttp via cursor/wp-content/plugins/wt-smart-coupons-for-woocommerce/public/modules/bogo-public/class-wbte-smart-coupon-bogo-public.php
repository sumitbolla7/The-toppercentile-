<?php
/**
 * BOGO public section
 *
 * @since 2.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wbte_Smart_Coupon_Bogo_Common' ) ) {
	return;
}

/**
 * The public functionality of revamped BOGO module.
 *
 * @since 2.0.0
 */
class Wbte_Smart_Coupon_Bogo_Public extends Wbte_Smart_Coupon_Bogo_Common {

	/**
	 *  Module name
	 *
	 *  @var string $module_base module name
	 */
	public $module_base = 'bogo';

	/**
	 *  Module Id
	 *
	 *  @var string $module_id module id
	 */
	public $module_id = '';

	/**
	 *  Module static id
	 *
	 *  @var string $module_id_static module static id
	 */
	public static $module_id_static = '';

	/**
	 *  Class instance
	 *
	 *  @var null|object $instance instance of class or null
	 */
	private static $instance = null;

	/**
	 * To store discount applied for a product
	 * Array key is coupon code and value is array of product id and discount amount.
	 *
	 * @var array $bogo_discount_amount_for_products array of discount amount for each product.
	 */
	public static $bogo_discount_amount_for_products = array();

	/**
	 * To store total BOGO discount amount.
	 * Array key is coupon code, and value is total discount amount.
	 *
	 * @var array $bogo_discounts array of total discount amount for each coupon
	 */
	public static $bogo_discounts = array();

	/**
	 * To store balance amount available for discounting with normal coupons.
	 * eg structure:
	 *      array(
	 *          'cart_item_key' => 17.99
	 *      )
	 *
	 * @var array $giveaway_discounted_amount
	 */
	public static $giveaway_discounted_amount = array();

	/**
	 *  To check if the free product quantity is updating.
	 *
	 *  @var boolean $free_product_qty_updating
	 */
	public static $free_product_qty_updating = false;

	/**
	 * To store the last successful output of bogo products display.
	 *
	 * @var string $last_successful_output_bogo_products
	 */
	private static $last_successful_output_bogo_products = '';

	/**
	 * To store the bogo restricted products and categories.
	 * eg structure:
	 *      array(
	 *          'coupon_id' => array(
	 *              'product_ids' => array( 1, 2, 3 ),
	 *              'exclude_product_ids' => array( 4, 5, 6 ),
	 *          )
	 *      )
	 *
	 * @var array $bogo_products
	 */
	private static $bogo_products = array();

	/**
	 * To store the normal discounted amount for each coupon.
	 * eg structure:
	 *      array(
	 *          'coupon_id' => array(
	 *              'cart_item_key' => 10.00
	 *          )
	 *      )
	 *
	 * @var array $normal_discounted_amount
	 */
	private static $normal_discounted_amount = array();

	/**
	 * Constructor function of the class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		/**
		 *  Ajax hooks
		 */
		$this->hooks_ajax();

		/**
		 *
		 * Action/processing hooks
		 */
		$this->hooks_actions_and_processing();

		/**
		 *  Display hooks
		 */
		$this->hooks_display();

		/**
		 *
		 * Value update/calculation hooks
		 */
		$this->hooks_calc_and_update();

		/**
		 *
		 * Other hooks
		 */
		$this->hooks_others();
	}

	/**
	 *  This function lists all ajax hooks.
	 *
	 *  @since 2.0.0
	 */
	public function hooks_ajax() {
		// Ajax hook to return variation ID on giveaway product attribute change.
		add_action( 'wc_ajax_update_variation_id_on_choose', array( $this, 'ajax_find_matching_product_variation_id' ) );

		add_action( 'wc_ajax_wbte_choose_multiple_free_products', array( $this, 'add_choosed_free_products_to_cart' ) );
	}

	/**
	 *  This function lists all hooks related to action/processing.
	 *
	 *  @since 2.0.0
	 */
	public function hooks_actions_and_processing() {

		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'is_coupon_valid' ), 10, 2 );

		add_action( 'woocommerce_applied_coupon', array( $this, 'add_free_product_into_cart' ) );

		add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'make_product_valid_for_bogo' ), 10, 3 );

		add_action( 'template_redirect', array( $this, 'check_any_free_products_without_coupon' ), 15 );

		add_action( 'woocommerce_removed_coupon', array( $this, 'remove_free_product_from_cart' ) );

		add_action( 'woocommerce_add_to_cart', array( $this, 'check_and_add_giveaway_on_add_to_cart' ), 111, 6 );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'check_to_add_giveaway' ), 111, 4 );

		add_action( 'woocommerce_cart_item_removed', array( $this, 'update_cart_giveaway_count_on_item_removed' ), 111, 2 );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'prevent_free_product_quantity_update' ), 999, 4 );
	}

	/**
	 *  This function lists all hooks related to display.
	 *
	 *  @since 2.0.0
	 */
	public function hooks_display() {

		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'campaign_name_instead_code' ), 10, 2 );

		add_filter( 'wt_smart_coupon_meta_data', array( $this, 'alter_coupon_title_text' ), 10, 2 );

		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'alter_cart_item_price' ), 1000, 2 );

		/* Display giveaway products in the cart page */
		add_action( 'template_redirect', array( $this, 'add_giveaway_products_with_coupon' ), 16 );

		add_action( 'woocommerce_after_cart_item_name', array( $this, 'display_giveaway_product_description' ) );

		add_filter( 'wbte_sc_alter_blocks_data', array( $this, 'add_blocks_data' ) );

		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'unset_free_product_order_item_meta_data' ), 10, 2 );

		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'update_cart_item_quantity_field' ), 5, 3 );

		add_filter( 'woocommerce_coupon_message', array( $this, 'alter_bogo_applied_message' ), 11, 3 );
	}

	/**
	 *  This function lists all hooks related to value updates/calculation.
	 *
	 *  @since 2.0.0
	 */
	public function hooks_calc_and_update() {

		// Update gift item details as order item meta when creating an order.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_free_product_details_into_order' ), 10, 3 );

		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'get_giveaway_discount_amount' ), 10, 5 );

		add_filter( 'woocommerce_coupon_get_discount_amount', array( $this, 'alter_discount_amount_for_giveaway_products' ), 9, 5 );

		add_filter( 'woocommerce_coupon_validate_minimum_amount', array( $this, 'alter_coupon_validate_minimum_amount' ), 10, 3 );
	}

	/**
	 *  This function lists all hooks other than above list
	 *
	 *  @since 2.0.0
	 */
	public function hooks_others() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_filter( 'wt_sc_blocks_register', array( $this, 'register_blocks' ) );
	}

	/**
	 *  Add required scripts/styles for public side BOGO functionality.
	 *
	 *  @since 2.0.0
	 */
	public function enqueue_scripts() {
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			wp_enqueue_style( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/style.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION );
			wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/script.js', array( 'jquery' ), WEBTOFFEE_SMARTCOUPON_VERSION, false );
		}
	}

	/**
	 * Get Instance
	 *
	 * @since 2.0.0
	 * @return object Class instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wbte_Smart_Coupon_Bogo_Public();
		}
		return self::$instance;
	}

	/**
	 * Check applied BOGO coupon is valid or not.
	 * Check the cart amount and quantity against the coupon's minimum and maximum restrictions. Only consider products applicable for the coupon when performing the check. If there are no restrictions, include all products (except free products for both scenarios).
	 *
	 * @since 2.0.0
	 *
	 * @param  boolean $valid  Current status of coupon.
	 * @param  object  $coupon Coupon object.
	 * @return boolean     Return true if coupon is valid, otherwise false
	 * @throws Exception   Throws exception if the cart does not meet the required conditions for the coupon.
	 */
	public static function is_coupon_valid( $valid, $coupon ) {

		// If coupon is not valid or not a BOGO coupon, return the current status.
		if ( ! $valid || ! self::is_new_bogo_activated() || ! self::is_bogo( $coupon->get_id() ) ) {
			return $valid;
		}

		// If BOGO not created from SC, return false.
		if ( ! get_post_meta( $coupon->get_id(), 'wbte_sc_bogo_created_on_sc_bogo', true ) ) {
			return false;
		}

		$coupon_id           = $coupon->get_id();
		$applicable_products = array();

		// $applicable_products is reference arguments for the below function.
		if ( ! self::validate_coupon_on_products_categories( $coupon_id, $applicable_products ) ) {
			throw new Exception( esc_html__( 'Sorry, this coupon is not applicable to selected products.', 'thetoppercentile-coupons' ), 109 );
		}

		$cart_amount = 0;
		$cart_qty    = 0;

		$wbte_sc_min_each_qty = max( 1, absint( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_min_qty_each' ) ) );
		$wbte_sc_max_each_qty = self::get_coupon_meta_value( $coupon_id, '_wbte_sc_max_qty_each' );

		foreach ( $applicable_products as $applicable_product ) {

			$item = $applicable_product->object;
			if ( self::is_a_free_item( $item ) || self::is_old_bogo_free_product( $item ) || isset( $item['wt_credit_amount'] ) ) {
				continue;
			}

			$_product_id = $applicable_product->product->get_id();
			$_parent_id  = $applicable_product->product->get_parent_id();

			// Enabled exclude for coupons in product edit page.
			if ( 'yes' === get_post_meta( $_parent_id > 0 ? $_parent_id : $_product_id, '_wt_disabled_for_coupons', true ) ) {
				continue;
			}

			$item_qty   = $item['quantity'];
			$item_price = $item['data']->get_price();

			$item_price   = self::alter_price_for_validation_check( $item_price, $item, $coupon_id );
			$_item_amount = $item_price * $item_qty;

			$cart_amount += $_item_amount;
			$cart_qty    += $item_qty;

			// Min each qty check.
			if ( $wbte_sc_min_each_qty && $item_qty < $wbte_sc_min_each_qty ) {
				// translators: %s: Minimum quantity.
				throw new Exception( sprintf( esc_html__( 'The minimum quantity of each product for this coupon is %s.', 'thetoppercentile-coupons' ), absint( $wbte_sc_min_each_qty ) ), 113 );
			}

			// Max each qty check.
			if ( ! empty( $wbte_sc_max_each_qty ) && $item_qty > $wbte_sc_max_each_qty ) {
				// translators: %s: Maximum quantity.
				throw new Exception( sprintf( esc_html__( 'The maximum quantity of each product for this coupon is %s.', 'thetoppercentile-coupons' ), absint( $wbte_sc_max_each_qty ) ), 114 );
			}
		}

		/**
		 * Filter to alter the cart amount for validation.
		 *
		 * @since 2.0.0
		 * @param float $cart_amount Cart amount.
		 * @param int $coupon_id Coupon id.
		 * @return float Cart amount.
		 */
		$cart_amount = apply_filters( 'wbte_sc_bogo_alter_cart_amount_for_validation', $cart_amount, $coupon_id );

		// Amount checks.
		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount' ), $cart_amount, 'min', false );
		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_amount' ), $cart_amount, 'max', false );

		// Quantity checks.
		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' ), $cart_qty, 'min', true );
		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty' ), $cart_qty, 'max', true );

		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty_add' ), $cart_qty, 'min', true );
		self::check_coupon_min_max_condition( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty_add' ), $cart_qty, 'max', true );

		return $valid;
	}

	/**
	 * Summary of check_coupon_min_max_condition.
	 *
	 * @since 2.0.0
	 * @param int|float $condition   Coupon min/max values of amount, quantity or additional qty.
	 * @param int|float $cart_value  Cart amount, quantity or additional qty.
	 * @param string    $type        Type of check (min/max).
	 * @param bool      $is_qty      True if checking quantity, otherwise false.
	 * @throws \Exception            Throws exception if condition is not met, that is coupon is not valid.
	 */
	private static function check_coupon_min_max_condition( $condition, $cart_value, $type, $is_qty ) {
		if ( 0 < $condition && ( ( 'min' === $type && $cart_value < $condition ) || ( 'max' === $type && $cart_value > $condition ) ) ) {

			$msg = sprintf(
				// translators: 1$s is 'minimum' or 'maximum', 2$s is 'quantity' or 'subtotal', 3$s is min/max values of amount, quantity or additional qty.
				__( 'The %1$s %2$s of matching products for this coupon is %3$s.', 'thetoppercentile-coupons' ),
				'min' === $type ? 'minimum' : 'maximum',
				$is_qty ? 'quantity' : 'subtotal',
				$condition
			);
			throw new Exception( esc_html( $msg ), 112 );
		}
	}

	/**
	 *  Checks the current cart item is a free item. Or a free item under the given coupon code
	 *
	 *  @since 2.0.0
	 *  @param array  $cart_item   Cart item array.
	 *  @param string $coupon_code Coupon code, default is empty.
	 *  @return bool               Return true if the cart item is a free item, otherwise false.
	 */
	public static function is_a_free_item( $cart_item, $coupon_code = '' ) {
		$out = isset( $cart_item['wbte_sc_free_gift_coupon'] ) && isset( $cart_item['wbte_sc_free_product'] ) && 'wbte_sc_giveaway_product' === $cart_item['wbte_sc_free_product'];

		if ( '' !== $coupon_code && $out ) {
			$out = wc_format_coupon_code( $cart_item['wbte_sc_free_gift_coupon'] ) === wc_format_coupon_code( $coupon_code );
		}

		/**
		 * Filter to alter the is free cart item.
		 * For other plugins to confirm their giveaway item.
		 *
		 * @since 2.0.0
		 * @param bool $out Return true if the cart item is a free item, otherwise false.
		 * @param array $cart_item Cart item array.
		 * @param string $coupon_code Coupon code, default is empty.
		 * @return bool Return true if the cart item is a free item, otherwise false.
		 */
		$out = apply_filters( 'wt_sc_alter_is_free_cart_item', $out, $cart_item, $coupon_code ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		return $out;
	}

	/**
	 *  Checks the current cart item is a old bogo free item. Or a free item under the given coupon code
	 *
	 *  @since 2.0.0
	 *  @param array  $cart_item   Cart item array.
	 *  @param string $coupon_code Coupon code, default is empty.
	 *  @return bool               Return true if the cart item is a old bogo free item, otherwise false.
	 */
	private static function is_old_bogo_free_product( $cart_item, $coupon_code = '' ) {

		$out = false;

		if ( class_exists( 'Wt_Smart_Coupon_Giveaway_Product_Public' ) && method_exists( 'Wt_Smart_Coupon_Giveaway_Product_Public', 'is_a_free_item' ) ) {
			$out = Wt_Smart_Coupon_Giveaway_Product_Public::is_a_free_item( $cart_item, $coupon_code ); // For giving compatibility with normal bogo.
		}

		return $out;
	}

	/**
	 * Alter price of product for coupon validation check.
	 * For store credit, price will not get by get_price function, so at that time, take price from cart_item. Also added a hook to add compatibility for other plugins.
	 *
	 * @since 2.0.0
	 * @param  float $item_price Price of the product.
	 * @param  array $item       Cart item data.
	 * @param  int   $coupon_id  Coupon ID.
	 * @return float             Price of the product.
	 */
	private static function alter_price_for_validation_check( $item_price, $item, $coupon_id ) {

		$item_price = empty( $item_price ) && isset( $item['wt_credit_amount'] ) ? $item['wt_credit_amount'] : $item_price;

		/**
		 * Filter to alter the item price for coupon validation.
		 *
		 * @since 2.0.0
		 * @param float $item_price Item price.
		 * @param array $item Cart item data.
		 * @param int $coupon_id Coupon id.
		 * @return float Item price.
		 */
		return (float) apply_filters( 'wbte_sc_bogo_alter_item_price_for_coupon_validation', $item_price, $item, $coupon_id ); // Allow other plugins to alter item price for coupon validation if price not get as expected.
	}

	/**
	 * Validate coupon on products, exclude products restrictions.
	 *
	 * @since 2.0.0
	 * @param int   $coupon_id              Coupon ID.
	 * @param array $applicable_products    Products applicable for the coupon( passed by reference, initially empty array, will be updated from this function ).
	 * @return bool True if coupon is valid, otherwise false
	 */
	public static function validate_coupon_on_products_categories( $coupon_id, &$applicable_products ) {

		$specific_products = self::get_bogo_products( $coupon_id, 'product_ids' );
		$excluded_products = self::get_bogo_products( $coupon_id, 'exclude_product_ids' );

		$_specific_products = $specific_products;

		$cart = self::get_cart_object();
		if ( is_null( $cart ) ) {
			return false;
		}

		$discounts_obj     = new WC_Discounts( $cart );
		$items_to_validate = $discounts_obj->get_items_to_validate();

		if ( empty( $specific_products ) && empty( $excluded_products ) ) {
			$applicable_products = $items_to_validate;
			return true;
		}

		$product_condition = self::get_coupon_meta_value( $coupon_id, '_wt_product_condition' );

		$product_condition_valid = false;

		// Specific products.
		if ( ! empty( $specific_products ) ) {
			foreach ( $items_to_validate as $item ) {
				if ( ! $item->product || self::is_a_free_item( $item->object ) || self::is_old_bogo_free_product( $item->object ) ) {
					continue;
				}

				$product_id = $item->product->get_id();
				$parent_id  = $item->product->get_parent_id();

				// Enabled exclude for coupons in product edit page.
				if ( 'yes' === get_post_meta( $parent_id > 0 ? $parent_id : $product_id, '_wt_disabled_for_coupons', true ) ) {
					continue;
				}

				if ( ( in_array( $product_id, $specific_products, true ) || in_array( $parent_id, $specific_products, true ) ) ) {
					$product_condition_valid = true;
					if ( 'or' === $product_condition ) {
						break;
					}
					$specific_products = array_diff( $specific_products, array( $product_id, $parent_id ) );
				}
			}
			if ( 'and' === $product_condition && ! empty( $specific_products ) ) {
				$product_condition_valid = false;
			}
		}

		// Excluded products.
		if ( ! empty( $excluded_products ) ) {
			foreach ( $items_to_validate as $item ) {
				if ( ! $item->product || self::is_a_free_item( $item->object ) || self::is_old_bogo_free_product( $item->object ) ) {
					continue;
				}

				$product_id = $item->product->get_id();
				$parent_id  = $item->product->get_parent_id();

				if ( in_array( $product_id, $excluded_products, true ) || in_array( $parent_id, $excluded_products, true ) ) {
					$product_condition_valid = false;
					break;
				} else {
					$product_condition_valid = true;
				}
			}
		}

		// To get applicable products.
		$args = array(
			'coupon_products'         => $_specific_products,
			'coupon_exclude_products' => $excluded_products,
		);

		foreach ( $items_to_validate as $key => $item ) {
			if ( ! $item->product || self::is_a_free_item( $item->object ) || self::is_old_bogo_free_product( $item->object ) ) {
				continue;
			}

			// Check if the item is valid based on specific and excluded products/categories.
			if ( self::is_coupon_applicable_product( $item->object, $args ) ) {
				$applicable_products[ $key ] = $item;
			}
		}

		return $product_condition_valid;
	}

	/**
	 * To get cart object
	 *
	 * @since 2.0.0
	 * @return WC_Cart|null   Return cart object if available, otherwise return null.
	 */
	public static function get_cart_object() {

		return ( is_object( WC() ) && isset( WC()->cart ) ) ? WC()->cart : null;
	}

	/**
	 * To check whether the item is applicable for the coupon or not.
	 *
	 * @since 2.0.0
	 * @param array $item Cart item.
	 * @param array $args Coupon arguments( coupon_products, coupon_exclude_products ).
	 * @return bool      True if the item is applicable for the coupon, otherwise false.
	 */
	public static function is_coupon_applicable_product( $item, $args = array() ) {

		$allowed_products  = isset( $args['coupon_products'] ) && is_array( $args['coupon_products'] )
			? $args['coupon_products']
			: array();
		$excluded_products = isset( $args['coupon_exclude_products'] ) && is_array( $args['coupon_exclude_products'] )
			? $args['coupon_exclude_products']
			: array();

		// If no restrictions exist, product is eligible.
		if ( empty( $allowed_products ) && empty( $excluded_products ) ) {
			return true;
		}

		$product_id = 0 < $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
		$parent_id  = 0 < $item['variation_id'] ? $item['product_id'] : 0;

		// Check if coupon is disabled for product in product edit page.
		$check_id = $parent_id > 0 ? $parent_id : $product_id;
		if ( 'yes' === get_post_meta( $check_id, '_wt_disabled_for_coupons', true ) ) {
			return false;
		}

		// Check if product is in allowed list (if restrictions exist).
		$is_allowed = empty( $allowed_products ) ||
					in_array( $product_id, $allowed_products, true ) ||
					in_array( $parent_id, $allowed_products, true );

		// Check if product is in excluded list.
		$is_excluded = ! empty( $excluded_products ) &&
						( in_array( $product_id, $excluded_products, true ) ||
						in_array( $parent_id, $excluded_products, true ) );

		return $is_allowed && ! $is_excluded;
	}

	/**
	 * Display BOGO coupon name instead of code in the cart page coupon section for auto BOGO coupons.
	 *
	 * @since 2.0.0
	 * @param  string    $label  Default coupon label.
	 * @param  WC_Coupon $coupon Coupon object.
	 * @return string            BOGO name if it is auto BOGO coupon, otherwise return the default label.
	 */
	public static function campaign_name_instead_code( $label, $coupon ) {

		if ( self::is_bogo( $coupon->get_id() ) && self::is_auto_bogo( $coupon->get_id() ) ) {
			$label = esc_html__( 'Coupon: ', 'thetoppercentile-coupons' ) . get_post_meta( $coupon->get_id(), 'wbte_sc_bogo_coupon_name', true );
		}
		return $label;
	}

	/**
	 *  Alter coupon block title text.
	 *
	 *  @since  2.0.0
	 *  @param      array  $coupon_data    Coupon data.
	 *  @param      object $coupon         WC_Coupon object.
	 *  @return     array                  $coupon_data
	 */
	public static function alter_coupon_title_text( $coupon_data, $coupon ) {
		$coupon_id = $coupon->get_id();
		if ( self::is_bogo( $coupon_id ) ) {
			$bogo_type       = esc_html__( 'Buy X Get X/Y', 'thetoppercentile-coupons' );
			$discount_text   = '';
			$currency_symbol = get_woocommerce_currency_symbol();
			$discount_type   = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_type' );

			if ( 'wbte_sc_bogo_customer_gets_free' === $discount_type ) {
				$discount_text = __( 'Free', 'thetoppercentile-coupons' );
			} elseif ( 'wbte_sc_bogo_customer_gets_with_perc_discount' === $discount_type ) {
				$discount_perc = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_perc' );

				if ( $discount_perc > 100 ) {
					$discount_perc = 100;
				} elseif ( $discount_perc < 0 ) {
					$discount_perc = 0;
				}

				// translators: %s: Discount percentage.
				$discount_text = sprintf( __( '%s%% off', 'thetoppercentile-coupons' ), $discount_perc );
			} else {
				$discount_price = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_price' );

				// translators: %s: Discount price.
				$discount_text = sprintf( __( '%s off', 'thetoppercentile-coupons' ), $currency_symbol . $discount_price );
			}
			$coupon_data['coupon_amount'] = $discount_text;
			$coupon_data['coupon_type']   = $bogo_type;
		}
		return $coupon_data;
	}

	/**
	 * Add giveaway products to the cart when the coupon is applied.
	 *
	 * @since 2.0.0
	 * @param string $coupon_code Coupon code.
	 */
	public function add_free_product_into_cart( $coupon_code ) {

		$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

		if ( ! $coupon_id || ! self::is_bogo( $coupon_id ) ) {
			return;
		}

		$cart = self::get_cart_object();
		if ( is_null( $cart ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				return;
			}
		}

		$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );

		if ( 'specific_product' === $bogo_customer_gets ) {
			self::process_specific_product_giveaway( $coupon_id );
		}
	}

	/**
	 * Specific product BOGO functionality.
	 *
	 * @since 2.0.0
	 * @param int  $coupon_id    Coupon ID.
	 * @param bool $when_applied If true, it is called when coupon applied, otherwise called when qty updated.
	 */
	public static function process_specific_product_giveaway( $coupon_id, $when_applied = true ) {

		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		$customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		if ( 'specific_product' !== $customer_gets ) {
			return;
		}

		$coupon_code   = wc_get_coupon_code_by_id( $coupon_id );
		$free_products = self::get_giveaway_products( $coupon_id );
		$free_products = self::unset_no_discount_product_from_free_products( $free_products, $coupon_id );
		if ( ! empty( $free_products ) ) {
			$free_products_qty      = self::get_bogo_eligible_qty( $coupon_id );
			$bogo_product_condition = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' );
			if ( $when_applied ) {
				$item_added = false;
				if ( self::is_auto_add_giveaway( $coupon_id, $coupon_code, $free_products ) ) {
					foreach ( $free_products as $item_id ) {
						$_product   = wc_get_product( $item_id );
						$item_price = self::get_product_price( $_product );
						$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $_product );

						// If auto add only full discount is enabled and discount is not 100% discount, then skip adding the product.
						if ( self::is_auto_add_only_full_discount() && $discount !== $item_price ) {
							continue;
						}
						$item_added = self::add_item_to_cart( $item_id, $free_products_qty, $coupon_code );
					}
				} elseif ( 'all' === $bogo_product_condition ) {
					foreach ( $free_products as $item_id ) {
						$_product   = wc_get_product( $item_id );
						$item_price = self::get_product_price( wc_get_product( $item_id ) );
						$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $_product );

						if ( self::is_auto_add_product( $item_id, $coupon_id ) && ! ( self::is_auto_add_only_full_discount() && ( $discount < $item_price ) ) ) {
							$item_added = self::add_item_to_cart( $item_id, $free_products_qty, $coupon_code );
						}
					}
				}

				if ( $item_added ) {
					self::show_product_added_msg( $coupon_id );
				}
			} else {

				if ( ! self::is_user_can_change_free_product_qty( $coupon_id ) ) {
					$old_giveaway_qty = self::get_coupon_giveaway_count_in_cart( $coupon_code );
					if ( $old_giveaway_qty !== $free_products_qty ) {
						self::update_giveaway_cart_qty( $coupon_code, $free_products_qty );
						return;
					}
				}

				$free_prod_qty_in_cart = self::get_coupon_giveaway_count_in_cart( $coupon_code, true );
				if ( 'all' === $bogo_product_condition || 1 === count( $free_products ) ) {
					$free_products_qty *= count( $free_products );

					$cart_items = $cart->get_cart();
					if ( $free_products_qty < $free_prod_qty_in_cart ) { // giveaway qty reduced, so remove the free items from cart.
						self::reduce_free_product_qty( $coupon_code, $free_prod_qty_in_cart - $free_products_qty );
						return;
					}
					foreach ( $cart_items as $cart_item_key => $cart_item ) {
						if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
							$free_qty_ratio  = $cart_item['quantity'] / $free_prod_qty_in_cart;
							$free_qty_to_add = absint( $free_products_qty * $free_qty_ratio );
							if ( 0 < $cart_item['variation_id'] && ! in_array( $cart_item['variation_id'], $free_products, true ) && $free_qty_to_add > $cart_item['quantity'] ) { // If variation product and variation id not in free products( parent is in free products) and qty increased, then skip auto add.
								continue;
							}
							if ( $cart->get_cart_item( $cart_item_key ) ) {
								self::$free_product_qty_updating = true;
								$cart->set_quantity( $cart_item_key, $free_qty_to_add );
								self::$free_product_qty_updating = false;
							}
						}
					}
				} elseif ( 'any' === $bogo_product_condition && $free_products_qty < $free_prod_qty_in_cart ) {
					self::reduce_free_product_qty( $coupon_code, $free_prod_qty_in_cart - $free_products_qty );
				}
			}
		}
	}

	/**
	 *  Get all giveaway product ids for cart operations.
	 *
	 *  @since  2.0.0
	 *  @param  int $post_id   Id of coupon.
	 *  @return array            Array of giveaway product ids. Product ids will be updated to current language product ids if multi language plugin(WPML) is active
	 */
	public static function get_giveaway_products( $post_id ) {
		$free_products          = parent::get_giveaway_products( $post_id );
		$free_products_original = $free_products; // assumes main language product id.

		$multi_lang_obj = Wt_Smart_Coupon_Multilanguage::get_instance();

		if ( $multi_lang_obj->is_multilanguage_plugin_active() ) {
			$out = array();

			foreach ( $free_products as $product_id ) {
				/**
				 *  Take id of product in the current language.
				 *
				 *  @since 2.0.0
				 *  @param  $product_id       Id of product.
				 *  @param  string posttype   Post type of the product. Default: product.
				 *  @param  bool              Return original if no translation found in the current language. Default: false
				 */
				$out[] = apply_filters( 'wpml_object_id', $product_id, 'product', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			}

			$free_products = $out;
		}

		$free_products = array_map( 'intval', $free_products );
		/**
		 *  Alter BOGO product ids for cart (Only applicable for frontend functionalities)
		 *
		 *  @since 2.0.0
		 *  @param  $free_products              int[]       Array of giveaway product ids. Product ids of this array was converted to current language ids if any multi lang plugin(WPML) exists.
		 *  @param  $post_id                    int         Id of coupon.
		 *  @param  $free_products_original     int[]       Array of giveaway product ids. Here the product ids are the ids configured by admin from backend.
		 */
		return apply_filters( 'wt_sc_alter_bogo_giveaway_product_ids_for_cart', $free_products, $post_id, $free_products_original ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Get the quantity of the free product for the given coupon.
	 *
	 * @since  2.0.0
	 * @param  int $coupon_id  Coupon ID.
	 * @return int              The quantity of the free product based on the offer type and conditions.
	 */
	public static function get_bogo_eligible_qty( $coupon_id ) {
		$free_qty          = (int) self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_qty' );
		$apply_offer_times = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_offer' );
		$bogo_triggers     = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_triggers_when' );

		switch ( $apply_offer_times ) {
			case 'wbte_sc_bogo_apply_once':
				return $free_qty;

			case 'wbte_sc_bogo_apply_repeatedly':
				$min_value = ( 'wbte_sc_bogo_triggers_qty' === $bogo_triggers )
					? (int) self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty' )
					: (int) self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_amount' );

				$eligible_value = ( 'wbte_sc_bogo_triggers_qty' === $bogo_triggers )
					? self::get_coupon_eligible_cart_amount_qty( $coupon_id, 'qty' )
					: self::get_coupon_eligible_cart_amount_qty( $coupon_id, 'amount' );

				$min_value = ( 0 >= $min_value ) ? 1 : $min_value;
				$frequency = max( 1, (int) ( $eligible_value / $min_value ) );

				return $frequency * $free_qty;

			default:
				return $free_qty;
		}
	}

	/**
	 * To get coupon eligible cart amount or quantity.
	 *
	 * @since 2.0.0
	 *
	 * @param  int    $coupon_id          Id of the coupon.
	 * @param  string $type               Type of the return value. 'qty' or 'amount'.
	 * @return int      $eligible_count     Return eligible amount or quantity by iterating through cart items.
	 */
	public static function get_coupon_eligible_cart_amount_qty( $coupon_id, $type ) {

		$cart           = self::get_cart_object();
		$eligible_count = 0;
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $eligible_count;
		}

		$coupon_products          = self::get_bogo_products( $coupon_id, 'product_ids' );
		$coupon_excluded_products = self::get_bogo_products( $coupon_id, 'exclude_product_ids' );

		// Assigning $args before for loop to avoid multiple time fetching restriction data.
		$args = array(
			'coupon_products'         => $coupon_products,
			'coupon_exclude_products' => $coupon_excluded_products,
		);

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item ) || self::is_old_bogo_free_product( $cart_item ) ) {
				continue;
			}
			if ( ( empty( $coupon_products ) && empty( $coupon_excluded_products ) ) || self::is_coupon_applicable_product( $cart_item, $args ) ) {

				if ( 'qty' === $type ) {
					$eligible_count += $cart_item['quantity'];
				} elseif ( 'amount' === $type ) {
					$eligible_count += $cart_item['data']->get_price() * $cart_item['quantity'];
				}
			}
		}
		return $eligible_count;
	}

	/**
	 *  Is automatically add giveaway products to cart.
	 *
	 *  @param  int    $coupon_id              Id of coupon.
	 *  @param  string $coupon_code            Coupon code.
	 *  @param  array  $free_products          Available free product ids.
	 *  @return bool                           Is auto add or not.
	 */
	private static function is_auto_add_giveaway( $coupon_id, $coupon_code, $free_products ) {

		$customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
		/**
		 *  Only applicable for `specific_product`
		 */
		if ( 'specific_product' !== $customer_gets ) {
			return false;
		}

		$bogo_product_condition = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' );

		/**
		 *  `or(any)` condition.
		 */
		if ( 'any' === $bogo_product_condition ) {
			if ( 1 === count( $free_products ) && self::is_auto_add_product( $free_products[0], $coupon_id ) ) {
				return true;
			}

			return false;
		}

		foreach ( $free_products as $free_product_id ) {
			if ( ! self::is_auto_add_product( $free_product_id, $coupon_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * To check whether the product is able to be added automatically to the cart.
	 *
	 * @since 2.0.0
	 * @param int $product_id  Product id.
	 * @param int $coupon_id   Coupon id.
	 * @return bool            True if product is able to be added automatically to the cart, otherwise false.
	 */
	public static function is_auto_add_product( $product_id, $coupon_id ) {

		$free_product = wc_get_product( $product_id );
		if ( ! self::is_purchasable( $free_product ) ) {
			return false;
		}

		/**
		 *  `specific_product` BOGO
		 */
		if ( 'variable' === $free_product->get_type() ) {
			return false;
		}

		/**
		 *  Alter the product types for auto add.
		 *
		 *  @since 2.0.0
		 *  @param string[]     Product types. Default: array( `simple`, `variation` ).
		 *  @param string       Coupon code.
		 */
		$alter_allowed_product_types_for_auto_add = (array) apply_filters( 'wbte_sc_alter_allowed_product_types_for_auto_add', array( 'simple', 'variation' ), $coupon_id );

		/**
		 *  Variation product in `same_product_in_the_cart` BOGO
		 */
		if ( ! in_array( $free_product->get_type(), $alter_allowed_product_types_for_auto_add, true ) ) {
			return false;
		}

		/**
		 *  Variation product in `specific_product` non without attributes
		 */
		if ( 'variation' === $free_product->get_type() ) {
			foreach ( $free_product->get_variation_attributes() as $attribute_name => $options ) {
				if ( '' === $options ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 *  Checks the product purchasable or not.
	 *  If varaible product, checks any of the variation is purchasable, and returns the variation id if successfull, otherwise false will return
	 *
	 *  @since 2.0.0
	 *  @param  Wc_Product $_product  Product object.
	 *  @return bool|int               Return false if not purchasable, otherwise return variation id if successfull
	 */
	public static function is_purchasable( $_product ) {
		if ( is_int( $_product ) ) {
			$_product = wc_get_product( $_product );
		}

		if ( ! $_product ) {
			return false;
		}

		if ( $_product->is_type( 'variable' ) ) {
			$variations = $_product->get_available_variations();

			if ( empty( $variations ) ) {
				return false;
			}

			foreach ( $variations as $variation ) {
				$variation_product = wc_get_product( $variation['variation_id'] );

				if ( self::is_purchasable( $variation_product ) ) {
					return $variation['variation_id'];
				}
			}

			return false;
		}

		if ( ! $_product->has_enough_stock( 1 ) ) {
			if ( 0 === $_product->get_stock_quantity() ) {
				return false;
			}
		}

		return $_product->is_purchasable();
	}

	/**
	 *  To get the available discount for the giveaway product.
	 *  If product is on sale, then sale price will be considered, otherwise regular price. If both are empty, then get_price will be considered. A filter hook is also available to alter the price.
	 *
	 *  @since  2.0.0
	 *  @param  object $product    Product object.
	 *  @return float   Discount amount
	 */
	public static function get_product_price( $product ) {

		$product_price = 0.0;

		if ( $product instanceof WC_Product ) {
			$product_price = $product->is_on_sale() ? $product->get_sale_price() : $product->get_regular_price();

			if ( empty( $product_price ) ) {
				$product_price = $product->get_price();
			}
		}

		$product_price = (float) $product_price;

		/**
		 * Filter to alter the giveaway product price.
		 *
		 * @since 2.0.0
		 * @param float $product_price Product price.
		 * @param object $product Product object.
		 * @return float Product price.
		 */
		return (float) apply_filters( 'wt_sc_alter_giveaway_product_price', $product_price, $product ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * To get discount amount for giveaway product with the given coupon.
	 * Also store the discount amount in a static variable to avoid multiple calculations.
	 *
	 * @since 2.0.0
	 * @param  int    $coupon_id Coupon id.
	 * @param  object $product   Product object.
	 * @return float             Discount amount.
	 */
	public static function get_available_discount_for_giveaway_product( $coupon_id, $product ) {
		if ( ! $product ) {
			return 0;
		}
		$product_id = $product->get_id();

		// Check if the discount is already calculated for this product and coupon.
		if ( isset( self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ] ) ) {
			return self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ];
		}

		$item_price     = self::get_product_price( $product );
		$discount_price = 0.0;
		$coupon         = new WC_Coupon( $coupon_id );
		if ( ! $coupon || ! self::is_bogo( $coupon_id ) ) {
			return $discount_price;
		}

		$discount_type = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_type' );

		if ( 'wbte_sc_bogo_customer_gets_free' === $discount_type ) {
			$discount_price = $item_price;
		} elseif ( 'wbte_sc_bogo_customer_gets_with_perc_discount' === $discount_type ) {
			$discount_perc = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_perc' );

			if ( $discount_perc > 100 ) {
				$discount_perc = 100;
			} elseif ( $discount_perc < 0 ) {
				$discount_perc = 0;
			}

			$discount_price = ( $item_price * $discount_perc ) / 100;
		} else {
			$discount_price = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_price' );

			if ( $discount_price >= $item_price ) {
				$discount_price = $item_price;
			}
		}

		if ( ! isset( self::$bogo_discount_amount_for_products[ $coupon_id ] ) ) {
			self::$bogo_discount_amount_for_products[ $coupon_id ] = array();
		}

		$discount_price = \Automattic\WooCommerce\Utilities\NumberUtil::round( $discount_price, wc_get_price_decimals() );
		self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ] = $discount_price;
		return (float) $discount_price;
	}

	/**
	 * To check whether automatically add only full discount products.
	 * This value is set in the BOGO general settings.
	 *
	 * @since 2.0.0
	 * @return bool True if automatically add only full discount products, otherwise false.
	 */
	public static function is_auto_add_only_full_discount() {
		return 'wbte_sc_bogo_auto_add_full_giveaway' === self::get_general_settings_value( 'wbte_sc_bogo_auto_add_giveaway' );
	}

	/**
	 * To check whether the free product is full giveaway or not. That is, 100% discount is applied.
	 *
	 * @since 2.0.0
	 * @param int $coupon_id Coupon ID.
	 * @param int $item_id   Product ID or Variation ID which the discount is applied.
	 * @return bool          True if full giveaway, otherwise false.
	 */
	public static function is_full_giveaway( $coupon_id, $item_id ) {
		$product = wc_get_product( $item_id );
		if ( ! $product ) {
			return false;
		}
		$discount      = self::get_available_discount_for_giveaway_product( $coupon_id, $product );
		$product_price = $product->get_price();

		return $discount >= $product_price;
	}

	/**
	 *  Giveaway add to cart function
	 *
	 *  @since 2.0.0
	 *  @param  int    $item_id        Product/variation id.
	 *  @param  int    $quantity       Quantity.
	 *  @param  string $coupon_code    Coupon code.
	 *  @param  array  $args           Extra args [Optional].
	 *  @return bool|string            Return cart_item_key if successfull, otherwise false
	 */
	private static function add_item_to_cart( $item_id, $quantity, $coupon_code, $args = array() ) {
		$product = wc_get_product( $item_id );
		if ( $product ) {
			if ( ! self::is_purchasable( $product ) ) {
				return false;
			}
			if ( 'variable' === $product->get_type() ) {
				return false; /* not possible to add variable parent  */
			}

			if ( ! $product->has_enough_stock( $quantity ) ) {
				$quantity = $product->get_stock_quantity();
				if ( 0 === $quantity ) {
					return false;
				}
			}

			$variation_id = 0;
			$product_id   = $item_id;
			$variation    = $args['variation_attributes'] ?? array();

			if ( $product && 'variation' === $product->get_type() ) {
				$variation_id = $product_id;
				$product_id   = $product->get_parent_id();

				if ( empty( $variation ) ) {
					$variation = Wt_Smart_Coupon_Security_Helper::sanitize_item( isset( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : array(), 'text_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Already sanitized with helper function.
					$variation = empty( $variation ) ? array() : $variation;
				}

				if ( empty( $variation ) ) {
					$variation_attributes = $product->get_variation_attributes();

					foreach ( $variation_attributes as $key => $value ) {
						if ( empty( $value ) ) {
							$variation[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
						}
					}
				}

				foreach ( $variation as $attribute_name => $options ) {
					if ( '' === $options ) {
						return false;
					}
				}
			}

			$coupon_id = wc_get_coupon_id_by_code( $coupon_code );
			$discount  = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $product );

			$cart_item_data = array(
				'wbte_sc_free_product'     => 'wbte_sc_giveaway_product',
				'wbte_sc_free_gift_coupon' => wc_format_coupon_code( $coupon_code ),
				'wbte_sc_bogo_discount'    => $discount,
			);

			// Extra cart item data.
			if ( isset( $args['cart_item_data'] ) && is_array( $args['cart_item_data'] ) ) {
				$cart_item_data = array_merge( $cart_item_data, $args['cart_item_data'] );
			}

			$old_cart_item_data = $args['old_cart_item_data'] ?? array();
			/**
			 * Filter to alter the cart item data before adding to cart.
			 *
			 * @since 2.0.0
			 * @param array $cart_item_data Cart item data.
			 * @param int $product_id Product id.
			 * @param int $variation_id Variation id.
			 * @param int $quantity Quantity.
			 * @param array $old_cart_item_data Old cart item data.
			 * @return array Cart item data.
			 */
			$cart_item_data = apply_filters( 'wt_sc_alter_giveaway_cart_item_data_before_add_to_cart', $cart_item_data, $product_id, $variation_id, $quantity, $old_cart_item_data ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			self::$free_product_qty_updating = true;
			$cart_item_key                   = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation, $cart_item_data );
			self::$free_product_qty_updating = false;
			return $cart_item_key;
		}
		return false;
	}

	/**
	 * To show product added message when a product added to cart as a giveaway.
	 * If placeholder {bogo_title} available in the message, then replace it with the BOGO title.
	 *
	 * @since 2.0.0
	 * @param int $coupon_id Coupon id.
	 */
	private static function show_product_added_msg( $coupon_id ) {
		$bogo_title        = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
		$product_added_msg = self::get_general_settings_value( 'wbte_sc_bogo_general_product_added_message' );
		$product_added_msg = str_replace( '{bogo_title}', $bogo_title, $product_added_msg );
		wc_add_notice( $product_added_msg, 'success' );
	}

	/**
	 * To check whether the user can change the quantity of the free product.
	 * Default is true. Customer can change the behavior by using the filter hook.
	 * It is used for 'specific_product' giveaway types, in which giveaway products will list in the cart page.
	 *
	 * @since 2.0.0
	 * @param  int $coupon_id Coupon id.
	 * @return boolean
	 */
	private static function is_user_can_change_free_product_qty( $coupon_id ) {
		/**
		 * Filter to alter the user can change the quantity of the free product.
		 *
		 * @since 2.0.0
		 * @param bool $is_user_can_change_free_product_qty If true, then user can change the quantity of the free product.
		 * @param int $coupon_id Coupon id.
		 * @return bool If true, then user can change the quantity of the free product.
		 */
		return (bool) apply_filters( 'wbte_sc_bogo_user_can_change_free_product_qty', true, $coupon_id );
	}

	/**
	 * To get the quantity of giveaway product in cart for given coupon.
	 * This function is using to get the old giveaway quantity when updating the cart for specific product giveaway.
	 *
	 * @since 2.0.0
	 * @param  string $coupon_code  Coupon code to check whether the free item belongs to this coupon.
	 * @param  bool   $total        If true, return total quantity of giveaway products in cart, otherwise return quantity of one giveaway product.
	 * @return int                  Quantity of giveaway product in cart.
	 */
	private static function get_coupon_giveaway_count_in_cart( $coupon_code, $total = false ) {
		$cart           = self::get_cart_object();
		$giveaway_count = 0;
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $giveaway_count;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				if ( $total ) {
					$giveaway_count += $cart_item['quantity'];
				} else {
					return $cart_item['quantity'];
				}
			}
		}
		return $giveaway_count;
	}

	/**
	 * To update quantity of giveaway product in cart when cart items quantity updated.
	 *
	 * @since 2.0.0
	 * @param string $coupon_code Coupon code to check whether the free item belongs to this coupon.
	 * @param int    $qty         Quantity to update.
	 */
	public static function update_giveaway_cart_qty( $coupon_code, $qty ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				self::$free_product_qty_updating = true;
				$cart->set_quantity( $cart_item_key, $qty );
				self::$free_product_qty_updating = false;
			}
		}
	}

	/**
	 *  Show altered cart item price for giveaway item.
	 *
	 *  @since 2.0.0
	 *  @param  string $price       Cart item price HTML.
	 *  @param  array  $cart_item   Cart item array.
	 *  @return string              Altered cart item price HTML.
	 */
	public static function alter_cart_item_price( $price, $cart_item ) {
		$out = $price;
		if ( self::is_a_free_item( $cart_item ) ) {

			$discount_data = self::calculate_bogo_discount( $cart_item );
			$item_price    = $discount_data['product_price'];
			$discount      = $discount_data['discount'];

			if ( ( $item_price - $discount ) >= $item_price ) {
				return $out;
			}

			$out = '<span>' . wp_kses_post( wc_price( $item_price ) ) . '</span> <br /> <span class="wt_sc_bogo_cart_item_discount">' . esc_html__( 'Discounted price: ', 'thetoppercentile-coupons' ) . wp_kses_post( wc_price( $item_price - $discount ) ) . '</span>';
		}

		return $out;
	}

	/**
	 * Calculate the discount for giveaway item.
	 *
	 * @since 2.0.0  Moved to separate function.
	 * @param  array $cart_item  Cart item.
	 * @return array             Discount data.
	 */
	public static function calculate_bogo_discount( $cart_item ) {
		$out = array(
			'product_price' => 0,
			'discount'      => 0,
		);

		if ( self::is_a_free_item( $cart_item ) ) {

			$coupon_code = isset( $cart_item['wbte_sc_free_gift_coupon'] ) ? wc_format_coupon_code( $cart_item['wbte_sc_free_gift_coupon'] ) : '';
			$coupon_id   = wc_get_coupon_id_by_code( $coupon_code );
			if ( $coupon_id ) {
				$item_id    = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
				$product    = wc_get_product( $item_id );
				$discount   = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $product );
				$item_price = self::get_product_price( $product );

				$qty         = $cart_item['quantity'] ?? 1;
				$item_price *= $qty;
				$discount   *= $qty;

				if ( ! isset( self::$bogo_discounts[ $coupon_code ] ) ) {
					self::$bogo_discounts[ $coupon_code ] = $discount;
				} else {
					self::$bogo_discounts[ $coupon_code ] += $discount;
				}

				if ( ( $item_price - $discount ) >= $item_price ) {
					return $out;
				}

				$out = array(
					'product_price' => $item_price,
					'discount'      => $discount,
				);
			}
		}

		return $out;
	}

	/**
	 * To display free products choosing box in cart page.
	 * Applicable for 'specific_product' BOGO types.
	 * This function will be called on hook 'template_redirect'.
	 *
	 * @since 2.0.0
	 */
	public function add_giveaway_products_with_coupon() {
		$cart = self::get_cart_object();

		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}

		$coupons = $cart->get_applied_coupons();
		$coupons = ! is_array( $coupons ) ? array() : $coupons;

		foreach ( $coupons as $coupon_code ) {

			$coupon_code = wc_format_coupon_code( $coupon_code );
			$coupon      = new WC_Coupon( $coupon_code );

			$coupon_id = $coupon->get_id();

			if ( ! $coupon_id || ! self::is_bogo( $coupon_id ) ) {
				continue;
			}

			$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );

			if ( 'specific_product' === $bogo_customer_gets ) {
				$free_products = self::get_giveaway_products( $coupon_id );

				if ( ! empty( $free_products ) ) {
					add_action( 'woocommerce_after_cart_table', array( $this, 'display_giveaway_products' ), 1 );
				}
			}
		}
	}

	/**
	 * Callback function for displaying giveaway products in the cart page.
	 *
	 * @since 2.0.0
	 */
	public static function display_giveaway_products() {
		$applied_coupons = WC()->cart->applied_coupons;
		if ( empty( $applied_coupons ) ) {
			return;
		}

		$free_products     = array();
		$free_products_qty = array();
		$qty_alter_option  = array();
		foreach ( $applied_coupons as $coupon_code ) {
			$coupon_code = wc_format_coupon_code( $coupon_code );
			$coupon      = new WC_Coupon( $coupon_code );
			if ( ! $coupon ) {
				continue;
			}

			$coupon_id = $coupon->get_id();

			if ( self::is_bogo( $coupon_id ) ) {
				$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );

				if ( 'specific_product' === $bogo_customer_gets ) {
					$bogo_eligible_qty      = self::get_bogo_eligible_qty( $coupon_id );
					$bogo_product_condition = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' );

					$bogo_products = self::get_giveaway_products( $coupon_id );

					$bogo_products = self::unset_no_discount_product_from_free_products( $bogo_products, $coupon_id );

					if ( self::is_user_can_change_free_product_qty( $coupon_id ) ) {

						$qty_alter_option[ $coupon_code ] = true;

						if ( 'all' === $bogo_product_condition ) {
							$bogo_eligible_qty *= count( $bogo_products );
						} else {
							$qty_alter_option[ $coupon_code ] = false;
						}

						$bogo_eligible_qty = self::alter_giveaway_eligible_qty_based_on_cart( $coupon_code, $bogo_eligible_qty );

						if ( 0 >= $bogo_eligible_qty ) {
							$bogo_products = array();
						}
					} else {

						$qty_alter_option[ $coupon_code ] = false;

						$bogo_products = self::alter_free_products_display_arr( $coupon_code, $bogo_products, $bogo_customer_gets, array( 'product_condition' => $bogo_product_condition ) );
					}

					foreach ( $bogo_products as $product_id ) {
						$free_products_qty[ $coupon_code ][ $product_id ] = $bogo_eligible_qty;
					}
					$free_products[ $coupon_code ] = $bogo_products;
				}
			}
		}

		if ( empty( $free_products ) ) {
			return;
		}

		$popup_theme_color = self::get_general_settings_value( 'wbte_sc_bogo_prod_select_theme_color' );

		include_once plugin_dir_path( __FILE__ ) . 'views/-cart-giveaway-products.php';
	}

	/**
	 * If product not have any discount, remove from the free product list
	 *
	 * @since 2.0.0
	 * @param array $free_products  Array of free products id.
	 * @param int   $coupon_id      Coupon id.
	 * @return      array           Updated free products array
	 */
	private static function unset_no_discount_product_from_free_products( $free_products, $coupon_id ) {
		foreach ( $free_products as $key => $product_id ) {
			$_product = wc_get_product( $product_id );
			if ( ! $_product ) {
				unset( $free_products[ $key ] );
				continue;
			}
			$discount = self::$bogo_discount_amount_for_products[ $coupon_id ][ $product_id ] ?? self::get_available_discount_for_giveaway_product( $coupon_id, $_product );
			if ( 0 >= $discount ) {
				unset( $free_products[ $key ] );
			}
		}
		return $free_products;
	}

	/**
	 * Reduce the BOGO eligible quantity based on the cart free items.
	 *
	 * @since 2.0.0
	 * @param  string $coupon_code          Coupon code.
	 * @param  int    $bogo_eligible_qty    BOGO eligible quantity.
	 * @return int                          Updated BOGO eligible quantity
	 */
	public static function alter_giveaway_eligible_qty_based_on_cart( $coupon_code, $bogo_eligible_qty ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $bogo_eligible_qty;
		}

		$cart_items = $cart->get_cart();

		foreach ( $cart_items as $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				$bogo_eligible_qty -= $cart_item['quantity'];
			}
		}
		return $bogo_eligible_qty;
	}

	/**
	 * To alter the free products array based on the cart items.
	 * This array is used to display the free products in the cart page for BOGO type 'specific_product' and 'same_product_in_the_cart'.
	 *
	 * @since 2.0.0
	 * @param string $coupon_code   Coupon code.
	 * @param mixed  $bogo_products  BOGO products array.
	 * @param string $customer_gets Customer gets, specific_product or same_product_in_the_cart.
	 * @param array  $args           Addition arguments.
	 * @return array                Updated free products array
	 */
	public static function alter_free_products_display_arr( $coupon_code, $bogo_products, $customer_gets, $args = array() ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return $bogo_products;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				$item_id = self::prepare_item_id_for_free_products( $cart_item, $bogo_products );

				if ( in_array( $item_id, $bogo_products, true ) ) {

					if ( 'specific_product' === $customer_gets ) {
						$bogo_product_condition = $args['product_condition'] ?? 'all';
						if ( 'all' === $bogo_product_condition ) {
							unset( $bogo_products[ array_search( $item_id, $bogo_products, true ) ] );
						} else {
							$bogo_products = array(); // Reset $bogo_products to an empty array.
							break; // Exit the loop since we don't need to check other items.
						}
					}
				}
			}
		}
		return $bogo_products;
	}

	/**
	 *  Take the giveaway item id based on BOGO configuration.
	 *
	 *  @since  2.0.0
	 *  @param  array $cart_item          Cart item array.
	 *  @param  array $bogo_products      BOGO product array.
	 *  @return int         Item id, variation id when variation is configured as BOGO product, otherwise product id
	 */
	private static function prepare_item_id_for_free_products( $cart_item, $bogo_products ) {
		$item_id = 0;

		if ( 0 < $cart_item['variation_id'] && in_array( $cart_item['variation_id'], $bogo_products, true ) ) {
			$item_id = $cart_item['variation_id'];

		} elseif ( in_array( $cart_item['product_id'], $bogo_products, true ) ) {
			$item_id = $cart_item['product_id'];
		}

		return $item_id;
	}

	/**
	 *  Error/Validation messages when giveaway products are adding to cart.
	 *
	 *  @since 2.0.0
	 *  @param string $reason reason string.
	 *  @param array  $extra_args extra arguments to process the message.
	 *  @param string $coupon_type coupon type.
	 */
	public static function set_add_to_cart_messages( $reason, $extra_args = array(), $coupon_type = null ) {
		$out = '';
		switch ( $reason ) {
			case 'product_id_missing':
			case 'coupon_id_missing':
				$out = __( "Oops! It seems like you've made an invalid request. Please try again.", 'thetoppercentile-coupons' );
				break;
			case 'product_not_added':
				$out = __( 'Failed to add product to cart.', 'thetoppercentile-coupons' );
				break;
			case 'exception_error':
				$out = isset( $extra_args['err_msg'] ) ? $extra_args['err_msg'] : __( 'Failed to add product to cart.', 'thetoppercentile-coupons' );
				break;
		}

		/**
		 * Filter to alter the giveaway add to cart messages.
		 *
		 * @since 2.0.0
		 * @param string $out Message HTML.
		 * @param string $reason Reason string.
		 * @param array $extra_args Extra arguments to process the message.
		 * @param string $coupon_type Coupon type.
		 * @return string Message HTML.
		 */
		$msg = apply_filters( 'wt_sc_alter_giveaway_addtocart_messages', $out, $reason, $extra_args, $coupon_type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( '' !== $msg ) {
			wc_add_notice( $msg, 'error' );
			wc_print_notices();
		}
	}

	/**
	 * Get the discount amount for giveaway item.
	 *
	 * @since 2.0.0
	 * @param  float  $discount           Discount amount.
	 * @param  float  $discounting_amount Discounting amount.
	 * @param  array  $cart_item          Cart item.
	 * @param  bool   $single             If true, then discount amount for single quantity.
	 * @param  object $coupon             Coupon object.
	 * @return float                      Discount amount.
	 */
	public static function get_giveaway_discount_amount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {

		$coupon_id = $coupon->get_id();
		if ( ! self::is_bogo( $coupon_id ) || ! self::is_a_free_item( $cart_item, $coupon->get_code() ) ) {
			return $discount;
		}

		$item_id = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
		if ( isset( self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ] ) ) {
			$discount = self::$bogo_discount_amount_for_products[ $coupon_id ][ $item_id ];
		} else {
			$product  = wc_get_product( $item_id );
			$discount = self::get_available_discount_for_giveaway_product( $coupon_id, $product );
		}
		return $discount;
	}

	/**
	 *  Make the product valid for BOGO.
	 *
	 *  @since    2.0.0
	 *  @param bool       $valid     Is valid or not.
	 *  @param WC_Product $product   Product instance.
	 *  @param WC_Coupon  $coupon    Coupon data.
	 *  @return bool                 If coupon is BOGO, return true. Otherwise return default value.
	 */
	public static function make_product_valid_for_bogo( $valid, $product, $coupon ) {

		if ( self::is_bogo( $coupon->get_id() ) ) {
			return true;
		}

		return $valid;
	}

	/**
	 *  Removes any free products from the cart if their related coupon is not present in the cart
	 *
	 *  @since 2.0.0
	 */
	public static function check_any_free_products_without_coupon() {
		$cart = self::get_cart_object();

		if ( ! is_null( $cart ) && is_object( $cart ) && is_callable( array( $cart, 'is_empty' ) ) && ! $cart->is_empty() ) {
			$coupons    = $cart->get_applied_coupons();
			$cart_items = $cart->get_cart();
			$cart_items = ( isset( $cart_items ) && is_array( $cart_items ) ) ? $cart_items : array();
			foreach ( $cart_items as $cart_item_key => $cart_item ) {
				if ( self::is_a_free_item( $cart_item ) ) {
					if ( ! in_array( wc_format_coupon_code( $cart_item['wbte_sc_free_gift_coupon'] ), $coupons, true ) ) {
						$cart->remove_cart_item( $cart_item_key ); /* remove the free item */
						unset( self::$giveaway_discounted_amount[ $cart_item_key ] );
					}
				}
			}
		}
	}

	/**
	 * Remove free products from cart when coupon removed.
	 *
	 * @since 2.0.0
	 * @param string $coupon_code Coupon code.
	 */
	public static function remove_free_product_from_cart( $coupon_code ) {

		$cart            = WC()->cart;
		$applied_coupons = $cart->get_applied_coupons();
		if ( isset( $coupon_code ) && ! empty( $coupon_code ) && ! in_array( $coupon_code, $applied_coupons, true ) ) {
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
					$cart->remove_cart_item( $cart_item_key );
					unset( self::$giveaway_discounted_amount[ $cart_item_key ] );
				}
			}
		}
	}

	/**
	 * Process BOGO functionalites when a product is added to the cart.
	 *
	 * @since 2.0.0
	 * @param string $cart_item_key Cart item key of added/updated product.
	 * @param int    $product_id       Product ID.
	 * @param int    $quantity         Currenct quantity in the cart.
	 * @param int    $variation_id     Variation ID.
	 * @param array  $variation      Variation data.
	 * @param array  $cart_item_data Cart item data.
	 */
	public static function check_and_add_giveaway_on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		self::check_to_add_giveaway( $cart_item_key, $quantity, 0, WC()->cart );
	}

	/**
	 * Process BOGO functionalites when added/qty updated in the cart.
	 *
	 * @since 2.0.0
	 * @param string $cart_item_key     Cart item key of added/updated product.
	 * @param int    $quantity          Currenct quantity in the cart.
	 * @param int    $old_quantity      Old quantity.
	 * @param object $cart              Cart object.
	 */
	public static function check_to_add_giveaway( $cart_item_key, $quantity, $old_quantity, $cart ) {

		$cart_item_data = $cart->cart_contents[ $cart_item_key ] ?? null;

		if ( is_null( $cart_item_data ) ) {
			return;
		}

		if ( self::is_a_free_item( $cart_item_data ) ) {
			return; /* already a free item so no need to check */
		}

		$cart_coupons = $cart->get_applied_coupons();
		if ( empty( $cart_coupons ) ) {
			return;
		}

		self::process_giveaway_for_coupons( $cart_coupons, $cart_item_key );
	}

	/**
	 * Process BOGO coupons based on the customer gets option when a product added to cart or quantity updated.
	 *
	 * @since 2.0.0
	 * @param array  $coupons       Array of coupon codes.
	 * @param string $cart_item_key Cart item key. Used for same product in cart giveaway.
	 */
	public static function process_giveaway_for_coupons( $coupons, $cart_item_key = '' ) {

		if ( empty( $coupons ) ) {
			return;
		}

		foreach ( $coupons as $coupon_code ) {

			$coupon_code = wc_format_coupon_code( $coupon_code );
			$coupon      = new WC_Coupon( $coupon_code );

			if ( ! $coupon ) {
				continue;
			}

			$coupon_id = $coupon->get_id();

			if ( self::is_bogo( $coupon_id ) ) {

				$bogo_customer_gets = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets' );
				if ( 'specific_product' === $bogo_customer_gets ) {
					self::process_specific_product_giveaway( $coupon_id, false );
				}
			}
		}
	}

	/**
	 * Process BOGO functionality function when a product is removed from the cart.
	 * It will adjust the free product quantity if eligible qty is changed.
	 *
	 * @since 2.0.0
	 * @param string $cart_item_key Cart item key of removed product.
	 * @param object $cart          Cart object.
	 */
	public static function update_cart_giveaway_count_on_item_removed( $cart_item_key, $cart ) {

		if ( empty( $cart ) ) {
			$cart = self::get_cart_object();
			if ( is_null( $cart ) ) {
				return;
			}
		}

		$cart_coupons = $cart->get_applied_coupons();
		if ( empty( $cart_coupons ) ) {
			return;
		}

		self::process_giveaway_for_coupons( $cart_coupons );
	}

	/**
	 *  Ajax action function for getting variation id
	 *
	 *  @since 2.0.0
	 */
	public static function ajax_find_matching_product_variation_id() {
		$out = array(
			'status'     => false,
			'status_msg' => __( 'Invalid request', 'thetoppercentile-coupons' ),
		);

		if ( check_ajax_referer( 'wt_smart_coupons_public', '_wpnonce', false ) ) {
			if ( isset( $_POST['attributes'] ) && isset( $_POST['product'] ) ) {
				$product_id = Wt_Smart_Coupon_Security_Helper::sanitize_item( isset( $_POST['product'] ) ? wp_unslash( $_POST['product'] ) : '', 'int' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized with helper function.
				$attributes = Wt_Smart_Coupon_Security_Helper::sanitize_item( isset( $_POST['attributes'] ) ? wp_unslash( $_POST['attributes'] ) : array(), 'text_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized with helper function.
				if ( '' !== $product_id && ! empty( $attributes ) ) {
					$variation_id = self::find_matching_product_variation_id( $product_id, $attributes );
					$_product     = wc_get_product( $variation_id );

					$image   = $_product ? wp_get_attachment_image_src( $_product->get_image_id(), 'woocommerce_thumbnail' ) : false;
					$img_url = '';
					if ( $image && is_array( $image ) && isset( $image[0] ) ) {
						$img_url = $image[0];
					}

					if ( self::is_purchasable( $_product ) ) {
						$out = array(
							'variation_id' => $variation_id,
							'status'       => true,
							'status_msg'   => __( 'Success', 'thetoppercentile-coupons' ),
							'img_url'      => $img_url,
						);
					} else {
						$out['status_msg'] = esc_html__( 'Sorry! this product is not available for giveaway.', 'thetoppercentile-coupons' );
					}
				}
			}
		}

		echo wp_json_encode( $out );
		wp_die();
	}

	/**
	 * Function for getting variation id from product and selected attributes
	 *
	 * @param int   $product_id Given Product Id.
	 * @param array $attributes Attribute values ad key value pair.
	 * @since 2.0.0
	 */
	public static function find_matching_product_variation_id( $product_id, $attributes ) {
		return ( new \WC_Product_Data_Store_CPT() )->find_matching_product_variation(
			new \WC_Product( $product_id ),
			$attributes
		);
	}

	/**
	 * Action function for displaying description for Giveaway product on cart page
	 *
	 *  @since  2.0.0
	 *  @param  array $cart_item    Cart item array.
	 */
	public static function display_giveaway_product_description( $cart_item ) {

		if ( self::is_a_free_item( $cart_item ) ) { // This is a free item.

			echo wp_kses_post( self::get_product_under_msg( $cart_item ) );
		}
	}

	/**
	 * To get the message to display under the free gift product.
	 * User can customize the message from the BOGO general settings. If msg contains {bogo_title} then it will be replaced with the BOGO coupon title.
	 *
	 * @since   2.0.0
	 * @param   array  $cart_item   Cart line item data.
	 * @param   string $coupon_code Coupon code.
	 * @return  string              Message to display under the free gift product.
	 */
	private static function get_product_under_msg( $cart_item, $coupon_code = '' ) {

		if ( empty( $cart_item ) ) {
			return '';
		}

		$info_text = self::get_general_settings_value( 'wbte_sc_bogo_general_discount_under_product_msg' );
		$coupon_id = wc_get_coupon_id_by_code( $cart_item['wbte_sc_free_gift_coupon'] ?? $coupon_code );

		$bogo_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );

		$info_text = str_replace( '{bogo_title}', $bogo_title, $info_text );

		/**
		 * Filter to alter the giveaway line item text.
		 *
		 * @since 2.0.0
		 * @param string $info_text Message HTML.
		 * @param array $cart_item Cart item.
		 * @return string Message HTML.
		 */
		return apply_filters( 'wt_sc_alter_giveaway_cart_lineitem_text', '<p class="wbte_sc_bogo_msg_under_free_gift">' . $info_text . '</p>', $cart_item ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Add block to the block list
	 *
	 * @since 2.0.0
	 * @param  array $registered_blocks Blocks data array.
	 * @return array Registered blocks data array
	 */
	public static function register_blocks( $registered_blocks ) {

		$registered_blocks['bogo'] = array(
			'block_dir'      => 'bogo',
			'script_handles' => array( 'frontend-js' ),
		);

		return $registered_blocks;
	}

	/**
	 * Add giveaway product msg for block.
	 * Hooked into: wbte_sc_alter_blocks_data
	 *
	 * @since 2.0.0
	 * @param  array $block_data block data array.
	 * @return array             Block data array with added giveaway product msg.
	 */
	public static function add_blocks_data( $block_data ) {

		$cart = self::get_cart_object();
		if ( ! is_null( $cart ) && ! $cart->is_empty() ) {
			$out = array();

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

				if ( self::is_a_free_item( $cart_item ) ) {

					$item_id    = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
					$qty        = $cart_item['quantity'];
					$product    = wc_get_product( $item_id );
					$item_price = self::get_product_price( $product );

					$gift_msg = self::get_product_under_msg( $cart_item );
					if ( ! empty( $gift_msg ) ) {
						$out[ $cart_item_key ] = '<div class="wbte_sc_bogo_bxgx_product_under_msg" >' . $gift_msg . '<p class="wt_sc_bogo_cart_item_discount">' . esc_html__( 'Discounted price: ', 'thetoppercentile-coupons' ) . wp_kses_post( wc_price( ( $item_price - $cart_item['wbte_sc_bogo_discount'] ) * $qty ) ) . '</p></div>';
					}
					continue;
				}
			}

			if ( ! empty( $out ) ) {
				$block_data['cartitem_bogo_text'] = $out;
			}

			/** Giveaway products ================================ */
			$out = '';
			ob_start();
			self::display_giveaway_products();
			$out = ob_get_clean();

			if ( ! empty( $out ) ) {
				self::$last_successful_output_bogo_products = $out;
			}

			// Use last successful output if current call is empty.
			if ( empty( $out ) && ! empty( self::$last_successful_output_bogo_products ) ) {
				$out = self::$last_successful_output_bogo_products;
			}

			$block_data['bogo_products_html'] = $out;

			/** BOGO coupons list for displaying offer title instead of code in block ===== */

			$coupons = $cart->get_applied_coupons();
			$coupons = ! is_array( $coupons ) ? array() : $coupons;

			$bogo_coupons = array();
			foreach ( $coupons as $coupon_code ) {
				$coupon    = new WC_Coupon( $coupon_code );
				$coupon_id = $coupon->get_id();
				if ( self::is_bogo( $coupon_id ) && self::is_auto_bogo( $coupon->get_id() ) ) {
					$bogo_title                   = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
					$bogo_coupons[ $coupon_code ] = $bogo_title;
				}
			}
			$block_data['auto_bogo_coupons'] = $bogo_coupons;
		}
		return $block_data;
	}

	/**
	 * Add Free Prodcut details on cart item list.
	 *
	 * @since 2.0.0
	 * @param object $item          Cart item object.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $values        Cart item data.
	 */
	public static function add_free_product_details_into_order( $item, $cart_item_key, $values ) {
		if ( self::is_a_free_item( $values ) ) {
			$item->add_meta_data( 'wbte_sc_free_product', $values['wbte_sc_free_product'] );
			$item->add_meta_data( 'wbte_sc_free_gift_coupon', $values['wbte_sc_free_gift_coupon'] );
			return;
		}
	}

	/**
	 * Hide free product meta details in order details page.
	 *
	 * @since 2.0.0
	 * @param array  $formatted_meta    Formatted meta data.
	 * @param object $item              Order item object.
	 */
	public static function unset_free_product_order_item_meta_data( $formatted_meta, $item ) {

		foreach ( $formatted_meta as $key => $meta ) {
			if ( in_array( $meta->key, array( 'wbte_sc_free_product', 'wbte_sc_free_gift_coupon' ), true )
			) {
				unset( $formatted_meta[ $key ] );
			}
		}
		return $formatted_meta;
	}

	/**
	 * Alter the discount amount for giveaway item for normal coupons.
	 *
	 * @since 2.0.0
	 * @param  float  $discount           Discount amount.
	 * @param  float  $discounting_amount Discounting amount.
	 * @param  array  $cart_item          Cart item.
	 * @param  bool   $single             If true, then discount amount for single quantity.
	 * @param  object $coupon             Coupon object.
	 * @return float                      Discount amount.
	 */
	public static function alter_discount_amount_for_giveaway_products( $discount, $discounting_amount, $cart_item, $single, $coupon ) {

		$coupon_id = $coupon->get_id();
		if ( self::is_bogo( $coupon_id ) ) {
			return $discount;
		}

		$cart_item_key = $cart_item['key'];

		if ( isset( self::$normal_discounted_amount[ $coupon_id ][ $cart_item_key ] ) ) {
			return self::$normal_discounted_amount[ $coupon_id ][ $cart_item_key ];
		}

		if ( self::is_a_free_item( $cart_item ) ) {

			$free_product_price     = self::get_product_price( $cart_item['data'] );
			$discount_type          = $coupon->get_discount_type();
			$free_product_new_price = ( $free_product_price - $cart_item['wbte_sc_bogo_discount'] ) * $cart_item['quantity'];

			if ( isset( self::$giveaway_discounted_amount[ $cart_item_key ] ) ) {
				$free_product_new_price = self::$giveaway_discounted_amount[ $cart_item_key ];
			}

			switch ( $discount_type ) {
				case 'percent':
					$discounting_perc = ( $discount * 100 ) / $discounting_amount;
					$discount         = $free_product_new_price * ( $discounting_perc / 100 );
					break;

				case 'fixed_cart':
				case 'fixed_product':
					$discount = 0;
					break;

				default:
					// Not changing the discount amount for other discount types, it can be altered with the following filter hook.
					break;
			}

			/**
			 * Filter to alter the discount amount for giveaway products.
			 *
			 * @since 2.0.0
			 * @param float  $discount Discount amount.
			 * @param float  $discounting_amount Discounting amount.
			 * @param array  $cart_item Cart item.
			 * @param bool   $single If true, then discount amount for single quantity.
			 * @param object $coupon Coupon object.
			 * @return float Discount amount.
			 */
			$discount = apply_filters( 'wbte_sc_alter_get_discount_amount', $discount, $discounting_amount, $cart_item, $single, $coupon );

			self::$normal_discounted_amount[ $coupon_id ][ $cart_item_key ] = $discount;

			self::$giveaway_discounted_amount[ $cart_item_key ] = max( $free_product_new_price - $discount, 0 );
		}

		return $discount;
	}

	/**
	 * Make the quantity field as uneditable for free giveaway products.
	 *
	 * @since 2.0.0
	 * @param  string $product_quantity HTML code of the product quantity field.
	 * @param  string $cart_item_key    Cart item key.
	 * @param  array  $cart_item        Cart item data.
	 * @return string                   If product is free, then return the quantity field as uneditable, otherwise return the original quantity field.
	 */
	public function update_cart_item_quantity_field( $product_quantity, $cart_item_key, $cart_item ) {
		if ( self::is_a_free_item( $cart_item ) ) {
			$product_quantity = sprintf( '%s <input type="hidden" name="cart[%s][qty]" value="%s" />', $cart_item['quantity'], $cart_item_key, $cart_item['quantity'] );
		}
		return $product_quantity;
	}

	/**
	 *  To reduce the free product quantity when the boogo eligibility changed.
	 *  When bogo eligibility quantity is reduced, then free product quantity will be reduced, free product with higher price will be reduced first.
	 *
	 *  @since  2.0.0
	 *  @param  string $coupon_code    Coupon code.
	 *  @param  int    $qty            Quantity to reduce.
	 */
	private static function reduce_free_product_qty( $coupon_code, $qty ) {
		$cart = self::get_cart_object();
		if ( is_null( $cart ) || $cart->is_empty() ) {
			return;
		}
		$cart_items     = $cart->get_cart();
		$qty            = abs( $qty );
		$free_items_arr = array();
		foreach ( $cart_items as $cart_item_key => $cart_item ) {
			if ( self::is_a_free_item( $cart_item, $coupon_code ) ) {
				$free_items_arr[ $cart_item_key ] = array(
					'qty'   => $cart_item['quantity'],
					'price' => $cart_item['data']->get_price() * $cart_item['quantity'],
				);
			}
		}

		// Sort the free items by price in descending order.
		uasort(
			$free_items_arr,
			function ( $a, $b ) {
				return $b['price'] - $a['price'];
			}
		);

		if ( $qty > 0 ) {
			foreach ( $free_items_arr as $cart_item_key => $item ) {
				if ( $qty <= 0 ) {
					break;
				}

				$current_qty = $item['qty'];

				if ( $current_qty <= $qty ) {
					// Remove the entire item if its quantity is less than or equal to qty to remove.
					$cart->remove_cart_item( $cart_item_key );
					unset( self::$giveaway_discounted_amount[ $cart_item_key ] );
					$qty -= $current_qty;
				} else {
					// Otherwise, reduce the quantity of the item.
					$new_qty                         = $current_qty - $qty;
					self::$free_product_qty_updating = true;
					$cart->set_quantity( $cart_item_key, $new_qty );
					self::$free_product_qty_updating = false;
					$qty                             = 0;
				}
			}
		}
	}

	/**
	 *  To alter coupon applied message. If coupon is BOGO, then message saved in general settings will be considered.
	 *  {bogo_title} will be replaced with the BOGO title.
	 *
	 *  @since  2.0.0
	 *  @param  string    $msg            Coupon applied msg.
	 *  @param  string    $msg_code       Coupon applied msg code.
	 *  @param  WC_Coupon $coupon         Coupon.
	 *  @return string                      If bogo coupon new message, otherwise old message
	 */
	public static function alter_bogo_applied_message( $msg, $msg_code, $coupon ) {
		$coupon_id = $coupon->get_id();
		if ( self::is_bogo( $coupon_id ) ) {
			$message    = self::get_general_settings_value( 'wbte_sc_bogo_general_discount_apply_message' );
			$bogo_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
			$message    = str_replace( '{bogo_title}', $bogo_title, $message );
			return $message;
		}
		return $msg;
	}

	/**
	 *  Prevent free product quantity update.
	 *
	 *  @since 2.2.0
	 *
	 *  @param string $cart_item_key  Cart item key.
	 *  @param int    $quantity       Quantity.
	 *  @param int    $old_quantity   Old quantity.
	 *  @param object $cart           Cart object.
	 */
	public function prevent_free_product_quantity_update( $cart_item_key, $quantity, $old_quantity, $cart ) {

		if ( ! self::$free_product_qty_updating && self::is_a_free_item( $cart->get_cart_item( $cart_item_key ) ) ) {
			remove_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'prevent_free_product_quantity_update' ), 999 );
			WC()->cart->set_quantity( $cart_item_key, $old_quantity );
		}
	}

	/**
	 * Get the BOGO products.
	 *
	 * @since 2.2.1
	 * @param int    $coupon_id Coupon id.
	 * @param string $type      Type of products, default product_ids, other value is exclude_product_ids.
	 * @return array            BOGO products.
	 */
	private static function get_bogo_products( $coupon_id, $type = 'product_ids' ) {
		if ( isset( self::$bogo_products[ $coupon_id ][ $type ] ) ) {
			return self::$bogo_products[ $coupon_id ][ $type ];
		}

		$products = array_map( 'absint', array_filter( explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_' . $type ) ) ) );

		if ( empty( $products ) ) {
			self::$bogo_products[ $coupon_id ][ $type ] = array();
			return array();
		}

		/**
		 * Filter to alter the BOGO products.
		 *
		 * @since 2.2.1
		 *
		 * @param array  $products     BOGO products, product ids or exclude product ids.
		 * @param int    $coupon_id    Coupon id.
		 */
		$products = array_map( 'absint', array_filter( apply_filters( 'wbte_sc_alter_bogo_' . $type, $products, $coupon_id ) ) );

		self::$bogo_products[ $coupon_id ][ $type ] = $products;
		return $products;
	}

	/**
	 * Reduce BOGO discount from subtotal for coupon minimum amount validation.
	 *
	 * @since 2.2.1
	 * @param bool      $not_valid Not valid.
	 * @param WC_Coupon $coupon Coupon.
	 * @param float     $subtotal Subtotal.
	 * @return bool True if coupon is not valid, otherwise false.
	 */
	public static function alter_coupon_validate_minimum_amount( $not_valid, $coupon, $subtotal ) {
		if ( $not_valid ) {
			return $not_valid;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( self::is_a_free_item( $cart_item ) ) {
				$subtotal -= $cart_item['wbte_sc_bogo_discount'] * $cart_item['quantity'];
			}
		}

		return $coupon->get_minimum_amount() > $subtotal;
	}

	/**
	 * Ajax function to add multiple free products to cart
	 *
	 * @since 2.2.8
	 */
	public function add_choosed_free_products_to_cart() {
		check_ajax_referer( 'wt_smart_coupons_public', '_wpnonce' );

		$products = isset( $_POST['products'] ) ? wc_clean( wp_unslash( $_POST['products'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( empty( $products ) ) {
			wp_send_json(
				array(
					'success' => false,
					'message' => __( 'No products selected.', 'thetoppercentile-coupons' ),
				)
			);
			return;
		}

		$added_to_cart            = false;
		$free_products_coupon_ids = array();

		foreach ( $products as $product_data ) {
			$product_id   = isset( $product_data['product_id'] ) ? absint( $product_data['product_id'] ) : 0;
			$variation_id = isset( $product_data['variation_id'] ) ? absint( $product_data['variation_id'] ) : 0;
			$quantity     = isset( $product_data['free_qty'] ) ? absint( $product_data['free_qty'] ) : 0;
			$coupon_id    = isset( $product_data['coupon_id'] ) ? absint( $product_data['coupon_id'] ) : 0;

			$coupon_code = wc_get_coupon_code_by_id( $coupon_id );

			$variation_attributes = isset( $product_data['attributes'] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( $product_data['attributes'], 'text_arr' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized with helper function.

			if ( 0 === $coupon_id ) {
				self::set_add_to_cart_messages( 'coupon_id_missing' );
			} elseif ( 0 === $product_id ) {
				self::set_add_to_cart_messages( 'product_id_missing', array( 'coupon_id' => $coupon_id ) );
			} else {
				try {
					$args = array(
						'variation_attributes' => $variation_attributes,
						'variation_id'         => $variation_id,
					);

					$item_id       = $variation_id > 0 ? $variation_id : $product_id;
					$cart_item_key = $this->add_item_to_cart( $item_id, $quantity, $coupon_code, $args );

					if ( $cart_item_key ) {
						$added_to_cart = true;
						if ( ! in_array( $coupon_id, $free_products_coupon_ids, true ) ) {
							$free_products_coupon_ids[] = $coupon_id;
						}
					} else {
						self::set_add_to_cart_messages( 'product_not_added' );
						wp_die();
					}
				} catch ( Exception $e ) {
					self::set_add_to_cart_messages( 'exception_error', array( 'err_msg' => $e->getMessage() ) );
					wp_die();
				}
			}
		}

		if ( $added_to_cart ) {
			foreach ( $free_products_coupon_ids as $coupon_id ) {
				self::show_product_added_msg( $coupon_id );
			}
			echo true;
			wp_die();
		} else {
			$notices = wc_get_notices( 'error' );
			if ( 0 < count( $notices ) ) {
				$last_error = end( $notices );
				if ( isset( $last_error['notice'] ) ) {
					echo '<ul class="woocommerce-error" role="alert">
							<li>' . wp_kses_post( $last_error['notice'] ) . '</li>
					</ul>';
					wc_clear_notices(); /* to avoid notice printing on page refresh */
					wp_die();
				}
			} else {
				echo false;
				wp_die();
			}
		}
	}
}
Wbte_Smart_Coupon_Bogo_Public::get_instance();
