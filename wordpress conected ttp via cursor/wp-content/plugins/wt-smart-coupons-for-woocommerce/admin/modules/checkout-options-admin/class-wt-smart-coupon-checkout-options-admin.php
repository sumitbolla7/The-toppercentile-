<?php
/**
 * Checkout options admin
 *
 * @link
 * @since 1.4.6
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Checkout_Options' ) ) {
	return;
}


/**
 * Class Wt_Smart_Coupon_Checkout_Options_Admin
 *
 * @since 1.4.6
 */
class Wt_Smart_Coupon_Checkout_Options_Admin extends Wt_Smart_Coupon_Checkout_Options {

	/**
	 * Module base
	 *
	 * @var string Module base.
	 */
	public $module_base = 'checkout_options';

	/**
	 * Module id
	 *
	 * @var string Module id.
	 */
	public $module_id = '';

	/**
	 * Module id static
	 *
	 * @var string Module id.
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

		add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'add_checkout_options_tab' ), 20, 1 );
		add_action( 'woocommerce_coupon_data_panels', array( $this, 'checkout_options_tab_content' ), 10, 0 );
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ) );
	}


	/**
	 *  Get Instance
	 *
	 *  @since 1.4.6
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wt_Smart_Coupon_Checkout_Options_Admin();
		}

		return self::$instance;
	}


	/**
	 *  Add tabs to the coupon data section.
	 *
	 *  @since  1.4.6
	 *  @param  array $tabs   Array of tabs.
	 *  @return array    $tabs   Array of tabs
	 */
	public function add_checkout_options_tab( $tabs ) {

		$tabs['wt_coupon_checkout_options'] = array(
			'label'  => __( 'Checkout options', 'thetoppercentile-coupons' ),
			'target' => 'webtoffee_coupondata_checkout1',
			'class'  => 'webtoffee_coupondata_checkout1',
		);

		return $tabs;
	}


	/**
	 *  Tab content for checkout options tab.
	 *
	 *  @since 1.4.6
	 */
	public function checkout_options_tab_content() {
		global $thepostid, $post;

		$post_id = ( empty( $thepostid ) ? $post->ID : $thepostid );
		?>
		<div id="webtoffee_coupondata_checkout1" class="panel woocommerce_options_panel">
			
			<!-- Shipping methods:start -->
			<p class="form-field">
				<label for="_wt_sc_shipping_methods"><?php esc_html_e( 'Shipping methods', 'thetoppercentile-coupons' ); ?></label>
				<select id="_wt_sc_shipping_methods" name="_wt_sc_shipping_methods[]" style="width:50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any shipping method', 'thetoppercentile-coupons' ); ?>">
					<?php
					$shipping_methods = WC()->shipping->load_shipping_methods();

					if ( ! empty( $shipping_methods ) ) {

						$shipping_method_ids = self::get_processed_coupon_meta_value( $post_id, '_wt_sc_shipping_methods' );
						foreach ( $shipping_methods as $shipping_method ) {

							// Skip disabled items.
							if ( 'yes' !== $shipping_method->enabled ) {
								continue;
							}

							$method_title = $shipping_method->method_title;
							$method_id    = $shipping_method->id;

							if ( 'pickup_location' === $method_id ) {
								$method_title .= __( ' (Only for block checkout)', 'thetoppercentile-coupons' );
							}

							?>
							<option value="<?php echo esc_attr( $method_id ); ?>"
							<?php
							echo selected( in_array( $method_id, $shipping_method_ids, true ), true, false );
							?>
							>
								<?php echo esc_html( $method_title ); ?>
							</option>
							<?php
						}
					}
					?>
				</select>
				<?php echo wp_kses_post( wc_help_tip( __( 'Coupon will be applicable if any of these shipping methods are selected.', 'thetoppercentile-coupons' ) ) ); ?> 
			</p>
			<!-- Shipping methods:end -->


			<!-- Payment methods:start -->
			<p class="form-field">
				<label for="_wt_sc_payment_methods"><?php esc_html_e( 'Payment methods', 'thetoppercentile-coupons' ); ?></label>
				<select id="webtoffee_payment_methods" name="_wt_sc_payment_methods[]" style="width:50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any payment method', 'thetoppercentile-coupons' ); ?>">
					<?php
					$payment_methods = WC()->payment_gateways->payment_gateways();

					if ( ! empty( $payment_methods ) ) {
						$payment_method_ids = self::get_processed_coupon_meta_value( $post_id, '_wt_sc_payment_methods' );

						foreach ( $payment_methods as $payment_method ) {
							if ( wc_string_to_bool( $payment_method->enabled ) ) {
								echo '<option value="' . esc_attr( $payment_method->id ) . '" ' . selected( in_array( $payment_method->id, $payment_method_ids, true ), true, false ) . '>' . esc_html( $payment_method->title ) . '</option>';
							}
						}
					}
					?>
				</select>
				<?php echo wp_kses_post( wc_help_tip( __( 'Coupon will be applicable if any of these payment methods are selected.', 'thetoppercentile-coupons' ) ) ); ?>
			</p>
			<!-- Payment methods:end -->

			<!-- User roles:start -->
			<p class="form-field">
				<label for="_wt_sc_user_roles"><?php esc_html_e( 'Applicable Roles', 'thetoppercentile-coupons' ); ?></label>
				<select id="_wt_sc_user_roles" name="_wt_sc_user_roles[]" style="width:50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any role', 'thetoppercentile-coupons' ); ?>">
					<?php
					$available_roles                  = wp_roles()->roles;
					$available_roles['wbte_sc_guest'] = array(
						'name' => __( 'Guest', 'thetoppercentile-coupons' ),
					);

					if ( ! empty( $available_roles ) ) {
						$user_roles = self::get_processed_coupon_meta_value( $post_id, '_wt_sc_user_roles' );

						foreach ( $available_roles as $role_id => $role ) {
							echo '<option value="' . esc_attr( $role_id ) . '"' . selected( in_array( $role_id, $user_roles, true ), true, false ) . '>' . esc_html( translate_user_role( $role['name'] ) ) . '</option>';
						}
					}
					?>
				</select> 
				<?php echo wp_kses_post( wc_help_tip( __( 'Coupon will be applicable if customer belongs to any of these User Roles.', 'thetoppercentile-coupons' ) ) ); ?>
			</p>
			<!-- User roles:end -->


			<?php
			/**
			 * Action hook to add custom checkout options.
			 *
			 * @since 1.4.6
			 * @param int     $post_id  Coupon id.
			 * @param WP_Post $post     Post object.
			 */
			do_action( 'webtoffee_coupon_metabox_checkout', $post_id, $post ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			?>
		</div>
		<?php
	}


	/**
	 *  Save the checkout options meta data
	 *
	 *  @param int $post_id  Coupon id.
	 */
	public function process_shop_coupon_meta( $post_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		if ( ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
			wp_die( esc_html__( 'You do not have sufficient permission to perform this operation', 'thetoppercentile-coupons' ) );
		}

		foreach ( self::$meta_arr as $mata_key => $meta_info ) {
			if ( isset( $_POST[ $mata_key ] ) && ! empty( $_POST[ $mata_key ] ) ) {
				if ( isset( $meta_info['type'] ) ) {
					if ( 'text_arr' === $meta_info['type'] ) {
						$val = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $mata_key ] ), 'text_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
					} else {
						$val = sanitize_text_field( wp_unslash( $_POST[ $mata_key ] ) );
					}
				} else {
					$val = sanitize_text_field( wp_unslash( $_POST[ $mata_key ] ) );
				}
			} else {
				$val = ( isset( $meta_info['default'] ) ? $meta_info['default'] : '' );
			}

			/**
			 *  Some array values we need to save as strings for giving backward compatibility
			 */
			if ( isset( $meta_info['save_as'] ) && is_array( $val ) && 'text' === $meta_info['save_as'] ) {
				$val = implode( ',', $val );
			}

			// save the post meta.
			update_post_meta( $post_id, $mata_key, $val );
		}
	}
}

Wt_Smart_Coupon_Checkout_Options_Admin::get_instance();