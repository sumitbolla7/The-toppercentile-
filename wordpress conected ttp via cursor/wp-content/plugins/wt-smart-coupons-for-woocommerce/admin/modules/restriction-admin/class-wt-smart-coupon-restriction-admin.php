<?php
/**
 * Coupon usage restriction admin section
 *
 * @link
 * @since 1.4.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Restriction' ) ) {
	return;
}

/**
 * Class Wt_Smart_Coupon_Restriction_Admin
 * Coupon usage restriction admin section
 *
 * @since 1.4.0
 */
class Wt_Smart_Coupon_Restriction_Admin extends Wt_Smart_Coupon_Restriction {

	/**
	 * Module base
	 *
	 * @var string Module base.
	 */
	public $module_base = 'coupon_restriction';

	/**
	 * Module id
	 *
	 * @var string Module id.
	 */
	public $module_id = '';

	/**
	 * Module id static
	 *
	 * @var string Module id static.
	 */
	public static $module_id_static = '';

	/**
	 * Instance
	 *
	 * @var object|null Class instance.
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_usage_restriction_fields' ), 10, 1 );

		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ), 10, 0 );

		add_action( 'wp_ajax_wbte_sc_hide_usage_restriction_pro_cta', array( $this, 'hide_usage_restriction_pro_cta' ) );
	}

	/**
	 * Get Instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wt_Smart_Coupon_Restriction_Admin();
		}
		return self::$instance;
	}

	/**
	 *  Process the meta data from product/category meta table.
	 *  This method will return an associative array with item id (Product/category id) as array key and min/max data array as value.
	 *
	 *  @since 1.4.0
	 *
	 *  @param string $id_key item id POST array key.
	 *  @param string $min_qty_key min quantity POST array key.
	 *  @param string $max_qty_key max quantity POST array key.
	 *
	 *  @return array  associative array with min/max quantity data. Empty array when no POST data exists
	 */
	private function prepare_meta_data_from_post_data( $id_key, $min_qty_key, $max_qty_key ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			array();
		}

		$item_ids     = ( isset( $_POST[ $id_key ] ) && is_array( $_POST[ $id_key ] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $id_key ] ), 'int_arr' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
		$item_min_qty = ( isset( $_POST[ $min_qty_key ] ) && is_array( $_POST[ $min_qty_key ] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $min_qty_key ] ), 'text_arr' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
		$item_max_qty = ( isset( $_POST[ $max_qty_key ] ) && is_array( $_POST[ $max_qty_key ] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $max_qty_key ] ), 'text_arr' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.

		$item_data = array();
		foreach ( $item_ids as $i => $item_id ) {
			$item_data[ $item_id ] = array(
				'min' => ( isset( $item_min_qty[ $i ] ) ? $item_min_qty[ $i ] : '' ),
				'max' => ( isset( $item_max_qty[ $i ] ) ? $item_max_qty[ $i ] : '' ),
			);
		}

		return $item_data;
	}

	/**
	 *  Update checkbox field data to postmeta.
	 *  Checkbox fields have no post data when unchecked
	 *
	 *  @since 1.4.0
	 *
	 *  @param int    $post_id post/coupon id.
	 *  @param string $field_name post meta/form field key name.
	 */
	private function update_checkbox_field_data( $post_id, $field_name ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( isset( $_POST[ $field_name ] ) && 'yes' === $_POST[ $field_name ] ) {
			update_post_meta( $post_id, $field_name, 'yes' );
		} else {
			update_post_meta( $post_id, $field_name, 'no' );
		}
	}

	/**
	 *  Save coupon restrictions meta data
	 *
	 *  @param int $post_id post/coupon id.
	 */
	public function process_shop_coupon_meta( $post_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
			wp_die( esc_html__( 'You do not have sufficient permission to perform this operation', 'thetoppercentile-coupons' ) );
		}

		/* product data */
		$wt_sc_coupon_products = $this->prepare_meta_data_from_post_data( '_wt_sc_coupon_product_ids', '_wt_sc_coupon_product_min_qty', '_wt_sc_coupon_product_max_qty' );
		update_post_meta( $post_id, '_wt_sc_coupon_products', $wt_sc_coupon_products );

		/* category data */
		$wt_sc_coupon_categories = $this->prepare_meta_data_from_post_data( '_wt_sc_coupon_category_ids', '_wt_sc_coupon_category_min_qty', '_wt_sc_coupon_category_max_qty' );
		update_post_meta( $post_id, '_wt_sc_coupon_categories', $wt_sc_coupon_categories );

		$this->update_checkbox_field_data( $post_id, '_wt_use_individual_min_max' );
		$this->update_checkbox_field_data( $post_id, '_wt_enable_product_category_restriction' );

		/* fields to skip from below meta data update loop. Because they are alreay updated. */
		$skip_post_arr = array(
			'_wt_sc_coupon_products',
			'_wt_sc_coupon_categories',
			'_wt_use_individual_min_max',
			'_wt_enable_product_category_restriction',
		);

		foreach ( self::$meta_arr as $mata_key => $meta_info ) {
			if ( in_array( $mata_key, $skip_post_arr, true ) ) {
				continue; // already updated via above code block.
			}

			if ( isset( $_POST[ $mata_key ] ) && ! empty( $_POST[ $mata_key ] ) ) {
				if ( isset( $meta_info['type'] ) ) {
					if ( 'absint' === $meta_info['type'] ) {
						$val = absint( wp_unslash( $_POST[ $mata_key ] ) );
					} elseif ( 'float' === $meta_info['type'] ) {
						$val = floatval( wp_unslash( $_POST[ $mata_key ] ) );
					} else {
						$val = sanitize_text_field( wp_unslash( $_POST[ $mata_key ] ) );
					}
				} else {
					$val = sanitize_text_field( wp_unslash( $_POST[ $mata_key ] ) );
				}

				update_post_meta( $post_id, $mata_key, $val );

			} else {
				$default = ( isset( $meta_info['default'] ) ? $meta_info['default'] : '' );
				update_post_meta( $post_id, $mata_key, $default );
			}
		}
	}

	/**
	 *  Extra fields on coupon usage restrictions tab. Coupon edit/add page
	 *
	 *  @since 1.0.0
	 *
	 *  @param int $post_id post/coupon id.
	 */
	public function coupon_usage_restriction_fields( $post_id ) {
		$coupon = new WC_Coupon( $post_id );

		/**
		 *  Checkbox to enable/disable product/category restriction
		 */
		$wt_enable_product_category_restriction = $this->get_coupon_meta_value( $post_id, '_wt_enable_product_category_restriction' );
		woocommerce_wp_checkbox(
			array(
				'id'          => '_wt_enable_product_category_restriction',
				'value'       => $wt_enable_product_category_restriction,
				'class'       => 'wt_enable_product_category_restriction',
				'label'       => __( 'Product restrictions', 'thetoppercentile-coupons' ),
				'description' => __( 'Enable to apply the coupon only when the cart meets the specified product restrictions.', 'thetoppercentile-coupons' ),
			)
		);

		$hide_usage_restriction_pro_cta = get_option( 'wbte_sc_hide_usage_restriction_pro_cta' );
		if ( ! $hide_usage_restriction_pro_cta ) {
			printf(
				'<div class="wbte_sc_usage_restriction_pro_promo" style="background-color: #F7FAFF; margin: 0 15px; display: flex; align-items: center; border-left: 4px solid #0055FF; padding: 10px 12px; font-weight: 400; box-sizing: border-box; gap: 10px;"><p style="margin: 0; flex: 1;">%s</p><a href="%s" target="_blank" style="text-decoration: none; color: #fff; background-color: #0055FF; padding: 10px 14px; border-radius: 2px;">%s</a><button class="wbte_sc_usage_restriction_pro_promo_btn_later wbte_sc_button wbte_sc_button-text wbte_sc_button-small" style="font-size: 12px; color: #434343;">%s</button></div>',
				sprintf(
					// translators: 1: lightbulb icon, 2: span tag opening, 3: span tag closing.
					esc_html__( '%1$s %2$s Did You Know? %3$s You can now get full control over coupon stacking—choose which discounts can and cannot be combined with TheTopPercentile Coupons Pro!', 'thetoppercentile-coupons' ),
					'<span class="dashicons dashicons-lightbulb" style="color: #0055FF; font-size: 18px;"></span>',
					'<span style="font-weight: 600;">',
					'</span>'
				),
				esc_url(
					add_query_arg(
						array(
							'utm_source'   => 'free_plugin_smart_coupon_giveaway',
							'utm_medium'   => 'smart_coupons_basic',
							'utm_campaign' => 'smart_coupons',
							'utm_content'  => WEBTOFFEE_SMARTCOUPON_VERSION,
						),
						'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/'
					)
				),
				sprintf(
					// translators: 1: arrow right icon.
					esc_html__( 'Check out plugin %s', 'thetoppercentile-coupons' ),
					'<span class="dashicons dashicons-arrow-right-alt"></span>'
				),
				esc_html__( 'Maybe later', 'thetoppercentile-coupons' )
			);
		}

		/**
		 *  Checkbox to enable/disable individual min/max quantity restriction
		 */
		$wt_use_individual_min_max = $this->get_coupon_meta_value( $post_id, '_wt_use_individual_min_max' );
		woocommerce_wp_checkbox(
			array(
				'id'          => '_wt_use_individual_min_max',
				'value'       => $wt_use_individual_min_max,
				'class'       => 'wt_use_individual_min_max',
				'label'       => __( 'Individual quantity restriction', 'thetoppercentile-coupons' ),
				'description' => __( 'Enable to set minimum and maximum quantity restrictions for individual product/category instead of entire cart.', 'thetoppercentile-coupons' ),
			)
		);

		/**
		 *  Checkbox for Any/All product option
		 */
		$wt_product_condition = $this->get_coupon_meta_value( $post_id, '_wt_product_condition' );
		woocommerce_wp_radio(
			array(
				'id'          => '_wt_product_condition',
				'value'       => $wt_product_condition,
				'class'       => 'wt_product_restrictions',
				'label'       => __( 'Product conditions:', 'thetoppercentile-coupons' ),
				'options'     => array(
					'or'  => __( 'Any from below selection', 'thetoppercentile-coupons' ),
					'and' => __( 'All from below selection', 'thetoppercentile-coupons' ),
				),
				'description' => sprintf(
					// translators: 1: bold tag opening, 2: bold tag closing.
					__( '%1$s Any: %2$s Applies coupon if any of the products from the below is available in the cart.', 'thetoppercentile-coupons' ),
					'<b>',
					'</b>'
				) . '<br />' . sprintf(
					// translators: 1: bold tag opening, 2: bold tag closing.
					__( '%1$s All: %2$s Applies coupon if the cart contains all of the listed products.', 'thetoppercentile-coupons' ),
					'<b>',
					'</b>'
				),
			)
		);

		/**
		 *  Checkbox for Any/All category option
		 */
		$wt_category_condition = $this->get_coupon_meta_value( $post_id, '_wt_category_condition' );
		woocommerce_wp_radio(
			array(
				'id'          => '_wt_category_condition',
				'value'       => $wt_category_condition,
				'class'       => 'wt_category_condition',
				'label'       => __( 'Category condition:', 'thetoppercentile-coupons' ),
				'options'     => array(
					'or'  => __( 'Any from below selection', 'thetoppercentile-coupons' ),
					'and' => __( 'All from below selection', 'thetoppercentile-coupons' ),
				),
				'description' => sprintf(
					// translators: 1: bold tag opening, 2: bold tag closing.
					__( '%1$s Any: %2$s Applies coupon if the eligible quantity of products from any of the below selected categories are available in the cart.', 'thetoppercentile-coupons' ),
					'<b>',
					'</b>'
				) . '<br />' . sprintf(
					// translators: 1: bold tag opening, 2: bold tag closing.
					__( '%1$s All: %2$s Applies coupon if the cart contains the eligible quantity of products from all of the below listed categories.', 'thetoppercentile-coupons' ),
					'<b>',
					'</b>'
				),
			)
		);

		/**
		 * Product and category form fields
		 */
		include_once plugin_dir_path( __FILE__ ) . 'views/-usage-restrictions-tab-content.php';

		echo '<div class="options_group wt_sc_coupon_restriction_matching_products">';

			// Minimum quantity of matching products (product/category).
			woocommerce_wp_text_input(
				array(
					'id'          => '_wt_min_matching_product_qty',
					'label'       => __( 'Minimum quantity of matching products', 'thetoppercentile-coupons' ),
					'placeholder' => __( 'No minimum', 'thetoppercentile-coupons' ),
					'description' => __( 'Minimum quantity of the products that match the given product or category restrictions. If no product or category restrictions are specified, the total number of products is used.', 'thetoppercentile-coupons' ),
					'data_type'   => 'decimal',
					'desc_tip'    => true,
				)
			);

			// Maximum quantity of matching products (product/category).
			woocommerce_wp_text_input(
				array(
					'id'          => '_wt_max_matching_product_qty',
					'label'       => __( 'Maximum quantity of matching products', 'thetoppercentile-coupons' ),
					'placeholder' => __( 'No maximum', 'thetoppercentile-coupons' ),
					'description' => __( 'Maximum quantity of the products that match the given product or category restrictions. If no product or category restrictions are specified, the total number of products is used.', 'thetoppercentile-coupons' ),
					'data_type'   => 'decimal',
					'desc_tip'    => true,
				)
			);

			// Minimum subtotal of matching products (product/category).
			woocommerce_wp_text_input(
				array(
					'id'          => '_wt_min_matching_product_subtotal',
					'label'       => __( 'Minimum subtotal of matching products', 'thetoppercentile-coupons' ),
					'placeholder' => __( 'No minimum', 'thetoppercentile-coupons' ),
					'description' => __( 'Minimum price subtotal of the products that match the given product or category restrictions.', 'thetoppercentile-coupons' ),
					'data_type'   => 'price',
					'desc_tip'    => true,
				)
			);

			// Maximum subtotal of matching products (product/category).
			woocommerce_wp_text_input(
				array(
					'id'          => '_wt_max_matching_product_subtotal',
					'label'       => __( 'Maximum subtotal of matching products', 'thetoppercentile-coupons' ),
					'placeholder' => __( 'No maximum', 'thetoppercentile-coupons' ),
					'description' => __( 'Maximum price subtotal of the products that match the given product or category restrictions.', 'thetoppercentile-coupons' ),
					'data_type'   => 'price',
					'desc_tip'    => true,
				)
			);

		echo '</div>';
	}


	/**
	 *  Enqueue Scripts and Styles
	 *
	 *  @since 1.0.0
	 */
	public function enqueue_scripts_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$screen_id_arr = array( 'shop_coupon', 'smart-coupons_page_wt-smart-coupon-for-woo_bulk_generate' );

		/**
		 *  Alter the screen ID list where the JS to enqueue
		 *
		 *  @since 1.4.0
		 *  @param array $screen_id_arr array of default screen id list.
		 */
		$screen_id_arr = apply_filters( 'wt_sc_coupon_restriction_admin_assets_screen_ids', $screen_id_arr ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( in_array( $screen_id, $screen_id_arr, true ) ) {
			wp_enqueue_script( $this->module_id . '_coupon_edit', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery', WT_SC_PLUGIN_NAME ), WEBTOFFEE_SMARTCOUPON_VERSION, false );
		}
	}

	/**
	 *  Get discount type help array
	 *
	 *  @since 1.4.0
	 *
	 *  @param string $type discount type.
	 *  @return array discount type help array.
	 */
	public static function discount_type_help_arr( $type = 'product' ) {
		switch ( $type ) {
			case 'product':
				$discount_type_help = array(
					'percentage|fixed_product' => __( 'Apply coupon only if the selected quantity of products are in the cart. Discounts will be given for those products and not the total cart amount.', 'thetoppercentile-coupons' ),
					'fixed_cart'               => __( 'Applies coupon only if the selected quantity of products are in the cart. A discount will be given for the total cart amount.', 'thetoppercentile-coupons' ),
				);
				break;

			case 'category':
				$discount_type_help = array(
					'percentage|fixed_product' => __( 'Apply coupon only if the selected quantity of products of the chosen category are in the cart. Discounts will be given for those products and not the total cart amount.', 'thetoppercentile-coupons' ),
					'fixed_cart'               => __( 'Applies coupon only if the selected quantity of products of the chosen category are in the cart. A discount will be given for the total cart amount.', 'thetoppercentile-coupons' ),
				);
				break;

			case 'exclude_product':
				$discount_type_help = array(
					'percentage|fixed_product' => __( 'If eligible products are in the cart along with excluded products, the coupon will be applied, but the discount will be limited to eligible products.', 'thetoppercentile-coupons' ),
					'fixed_cart'               => __( 'The coupon is not applicable when the cart contains an excluded product.', 'thetoppercentile-coupons' ),
				);
				break;

			case 'exclude_category':
				$discount_type_help = array(
					'percentage|fixed_product' => __( 'If eligible products are in the cart along with products from an excluded category, the coupon will be applied, but the discount will be limited to eligible products.', 'thetoppercentile-coupons' ),
					'fixed_cart'               => __( 'The coupon is not applicable when the cart contains products from an excluded category.', 'thetoppercentile-coupons' ),
				);
				break;

			default:
				$discount_type_help = array();
				break;
		}

		/**
		 * Filter to alter discount type help array.
		 *
		 * @since 1.4.0
		 *
		 * @param array  $discount_type_help discount type help array.
		 * @param string $type               discount type.
		 */
		return apply_filters( 'wt_sc_intl_alter_discount_type_help_arr', $discount_type_help, $type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 *  Hide usage restriction pro promo ajax callback
	 *
	 *  @since 2.2.0
	 */
	public function hide_usage_restriction_pro_cta() {
		check_ajax_referer( 'wt_smart_coupons_admin_nonce', '_wpnonce' );
		update_option( 'wbte_sc_hide_usage_restriction_pro_cta', true );
		wp_send_json_success();
	}
}
Wt_Smart_Coupon_Restriction_Admin::get_instance();
