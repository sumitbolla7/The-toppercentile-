<?php
/**
 * Class Wt_P_IEW_Cta_Banner
 *
 * This class is responsible for displaying the CTA banner on the product edit page.
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Wt_P_IEW_Cta_Banner' ) ) {
	/**
	 * Class Wt_P_IEW_Cta_Banner
	 */
	class Wt_P_IEW_Cta_Banner {

		/**
		 * Is BFCM season.
		 *
		 * @var bool
		 */
		private static $is_bfcm_season = false;

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Check if premium plugin is active.
			/**
			 * Filter hook to alter active plugins.
			 *
			 * @since 2.2.2
			 * @param array $active_plugins Active plugins.
			 * @return array Active plugins.
			 */
			$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			if (
				! in_array( 'wt-import-export-for-woo-product/wt-import-export-for-woo-product.php', $active_plugins, true ) &&
				! in_array( 'import-export-suite-for-woocommerce/import-export-suite-for-woocommerce.php', $active_plugins, true )
			) {
				self::$is_bfcm_season = method_exists( 'Wt_Smart_Coupon_Admin', 'is_bfcm_season' ) && Wt_Smart_Coupon_Admin::is_bfcm_season();

				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
				add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
				add_action( 'wp_ajax_wt_dismiss_product_ie_cta_banner', array( $this, 'dismiss_banner' ) );
			}
		}
		/**
		 * Enqueue required scripts and styles.
		 *
		 * @param string $hook The current admin page.
		 */
		public function enqueue_scripts( $hook ) {
			if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || get_post_type() !== 'product' ) {
				return;
			}

			wp_enqueue_style(
				'wt-wbte-cta-banner',
				plugin_dir_url( __FILE__ ) . 'assets/css/wbte-cross-promotion-banners.css',
				array(),
				Wbte_Cross_Promotion_Banners::get_banner_version(),
			);

			wp_enqueue_script(
				'wt-wbte-cta-banner',
				plugin_dir_url( __FILE__ ) . 'assets/js/wbte-cross-promotion-banners.js',
				array( 'jquery' ),
				Wbte_Cross_Promotion_Banners::get_banner_version(),
				true
			);

			// Localize script with AJAX data.
			wp_localize_script(
				'wt-wbte-cta-banner',
				'wt_product_ie_cta_banner_ajax',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'wt_dismiss_product_ie_cta_banner_nonce' ),
					'action'   => 'wt_dismiss_product_ie_cta_banner',
				)
			);
		}

		/**
		 * Add the meta box to the product edit screen.
		 */
		public function add_meta_box() {
			global $wpdb;

			$total_products = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"
                SELECT COUNT(ID)
                FROM {$wpdb->posts}
                WHERE post_type = 'product'
                AND post_status NOT IN ('trash')
            "
			);

			// Show banner if there are 50 or more products.
			if ( ! defined( 'WT_PRODUCT_IMPORT_EXPORT_DISPLAY_BANNER' ) && 50 <= $total_products ) {
				add_meta_box(
					'wt_product_import_export_pro',
					self::$is_bfcm_season ? ' ' : __( 'Product Import Export for WooCommerce', 'thetoppercentile-coupons' ),
					array( $this, 'render_banner' ),
					'product',
					'side',
					'low'
				);
				define( 'WT_PRODUCT_IMPORT_EXPORT_DISPLAY_BANNER', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
			}
		}

		/**
		 * Render the banner HTML.
		 */
		public function render_banner() {

			$plugin_url        = 'https://www.thetoppercentile.com/product/product-import-export-woocommerce/?utm_source=free_plugin_cross_promotion&utm_medium=add_new_product_tab&utm_campaign=Product_import_export';
			$wt_admin_img_path = plugin_dir_url( __FILE__ ) . 'assets/images';

			?>
			<style type="text/css">
				<?php
				if ( self::$is_bfcm_season ) {
					?>
					#wt_product_import_export_pro .postbox-header{  height: 66px; background: url( <?php echo esc_url( WT_SMARTCOUPON_MAIN_URL . 'admin/images/bfcm-doc-settings-coupon.svg' ); ?> ) no-repeat 18px 0 #FFFBD5; }
					.wbte-cta-banner-features_head_div{ height: 80px; border-bottom: 1px solid #c3c4c7; display: flex; align-items: center; padding-left: 15px; justify-content: center; }
					.wbte-cta-banner-features_head_div img{ width: 50px; }
					.wbte-cta-banner-features_head_div h2{ font-weight: 600 !important; font-size: 13px !important; }
					<?php
				} else {
					echo '#wt_product_import_export_pro .postbox-header{  height:80px; background:url(' . esc_url( $wt_admin_img_path . '/product-ie.svg' ) . ') no-repeat 18px 18px #fff; padding-left:65px; margin-bottom:18px; background-size: 45px 45px; }';
				}
				?>
			</style>
			<div class="wt-cta-banner">
				<div class="wt-cta-content">

					<?php
					if ( self::$is_bfcm_season ) {
						?>
						<div class="wbte-cta-banner-features_head_div">
							<img src=" <?php echo esc_url( $wt_admin_img_path . '/product-ie.svg' ); ?>" alt="<?php esc_attr_e( 'upgrade box icon', 'thetoppercentile-coupons' ); ?>">
							<h2><?php esc_html_e( 'Product Import Export for WooCommerce', 'thetoppercentile-coupons' ); ?></h2>
						</div>
						<?php
					}
					?>

					<ul class="wt-cta-features">
						<li><?php esc_html_e( 'Import, export, or update WooCommerce products', 'thetoppercentile-coupons' ); ?></li>
						<li><?php esc_html_e( 'Supports all types of products (Simple, variable, subscription grouped, and external)', 'thetoppercentile-coupons' ); ?></li>
						<li><?php esc_html_e( 'Multiple file formats - CSV, XML, Excel, and TSV', 'thetoppercentile-coupons' ); ?></li>
						<li><?php esc_html_e( 'Advanced filters and customizations for better control', 'thetoppercentile-coupons' ); ?></li>
						<li class="hidden-feature"><?php esc_html_e( 'Bulk update WooCommerce product data', 'thetoppercentile-coupons' ); ?></li>
						<li class="hidden-feature"><?php esc_html_e( 'Import via FTP/SFTP and URL', 'thetoppercentile-coupons' ); ?></li>
						<li class="hidden-feature"><?php esc_html_e( 'Schedule automated import & export', 'thetoppercentile-coupons' ); ?></li>
						<li class="hidden-feature"><?php esc_html_e( 'Export and Import custom fields and third-party plugin fields', 'thetoppercentile-coupons' ); ?></li>
					</ul>

					<div class="wt-cta-footer">
						<div class="wt-cta-footer-links">
							<a href="#" class="wt-cta-toggle" data-show-text="<?php esc_attr_e( 'View all premium features', 'thetoppercentile-coupons' ); ?>" data-hide-text="<?php esc_attr_e( 'Show less', 'thetoppercentile-coupons' ); ?>"><?php esc_html_e( 'View all premium features', 'thetoppercentile-coupons' ); ?></a>
							<a href="<?php echo esc_url( $plugin_url ); ?>" class="wt-cta-button" target="_blank"><img src="<?php echo esc_url( $wt_admin_img_path . '/promote_crown.svg' ); ?>" style="width: 15.01px; height: 10.08px; margin-right: 8px;" alt=""><?php esc_html_e( 'Get the plugin', 'thetoppercentile-coupons' ); ?></a>
						</div>
						<a href="#" class="wt-cta-dismiss" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;"><?php esc_html_e( 'Dismiss', 'thetoppercentile-coupons' ); ?></a>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Handle the dismiss action via AJAX.
		 */
		public function dismiss_banner() {
			check_ajax_referer( 'wt_dismiss_product_ie_cta_banner_nonce', 'nonce' );

			// Check if user has permission.
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}

			// Update the option to hide the banner.
			update_option( 'wt_hide_product_ie_cta_banner', true );

			wp_send_json_success( 'Banner dismissed successfully' );
		}
	}

	new Wt_P_IEW_Cta_Banner();
}
