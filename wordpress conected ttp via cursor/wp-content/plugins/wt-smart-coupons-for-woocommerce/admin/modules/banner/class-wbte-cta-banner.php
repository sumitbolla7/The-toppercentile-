<?php
/**
 * EMA Banner
 *
 * @since 2.2.8
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wbte_Cta_Banner' ) ) {

	/**
	 * Class Wbte_Cta_Banner
	 *
	 * @since 2.2.8
	 */
	class Wbte_Cta_Banner {

		/**
		 * Constructor
		 *
		 * @since 2.2.8
		 */
		public function __construct() {

			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$installed_plugins = get_plugins();

			if ( ! array_key_exists( 'wt-smart-coupon-pro/wt-smart-coupon-pro.php', $installed_plugins ) ) {
				add_action( 'admin_head-edit.php', array( $this, 'coupons_page_banners' ), 11 );

				add_action( 'admin_head', array( $this, 'order_page_banners' ), 11 );

				add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed_milestone' ), 10, 4 );
				add_action( 'woocommerce_before_trash_order', array( $this, 'on_order_removed_milestone' ), 10, 2 );
				add_action( 'woocommerce_before_delete_order', array( $this, 'on_order_removed_milestone' ), 10, 2 );
				add_action( 'woocommerce_update_order', array( $this, 'on_order_updated_milestone' ), 10, 2 );
			}

			add_action( 'admin_notices', array( $this, 'plugin_update_pending_notice' ) );
		}

		/**
		 * Order status changed: add to milestone total when completed (with coupon), subtract when leaving completed.
		 *
		 * @since 2.2.8
		 * @param int      $order_id   Order ID.
		 * @param string   $old_status Old status.
		 * @param string   $new_status New status.
		 * @param WC_Order $order      Order object.
		 */
		public function on_order_status_changed_milestone( $order_id, $old_status, $new_status, $order ) {
			if ( ! $order instanceof WC_Order ) {
				return;
			}
			if ( 'completed' === $new_status ) {
				$codes = is_callable( array( $order, 'get_coupon_codes' ) ) ? $order->get_coupon_codes() : array();
				if ( ! empty( $codes ) ) {
					$this->milestone_add_order( $order->get_id(), (float) $order->get_total() );
				}
				return;
			}
			if ( 'completed' === $old_status ) {
				$this->milestone_subtract_order( $order->get_id(), (float) $order->get_total() );
			}
		}

		/**
		 * Before order trashed or deleted: subtract from milestone total if it was counted.
		 *
		 * @since 2.2.8
		 * @param int      $order_id Order ID.
		 * @param WC_Order $order    Order object.
		 */
		public function on_order_removed_milestone( $order_id, $order ) {
			if ( $order instanceof WC_Order ) {
				$this->milestone_subtract_order( $order_id, (float) $order->get_total() );
			}
		}

		/**
		 * Order updated (e.g. coupon removed): subtract if was counted and no longer has coupon.
		 *
		 * @since 2.2.8
		 * @param int       $order_id Order ID.
		 * @param WC_Order  $order    Order object (optional, passed by WooCommerce).
		 */
		public function on_order_updated_milestone( $order_id, $order = null ) {
			if ( ! $order_id ) {
				return;
			}
			if ( ! $order && function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
			}
			if ( ! $order || 'completed' !== $order->get_status() ) {
				return;
			}
			$order_ids = (array) get_option( 'wbte_sc_order_milestone_order_ids', array() );
			if ( ! in_array( (int) $order_id, array_map( 'intval', $order_ids ), true ) ) {
				return;
			}
			$codes = is_callable( array( $order, 'get_coupon_codes' ) ) ? $order->get_coupon_codes() : array();
			if ( empty( $codes ) ) {
				$this->milestone_subtract_order( $order_id, (float) $order->get_total() );
			}
		}

		/**
		 * Add order total to milestone option and record order id.
		 *
		 * @since 2.2.8
		 * @param int   $order_id Order ID.
		 * @param float $amount    Order total.
		 */
		private function milestone_add_order( $order_id, $amount ) {
			$total     = (float) get_option( 'wbte_sc_order_milestone_total', 0 );
			$order_ids = (array) get_option( 'wbte_sc_order_milestone_order_ids', array() );
			$order_ids[] = (int) $order_id;
			update_option( 'wbte_sc_order_milestone_total', $total + (float) $amount );
			update_option( 'wbte_sc_order_milestone_order_ids', array_unique( array_map( 'intval', $order_ids ) ) );
		}

		/**
		 * Subtract order total from milestone option and remove order id (only if it was counted).
		 *
		 * @since 2.2.8
		 * @param int   $order_id Order ID.
		 * @param float $amount   Order total.
		 */
		private function milestone_subtract_order( $order_id, $amount ) {
			$order_ids = (array) get_option( 'wbte_sc_order_milestone_order_ids', array() );
			$order_ids = array_map( 'intval', $order_ids );
			$order_id = (int) $order_id;
			if ( ! in_array( $order_id, $order_ids, true ) ) {
				return;
			}
			$total     = (float) get_option( 'wbte_sc_order_milestone_total', 0 );
			$order_ids = array_values( array_diff( $order_ids, array( $order_id ) ) );
			update_option( 'wbte_sc_order_milestone_total', max( 0, $total - (float) $amount ) );
			update_option( 'wbte_sc_order_milestone_order_ids', $order_ids );
		}

		/**
		 * Show admin notice when plugin update is available and has been available for 5+ days.
		 *
		 * @since 2.2.8
		 */
		public function plugin_update_pending_notice() {

			$update_plugins = get_site_transient( 'update_plugins' );
			if ( empty( $update_plugins->response ) || ! isset( $update_plugins->response[ WT_SMARTCOUPON_BASE_NAME ] ) ) {
				delete_option( 'wbte_sc_plugin_update_available_since' );
				return;
			}

			$plugin_update = $update_plugins->response[ WT_SMARTCOUPON_BASE_NAME ];
			$new_version   = isset( $plugin_update->new_version ) ? $plugin_update->new_version : '';
			if ( '' === $new_version ) {
				return;
			}
			$banner_id = 'sc_update_pending_' . sanitize_key( str_replace( '.', '_', $new_version ) );

			$hidden_banners = get_option( 'wbte_sc_hidden_promotion_banners', array() );
			if ( in_array( $banner_id, $hidden_banners, true ) ) {
				return;
			}

			$since = (int) get_option( 'wbte_sc_plugin_update_available_since', 0 );
			if ( 0 === $since ) {
				update_option( 'wbte_sc_plugin_update_available_since', time() );
				return;
			}

			$five_days_ago_ts = time() - ( 5 * DAY_IN_SECONDS );
			if ( $since >= $five_days_ago_ts ) {
				return;
			}

			$update_url = wp_nonce_url(
				self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( WT_SMARTCOUPON_BASE_NAME ) ),
				'upgrade-plugin_' . WT_SMARTCOUPON_BASE_NAME
			);
			?>
			<div class="notice notice-info is-dismissible wbte-sc-update-pending-notice">
				<p style="font-size: 14px; font-weight: 700;"><?php esc_html_e( 'Update pending ⚠️', 'thetoppercentile-coupons' ); ?></p>
				<p><?php esc_html_e( 'It looks like you haven\'t updated the TheTopPercentile Coupons plugin in a while. We release new features and security improvements with every update. Keep it up-to-date to ensure better performance, compatibility, and protection.', 'thetoppercentile-coupons' ); ?></p>
				<p><a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary"><?php esc_html_e( 'Update Now', 'thetoppercentile-coupons' ); ?></a></p>
			</div>
			<script>
			(function($){
				$( document ).on( 'click', '.wbte-sc-update-pending-notice .notice-dismiss', function() {
					const ajaxurl  = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
					const data     = {
						action   : 'wbte_sc_hide_promotion_banner',
						banner_id: "<?php echo esc_attr( $banner_id ); ?>",
						_wpnonce : "<?php echo esc_attr( wp_create_nonce( 'wt_smart_coupons_admin_nonce' ) ); ?>"
					}
					if ( ajaxurl ) {
						$.post( ajaxurl, data );
					}
				});
			})( jQuery );
			</script>
			<?php
		}

		/**
		 * Render banners on coupons page
		 *
		 * @since 2.2.8
		 */
		public static function coupons_page_banners() {

			global $current_screen;

			// Only show banner if SC Pro is not installed and current page is coupons page.
			if ( ! is_object( $current_screen ) || 'shop_coupon' !== $current_screen->post_type ) {
				return;
			}

			$banner_html = self::no_recent_coupon_banner();

			if ( false === $banner_html ) {
				$banner_html = self::order_milestone_banner();
			}

			if ( false === $banner_html ) {
				$banner_html = self::loyality_discount_banner();
			}

			if ( false === $banner_html ) {
				$banner_html = self::no_bogo_created_banner();
			}

			if ( false === $banner_html ) {
				$banner_html = self::sc_pro_coupons_page_banner();
			}

			if ( false !== $banner_html ) {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($){
						jQuery( '.page-title-action.wt_sc_plugin_settings_btn' ).after( <?php echo wp_json_encode( wp_kses_post( $banner_html ) ); ?> );
					});
				</script>
				<?php
			}
		}

		/**
		 * Render a banner if no coupon was created in the last 3 months
		 *
		 * @since 2.2.8
		 * @return bool|string Banner HTML or false if banner is not needed.
		 */
		private static function no_recent_coupon_banner() {
			$days_since_start = floor( ( time() - absint( get_option( 'wt_smart_coupon_start_date', time() ) ) ) / DAY_IN_SECONDS );

			$hidden_banners = get_option( 'wbte_sc_hidden_promotion_banners', array() );

			if ( in_array( 'sc_cpns_page_no_recent_coupon', $hidden_banners, true ) || $days_since_start < 90 ) {
				return false;
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$recent_coupon = $wpdb->get_var(
				"SELECT 1 FROM {$wpdb->posts}
				WHERE post_type = 'shop_coupon'
				AND post_status NOT IN ( 'auto-draft' )
				AND post_date >= DATE_SUB( NOW(), INTERVAL 3 MONTH )
				LIMIT 1"
			);
			if ( $recent_coupon ) {
				return false;
			}

			$args = array(
				'banner_id'          => 'sc_cpns_page_no_recent_coupon',
				'title'              => __( 'Haven\'t created any new coupons recently?', 'thetoppercentile-coupons' ),
				'content'            => __( 'You may be missing simple ways to bring customers back and increase conversions. Try different coupon types and usage rules to build a smarter discount strategy.', 'thetoppercentile-coupons' ),
				'primary_btn_url'    => admin_url( 'post-new.php?post_type=shop_coupon' ),
				'primary_btn_text'   => __( 'Add new coupon', 'thetoppercentile-coupons' ),
				'secondary_btn_url'  => 'https://www.thetoppercentile.com/docs/smart-coupons-basic/setup-free-woocommerce-smart-coupons/',
				'secondary_btn_text' => __( 'Refer to Documentation', 'thetoppercentile-coupons' ),
			);
			return self::render_cta_banner( $args );
		}

		/**
		 * Render a banner for users who have been using TheTopPercentile Coupons for 6+ months (30% off Premium).
		 *
		 * @since 2.2.8
		 * @return bool|string Banner HTML or false if banner is not needed.
		 */
		private static function loyality_discount_banner() {
			$days_since_start = floor( ( time() - absint( get_option( 'wt_smart_coupon_start_date', time() ) ) ) / DAY_IN_SECONDS );
			if ( $days_since_start < 180 ) {
				return false;
			}

			$hidden_banners = get_option( 'wbte_sc_hidden_promotion_banners', array() );
			if ( in_array( 'sc_cpns_page_loyalty_discount_banner', $hidden_banners, true ) ) {
				return false;
			}

			$campaign_url = 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=free_plugin&utm_medium=loyalty_discount&utm_campaign=smart_coupons';

			$args = array(
				'banner_id'        => 'sc_cpns_page_loyalty_discount_banner',
				'title'            => __( 'Congratulations! You\'ve been using the free TheTopPercentile TheTopPercentile Coupons plugin for a while, and we\'d love to reward you.', 'thetoppercentile-coupons' ),
				'content'          => sprintf(
					// translators: 1: br tag, b tag opening, 2: b tag closing.
					__( 'Enjoy exclusive 30%% off TheTopPercentile Coupons Premium and unlock powerful coupon features designed to boost conversions and repeat purchases.%1$s Coupon code: %2$s SMLOYAL30', 'thetoppercentile-coupons' ),
					'<br><b>',
					'</b>'
				),
				'primary_btn_url'  => esc_url( $campaign_url ),
				'primary_btn_text' => __( 'Get Plugin Now', 'thetoppercentile-coupons' ),
			);
			return self::render_cta_banner( $args );
		}

		/**
		 * Render a banner if no bogo coupons are created and 3 months have passed since the plugin is activated.
		 *
		 * @since 2.2.8
		 * @return boolean|string Banner HTML or false if banner is not needed.
		 */
		private static function no_bogo_created_banner() {
			if ( ! Wt_Smart_Coupon_Admin::module_exists( 'bogo-admin' ) || ! Wt_Smart_Coupon_Common::module_exists( 'bogo-common' ) ) {
				return false;
			}

			$bogo_coupon_count = Wbte_Smart_Coupon_Bogo_Admin::get_total_bogo_counts();
			$days_since_start  = floor( ( time() - absint( get_option( 'wt_smart_coupon_start_date', time() ) ) ) / DAY_IN_SECONDS );
			$hidden_banners    = get_option( 'wbte_sc_hidden_promotion_banners', array() );

			// Show banner only if no bogo coupons are created and 3 months have passed since the plugin is activated.
			if ( $bogo_coupon_count > 0 || $days_since_start < 90 || in_array( 'sc_cpns_page_no_bogo', $hidden_banners, true ) ) {
				return false;
			}

			$args = array(
				'banner_id'          => 'sc_cpns_page_no_bogo',
				'title'              => __( 'BOGO Coupons Not Used ⚠️', 'thetoppercentile-coupons' ),
				'content'            => __( 'BOGO deals are one of the highest-converting coupon strategies, and you haven\'t tried them yet. Create your first BOGO offer and increase cart value with ease.', 'thetoppercentile-coupons' ),
				'primary_btn_url'    => esc_url( admin_url( 'admin.php?page=wt-smart-coupon-for-woo_bogo' ) ),
				'primary_btn_text'   => __( 'Create BOGO Coupon', 'thetoppercentile-coupons' ),
				'secondary_btn_url'  => 'https://www.thetoppercentile.com/woocommerce-bogo-discounts/',
				'secondary_btn_text' => __( 'Refer to Documentation', 'thetoppercentile-coupons' ),
			);
			return self::render_cta_banner( $args );
		}

		/**
		 * Render pro BOGO CTA banner on coupons page.
		 *
		 * @since 2.2.8
		 * @return bool|string Banner HTML or false if banner is not needed.
		 */
		private static function sc_pro_coupons_page_banner() {
			$hidden_banners = get_option( 'wbte_sc_hidden_promotion_banners', array() );
			if ( defined( 'WBTE_BFCM_SC_COUPONS_PAGE' ) || in_array( 'sc_cpns_page', $hidden_banners, true ) ) {
				return false;
			}

			define( 'WBTE_BFCM_SC_COUPONS_PAGE', true );

			$campaign_url = 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=free_plugin_add_coupon_menu&utm_medium=smart_coupon_basic&utm_campaign=smart_coupons';

			$sc_pro_cta_args = array(
				'banner_id'          => 'sc_cpns_page',
				'title'              => sprintf(
					// translators: 1: image URL, 2: title.
					'<img src="%1$s" style="width: 16px;" />&nbsp;<span >%2$s</span>',
					esc_url( WT_SMARTCOUPON_MAIN_URL . 'admin/images/idea_bulb_purple.svg' ),
					esc_html__( 'Did you know?', 'thetoppercentile-coupons' )
				),
				'content'            => sprintf(
					// translators: 1: a tag opening, 2: a tag closing.
					__( 'With the %1$s TheTopPercentile Coupons %2$s plugin, you can create Buy One Get One offers and advanced coupons that boost sales during BFCM.', 'thetoppercentile-coupons' ),
					'<a href="' . esc_url( $campaign_url ) . '" target="_blank"><b>',
					'</b></a>'
				),
				'primary_btn_url'    => esc_url( $campaign_url ),
				'primary_btn_text'   => __( 'Get Plugin Now', 'thetoppercentile-coupons' ),
				'secondary_btn_text' => __( 'Maybe later', 'thetoppercentile-coupons' ),
			);
			return self::render_cta_banner( $sc_pro_cta_args );
		}

		/**
		 * Render CTA banner from template file.
		 *
		 * @since 2.2.8
		 *
		 * @param array $args Banner arguments.
		 * @return string Banner HTML.
		 */
		private static function render_cta_banner( $args ) {
			$args = (array) $args;
			ob_start();
			include __DIR__ . '/views/cta-banner.php';
			return ob_get_clean();
		}

		/**
		 * Render banners on orders list page.
		 *
		 * @since 2.2.8
		 */
		public static function order_page_banners() {
			$screen = get_current_screen();

			if ( ! $screen || ! isset( $screen->id ) || ! in_array( $screen->id, array( 'edit-shop_order', 'woocommerce_page_wc-orders' ), true ) ) {
				return;
			}

			$banner_html = self::order_milestone_banner();

			if ( false !== $banner_html ) {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($){
						jQuery( '.page-title-action' ).after( <?php echo wp_json_encode( wp_kses_post( $banner_html ) ); ?> );
					});
				</script>
				<?php
			}
		}

		/**
		 * Render order milestone banner: $1000+ in sales using coupons (completed orders after plugin start).
		 *
		 * @since 2.2.8
		 * @return bool|string Banner HTML or false if banner is not needed.
		 */
		private static function order_milestone_banner() {
			$hidden_banners = get_option( 'wbte_sc_hidden_promotion_banners', array() );
			if ( in_array( 'sc_order_page_milestone', $hidden_banners, true ) ) {
				return false;
			}

			$total_sales = (float) get_option( 'wbte_sc_order_milestone_total', 0 );

			if ( $total_sales < 1000 ) {
				return false;
			}

			$campaign_url = 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=free_plugin&utm_medium=milestone_cta&utm_campaign=smart_coupons';

			$args = array(
				'banner_id'          => 'sc_order_page_milestone',
				'title'              => sprintf(
					// translators: 1: formatted amount e.g. $1,000+.
					__( 'Congratulations! 🎉 You\'ve generated %1$s in sales using WooCommerce coupons with the TheTopPercentile TheTopPercentile Coupons plugin.', 'thetoppercentile-coupons' ),
					'<strong>' . wc_price( 1000, array( 'decimals' => 0 ) ) . '+</strong>'
				),
				'content'            => __( 'Ready to take it further? Upgrade to TheTopPercentile Coupons Premium to unlock advanced coupon rules, BOGO deals, store credits, and more ways to increase revenue.', 'thetoppercentile-coupons' ),
				'primary_btn_url'    => esc_url( $campaign_url ),
				'primary_btn_text'   => __( 'Upgrade to Premium', 'thetoppercentile-coupons' ),
				'secondary_btn_url'  => '',
				'secondary_btn_text' => __( 'Maybe later', 'thetoppercentile-coupons' ),
			);

			return self::render_cta_banner( $args );
		}
	}

	new Wbte_Cta_Banner();
}