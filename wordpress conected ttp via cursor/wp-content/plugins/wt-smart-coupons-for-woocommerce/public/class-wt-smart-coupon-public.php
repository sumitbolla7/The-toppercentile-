<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.thetoppercentile.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/public
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


if ( ! class_exists( 'Wt_Smart_Coupon_Public' ) ) {

	/**
	 * The public-facing functionality of the plugin.
	 *
	 * Defines the plugin name, version, and two examples hooks for how to
	 * enqueue the public-facing stylesheet and JavaScript.
	 *
	 * @package    Wt_Smart_Coupon
	 * @subpackage Wt_Smart_Coupon/public
	 */
	class Wt_Smart_Coupon_Public {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;

		/**
		 *  Module list, Module folder and main file must be same as that of module name
		 *  Please check the `register_modules` method for more details
		 *
		 *  @since 1.4.0
		 *  @var array Modules.
		 */
		public static $modules = array(
			'giveaway-product-public',
			'restriction-public',
			'auto-coupon-public',
			'url-coupon-public',
			'checkout-options-public', /* @since 1.4.6 */
			'exclude-product-public', /* @since 2.0.0 */
		);

		/**
		 * Existing modules
		 *
		 * @var array
		 */
		public static $existing_modules = array();

		/**
		 * Instance
		 *
		 * @var object
		 */
		private static $instance = null;

		/**
		 * Overwrite coupon message
		 *
		 * @var array
		 */
		protected $overwrite_coupon_message = array();

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param      string $plugin_name       The name of the plugin.
		 * @param      string $version    The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version     = $version;
		}


		/**
		 * Get Instance
		 *
		 * @since 1.4.0
		 * @param string $plugin_name Plugin name.
		 * @param string $version Plugin version.
		 * @return object Instance.
		 */
		public static function get_instance( $plugin_name, $version ) {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Wt_Smart_Coupon_Public( $plugin_name, $version );
			}

			return self::$instance;
		}

		/**
		 *  Register modules
		 *
		 *  @since 1.4.0
		 */
		public function register_modules() {
			Wt_Smart_Coupon::register_modules( self::$modules, 'wt_sc_public_modules', plugin_dir_path( __FILE__ ), self::$existing_modules );
		}

		/**
		 *  Check module enabled
		 *
		 *  @since 1.4.0
		 * @param string $module Module name.
		 * @return bool True if module exists, false otherwise.
		 */
		public static function module_exists( $module ) {
			return in_array( $module, self::$existing_modules, true );
		}


		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles() {

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wt-smart-coupon-public.css', array(), $this->version, 'all' );
		}

		/**
		 * Register the JavaScript for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts() {

			$_nonces = array(
				'public'       => wp_create_nonce( 'wt_smart_coupons_public' ),
				'apply_coupon' => wp_create_nonce( 'wt_smart_coupons_apply_coupon' ),
			);
			$params  = array(
				'ajaxurl'         => esc_url( admin_url( 'admin-ajax.php' ) ),
				'wc_ajax_url'     => esc_url( home_url( '/?wc-ajax=' ) ),
				'cart_url'        => esc_url( wc_get_cart_url() ),
				'nonces'          => $_nonces,
				'labels'          => array(
					'please_wait'      => __( 'Please wait...', 'thetoppercentile-coupons' ),
					'choose_variation' => __( 'Please choose a variation', 'thetoppercentile-coupons' ),
					'error'            => __( 'Error !!!', 'thetoppercentile-coupons' ),
				),
				'shipping_method' => array(),
				'payment_method'  => '',
				'is_cart'         => is_cart(),
			);

			if ( ! is_null( WC()->session ) ) {
				$params['shipping_method'] = is_array( WC()->session->get( 'chosen_shipping_methods' ) ) ? WC()->session->get( 'chosen_shipping_methods' ) : array();
				$params['payment_method']  = esc_html( WC()->session->get( 'chosen_payment_method' ) );
			}

			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wt-smart-coupon-public.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( $this->plugin_name, 'WTSmartCouponOBJ', $params );
		}

		/**
		 * Validate single-course rules before adding to cart.
		 *
		 * @since 2.2.9
		 * @param bool  $passed         Add-to-cart validation state.
		 * @param int   $product_id     Product id.
		 * @param int   $quantity       Quantity.
		 * @param int   $variation_id   Variation id.
		 * @param array $variations     Variation data.
		 * @param array $cart_item_data Cart item data.
		 * @return bool
		 */
		public function validate_single_course_cart_rules( $passed, $product_id, $quantity, $variation_id = 0, $variations = array(), $cart_item_data = array() ) {
			if ( ! $passed || ! WC()->cart ) {
				return $passed;
			}

			$target_id = ( $variation_id > 0 ? absint( $variation_id ) : absint( $product_id ) );

			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				if ( $current_user && wc_customer_bought_product( $current_user->user_email, $current_user->ID, $product_id ) ) {
					wc_add_notice( __( 'You have already purchased this course.', 'thetoppercentile-coupons' ), 'error' );
					return false;
				}
			}

			if ( $quantity > 1 ) {
				wc_add_notice( __( 'Only one course can be purchased at a time.', 'thetoppercentile-coupons' ), 'error' );
				return false;
			}

			foreach ( WC()->cart->get_cart() as $item ) {
				$item_id = ( ! empty( $item['variation_id'] ) ? absint( $item['variation_id'] ) : absint( $item['product_id'] ) );
				if ( $item_id === $target_id && isset( $item['quantity'] ) && absint( $item['quantity'] ) >= 1 ) {
					wc_add_notice( __( 'This course is already in your cart.', 'thetoppercentile-coupons' ), 'error' );
					return false;
				}
			}

			return $passed;
		}

		/**
		 * Prevent quantity update above 1 for each course.
		 *
		 * @since 2.2.9
		 * @param bool   $passed        Validation state.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $values        Cart item values.
		 * @param int    $quantity      Requested quantity.
		 * @return bool
		 */
		public function validate_cart_item_quantity_update( $passed, $cart_item_key, $values, $quantity ) {
			if ( ! $passed ) {
				return $passed;
			}
			if ( absint( $quantity ) > 1 ) {
				wc_add_notice( __( 'Quantity per course must be 1.', 'thetoppercentile-coupons' ), 'error' );
				return false;
			}
			return $passed;
		}

		/**
		 * Enforce single-course cart and remove legacy BOGO codes.
		 *
		 * @since 2.2.9
		 * @return void
		 */
		public function enforce_single_course_cart_rules() {
			if ( ! WC()->cart ) {
				return;
			}

			$applied_coupons = WC()->cart->get_applied_coupons();
			foreach ( $applied_coupons as $coupon_code ) {
				$is_legacy_bogo = ( 0 === strpos( (string) $coupon_code, 'buy-x-get-x' ) );
				if ( ! $is_legacy_bogo ) {
					$coupon = new WC_Coupon( $coupon_code );
					if ( $coupon && 'wt_sc_bogo' === $coupon->get_discount_type() ) {
						$is_legacy_bogo = true;
					}
				}
				if ( $is_legacy_bogo ) {
					WC()->cart->remove_coupon( $coupon_code );
				}
			}

			$changed = false;
			foreach ( WC()->cart->get_cart() as $item_key => $cart_item ) {
				if ( isset( $cart_item['quantity'] ) && absint( $cart_item['quantity'] ) > 1 ) {
					WC()->cart->set_quantity( $item_key, 1, false );
					$changed = true;
				}
			}
			if ( $changed ) {
				wc_add_notice( __( 'Each course quantity has been reset to 1.', 'thetoppercentile-coupons' ), 'notice' );
			}
		}

		/**
		 * Show remove links in checkout order review table.
		 *
		 * @since 2.2.9
		 * @return void
		 */
		public function render_checkout_remove_item_links() {
			if ( ! WC()->cart || WC()->cart->is_empty() ) {
				return;
			}
			?>
			<tr class="wt_sc_checkout_cart_actions">
				<th><?php esc_html_e( 'Cart actions', 'thetoppercentile-coupons' ); ?></th>
				<td>
					<ul class="wt_sc_checkout_remove_list">
						<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : ?>
							<li>
								<a class="wt_sc_remove_checkout_item" href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>">
									<?php esc_html_e( 'Remove item', 'thetoppercentile-coupons' ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
					<a class="button wt_sc_go_to_cart_btn" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
						<?php esc_html_e( 'Go to cart', 'thetoppercentile-coupons' ); ?>
					</a>
				</td>
			</tr>
			<?php
		}

		/**
		 * Add remove item link beside each checkout line item.
		 *
		 * @since 2.2.9
		 * @param string $quantity_html Quantity html.
		 * @param array  $cart_item     Cart item.
		 * @param string $cart_item_key Cart item key.
		 * @return string
		 */
		public function add_remove_link_in_checkout_line_item( $quantity_html, $cart_item, $cart_item_key ) {
			$remove_link = '<a class="wt_sc_inline_remove_checkout_item" href="' . esc_url( wc_get_cart_remove_url( $cart_item_key ) ) . '">' . esc_html__( 'Remove item', 'thetoppercentile-coupons' ) . '</a>';
			return $quantity_html . '<br>' . $remove_link;
		}

		/**
		 * Block checkout if no payment gateway is enabled.
		 *
		 * @since 2.2.9
		 * @param array    $data   Posted checkout data.
		 * @param WP_Error $errors Checkout validation errors.
		 * @return void
		 */
		public function validate_gateway_config_before_order( $data, $errors ) {
			if ( ! WC()->payment_gateways ) {
				return;
			}
			$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
			if ( empty( $available_gateways ) ) {
				$errors->add( 'payment', __( 'No payment gateway is configured. Please configure a payment gateway before placing order.', 'thetoppercentile-coupons' ) );
			}
		}

		/**
		 * Remove emojis from order received page visual block.
		 *
		 * @since 2.2.9
		 * @return void
		 */
		public function strip_emojis_from_order_received_page() {
			if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
				return;
			}
			?>
			<script type="text/javascript">
				(function(){
					var root = document.querySelector('.woocommerce-order, .woocommerce-order-received, .woocommerce');
					if (!root) { return; }
					var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null, false);
					var nodes = [];
					while (walker.nextNode()) { nodes.push(walker.currentNode); }
					var emojiRegex = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/gu;
					nodes.forEach(function(n){
						if (n.nodeValue && emojiRegex.test(n.nodeValue)) {
							n.nodeValue = n.nodeValue.replace(emojiRegex, '');
						}
					});
				})();
			</script>
			<?php
		}

		/**
		 * Get formatted Meta values of a coupon.
		 *
		 * @since 1.0.0
		 *
		 * @param object $coupon Coupon object.
		 * @return array Coupon data.
		 */
		public static function get_coupon_meta_data( $coupon ) {

			if ( ! $coupon || ! is_a( $coupon, 'WC_Coupon' ) ) {
				return array();
			}

			$discount_types = wc_get_coupon_types();
			$coupon_data    = array();
			$coupon_amount  = $coupon->get_amount();

			switch ( $coupon->get_discount_type() ) {
				case 'fixed_cart':
					$coupon_data['coupon_type']   = __( 'Cart discount', 'thetoppercentile-coupons' );
					$coupon_data['coupon_amount'] = Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_amount );
					break;

				case 'fixed_product':
					$coupon_data['coupon_type']   = __( 'Product discount', 'thetoppercentile-coupons' );
					$coupon_data['coupon_amount'] = Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_amount );
					break;

				case 'percent_product':
					$coupon_data['coupon_type']   = __( 'Product discount', 'thetoppercentile-coupons' );
					$coupon_data['coupon_amount'] = $coupon_amount . '%';
					break;

				case 'percent':
					$coupon_data['coupon_type']   = __( 'Cart discount', 'thetoppercentile-coupons' );
					$coupon_data['coupon_amount'] = $coupon_amount . '%';
					break;
				case 'store_credit':
					$coupon_data['coupon_type']   = $discount_types[ $coupon->get_discount_type() ];
					$coupon_data['coupon_amount'] = Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_amount );
					break;

				default:
					$coupon_data['coupon_type']   = $discount_types[ $coupon->get_discount_type() ];
					$coupon_data['coupon_amount'] = $coupon_amount;
					break;

			}

			if ( 0 === $coupon_amount && $coupon->get_free_shipping() ) {
				$coupon_data['coupon_type']   = __( 'Free shipping', 'thetoppercentile-coupons' );
				$coupon_data['coupon_amount'] = '';
			}

			$free_products = get_post_meta( $coupon->get_id(), '_wt_free_product_ids', true );

			if ( 0 === $coupon_amount && $free_products && is_string( $free_products ) && '' !== trim( $free_products ) ) {
				$coupon_data['coupon_type']   = __( 'Free products', 'thetoppercentile-coupons' );
				$coupon_data['coupon_amount'] = '';
			}

			$coupon_data['coupon_expires']    = self::get_coupon_expires( $coupon );
			$coupon_data['email_restriction'] = $coupon->get_email_restrictions();
			$coupon_data['coupon_id']         = $coupon->get_id();
			$coupon_data['start_date']        = self::get_coupon_start_date( $coupon->get_id(), true, true );

			/**
			 * Filter to alter coupon meta data.
			 *
			 * @since 1.0.0
			 * @param array $coupon_data Coupon data.
			 * @param object $coupon Coupon object.
			 * @return array Coupon data.
			 */
			return apply_filters( 'wt_smart_coupon_meta_data', $coupon_data, $coupon ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		/**
		 * Get coupon expiry date.
		 *
		 * @param object $coupon Coupon object.
		 * @return int Coupon expiry date.
		 */
		public static function get_coupon_expires( $coupon ) {
			$coupon_expiry = null;

			$coupon_expiry_date = $coupon->get_date_expires();

			if ( isset( $coupon_expiry_date ) && null !== $coupon_expiry_date ) {
				$coupon_expiry = $coupon_expiry_date->getOffsetTimestamp();
			}

			return $coupon_expiry;
		}

		/**
		 *  Get start date of a coupon.
		 *
		 *  @since  1.2.5
		 *  @since  1.4.1 Code updated
		 *  @param      int  $coupon_id Coupon ID.
		 *  @param      bool $timestamp     return timestamp or date string. (Optional) Default: false.
		 *  @param      bool $offset_timestamp     return timestamp or date string. (Optional) Default: false.
		 *  @return     string|int      timestamp or date string.
		 */
		public static function get_coupon_start_date( $coupon_id, $timestamp = false, $offset_timestamp = false ) {
			if ( ! metadata_exists( 'post', $coupon_id, '_wt_coupon_start_date' ) ) {
				return ( $timestamp ? 0 : '' );
			}

			$start_date = get_post_meta( $coupon_id, '_wt_coupon_start_date', true );

			return ( $timestamp ? Wt_Smart_Coupon_Common::get_date_timestamp( $start_date, $offset_timestamp ) : $start_date );
		}

		/**
		 *  Get formatted Start/Expiry date of a coupon.
		 *
		 *  @since 1.3.7
		 *
		 *  @param int    $date       The timestamp of the start/expiry date.
		 *  @param string $type       The type of date to format. Either "start_date" or "expiry_date".
		 *  @return string The formatted start/expiry date of the coupon.
		 */
		public static function get_coupon_start_expiry_date_texts( $date, $type = 'start_date' ) {
			$date      = intval( $date );
			$days_diff = ( ( $date - time() ) / ( 24 * 60 * 60 ) );

			$date_format = get_option( 'date_format', 'M j, Y' );
			$date_format = str_replace( 'F', 'M', $date_format );

			if ( 0 > $days_diff ) {
				$date_text = ( 'start_date' === $type ? '' : __( 'Expired', 'thetoppercentile-coupons' ) );
			} else {
				$date_text = ( 'start_date' === $type ? __( 'Starts on ', 'thetoppercentile-coupons' ) : __( 'Expires on ', 'thetoppercentile-coupons' ) ) . esc_html( date_i18n( $date_format, $date ) );

				/**
				 * Filter to alter coupon start/expiry date text.
				 *
				 * @since 1.3.7
				 * @param string $date_text Date text.
				 * @param int $date Date.
				 * @param string $type Type.
				 * @return string Date text.
				 */
				$date_text = apply_filters( 'wt_sc_alter_coupon_start_expiry_date_text', $date_text, $date, $type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			}

			return $date_text;
		}

		/**
		 *  Get all coupons used by a customer in previous orders.
		 *
		 *  @since 1.0.0
		 *  @since 1.5.1   Added HPOS Compatibility
		 *
		 * @param object $user User object.
		 * @param string $coupon_code Coupon code.
		 * @param string $return_type Return type.
		 * @return array|bool Coupons used by a customer.
		 */
		public static function get_coupon_used_by_a_customer( $user, $coupon_code = '', $return_type = 'COUPONS' ) {
			if ( ! $user ) {
				$user = wp_get_current_user();
			}
			$coupon_used = array();
			$customer_id = $user->ID;

			if ( ! $user || ! $customer_id ) {
				return false;
			}

			$customer_orders = Wt_Smart_Coupon_Common::get_orders(
				array(
					'customer' => $customer_id,
					'limit'    => -1,
				)
			);

			if ( $customer_orders ) {
				foreach ( $customer_orders as $order ) {
					$coupons = Wt_Smart_Coupon::wt_sc_is_woocommerce_prior_to( '3.7' ) ? $order->get_used_coupons() : $order->get_coupon_codes();

					if ( $coupons ) {
						$coupon_used = array_merge( $coupon_used, $coupons );
					}
				}

				$filtered_coupon_used = array();
				foreach ( $coupon_used as $coupon_code ) {
					$coupon_id = wc_get_coupon_id_by_code( $coupon_code );
					if ( $coupon_id ) {
						$coupon_display = explode( ',', get_post_meta( $coupon_id, '_wc_make_coupon_available', true ) );
						if ( ! in_array( 'my_account', $coupon_display, true ) ) {
							continue;
						}

						$coupon       = new WC_Coupon( $coupon_id );
						$date_expires = self::get_coupon_expires( $coupon );
						if ( $date_expires && $date_expires < strtotime( current_time( 'mysql' ) ) ) {
							continue;
						}

						// Check usage limits.
						$usage_limit          = $coupon->get_usage_limit();
						$usage_count          = $coupon->get_usage_count();
						$usage_limit_per_user = $coupon->get_usage_limit_per_user();
						$data_store           = $coupon->get_data_store();
						$used_by_user         = (int) $data_store->get_usage_by_user_id( $coupon, $customer_id );

						if ( ( 0 < $usage_limit && $usage_count >= $usage_limit )
							|| ( 0 < $usage_limit_per_user && $used_by_user >= $usage_limit_per_user )
						) {
							continue;
						}

						$filtered_coupon_used[] = wc_format_coupon_code( $coupon_code );
					}
				}

				if ( 'NO_OF_TIMES' === $return_type && '' !== $coupon_code ) {
					$count_of_used = array_count_values( $filtered_coupon_used );
					return isset( $count_of_used[ $coupon_code ] ) ? $count_of_used[ $coupon_code ] : 0;
				}

				/**
				 * Filter to alter used coupons.
				 *
				 * @since 1.0.0
				 * @param array $filtered_coupon_used Used coupons.
				 * @param object $user User object.
				 * @return array Used coupons.
				 */
				return apply_filters( 'wt_smart_coupon_used_coupons', array_unique( $filtered_coupon_used ), $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			} else {
				return false;
			}
		}

		/**
		 * Check if coupon applicable to specific user roles
		 *
		 * @since  1.2.6
		 * @param int $coupon_id Coupon ID.
		 * @return bool
		 */
		public static function wt_sc_check_valid_user_roles( $coupon_id ) {
			$_wt_sc_user_roles = get_post_meta( $coupon_id, '_wt_sc_user_roles', true );
			if ( isset( $_wt_sc_user_roles ) ) {
				if ( '' !== $_wt_sc_user_roles && ! is_array( $_wt_sc_user_roles ) ) {
					$_wt_sc_user_roles = explode( ',', $_wt_sc_user_roles );
				}
				$user = wp_get_current_user();
				if ( isset( $user ) ) {
					$user_roles = ( isset( $user->roles ) && is_array( $user->roles ) ) ? $user->roles : array();
					if ( ! empty( $_wt_sc_user_roles ) ) {
						$roles = array_intersect( $user_roles, $_wt_sc_user_roles );
						if ( empty( $roles ) ) {
							return false;
						}
					}
				}
			}
			return true;
		}

		/**
		 * Check if coupon is valid for displaying.
		 *
		 * @param object $coupon Coupon object.
		 * @param string $email Email.
		 * @param int    $user_id User ID.
		 * @param bool   $display_invalid_coupons Display invalid coupons.
		 * @param array  $expired_coupon Expired coupons.
		 * @param string $expire_text Expire text.
		 * @return bool True if coupon is valid for displaying, false otherwise.
		 */
		public static function coupon_is_valid_for_displaying( $coupon, $email, $user_id, $display_invalid_coupons, &$expired_coupon, &$expire_text ) {
			$coupon_obj        = new WC_Coupon( $coupon->ID );
			$email_restriction = $coupon_obj->get_email_restrictions();

			// Check is coupon restricted for other Email.
			if ( ! empty( $email_restriction ) && ! in_array( $email, $email_restriction, true ) ) {
				return false;
			}

			// Check is coupon restricted for the user roles.
			$coupon_id = $coupon_obj->get_id();
			if ( self::wt_sc_check_valid_user_roles( $coupon_id ) === false ) {
				return false;
			}

			// Check is Coupon Expired.
			$coupon_data = self::get_coupon_meta_data( $coupon_obj );

			if ( isset( $coupon_data['coupon_expires'] ) && ! is_null( $coupon_data['coupon_expires'] ) ) {
				$expire_text = self::get_coupon_start_expiry_date_texts( $coupon_data['coupon_expires'], 'expiry_date' );
				if ( __( 'Expired', 'thetoppercentile-coupons' ) === $expire_text ) {
					array_push( $expired_coupon, $coupon_obj->get_code() );
					return false;
				}
			} else {
				$expire_text = '';
			}

			// Check is usage limit per user is exeeded.
			if ( $coupon_obj->get_usage_limit() > 0 && $coupon_obj->get_usage_count() >= $coupon_obj->get_usage_limit() ) {
				array_push( $expired_coupon, $coupon_obj->get_code() );
				return false;
			}

			if ( $coupon_obj && $user_id && $coupon_obj->get_usage_limit_per_user() > 0 && $coupon_obj->get_id() && $coupon_obj->get_data_store() ) {
				$data_store  = $coupon_obj->get_data_store();
				$usage_count = $data_store->get_usage_by_user_id( $coupon_obj, $user_id );
				if ( $usage_count >= $coupon_obj->get_usage_limit_per_user() ) {
					array_push( $expired_coupon, $coupon_obj->get_code() );
					return false;
				}
			}

			if ( false === $display_invalid_coupons ) {
				$discounts = new WC_Discounts( WC()->cart );
				if ( is_wp_error( $discounts->is_coupon_valid( $coupon_obj ) ) ) {
					return false;
				}
			}

			return true;
		}


		/**
		 *  Get coupon html based on current style
		 *
		 *  @since 1.1.0
		 *  @since 1.4.7    Taking data from `Coupon_Style` module
		 *
		 * @param object $coupon Coupon object.
		 * @param array  $coupon_data Coupon data.
		 * @param string $coupon_type Coupon type.
		 * @param bool   $include_css Include CSS.
		 * @return string Coupon HTML.
		 */
		public static function get_coupon_html( $coupon, $coupon_data, $coupon_type = 'available_coupon', $include_css = false ) {
			$coupon_html = '';

			if ( Wt_Smart_Coupon_Common::module_exists( 'style' ) ) {
				if ( 'email_coupon' === $coupon_type ) {
					/* forcefully include CSS along with HTML*/
					$include_css = true;
				}

				$coupon_id   = ( isset( $coupon_data['coupon_id'] ) ? absint( $coupon_data['coupon_id'] ) : 0 );
				$coupon      = new WC_Coupon( $coupon_id );
				$coupon_html = Wt_Smart_Coupon_Style::prepare_coupon_html( $coupon, $coupon_data, $coupon_type, $include_css );
			}

			return $coupon_html;
		}


		/**
		 *  This function will print CSS for default coupon code block
		 *
		 *  @since 1.4.7
		 */
		public static function print_coupon_default_css() {
			if ( Wt_Smart_Coupon_Common::module_exists( 'style' ) ) {
				echo '<style type="text/css">';
				echo wp_kses_post( Wt_Smart_Coupon_Style::get_coupon_default_css() );
				echo '</style>';
			}
		}

		/**
		 * Ensure a coupon input is present on checkout.
		 *
		 * Some themes (or customizations) remove WooCommerce's default
		 * `woocommerce_checkout_coupon_form` hook. When missing, customers can't apply
		 * coupons on checkout. This re-adds the standard WooCommerce form only when
		 * it is not already being output.
		 *
		 * @since 2.2.9
		 */
		public function maybe_add_checkout_coupon_form() {
			if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
				return;
			}

			// If the default form is already hooked, do nothing to avoid duplicates.
			if ( has_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form' ) ) {
				return;
			}

			if ( function_exists( 'woocommerce_checkout_coupon_form' ) ) {
				woocommerce_checkout_coupon_form();
			}
		}

		/**
		 * Ensure a coupon input is present on cart page.
		 *
		 * Some themes override the cart template and hide/remove the coupon box.
		 * This prints a simple cart coupon form only when the default coupon field
		 * does not exist in the current cart form DOM.
		 *
		 * @since 2.2.9
		 */
		public function maybe_add_cart_coupon_form() {
			if ( ! function_exists( 'wc_coupons_enabled' ) || ! wc_coupons_enabled() ) {
				return;
			}

			?>
			<div class="wt-sc-cart-coupon-fallback" style="display:none;">
				<p class="form-row form-row-first">
					<label for="wt_sc_coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon code', 'thetoppercentile-coupons' ); ?></label>
					<input type="text" name="coupon_code" class="input-text" id="wt_sc_coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'thetoppercentile-coupons' ); ?>" />
				</p>
				<p class="form-row form-row-last">
					<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'thetoppercentile-coupons' ); ?>"><?php esc_html_e( 'Apply coupon', 'thetoppercentile-coupons' ); ?></button>
				</p>
				<div class="clear"></div>
			</div>
			<script type="text/javascript">
				(function($){
					$(function(){
						var $cartForm = $('form.woocommerce-cart-form');
						if (!$cartForm.length) {
							return;
						}
						if ($cartForm.find('input[name="coupon_code"], #coupon_code').length) {
							return;
						}
						$cartForm.find('.wt-sc-cart-coupon-fallback').show();
					});
				})(jQuery);
			</script>
			<?php
		}


		/**
		 * Print coupon CSS
		 *
		 *  @since 1.3.7
		 */
		public static function print_coupon_css() {
			wc_deprecated_function( 'Wt_Smart_Coupon_Public::print_coupon_css', '1.4.7', 'Wt_Smart_Coupon_Public::print_coupon_default_css' );

			self::print_coupon_default_css();
		}


		/**
		 *  Display available coupons in checkout
		 *
		 *  @since 1.3.7
		 */
		public function display_available_coupon_in_checkout() {
			$offset = ( isset( $_GET['wt_sc_available_coupons_offset'] ) ? absint( wp_unslash( $_GET['wt_sc_available_coupons_offset'] ) ) : 0 ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			/**
			 * Filter to alter checkout available coupons per page.
			 *
			 * @since 1.3.7
			 * @param int $limit Limit.
			 * @return int Limit.
			 */
			$limit = apply_filters( 'wt_sc_checkout_available_coupons_per_page', 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			/**
			 * Action to execute before checkout coupons.
			 *
			 * @since 1.3.7
			 */
			do_action( 'wt_smart_coupon_before_checkout_coupons' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			$available_coupons = self::print_user_available_coupon( false, 'checkout', $offset, $limit );

			/**
			 * Action to execute after checkout coupons.
			 *
			 * @since 1.3.7
			 * @param array $available_coupons Available coupons.
			 */
			do_action( 'wt_smart_coupon_after_checkout_coupons', array( 'available_coupons' => $available_coupons ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		/**
		 * Action for displaying avalable coupon in cart page.
		 *
		 * @since 1.4.3
		 */
		public function display_available_coupon_in_cart() {
			$offset = ( isset( $_GET['wt_sc_available_coupons_offset'] ) ? absint( wp_unslash( $_GET['wt_sc_available_coupons_offset'] ) ) : 0 ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			/**
			 * Filter to alter cart available coupons per page.
			 *
			 * @since 1.4.1
			 * @param int $limit Limit.
			 * @return int Limit.
			 */
			$limit = apply_filters( 'wt_sc_cart_available_coupons_per_page', 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			self::print_user_available_coupon( false, 'cart', $offset, $limit );
		}

		/**
		 *  Get user coupons
		 *
		 *  @since 1.4.1
		 *  @since 1.4.3    SQL query updated to take data from lookup table
		 *  @since 1.4.4    Recursively fetching the coupon ids to reach the limit.
		 *                  [Bug fix] Non existing post ids are in the list.
		 * @param object $user User object.
		 * @param int    $offset Offset.
		 * @param int    $limit Limit.
		 * @param array  $args Arguments.
		 * @return array User coupons.
		 */
		public static function get_user_coupons( $user = '', $offset = 0, $limit = 30, $args = array() ) {
			global $wpdb;

			if ( ! $user ) {
				$user = wp_get_current_user();
			}
			if ( $user ) {
				$user_id = $user->ID;
				$email   = $user->user_email;
			} else {
				return array();
			}

			$type = ( isset( $args['type'] ) ? $args['type'] : 'available_coupons' );

			if ( ! in_array( $type, array( 'available_coupons', 'expired_coupons', 'auto_coupons' ), true ) ) {
				return array(); /* not in the allowed type list */
			}

			$lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

			if ( ! Wt_Smart_Coupon::is_table_exists( $lookup_tb ) ) {
				return array();
			}

			$already_fetched_ids       = isset( $args['already_fetched_ids'] ) && is_array( $args['already_fetched_ids'] ) ? $args['already_fetched_ids'] : array();
			$already_fetched_ids_count = count( $already_fetched_ids );

			$sql             = "SELECT coupon_id AS ID FROM {$lookup_tb} WHERE post_status = 'publish' AND is_wt_gc_wallet_coupon = 0";
			$sql_placeholder = array();

			if ( 'available_coupons' === $type || 'expired_coupons' === $type ) {
				$section          = ( isset( $args['section'] ) ? $args['section'] : 'my_account' );
				$allowed_sections = array( 'my_account', 'checkout', 'cart' );

				if ( '' !== $section && in_array( $section, $allowed_sections, true ) ) {
					$sql .= " AND {$section}_display = 1";
				}
			} else /* 'auto_coupons' */
			{
				$sql .= ' AND is_auto_coupon = 1';
			}

			// usage count check.
			$used_by_meta_sql = "(SELECT COUNT(pm.meta_id) FROM {$wpdb->postmeta} AS pm WHERE pm.post_id = coupon_id AND pm.meta_key = '_used_by' AND pm.meta_value = %s)";

			// email restriction.
			if ( '' !== trim( $email ) ) {
				$sql              .= " AND (email_restriction = 'a:0:{}' OR email_restriction LIKE %s OR email_restriction LIKE '%*%')";
				$sql_placeholder[] = '%' . $wpdb->esc_like( $email ) . '%';
			} else {
				$sql .= " AND (email_restriction = 'a:0:{}' OR email_restriction = '')";
			}

			// user roles.
			$user_roles = ( isset( $user->roles ) && is_array( $user->roles ) ? $user->roles : array() );

			/**
			 *  Guest users do not have user roles, so add one to the array to filter user coupons.
			 *
			 *  @since 1.7.0
			 */
			if ( empty( $user_roles ) && 0 === $user_id ) {
				$user_roles = array( 'wbte_sc_guest' );
			}

			if ( ! empty( $user_roles ) ) {
				$role_sql_arr = array();
				foreach ( $user_roles as $k => $user_role ) {
					$role_sql_arr[]   = 'user_roles LIKE %s';
					$user_roles[ $k ] = '%' . $wpdb->esc_like( $user_role ) . '%';
				}
				$sql .= " AND (user_roles = '' OR " . $wpdb->prepare( implode( ' OR ', $role_sql_arr ), $user_roles ) . ')'; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders
			}

			$gmt_offset = wc_timezone_offset();

			if ( 'available_coupons' === $type || 'auto_coupons' === $type ) {
				// expiry date check.
				$sql              .= " AND (expiry = '' OR 
                    ((UNIX_TIMESTAMP(expiry) + TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP()))) - %d) >= UNIX_TIMESTAMP()
                )";
				$sql_placeholder[] = $gmt_offset;

				// store credit amount check.
				$sql .= " AND ((discount_type = 'store_credit' AND amount > 0) OR discount_type != 'store_credit')";

				// usage limit check.
				$sql .= ' AND (usage_limit = 0 OR (usage_limit > 0 AND usage_limit > usage_count))';
				$sql .= " AND (usage_limit_per_user = 0 OR (usage_limit_per_user > 0 AND usage_limit_per_user > {$used_by_meta_sql}))";

				$sql_placeholder[] = 0 < $user_id ? $user_id : $email;

			} else /* 'expired_coupons' */
			{
				// expiry date check, store credit amount check, usage limit check.
				$sql .= " AND (
                    (expiry != '' AND 
                        ((UNIX_TIMESTAMP(expiry) + TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP()))) - %d) < UNIX_TIMESTAMP()
                    ) OR 
                    (discount_type = 'store_credit' AND amount <= 0) OR 
                    ((usage_limit > 0 AND usage_limit <= usage_count) OR (usage_limit_per_user > 0 AND usage_limit_per_user <= {$used_by_meta_sql}))
                )";

				$sql_placeholder[] = $gmt_offset;
				$sql_placeholder[] = $user_id;
			}

			// process order by data.
			$orderby_data = ( isset( $args['orderby'] ) ? $args['orderby'] : self::get_available_coupons_sort_order() );
			$orderby_arr  = explode( ':', $orderby_data );

			$orderby_allowed = array(
				'created_date' => 'id',
				'amount'       => 'amount',
			);
			$orderby         = ( ! isset( $orderby_allowed[ $orderby_arr[0] ] ) ? 'created_date' : $orderby_arr[0] );

			$orderby_order = strtolower( isset( $orderby_arr[1] ) ? $orderby_arr[1] : 'asc' );
			$orderby_order = strtoupper( ! in_array( $orderby_order, array( 'asc', 'desc' ), true ) ? 'asc' : $orderby_order );

			$sql              .= " ORDER  BY {$orderby_allowed[$orderby]} {$orderby_order} LIMIT %d, %d";
			$sql_placeholder[] = $offset;
			$sql_placeholder[] = $limit;

			$post_ids = $wpdb->get_col( $wpdb->prepare( $sql, $sql_placeholder ) ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$post_ids = ( $post_ids ? $post_ids : array() );

			/**
			 *  Filter the post_ids. In some cases lookup table was unable to record the post unavailability. So we need to do a further check here to evaluate this.
			 *
			 *  @since 1.4.4
			 */
			$fetched_post_ids  = $post_ids; // this is using to check any data exists in DB.
			$remaining_limit   = $limit - $already_fetched_ids_count; // may be not the first iteration.
			$filtered_post_ids = array();

			foreach ( $post_ids as $post_id ) {
				if ( 'publish' === get_post_status( $post_id ) ) {
					$filtered_post_ids[] = $post_id;

					if ( count( $filtered_post_ids ) === $remaining_limit ) {
						break;
					}
				}
			}

			$post_ids = array_merge( $already_fetched_ids, $filtered_post_ids ); // may be this not the first loop. So merge post ids from previous recrusion.

			/**
			 *  Recrusively fetch the coupon ids.
			 *  Here we are checking  not empty of $fetched_post_ids instead of $post_ids because we have to find data in the DB is finished or not. This is usefull when all of the post_ids are filtered by the above `foreach`.
			 *  Here we are altered the offset, but not the limit by remaining count, Because it may increase the iteration count in some situations.
			 *  Count of $fetched_post_ids and $limit is equal then assumes database have more records to fetch
			 *
			 *  @since 1.4.4
			 */
			if ( ! empty( $fetched_post_ids ) && count( $fetched_post_ids ) === $limit && count( $post_ids ) < $limit ) {
				$new_offset                  = $limit + $offset;
				$args['already_fetched_ids'] = $post_ids;

				return self::get_user_coupons( $user, $new_offset, $limit, $args );
			}

			/**
			 * Filter to alter user coupons.
			 *
			 * @since 1.7.0
			 * @param array $post_ids Post IDs.
			 * @param array $args Arguments.
			 * @return array Post IDs.
			 */
			return apply_filters( 'wt_sc_alter_user_coupons', $post_ids, $args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		/**
		 *  Fix the flex item alignment issue
		 *
		 *  @since 1.4.1
		 *  @param int $count Count of hidden coupon boxes.
		 */
		public static function add_hidden_coupon_boxes( $count = 2 ) {
			for ( $i = 0; $i < $count; $i++ ) {
				echo '<div class="wt-sc-hidden-coupon-box"></div>';
			}
		}

		/**
		 *  Print available coupon for a user
		 *
		 *  @param object|false $user        WP_User instance.
		 *  @param string       $section     section to print.
		 *  @param int          $offset      offset of records.
		 *  @param int          $limit       max records.
		 *  @param bool         $to_print       If false function will only return the data as array. Otherwise print the data along with return.
		 *  @param bool         $by_shortcode If true, the coupons are printed by shortcode.
		 *
		 *  @return array    array of coupon objects. Empty array if no record exists.
		 */
		public static function print_user_available_coupon( $user = false, $section = 'my_account', $offset = 0, $limit = 30, $to_print = true, $by_shortcode = false ) {
			if ( ! $user ) {
				$user = wp_get_current_user();
			}
			if ( $user ) {
				$email = $user->user_email;
			} else {
				return array();
			}

			$orderby = isset( $_GET['wt_sc_available_coupons_orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['wt_sc_available_coupons_orderby'] ) ) : self::get_available_coupons_sort_order(); //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$args = array(
				'type'         => 'available_coupons',
				'section'      => $section,
				'orderby'      => $orderby,
				'by_shortcode' => $by_shortcode,
			);

			$post_ids = self::get_user_coupons( $user, $offset, $limit, $args );

			$out = array();

			$printed = false;
			if ( ! empty( $post_ids ) ) {

				/**
				 *  Display cart valid coupons
				 *
				 * @since 1.4.1
				 * @param bool $display_invalid_coupons Display invalid coupons.
				 * @param string $section Section.
				 * @return bool Display invalid coupons.
				 */
				$display_invalid_coupons = apply_filters( 'wt_smart_coupon_display_invalid_coupons', true, $section ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				$e = 0;

				foreach ( $post_ids as $post_id ) {
					$coupon_obj = new WC_Coupon( $post_id );

					if ( false === $display_invalid_coupons ) {
						$discounts = new WC_Discounts( WC()->cart );
						$valid     = $discounts->is_coupon_valid( $coupon_obj );
						if ( is_wp_error( $valid ) ) {
							continue;
						}
					}

					// Limit to defined email addresses.
					if ( ! self::is_coupon_emails_allowed( array( $email ), $coupon_obj ) ) {
						continue;
					}

					if ( 0 === $e && $to_print ) {
						echo '<div class="wt_coupon_wrapper">';

						// inject CSS for coupon block.
						self::print_coupon_default_css();
						$printed = true;
					}

					$post                           = get_post( $post_id );
					$coupon_data                    = self::get_coupon_meta_data( $coupon_obj );
					$coupon_data['display_on_page'] = $by_shortcode ? 'by_shortcode' : $section . '_page';

					/**
					 * Filter to alter coupon post object before printing.
					 *
					 * @since 1.4.1
					 * @param object $post Post object.
					 * @param object $user User object.
					 * @param string $section Section.
					 * @return object Post object.
					 */
					$post = apply_filters( 'wt_alter_coupon_for_user_before_printing', $post, $user, $section ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

					/**
					 * Filter to alter coupon data before printing.
					 *
					 * @since 1.4.1
					 * @param array $coupon_data Coupon data.
					 * @param object $post Post object.
					 * @param object $user User object.
					 * @param string $section Section.
					 * @return array Coupon data.
					 */
					$coupon_data = apply_filters( 'wt_alter_coupon_data_for_user_before_printing', $coupon_data, $post, $user, $section ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

					if ( $to_print ) {
						if ( 0 === $e ) {
							echo self::get_coupon_html( $post, $coupon_data, 'available_coupon', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
						} else {
							echo self::get_coupon_html( $post, $coupon_data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
						}

						++$e;
					}

					$out[] = $coupon_obj;
				}

				if ( $to_print ) {
					self::add_hidden_coupon_boxes();
					if ( $printed ) {
						echo '</div>';
					}
				}
			}

			if ( $to_print && ! $printed && 'my_account' === $section ) {
					echo '<div class="wt_sc_myaccount_no_available_coupons">';
					/**
					 * Filter to alter my account no available coupons message.
					 *
					 * @since 1.4.1
					 * @param string $message Message.
					 * @return string Message.
					 */
					echo wp_kses_post( apply_filters( 'wt_sc_alter_myaccount_no_available_coupons_msg', __( "Sorry, you don't have any available coupons", 'thetoppercentile-coupons' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					echo '</div>';
			}

			/**
			 * Filter to enable pagination in user available coupons.
			 *
			 * @since 1.4.1
			 * @param bool $enable_pagination Enable pagination.
			 * @param string $section Section.
			 * @return bool Enable pagination.
			 */
			if ( $to_print && apply_filters( 'wt_sc_enable_pagination_in_user_available_coupons', true, $section ) && ! empty( $post_ids ) ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				global $wp;
				$display_args = WC()->session->get( 'wbte_sc_coupons_cart_checkout_display_args', array() );

				$current_url = isset( $display_args['url'] ) && defined( 'REST_REQUEST' ) && REST_REQUEST ? $display_args['url'] : home_url( $wp->request );

				$url_params = ! is_array( $_GET ) ? array() : wc_clean( wp_unslash( $_GET ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended

				/* previous link */
				$prev_url       = '';
				$prev_link_html = '';

				if ( 0 < $offset ) {
					$new_offset = max( ( $offset - $limit ), 0 ); // lesser than zero is not allowed.
					$post_ids   = self::get_user_coupons( $user, $new_offset, 1, $args );

					if ( ! empty( $post_ids ) ) {
						if ( 0 === $new_offset ) {
							unset( $url_params['wt_sc_available_coupons_offset'] );
						} else {
							$url_params['wt_sc_available_coupons_offset'] = $new_offset;
						}

						$prev_url       = $current_url . '?' . build_query( $url_params );
						$prev_link_html = '<a href="' . esc_attr( $prev_url ) . '" class="wt_sc_pagination_previous woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button">' . __( 'Previous', 'thetoppercentile-coupons' ) . '</a>';
					}
				}

				/* next link */
				$next_url       = '';
				$next_link_html = '';
				$new_offset     = $offset + $limit;
				$post_ids       = self::get_user_coupons( $user, $new_offset, 1, $args );
				if ( ! empty( $post_ids ) ) {
					$url_params['wt_sc_available_coupons_offset'] = $new_offset;

					$next_url       = $current_url . '?' . build_query( $url_params );
					$next_link_html = '&nbsp;<a href="' . esc_attr( $next_url ) . '" class="wt_sc_pagination_next woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button">' . __( 'Next', 'thetoppercentile-coupons' ) . '</a>';
				}

				if ( '' !== $prev_link_html || '' !== $next_link_html ) {
					?>
					<div class="wt_sc_pagination">
						<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
							<?php

							/**
							 * Filter to alter user available coupons previous link html.
							 *
							 * @since 1.4.1
							 * @param string $prev_link_html Previous link html.
							 * @param string $prev_url Previous url.
							 * @return string Previous link html.
							 */
							echo wp_kses_post( apply_filters( 'wt_sc_alter_user_available_coupons_previous_link_html', $prev_link_html, $prev_url ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

							/**
							 * Filter to alter user available coupons next link html.
							 *
							 * @since 1.4.1
							 * @param string $next_link_html Next link html.
							 * @param string $next_url Next url.
							 * @return string Next link html.
							 */
							echo wp_kses_post( apply_filters( 'wt_sc_alter_user_available_coupons_next_link_html', $next_link_html, $next_url ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

							?>
						</div>
					</div>
					<?php
				}
			}

			return $out;
		}


		/**
		 *  Get sort order for available coupons
		 *
		 *  @since 1.4.1
		 */
		public static function get_available_coupons_sort_order() {
			/**
			 * Filter to alter available coupons sort order.
			 *
			 * @since 1.4.1
			 * @return string Sort order.
			 */
			return apply_filters( 'wt_sc_alter_available_coupons_sort_order', 'created_date:asc' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		/**
		 * Overwrite the coupon added message
		 *
		 * @param string $coupon Coupon code.
		 * @param string $new_message New message.
		 */
		public function start_overwrite_coupon_success_message( $coupon, $new_message = '' ) {
			$this->overwrite_coupon_message[ $coupon ] = $new_message;
			add_filter( 'woocommerce_coupon_message', array( $this, 'overwrite_coupon_code_message' ), 10, 3 );
		}

		/**
		 * Display the coupon message
		 *
		 * @param string $msg Message.
		 * @param string $msg_code Message code.
		 * @param object $coupon Coupon object.
		 * @return string Message.
		 */
		public function overwrite_coupon_code_message( $msg, $msg_code, $coupon ) {
			if ( isset( $this->overwrite_coupon_message[ $coupon->get_code() ] ) ) {
				$msg = $this->overwrite_coupon_message[ $coupon->get_code() ];
			}
			return $msg;
		}

		/**
		 * Stop overwriting coupon
		 */
		public function stop_overwrite_coupon_success_message() {
			remove_filter( 'woocommerce_coupon_message', array( $this, 'overwrite_coupon_code_message' ), 10 );
			$this->overwrite_coupon_message = array();
		}

		/**
		 * Checks if the given email address(es) matches the ones specified on the coupon.
		 *
		 * @since 1.4.3 [Bug fix] Email validation is not working properly when email has capital letters
		 * @param array  $check_emails Array of customer email addresses.
		 * @param object $coupon_obj Coupon object.
		 * @return bool
		 */
		public static function is_coupon_emails_allowed( $check_emails, $coupon_obj ) {
			$restrictions = $coupon_obj->get_email_restrictions();

			if ( empty( $restrictions ) ) {
				return true;
			}

			foreach ( $check_emails as $check_email ) {

				$check_email = strtolower( $check_email );

				// With a direct match we return true.
				if ( in_array( $check_email, $restrictions, true ) ) {
					return true;
				}

				// Go through the allowed emails and return true if the email matches a wildcard.
				foreach ( $restrictions as $restriction ) {
					// Convert to PHP-regex syntax.
					$regex = '/^' . str_replace( '*', '(.+)?', $restriction ) . '$/';
					preg_match( $regex, $check_email, $match );
					if ( ! empty( $match ) ) {
						return true;
					}
				}
			}

			// No matches, this one isn't allowed.
			return false;
		}



		/**
		 *  Ajax action function for applying coupon on coupon block click
		 *
		 *  @since 1.4.8
		 */
		public function apply_coupon() {
			check_ajax_referer( 'wt_smart_coupons_apply_coupon', '_wpnonce' );

			$coupon_code = ( isset( $_POST['coupon_code'] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['coupon_code'] ) ) : false ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized with helper function.
			$coupon_id   = ( isset( $_POST['coupon_id'] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['coupon_id'] ), 'absint' ) : 0 ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized with helper function.

			if ( ! $coupon_code && 0 === $coupon_id ) {
				return;
			}

			$coupon_obj = new WC_Coupon( $coupon_id );

			if ( 0 === $coupon_obj->get_id() ) {
				return;
			}

			$coupon_code = $coupon_obj->get_code();

			if ( 0 < WC()->cart->get_cart_contents_count() ) {
				WC()->cart->add_discount( $coupon_code );
			} else {
				$message = __( 'Coupon code applied successfully, Please add products into cart', 'thetoppercentile-coupons' );
				/**
				 * Filter to alter coupon message when cart is empty.
				 *
				 * @since 1.7.0
				 * @param string $message Message.
				 * @return string Message.
				 */
				$message = apply_filters( 'wt_smart_coupon_click_to_apply_coupon_message_cart_empty', $message ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				$this->start_overwrite_coupon_success_message( $coupon_code, $message );
				WC()->cart->add_discount( $coupon_code );
				$this->stop_overwrite_coupon_success_message();
			}

			wc_print_notices();
			die();
		}

		/**
		 *  Ajax callback to save checkout values for block checkout.
		 *
		 *  @since 1.6.0
		 */
		public function set_block_checkout_values() {

			// Nonce verification.
			$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
			$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_public' ) || is_null( WC()->session ) ) {
				exit();
			}

			// Set payment method.
			$payment_method = ( isset( $_POST['payment_method'] ) ? wc_clean( sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) ) : '' );
			WC()->session->set( 'chosen_payment_method', $payment_method );

			// Set shipping.
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
			$chosen_shipping_methods = is_array( $chosen_shipping_methods ) ? $chosen_shipping_methods : array();
			$posted_shipping_methods = array();

			if ( isset( $_POST['shipping_method'] ) ) {
				$posted_shipping_methods = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['shipping_method'] ), 'text_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
			}

			if ( is_array( $posted_shipping_methods ) ) {
				foreach ( $posted_shipping_methods as $i => $value ) {
					$chosen_shipping_methods[ $i ] = $value;
				}
			}

			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

			exit();
		}

		/**
		 *  Determines whether the current request is for an administrative interface page.
		 *
		 *  @since 2.0.0
		 *  @return boolean   Is admin or not
		 */
		public static function is_admin() {
			/**
			 *  Hook to bypass is_admin check
			 *  Example scenario: When scripts using admin ajax on frontend.
			 *
			 *  @since 2.0.0
			 *  @param  bool     Is admin or not, Default: true
			 */
			return is_admin() && apply_filters( 'wt_sc_bypass_is_admin_check', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		/**
		 *  Ajax callback to update payment method on session when payment method is changed
		 *
		 *  @since 2.2.0
		 */
		public function update_payment_method_on_session() {

			check_ajax_referer( 'wt_smart_coupons_public', '_wpnonce' );

			$applied_coupons_before = WC()->cart->get_applied_coupons();

			$payment_method = ( isset( $_POST['payment_method'] ) ? wc_clean( sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) ) : '' );
			WC()->session->set( 'chosen_payment_method', $payment_method );

			WC()->cart->calculate_totals();

			$applied_coupons = WC()->cart->get_applied_coupons();
			if ( ! empty( array_diff( $applied_coupons_before, $applied_coupons ) ) ) {
				wp_send_json( true );
			} else {
				wp_send_json( false );
			}
		}

		/**
		 * Add 'available coupon in block cart/checkout' blocks data
		 * Hooked into: wbte_sc_alter_blocks_data
		 *
		 *  @since 2.2.1
		 *  @param array $block_data block data array.
		 *  @return array block data array with added coupon blocks data.
		 */
		public function add_coupon_blocks_data( $block_data ) {

			$get_value_altered = false;
			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				if ( WC()->session ) {
					$display_args = WC()->session->get( 'wbte_sc_coupons_cart_checkout_display_args', array() );
					if ( ! empty( $display_args ) && isset( $display_args['get_values'] ) ) {
						$_GET              = $display_args['get_values'];
						$get_value_altered = true;
					}
				}
			}

			ob_start();
			$this->display_available_coupon_in_cart();
			$coupons_html                     = ob_get_clean();
			$block_data['coupon_blocks_cart'] = $coupons_html;

			ob_start();
			$this->display_available_coupon_in_checkout();
			$coupons_html                         = ob_get_clean();
			$block_data['coupon_blocks_checkout'] = $coupons_html;

			if ( WC()->session && ! $get_value_altered ) {
				global $wp;
				WC()->session->set(
					'wbte_sc_coupons_cart_checkout_display_args',
					array(
						'get_values' => wc_clean( wp_unslash( $_GET ) ), //phpcs:ignore WordPress.Security.NonceVerification.Recommended
						'url'        => home_url( $wp->request ),
					)
				);
			}

			return $block_data;
		}
	}
}