<?php
/**
 * Giveaway products admin section
 *
 * @link
 * @since 1.4.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Giveaway_Product' ) ) {
	return;
}

/**
 * Giveaway products admin section class
 */
class Wt_Smart_Coupon_Giveaway_Product_Admin extends Wt_Smart_Coupon_Giveaway_Product {

	/**
	 * Module base
	 *
	 * @var string Module base.
	 */
	public $module_base = 'giveaway_product';

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

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		/**
		 *  Add fields on coupon general settings
		 */
		add_action( 'woocommerce_coupon_options', array( $this, 'add_general_settings_fields' ), 6 );

		add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'add_give_way_coupon_data_tab' ), 21, 1 );
		add_action( 'woocommerce_coupon_data_panels', array( $this, 'give_away_free_product_tab_content' ), 10, 1 );
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta_give_away' ), 11 );
		add_action( 'wp_ajax_woocommerce_json_search_products_and_variations_without_parent', array( $this, 'wt_products_and_variations_no_parent' ) );

		/* Giveaway details into order detail table */
		add_action( 'woocommerce_admin_order_totals_after_tax', array( $this, 'add_giveaway_info_to_order_detail_table' ) );

		/**
		 *  Help text for coupon restriction section
		 *
		 *  @since  1.4.3
		 */
		add_filter( 'wt_sc_intl_alter_discount_type_help_arr', array( $this, 'add_discount_type_help_text' ), 10, 2 );
	}

	/**
	 * Get Instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wt_Smart_Coupon_Giveaway_Product_Admin();
		}
		return self::$instance;
	}

	/**
	 *  Save giveaway related meta data
	 *
	 * @param int $post_id Post ID.
	 */
	public function process_shop_coupon_meta_give_away( $post_id ) {

		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
			wp_die( esc_html__( 'You do not have sufficient permission to perform this operation', 'thetoppercentile-coupons' ) );
		}

		$discount_type = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : '';

		/* product data */
		$bogo_free_products = $this->prepare_meta_data_from_post_data( '_wt_sc_bogo_free_product_ids', '_wt_sc_bogo_free_product_qty', '_wt_sc_bogo_free_product_price', '_wt_sc_bogo_free_product_price_type' );
		update_post_meta( $post_id, '_wt_sc_bogo_free_products', $bogo_free_products );

		if ( $discount_type === self::$bogo_coupon_type_name ) {
			$wt_sc_bogo_free_categories = array();
			$wt_product_condition       = 'and'; // all products option for BOGO.
			$wt_category_condition      = 'and';

			delete_post_meta( $post_id, 'product_categories' );
			delete_post_meta( $post_id, 'exclude_product_categories' );
			delete_post_meta( $post_id, '_wt_category_condition' );
		} else {
			$wt_sc_bogo_free_categories = $this->prepare_meta_data_from_post_data( '_wt_sc_bogo_free_category_ids', '_wt_sc_bogo_free_category_qty', '_wt_sc_bogo_free_category_price', '_wt_sc_bogo_free_category_price_type' );
			$wt_product_condition       = isset( $_POST['_wt_product_condition'] ) ? sanitize_text_field( wp_unslash( $_POST['_wt_product_condition'] ) ) : 'or';
			$wt_category_condition      = isset( $_POST['_wt_category_condition'] ) ? sanitize_text_field( wp_unslash( $_POST['_wt_category_condition'] ) ) : 'or';
		}

		update_post_meta( $post_id, '_wt_sc_bogo_free_categories', $wt_sc_bogo_free_categories );
		update_post_meta( $post_id, '_wt_product_condition', $wt_product_condition );

		update_post_meta( $post_id, '_wt_sc_bogo_customer_gets', 'specific_product' ); // bogo customer gets.
		update_post_meta( $post_id, '_wt_sc_bogo_product_condition', 'and' ); // bogo product condition.
		update_post_meta( $post_id, '_wt_category_condition', $wt_category_condition );

		// Giveaway free Products.
		if ( isset( $_POST['_wt_free_product_ids'] ) && '' !== $_POST['_wt_free_product_ids'] ) {
			$free_product_ids = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['_wt_free_product_ids'] ), 'int_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
			update_post_meta( $post_id, '_wt_free_product_ids', implode( ',', $free_product_ids ) );
		} else {
			update_post_meta( $post_id, '_wt_free_product_ids', '' );
		}

		$skip_post_arr = array(
			'_wt_sc_bogo_free_products',
			'_wt_free_product_ids',
			'_wt_product_condition',
			'_wt_category_condition',
			'_wt_sc_bogo_customer_gets',
		); /* fields that skip from below meta data update loop */

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
					} elseif ( 'boolean' === $meta_info['type'] ) {
						$val = boolval( wp_unslash( $_POST[ $mata_key ] ) );
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

		if ( $discount_type === self::$bogo_coupon_type_name ) {

			if ( isset( $_POST['_wt_sc_bogo_free_product_ids'] ) && '' !== $_POST['_wt_sc_bogo_free_product_ids'] ) {
				$free_product_ids = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['_wt_sc_bogo_free_product_ids'] ), 'int_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
				update_post_meta( $post_id, '_wt_free_product_ids', implode( ',', $free_product_ids ) );
			}
		}
	}

	/**
	 *  Enqueue Scripts and Styles
	 */
	public function enqueue_scripts_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$screen_id_arr = array( 'shop_coupon', 'smart-coupons_page_wt-smart-coupon-for-woo_bulk_generate' );
		/**
		 * Filter the screen IDs for the giveaway admin assets.
		 *
		 * @since 1.4.0
		 *
		 * @param array $screen_id_arr Screen IDs.
		 */
		$screen_id_arr = apply_filters( 'wt_sc_giveaway_admin_assets_screen_ids', $screen_id_arr ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( in_array( $screen_id, $screen_id_arr, true ) ) {
			wp_enqueue_style( $this->module_id . '_coupon_edit', plugin_dir_url( __FILE__ ) . 'assets/css/main.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION, 'all' );

			wp_enqueue_script( $this->module_id . '_coupon_edit', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery', WT_SC_PLUGIN_NAME ), WEBTOFFEE_SMARTCOUPON_VERSION, false );

			$script_parameters = array(
				'bogo_coupon_type' => self::$bogo_coupon_type_name,
				'msgs'             => array(
					'premium' => esc_html__( '(premium)', 'thetoppercentile-coupons' ),
				),
			);
			wp_localize_script( $this->module_id . '_coupon_edit', 'wt_sc_giveaway_params', $script_parameters );
		}
	}

	/**
	 * Add general settings fields
	 *
	 * @param int $post_id Post ID.
	 */
	public function add_general_settings_fields( $post_id = 0 ) {
		$wt_sc_bogo_apply_frequency = $this->get_coupon_meta_value( $post_id, '_wt_sc_bogo_apply_frequency' );
		echo '<div class="options_group" style="border:none;">';
		woocommerce_wp_radio(
			array(
				'id'          => '_wt_sc_bogo_apply_frequency',
				'value'       => $wt_sc_bogo_apply_frequency,
				'class'       => 'wt_sc_bogo_apply_frequency',
				'label'       => __( 'Number of times', 'thetoppercentile-coupons' ),
				'options'     => array(
					'once'   => __( 'Apply once', 'thetoppercentile-coupons' ),
					'repeat' => __( 'Apply repeatedly', 'thetoppercentile-coupons' ),
				),
				'description' => sprintf(
					// translators: %1$s: <b> tag, %2$s: </b> tag.
					__( '%1$s Apply once: %2$s If cart is eligible or conditions are met, coupon applies once. ie: If you set the coupon to offer Buy 2 Get 1, you get one free product. Moving more items to the cart will not make it eligible to get more free products.', 'thetoppercentile-coupons' ),
					'<b>',
					'</b>'
				) . '<br />' . sprintf(
					// translators: %1$s: <b> tag, %2$s: </b> tag.
					__( '%1$s Apply repeatedly: %2$s The coupon applies repeatedly whenever the cart is eligible or if conditions are met. ie: If you set the coupon to offer Buy 2 Get 1 then the coupon works repeatedly for Buy 4 Get 2 and so on.', 'thetoppercentile-coupons' ),
					'<b>',
					'</b>'
				),
			)
		);
		echo '</div>';
	}

	/**
	 * Add giveaway coupon data tab
	 *
	 * @param array $tabs Tabs.
	 * @return array Modified tabs.
	 */
	public function add_give_way_coupon_data_tab( $tabs ) {
		$tabs['wt_give_away_free_product'] = array(
			'label'  => __( 'Giveaway products', 'thetoppercentile-coupons' ),
			'target' => 'wt_give_away_free_products',
			'class'  => '',
		);

		return $tabs;
	}

	/**
	 * Giveaway Product tab content
	 *
	 * @param int $post_id Post ID.
	 */
	public function give_away_free_product_tab_content( $post_id = 0 ) {
		$free_product_id_arr = self::get_giveaway_products( $post_id );

		$dummy_qty_price        = self::get_dummy_qty_price();
		$bogo_customer_gets     = $this->get_coupon_meta_value( $post_id, '_wt_sc_bogo_customer_gets' );
		$bogo_product_condition = $this->get_coupon_meta_value( $post_id, '_wt_sc_bogo_product_condition' );

		$bogo_free_products = $this->get_coupon_meta_value( $post_id, '_wt_sc_bogo_free_products' );
		$bogo_products_data = self::prepare_items_data( $free_product_id_arr, $bogo_free_products );

		include_once plugin_dir_path( __FILE__ ) . 'views/giveaway-tab-content.php';
	}

	/**
	 *  Alter product search - exclude parent product from list (Only for non BOGO coupons)
	 *
	 *  @since 1.2.4
	 */
	public function wt_products_and_variations_no_parent() {

		check_ajax_referer( 'search-products', 'security' );

		if ( ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
			wp_die( esc_html__( 'You do not have sufficient permission to perform this operation', 'thetoppercentile-coupons' ) );
		}
		add_filter( 'woocommerce_json_search_found_products', array( $this, 'exclude_parent_product_from_search' ), 10, 1 );

		WC_AJAX::json_search_products( '', true );
	}

	/**
	 *  Exclude Parent Product from product search
	 *
	 *  @since 1.2.4
	 *
	 * @param array $products Products.
	 * @return array Modified products.
	 */
	public function exclude_parent_product_from_search( $products ) {
		foreach ( $products as $product_id => $product ) {
			$product_obj = wc_get_product( $product_id );
			if ( $product_obj->has_child() ) {
				unset( $products[ $product_id ] );
			}
		}
		return $products;
	}

	/**
	 *  Add giveaway info to order detail table
	 *
	 *  @since 1.4.0
	 *
	 * @param int $order_id Order ID.
	 */
	public function add_giveaway_info_to_order_detail_table( $order_id ) {
		$order       = new WC_Order( $order_id );
		$order_items = $order->get_items();
		foreach ( $order_items as $order_item_id => $order_item ) {
			$giveaway_info = $this->prepare_giveaway_info_for_order( $order_item_id, $order_item );
			if ( $giveaway_info ) {
				/**
				 * Filter the label text for the giveaway info in the order detail table.
				 *
				 * @since 1.4.0
				 *
				 * @param string $label_text Label text.
				 * @param object $order_item Order item.
				 * @param int $order_item_id Order item ID.
				 * @param object $order Order.
				 */
				$label_text = apply_filters( 'wt_sc_alter_order_detail_giveaway_info_label', __( 'Free gift:', 'thetoppercentile-coupons' ), $order_item, $order_item_id, $order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				?>
				<tr>
					<td class="label"><?php echo wp_kses_post( $label_text ); ?></td>
					<td width="1%"></td>
					<td class="total"><?php echo wp_kses_post( $giveaway_info ); // WPCS: XSS ok. ?></td>
				</tr>
				<?php
			}
		}
	}

	/**
	 *  Help text for coupon restriction section
	 *
	 *  @since  1.4.3
	 *  @param  array  $help_text_arr Help text array.
	 *  @param  string $type For which filed, default `product`.
	 *                  Possible values: product, exclude_product, category, exclude_category.
	 */
	public function add_discount_type_help_text( $help_text_arr, $type = 'product' ) {
		if ( 'product' === $type ) {
			$help_text_arr[ self::$bogo_coupon_type_name ] = __( 'Apply coupon only if the selected product quantity is in the cart. Discounts will be given for those products and not the total cart amount. For example, for setting up Buy X Get Y, choose the product/s X in this section.', 'thetoppercentile-coupons' );

		} elseif ( 'category' === $type ) {
			$help_text_arr[ self::$bogo_coupon_type_name ] = __( 'Apply coupon only if the selected quantity of products of the chosen category are in the cart. Discounts will be given for those products and not the total cart amount.', 'thetoppercentile-coupons' );

		} elseif ( 'exclude_product' === $type || 'exclude_category' === $type ) {
			$out = array();

			foreach ( $help_text_arr as $help_text_arr_key => $help_text_arr_val ) {
				if ( false !== stristr( $help_text_arr_key, 'fixed_cart' ) ) {
					$out[ $help_text_arr_key . '|' . self::$bogo_coupon_type_name ] = $help_text_arr_val;
				} else {
					$out[ $help_text_arr_key ] = $help_text_arr_val;
				}
			}

			$help_text_arr = $out;
		}

		return $help_text_arr;
	}

	/**
	 *  Prepare meta data from post data
	 *
	 *  @since 1.4.0
	 *
	 * @param string $id_key ID key.
	 * @param string $qty_key Quantity key.
	 * @param string $price_key Price key.
	 * @param string $price_type_key Price type key.
	 * @return array Item data.
	 */
	private function prepare_meta_data_from_post_data( $id_key, $qty_key, $price_key, $price_type_key ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return array();
		}

		$item_ids = ( isset( $_POST[ $id_key ] ) && is_array( $_POST[ $id_key ] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $id_key ] ), 'int_arr' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.

		$item_qty = ( isset( $_POST[ $qty_key ] ) && is_array( $_POST[ $qty_key ] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $qty_key ] ), 'text_arr' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.

		$item_price = ( isset( $_POST[ $price_key ] ) && is_array( $_POST[ $price_key ] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $price_key ] ), 'text_arr' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.

		$item_price_type = ( isset( $_POST[ $price_type_key ] ) && is_array( $_POST[ $price_type_key ] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $price_type_key ] ), 'text_arr' ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.

		$item_data = array();
		foreach ( $item_ids as $i => $item_id ) {
			$price_type = isset( $item_price_type[ $i ] ) ? $item_price_type[ $i ] : '';
			$price      = isset( $item_price[ $i ] ) ? (float) $item_price[ $i ] : '';
			if ( 'percent' === $price_type && '' !== $price ) {
				$price = min( $price, 100 );
			}
			$item_data[ $item_id ] = array(
				'qty'        => ( isset( $item_qty[ $i ] ) ? $item_qty[ $i ] : 1 ),
				'price'      => $price,
				'price_type' => $price_type,
			);
		}

		return $item_data;
	}
}
Wt_Smart_Coupon_Giveaway_Product_Admin::get_instance();