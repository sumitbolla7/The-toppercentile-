<?php
/**
 * BOGO common section
 *
 * @link
 * @since 2.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The common functionality of new BOGO module.
 */
class Wbte_Smart_Coupon_Bogo_Common {

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
	 * Array of BOGO general settings fields.
	 *
	 * @var array $general_settings_arr Array of BOGO general settings fields.
	 */
	public static $general_settings_arr = array();

	/**
	 * BOGO coupon type name
	 *
	 * @var string $bogo_coupon_type_name BOGO coupon type name.
	 */
	public static $bogo_coupon_type_name = 'wbte_sc_bogo';

	/**
	 * Array of meta fields
	 *
	 * @var array  $meta_arr Array of meta fields used when saving BOGO, array key is ID of fields, and value is properties of fields.
	 */
	public static $meta_arr = array();

	/**
	 * Array of coupon meta fields
	 *
	 * @var array $coupon_meta_arr To store all coupon meta fields.
	 */
	public static $coupon_meta_arr = array();

	/**
	 * Constructor function of the class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		self::$meta_arr = array(
			'wbte_sc_bogo_type'                         => array(
				'default' => 'wbte_sc_bogo_bxgx',
				'type'    => 'text',
			),
			// Step 2.
			'wbte_sc_bogo_triggers_when'                => array(
				'default' => 'wbte_sc_bogo_triggers_qty',
				'type'    => 'text',
			),
			'_wbte_sc_bogo_min_amount'                  => array(
				'default' => '',
				'type'    => 'float',
			),
			'_wbte_sc_bogo_max_amount'                  => array(
				'default' => '',
				'type'    => 'float',
			),
			'_wbte_sc_bogo_min_qty'                     => array(
				'default' => 1,
				'type'    => 'int',
			),
			'_wbte_sc_bogo_max_qty'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'wbte_sc_bogo_product_ids'                  => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'_wt_product_condition'                     => array(
				'default' => 'or',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_exclude_product_ids'          => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'_wbte_sc_min_qty_each'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_max_qty_each'                     => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_bogo_min_qty_add'                 => array(
				'default' => '',
				'type'    => 'int',
			),
			'_wbte_sc_bogo_max_qty_add'                 => array(
				'default' => '',
				'type'    => 'int',
			),
			'usage_limit_per_user'                      => array(
				'default' => '',
				'type'    => 'int',
			),
			'usage_limit'                               => array(
				'default' => '',
				'type'    => 'int',
			),
			'customer_email'                            => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'array',
			),
			// Step 1.
			'wbte_sc_bogo_customer_gets'                => array(
				'default' => 'specific_product',
				'type'    => 'text',
			),
			'wbte_sc_bogo_free_product_ids'             => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			'wbte_sc_bogo_gets_product_condition'       => array(
				'default' => 'all',
				'type'    => 'text',
			),
			'wbte_sc_bogo_customer_gets_qty'            => array(
				'default' => 1,
				'type'    => 'int',
			),
			'wbte_sc_bogo_customer_gets_with'           => array(
				'default' => 'wbte_sc_bogo_customer_gets_with_discount',
				'type'    => 'text',
			),
			'wbte_sc_bogo_customer_gets_discount_type'  => array(
				'default' => 'wbte_sc_bogo_customer_gets_free',
				'type'    => 'text',
			),
			'wbte_sc_bogo_customer_gets_final_price'    => array(
				'default' => '',
				'type'    => 'float',
			),
			'wbte_sc_bogo_customer_gets_discount_perc'  => array(
				'default' => '',
				'type'    => 'float',
			),
			'wbte_sc_bogo_customer_gets_discount_price' => array(
				'default' => '',
				'type'    => 'float',
			),
			'free_shipping'                             => array(
				'default' => 'no',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			// Step 3.
			'wbte_sc_bogo_apply_offer'                  => array(
				'default' => 'wbte_sc_bogo_apply_once',
				'type'    => 'text',
			),
			// Edit general.
			'wbte_sc_bogo_coupon_name'                  => array(
				'default' => '',
				'type'    => 'text',
			),
			'wbte_sc_bogo_code_condition'               => array(
				'default' => 'wbte_sc_bogo_code_auto',
				'type'    => 'text',
			),
			'_wc_make_coupon_available'                 => array(
				'default' => '',
				'type'    => 'text_arr',
				'save_as' => 'text',
			),
			// Common.
			'wbte_sc_bogo_created_on_sc_bogo'           => array(
				'default' => 1,
				'type'    => 'int',
			),
		);

		add_filter( 'woocommerce_coupon_discount_types', array( $this, 'add_bogo_coupon_type' ) );
	}

	/**
	 * Get Instance
	 *
	 * @since 2.0.0
	 * @return object Class instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wbte_Smart_Coupon_Bogo_Common();
		}
		return self::$instance;
	}

	/**
	 * Add BOGO coupon type
	 * Return default discount types if current page in provided restricted page or in coupon edit page.
	 *
	 * @since 2.0.0
	 *
	 * @param  array $discount_types Discount types.
	 * @return array                 Discount types.
	 */
	public function add_bogo_coupon_type( $discount_types ) {

		if ( self::is_new_bogo_activated() ) {
			if ( (
					isset( $_GET['post_type'] ) && 'shop_coupon' === $_GET['post_type'] && ! isset( $_GET['wbte_sc_auto_apply'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				) ||
				(
					isset( $_GET['post'] ) && 'shop_coupon' === get_post_type( absint( wp_unslash( $_GET['post'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				)
			) {
				return $discount_types;
			}
			$discount_types[ self::$bogo_coupon_type_name ] = __( 'BOGO', 'thetoppercentile-coupons' );
		}
		return $discount_types;
	}

	/**
	 * Is new BOGO activated
	 *
	 * @since 2.0.0
	 * @return bool True if new BOGO is activated, false otherwise.
	 */
	public static function is_new_bogo_activated() {
		return (bool) get_option( 'wbte_sc_new_bogo_actvated' );
	}

	/**
	 * To get BOGO general settings value by key.
	 * If it is not set in global static variable, then get all general settings from DB and store in global static variable.
	 *
	 * @since 2.0.0
	 *
	 * @param  string $field_name Field name.
	 * @return string             Field value
	 */
	public static function get_general_settings_value( $field_name ) {
		if ( empty( self::$general_settings_arr ) ) {
			self::$general_settings_arr = get_option( 'wbte_sc_bogo_general_settings', array() );
		}
		$default_fields = array(
			'wbte_sc_bogo_auto_add_giveaway'              => 'wbte_sc_bogo_auto_add_full_giveaway',
			'wbte_sc_bogo_apply_tax_on'                   => 'wbte_sc_bogo_apply_tax_on_discount',
			'wbte_sc_bogo_prod_select_theme_color'        => '#4F46E5',
			'wbte_sc_bogo_general_discount_apply_message' => __( '{bogo_title} applied!', 'thetoppercentile-coupons' ),
			'wbte_sc_bogo_general_product_added_message'  => __( 'Giveaway added to cart!', 'thetoppercentile-coupons' ),
			'wbte_sc_bogo_general_discount_under_product_msg' => '{bogo_title}',
			'wbte_sc_bogo_general_apply_choose_product_title' => __( 'Choose product {qty_counter}', 'thetoppercentile-coupons' ),
		);

		return self::$general_settings_arr[ $field_name ] ?? ( $default_fields[ $field_name ] ?? '' );
	}

	/**
	 * To get all coupon meta and store in global static variable.
	 *
	 * @since 2.0.0
	 * @param int $coupon_id Coupon id.
	 */
	public static function get_all_coupon_meta( $coupon_id ) {
		self::$coupon_meta_arr              = (array) get_post_meta( $coupon_id );
		self::$coupon_meta_arr['coupon_id'] = $coupon_id;
	}

	/**
	 * To get coupon meta value by key.
	 * If it is not set in global static variable, then get all coupon meta from DB and store in global static variable.
	 *
	 * @since 2.0.0
	 *
	 * @param  int    $coupon_id          Coupon id.
	 * @param  string $meta_key           Meta key.
	 * @param  mixed  $default_meta_val   Default value.
	 * @return mixed                      Meta value.
	 */
	public static function get_coupon_meta_value( $coupon_id, $meta_key, $default_meta_val = '' ) {
		if ( ! isset( self::$coupon_meta_arr['coupon_id'] ) || $coupon_id !== self::$coupon_meta_arr['coupon_id'] ) {
			self::get_all_coupon_meta( $coupon_id );
		}
		$default_vl = isset( self::$meta_arr[ $meta_key ] ) && isset( self::$meta_arr[ $meta_key ]['default'] ) ? self::$meta_arr[ $meta_key ]['default'] : $default_meta_val;

		$meta_value = isset( self::$coupon_meta_arr[ $meta_key ] ) ? self::$coupon_meta_arr[ $meta_key ][0] : $default_vl;

		/**
		 * Filter to alter the BOGO meta value.
		 *
		 * @since 2.2.1
		 *
		 * @param mixed  $meta_value Meta value.
		 * @param string $meta_key   Meta key.
		 * @param int    $coupon_id  Coupon id.
		 */
		return apply_filters( 'wbte_sc_alter_bogo_meta_value', $meta_value, $meta_key, $coupon_id );
	}

	/**
	 * Is given coupon is BOGO.
	 *
	 * @since 2.0.0
	 *
	 * @param  int $coupon_id Coupon id.
	 * @return boolean           True if the coupon is BOGO, false otherwise.
	 */
	public static function is_bogo( $coupon_id ) {
		return get_post_meta( $coupon_id, 'discount_type', true ) === self::$bogo_coupon_type_name;
	}

	/**
	 * To check if the coupon is triggered based on subtotal.
	 *
	 * @since  2.0.0
	 * @param  int $coupon_id Coupon id.
	 * @return bool           True if the coupon is triggered based on subtotal, false otherwise(that is based on qty).
	 */
	protected static function is_coupon_based_on_subtotal( $coupon_id ) {
		return 'wbte_sc_bogo_triggers_subtotal' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_triggers_when' );
	}

	/**
	 *  Check whether the coupon is auto BOGO or not.
	 *
	 *  @since   2.0.0
	 *  @param   int $coupon_id    Coupon id.
	 *  @return  boolean           True when auto BOGO otherwise false.
	 */
	public static function is_auto_bogo( $coupon_id ) {
		return 'wbte_sc_bogo_code_auto' === get_post_meta( $coupon_id, 'wbte_sc_bogo_code_condition', true );
	}

	/**
	 *  Get giveaway products id from coupon meta
	 *
	 *  @since 2.0.0
	 *  @param int $post_id Coupon id.
	 *  @return array of giveaway product ids
	 */
	public static function get_giveaway_products( $post_id ) {
		$free_product_ids    = self::get_instance()->get_coupon_meta_value( $post_id, 'wbte_sc_bogo_free_product_ids' );
		$free_product_id_arr = array();
		if ( $free_product_ids && is_string( $free_product_ids ) ) {
			$free_product_id_arr = explode( ',', $free_product_ids );
		}
		return $free_product_id_arr;
	}
}
Wbte_Smart_Coupon_Bogo_Common::get_instance();
