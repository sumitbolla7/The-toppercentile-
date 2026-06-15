<?php
/**
 * Fired during plugin activation
 *
 * @link       http://www.thetoppercentile.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/includes
 */

if ( ! class_exists( 'Wbte_Smart_Coupon_Activator' ) ) {

	/**
	 * Fired during plugin activation.
	 *
	 * This class defines all code necessary to run during the plugin's activation.
	 *
	 * @since      1.0.0
	 * @package    Wt_Smart_Coupon
	 * @subpackage Wt_Smart_Coupon/includes
	 */
	class Wbte_Smart_Coupon_Activator {

		/**
		 * Short Description. (use period)
		 *
		 * Long Description.
		 *
		 * @since    1.0.0
		 */
		public static function activate() {
			/**
			 *  Enable woocommmerce coupon settings
			 *
			 * @since 1.1.8
			 */
			if ( ! class_exists( 'WooCommerce' ) ) {
				deactivate_plugins( WT_SMARTCOUPON_BASE_NAME );
				wp_die( esc_html__( 'Oops! Woocommerce not activated..', 'thetoppercentile-coupons' ), '', array( 'back_link' => 1 ) );

			}
			if ( defined( 'WT_SMARTCOUPON_INSTALLED_VERSION' ) && 'PREMIUM' === WT_SMARTCOUPON_INSTALLED_VERSION ) {

				return;
			}

			update_option( 'woocommerce_enable_coupons', 'yes' );

			/**
			 *  Install necessary tables
			 *
			 *  @since 1.4.3
			 */
			Wt_Smart_Coupon::install_tables();
			self::update_cross_promo_banner_version(); // Update promotion banner version.

			$is_existing_user = get_option( 'wt-smart-coupon-for-woo' );
			if ( ! $is_existing_user ) {

				// Activate new BOGO as default.
				update_option( 'wbte_sc_new_bogo_actvated', true );
			}

			/**
			 *  Hook to run after plugin is activated
			 *
			 *  @since 1.0.0
			 */
			do_action( 'after_wt_smart_coupon_for_woocommerce_is_activated' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			update_option( 'wbte_sc_basic_activation_hook_version', WEBTOFFEE_SMARTCOUPON_VERSION );

			self::migrate();
		}

		/**
		 *  Check and update the cross promotion banner version.
		 */
		public static function update_cross_promo_banner_version() {
			$current_latest = get_option( 'wbfte_promotion_banner_version' );

			if ( false === $current_latest || // User is installing the plugin first time.
				version_compare( $current_latest, WBTE_SC_CROSS_PROMO_BANNER_VERSION, '<' ) // $current_latest is lesser than the installed version in this plugin.
			) {
				update_option( 'wbfte_promotion_banner_version', WBTE_SC_CROSS_PROMO_BANNER_VERSION );
			}
		}

		/**
		 *  Migrate data from old versions
		 *
		 *  @since 1.3.7
		 */
		public static function migrate() {
			global $wpdb;

			/**
			 *  Migrate option for coupon visibility sections
			 *
			 *  @since 1.3.7
			 */
			$couponlist = $wpdb->get_results( 'SELECT `post_id`, `meta_value` FROM `' . $wpdb->postmeta . "` WHERE `meta_key`='_wt_make_coupon_available_in_myaccount'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$couponlist = ( isset( $couponlist ) && is_array( $couponlist ) ) ? $couponlist : array();

			foreach ( $couponlist as $row ) {
				if ( 1 === $row['meta_value'] ) {
					add_post_meta( $row['post_id'], '_wc_make_coupon_available', 'my_account', true );
					delete_post_meta( $row['post_id'], '_wt_make_coupon_available_in_myaccount' );
				}
			}
		}
	}
}
