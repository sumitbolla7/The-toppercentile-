<?php
/**
 * Url Coupon public facing
 *
 * @link
 * @since 1.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Url Coupon public facing
 */
class Wt_Smart_Coupon_Url_Coupon_Public {

	/**
	 * Module base
	 *
	 * @var string
	 */
	public $module_base = 'url_coupon';

	/**
	 * Module id
	 *
	 * @var string
	 */
	public $module_id = '';

	/**
	 * Overwrite coupon message
	 *
	 * @var array
	 */
	protected $overwrite_coupon_message = array();

	/**
	 * Module id static
	 *
	 * @var string
	 */
	public static $module_id_static = '';

	/**
	 * Instance
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		add_action( 'wp_loaded', array( $this, 'apply_url_coupon' ) );

		add_action( 'woocommerce_after_calculate_totals', array( $this, 'apply_coupon_from_cookie' ), 1000 );
	}

	/**
	 * Get Instance
	 *
	 * @since 2.0.1
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wt_Smart_Coupon_Url_Coupon_Public();
		}
		return self::$instance;
	}

	/**
	 * Apply coupon by URL
	 */
	public function apply_url_coupon() {
		if ( ! isset( $_GET['wt_coupon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended 
			return;
		}
		if ( isset( $_GET['removed_item'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended 
			return;
		}

		$coupon_code = sanitize_text_field( wp_unslash( $_GET['wt_coupon'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended 

		if ( '' !== $coupon_code && Wt_Smart_Coupon_Common::is_coupon_exists( $coupon_code ) ) {
			$coupon_code = wc_sanitize_coupon_code( $coupon_code );
			if ( WC()->cart->get_cart_contents_count() > 0 ) {
				$new_message = __( 'Coupon code applied successfully', 'thetoppercentile-coupons' );

				/**
				 *  Alter url coupon message.
				 *
				 * @since 1.0.0
				 * @param string $new_message Message.
				 * @return string Message.
				 */
				$new_message = apply_filters( 'wt_smart_coupon_url_coupon_message', $new_message ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				if ( is_array( WC()->cart->get_applied_coupons() ) && ! in_array( $coupon_code, WC()->cart->get_applied_coupons(), true ) ) {
					$smart_coupon_obj = Wt_Smart_Coupon::get_instance();
					$smart_coupon_obj->plugin_public->start_overwrite_coupon_success_message( $coupon_code, $new_message );
					WC()->cart->add_discount( $coupon_code );
					$smart_coupon_obj->plugin_public->stop_overwrite_coupon_success_message();
				} else {
					delete_transient( 'wt_smart_url_coupon_pending_coupon' );
				}
			} else {
				set_transient( 'wt_smart_url_coupon_pending_coupon', $coupon_code, 1800 );

				$shop_page_url = get_page_link( get_option( 'woocommerce_shop_page_id' ) );
				// translators: %1$s: shop page url, %2$s: closing anchor tag.
				$new_message = sprintf( __( 'Oops your cart is empty! Add %1$s products %2$s to your cart to avail the offer.', 'thetoppercentile-coupons' ), '<a href="' . esc_url( $shop_page_url ) . '">', '</a>' );

				/**
				 *  Alter url coupon message.
				 *
				 * @since 1.0.0
				 * @param string $new_message Message.
				 * @return string Message.
				 */
				$new_message = apply_filters( 'wt_smart_coupon_url_coupon_message', $new_message ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				wc_add_notice( $new_message, 'error' );
			}
		}
	}

	/**
	 * Apply coupon from cookie if coupon is not applied when visit URL. If cart count is zero when visting the URL.
	 */
	public function apply_coupon_from_cookie() {
		$coupon_to_apply = get_transient( 'wt_smart_url_coupon_pending_coupon' );

		$coupon_to_apply = wc_sanitize_coupon_code( $coupon_to_apply );
		if ( '' === $coupon_to_apply ) {
			return;
		}

		if ( ! ( in_array( $coupon_to_apply, WC()->cart->get_applied_coupons(), true ) ) ) {
			$applied = WC()->cart->add_discount( $coupon_to_apply );
			if ( $applied ) {
				delete_transient( 'wt_smart_url_coupon_pending_coupon' );
			}
		} else {
			delete_transient( 'wt_smart_url_coupon_pending_coupon' );
		}

		if ( WC()->cart->get_cart_contents_count() > 0 ) {
			delete_transient( 'wt_smart_url_coupon_pending_coupon' );
		}
	}
}
Wt_Smart_Coupon_Url_Coupon_Public::get_instance();
