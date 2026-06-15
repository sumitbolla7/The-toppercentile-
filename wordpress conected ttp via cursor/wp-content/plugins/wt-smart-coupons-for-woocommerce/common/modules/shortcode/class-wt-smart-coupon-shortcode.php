<?php
/**
 * Coupon shortcode common section.
 *
 * @link
 * @since 1.3.7
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon shortcode common section.
 *
 * @link
 * @since 1.3.7
 *
 * @package  Wt_Smart_Coupon
 */
class WT_Smart_Coupon_Shortcode {

	/**
	 * Module base
	 *
	 * @var string
	 */
	public $module_base = 'coupon_shortcode';

	/**
	 * Module id
	 *
	 * @var string
	 */
	public $module_id = '';

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
	 * Coupon css added
	 *
	 * @var bool
	 */
	private static $coupon_css_added = false; /* limit the coupon style to be added to the page multiple times if the page has more shortcodes */

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;
		add_shortcode( 'wt-smart-coupon', array( $this, 'display_coupon' ) );
	}

	/**
	 * Get Instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WT_Smart_Coupon_Shortcode();
		}
		return self::$instance;
	}


	/**
	 * Display coupon by shortcode
	 *
	 *  @since 1.3.7
	 *  @since 1.4.7    Code updated
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Coupon HTML.
	 */
	public function display_coupon( $atts ) {
		if ( ! $atts['id'] || 'publish' !== get_post_status( $atts['id'] ) || 'shop_coupon' !== get_post_type( $atts['id'] ) ) {
			return __( 'Invalid coupon', 'thetoppercentile-coupons' );
		}

		$coupon_title = get_the_title( $atts['id'] );
		$coupon       = new WC_Coupon( $atts['id'] );

		$coupon_data                    = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon );
		$coupon_data['display_on_page'] = 'by_shortcode';

		$coupon_html        = '';
		$include_coupon_css = false;

		if ( ! self::$coupon_css_added ) {
			ob_start();
			Wt_Smart_Coupon_Public::print_coupon_default_css();
			$coupon_html = ob_get_clean(); // add css.

			$include_coupon_css = true;  // add template css along with HTML.

			self::$coupon_css_added = true; // mark it as added to avoid duplicate.
		}
		$coupon_type = 'available_coupon';

		$user_id = get_current_user_id();
		if ( $user_id ) {
			$data_store  = $coupon->get_data_store();
			$usage_count = $data_store->get_usage_by_user_id( $coupon, $user_id );
			if ( 0 < $usage_count ) {
				$coupon_type = 'used_coupon';
			}
		}

		$expiry_date = $coupon->get_date_expires();

		if ( $expiry_date ) {
			$timezone        = $expiry_date->getTimezone();
			$expiry_datetime = new WC_DateTime( $expiry_date->date( 'Y-m-d' ) );
			$now_datetime    = new WC_DateTime();

			$expiry_datetime->setTimezone( $timezone );
			$now_datetime->setTimezone( $timezone );

			if ( $now_datetime->getTimestamp() > $expiry_datetime->getTimestamp() ) {
				$coupon_type = 'expired_coupon';
			}
		}

		$coupon_html .= Wt_Smart_Coupon_Public::get_coupon_html( $coupon, $coupon_data, $coupon_type, $include_coupon_css );
		return $coupon_html;
	}
}

WT_Smart_Coupon_Shortcode::get_instance();
