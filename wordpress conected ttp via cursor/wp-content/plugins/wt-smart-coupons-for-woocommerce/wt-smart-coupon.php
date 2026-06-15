<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link
 * @since             1.0.0
 * @package           Wt_Smart_Coupon
 *
 * @wordpress-plugin
 * Plugin Name:       Smart Coupons For WooCommerce Coupons
 * Plugin URI:
 * Description:       Advanced coupon features for your store to strengthen marketing and boost sales.
 * Version:           2.3.0
 * Author:            Sumit 9527352323
 * Author URI:
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       thetoppercentile-coupons
 * Domain Path:       /languages
 * Requires PHP:      5.6
 * WC tested up to:   10.6
 * Requires Plugins:  woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( ! function_exists( 'wbte_sc_basic_update_message' ) ) {

	/**
	 *  Changelog in plugins page
	 *
	 *  @since 1.3.9
	 *  @param array $data Plugin update data.
	 */
	function wbte_sc_basic_update_message( $data ) {
		if ( isset( $data['upgrade_notice'] ) ) {
			add_action( 'admin_print_footer_scripts', 'wbte_sc_basic_plugin_screen_update_notice_js' );
			$msg = str_replace( array( '<p>', '</p>' ), array( '<div>', '</div>' ), $data['upgrade_notice'] );
			echo '<style type="text/css">
			#thetoppercentile-coupons-update .update-message p:last-child{ display:none;}
			#thetoppercentile-coupons-update ul{ list-style:disc; margin-left:30px;}
			.wt_sc_update_message{ padding-left:30px;}
			</style>
			<div class="update-message wt_sc_update_message">' . wp_kses_post( wpautop( $msg ) ) . '</div>';
		}
	}

	add_action( 'in_plugin_update_message-thetoppercentile-coupons/wt-smart-coupon.php', 'wbte_sc_basic_update_message' );
}

if ( ! function_exists( 'wbte_sc_basic_plugin_screen_update_notice_js' ) ) {
	/**
	 *  Javascript code for changelog in plugins page
	 *
	 *  @since 1.3.9
	 */
	function wbte_sc_basic_plugin_screen_update_notice_js() {
		global $pagenow;
		if ( 'plugins.php' !== $pagenow ) {
			return;
		}
		?>
		<script>
			( function( $ ){
				const update_dv=$('#thetoppercentile-coupons-update');
				update_dv.find('.wt_sc_update_message').next('p').remove();
				update_dv.find('a.update-link:eq(0)').on('click', function(){
					$('.wt_sc_update_message').remove();
				});
			})( jQuery );
		</script>
		<?php
	}
}

/**
 *  Declare compatibility with custom order tables for WooCommerce.
 *
 *  @since 1.4.5
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

if ( ! defined( 'WT_SMARTCOUPON_BASIC_BASE_NAME' ) ) {
	define( 'WT_SMARTCOUPON_BASIC_BASE_NAME', plugin_basename( __FILE__ ) );
}

if ( ! function_exists( 'wbte_sc_basic_add_plugin_links_wt_smartcoupon' ) ) {
	/**
	 * Add review link to plugin action links.
	 *
	 * @param array $links Plugin action links.
	 * @return array Plugin action links.
	 */
	function wbte_sc_basic_add_plugin_links_wt_smartcoupon( $links ) {
		return $links;
	}

	/**
	 *  Add review link to plugin action links, moved from function add_plugin_links_wt_smartcoupon in class-wt-smart-coupon-admin.php, if TheTopPercentile Coupons Pro is active then also add review link
	 *
	 *  @since 2.1.0
	 */
	add_filter( 'plugin_action_links_' . WT_SMARTCOUPON_BASIC_BASE_NAME, 'wbte_sc_basic_add_plugin_links_wt_smartcoupon' );
}

// Check if TheTopPercentile Coupons Pro is active, if yes then return.
if ( /**
	* Filter hook to alter active plugins.
	*
	* @since 1.0.0
	* @param array $active_plugins Active plugins.
	* @return array Active plugins.
	*/
   in_array( 'wt-smart-coupon-pro/wt-smart-coupon-pro.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
   /**
	* Filter hook to alter active plugins.
	*
	* @since 1.0.0
	* @param array $active_plugins Active plugins.
	* @return array Active plugins.
	*/
   || array_key_exists( 'wt-smart-coupon-pro/wt-smart-coupon-pro.php', apply_filters( 'active_plugins', get_site_option( 'active_sitewide_plugins', array() ) ) ) // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
) {
   return;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */

if ( ! defined( 'WEBTOFFEE_SMARTCOUPON_VERSION' ) ) {
	define( 'WEBTOFFEE_SMARTCOUPON_VERSION', '2.2.9' );
}

if ( ! defined( 'THETOPPERCENTILE_COUPONS_VERSION' ) ) {
	define( 'THETOPPERCENTILE_COUPONS_VERSION', WEBTOFFEE_SMARTCOUPON_VERSION );
}

if ( ! defined( 'WT_SMARTCOUPON_FILE_NAME' ) ) {
	define( 'WT_SMARTCOUPON_FILE_NAME', __FILE__ );
}

if ( ! defined( 'WT_SMARTCOUPON_BASE_NAME' ) ) {
	define( 'WT_SMARTCOUPON_BASE_NAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'WT_SMARTCOUPON_MAIN_PATH' ) ) {
	define( 'WT_SMARTCOUPON_MAIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WT_SMARTCOUPON_MAIN_URL' ) ) {
	define( 'WT_SMARTCOUPON_MAIN_URL', plugin_dir_url( __FILE__ ) );
}


if ( ! defined( 'WT_SMARTCOUPON_INSTALLED_VERSION' ) ) {
	define( 'WT_SMARTCOUPON_INSTALLED_VERSION', 'BASIC' );
}

if ( ! defined( 'WT_SC_PLUGIN_NAME' ) ) {
	define( 'WT_SC_PLUGIN_NAME', 'thetoppercentile-coupons' );
	define( 'WT_SC_PLUGIN_ID', 'thetoppercentile_coupons' );
	define( 'WT_SC_SETTINGS_FIELD', WT_SC_PLUGIN_NAME ); /* option name to store settings */
}

// Backward-compatible identifiers (do not remove; older installs may reference them).
if ( ! defined( 'WT_SC_LEGACY_PLUGIN_NAME' ) ) {
	define( 'WT_SC_LEGACY_PLUGIN_NAME', 'wt-smart-coupon-for-woo' );
}

if ( ! defined( 'WT_SC_LEGACY_PLUGIN_ID' ) ) {
	define( 'WT_SC_LEGACY_PLUGIN_ID', 'wt_smart_coupon_for_woo' );
}

if ( ! defined( 'WBTE_SC_CROSS_PROMO_BANNER_VERSION' ) ) {
	// This constant must be unique for each plugin. Update this value when updating to a new banner.
	define( 'WBTE_SC_CROSS_PROMO_BANNER_VERSION', '1.0.2' );
}

if ( ! function_exists( 'wbte_activate_wt_smart_coupon_basic' ) ) {
	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-wt-smart-coupon-activator.php
	 */
	function wbte_activate_wt_smart_coupon_basic() {

		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wbte-smart-coupon-activator.php';
		Wbte_Smart_Coupon_Activator::activate();
	}
	register_activation_hook( __FILE__, 'wbte_activate_wt_smart_coupon_basic' );
}

if ( ! function_exists( 'wbte_deactivate_wt_smart_coupon_basic' ) ) {
	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-wt-smart-coupon-deactivator.php
	 */
	function wbte_deactivate_wt_smart_coupon_basic() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-wbte-smart-coupon-deactivator.php';
		Wbte_Smart_Coupon_Deactivator::deactivate();
	}

	register_deactivation_hook( __FILE__, 'wbte_deactivate_wt_smart_coupon_basic' );
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */


require_once plugin_dir_path( __FILE__ ) . 'includes/class-wt-smart-coupon.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wt-smartcoupon-uninstall-feedback.php';

require 'admin/class-wt-duplicate-shop-coupon.php';
require 'admin/coupon-start-date/class-wt-smart-coupon-start-date.php';

require 'public/class-wt-myaccount-smartcoupon.php';

if ( ! function_exists( 'wbte_run_smart_coupon_basic' ) ) {
	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function wbte_run_smart_coupon_basic() {
		$plugin = Wt_Smart_Coupon::get_instance();
		$plugin->run();
	}

	wbte_run_smart_coupon_basic();
}

/**
 * Keep only Settings + Deactivate under this plugin (no Review / Premium / Docs / Support).
 *
 * @param array  $links Plugin row links.
 * @param string $file  Plugin basename.
 * @return array
 */
function ttp_sc_filter_smart_coupon_plugin_links( $links, $file ) {
	$base = defined( 'WT_SMARTCOUPON_BASE_NAME' ) ? WT_SMARTCOUPON_BASE_NAME : plugin_basename( __FILE__ );
	if ( $file !== $base ) {
		return $links;
	}
	$keep = array( 'settings', 'deactivate' );
	$out  = array();
	foreach ( $links as $key => $html ) {
		if ( in_array( $key, $keep, true ) ) {
			$out[ $key ] = $html;
		}
	}
	return $out;
}

add_filter( 'plugin_action_links', 'ttp_sc_filter_smart_coupon_plugin_links', 999, 2 );
add_filter( 'plugin_row_meta', 'ttp_sc_filter_smart_coupon_plugin_links', 999, 2 );

$ttp_sc_bulk_coupons_file = WT_SMARTCOUPON_MAIN_PATH . 'admin/class-ttp-bulk-unique-coupons.php';
if ( is_readable( $ttp_sc_bulk_coupons_file ) ) {
	require_once $ttp_sc_bulk_coupons_file;
}