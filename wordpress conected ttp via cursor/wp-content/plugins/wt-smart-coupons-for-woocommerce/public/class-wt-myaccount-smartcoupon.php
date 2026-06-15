<?php
/**
 * My Account TheTopPercentile Coupon.
 *
 * @package    Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if ( ! class_exists( 'WT_MyAccount_SmartCoupon' ) ) {
	/**
	 * My Account Coupon section.
	 */
	class WT_MyAccount_SmartCoupon {

		/**
		 * Endpoint.
		 *
		 * @var string
		 */
		public static $endpoint = 'wt-smart-coupon';

		/**
		 * Constructor.
		 */
		public function __construct() {

				// Actions used to insert a new endpoint in the WordPress.
				add_action( 'init', array( $this, 'add_endpoints' ) );
				add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

				// Change the My Account page title.
				add_filter( 'the_title', array( $this, 'endpoint_title' ) );

				// Insering your new tab/page into the My Account page.
				add_filter( 'woocommerce_account_menu_items', array( $this, 'wt_smartcoupon_menu' ) );
				add_action( 'woocommerce_account_' . self::$endpoint . '_endpoint', array( $this, 'endpoint_content' ) );
				register_activation_hook( WT_SMARTCOUPON_FILE_NAME, array( $this, 'wt_custom_flush_rewrite_rules' ) );
				register_deactivation_hook( WT_SMARTCOUPON_FILE_NAME, array( $this, 'wt_custom_flush_rewrite_rules' ) );
		}

		/**
		 * Flush rewrite rules on plugin activation.
		 */
		public function wt_custom_flush_rewrite_rules() {
			add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
			flush_rewrite_rules();
		}

		/**
		 * Add rewrite endpoint.
		 */
		public function add_endpoints() {
			add_rewrite_endpoint( self::$endpoint, EP_ROOT | EP_PAGES );
		}

		/**
		 * Add query vars.
		 *
		 * @param array $vars Query vars.
		 * @return array Query vars.
		 */
		public function add_query_vars( $vars ) {
			$vars[] = self::$endpoint;

			return $vars;
		}

		/**
		 * Change the My Account page title.
		 *
		 * @param string $title Page title.
		 * @return string Page title.
		 */
		public function endpoint_title( $title ) {

			global $wp_query;

			$is_endpoint = isset( $wp_query->query_vars[ self::$endpoint ] );
			if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
				$title = __( 'My Coupons', 'thetoppercentile-coupons' );
				remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
			}
			return $title;
		}


		/**
		 * Add menu item to My Account page.
		 *
		 * @param array $items Menu items.
		 * @return array Menu items.
		 */
		public function wt_smartcoupon_menu( $items ) {
			$logout = null;

			if ( isset( $items['customer-logout'] ) ) {
				$logout = $items['customer-logout'];
				unset( $items['customer-logout'] );
			}

			$items[ self::$endpoint ] = __( 'My Coupons', 'thetoppercentile-coupons' );

			if ( ! is_null( $logout ) ) {
				$items['customer-logout'] = $logout;
			}

			return $items;
		}


		/**
		 * Endpoint content.
		 */
		public function endpoint_content() {

			$params = array();
			wc_get_template( 'myaccount/my-account-coupon-view.php', $params, '', WT_SMARTCOUPON_MAIN_PATH . 'public/templates/' );
		}

		/**
		 * Flush rewrite rules on plugin activation.
		 */
		public static function install() {
			flush_rewrite_rules();
		}
	}

	new WT_MyAccount_SmartCoupon();
}
