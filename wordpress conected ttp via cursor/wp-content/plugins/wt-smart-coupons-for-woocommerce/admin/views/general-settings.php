<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://www.thetoppercentile.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$wt_sc_admin_view_path = plugin_dir_path( WT_SMARTCOUPON_FILE_NAME ) . 'admin/views/';
$ds_obj                = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );
$admin_img_path        = WT_SMARTCOUPON_MAIN_URL . 'admin/images/';

?>
<style>
	#wpbody-content{ margin-top: 130px; }
	#wpcontent{ background-color: #F1F8FE; }
</style>
<div class="wrap">
<?php
	$header_arr = array(
		'wt-sc-help'      => __( 'Help guide', 'thetoppercentile-coupons' ),
		'wbte-sc-develop' => __( 'Develop', 'thetoppercentile-coupons' ),
	);

	/**
	 * Action to add content to the settings tab.
	 *
	 * @since 1.0.0
	 */
	$header_arr = apply_filters( 'wt_sc_plugin_settings_tabhead', $header_arr ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	if ( isset( $_GET['debug'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$header_arr['wt-sc-debug'] = __( 'Debug', 'thetoppercentile-coupons' );
	}
	$header_items = array();
	foreach ( $header_arr as $key => $val ) {
		$_arr = array(
			'title' => esc_html( is_array( $val ) ? $val[0] : $val ),
			'href'  => esc_attr( '#' . $key ),
		);
		if ( 'wt-sc-coupon-style' === $key ) {
			$_arr['class'] = 'active';
		}

		$header_items[] = $_arr;
	}

	echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'header',
		array(
			'values' => array(
				'plugin_name'      => 'TheTopPercentile Coupons',
				'developed_by_txt' => esc_html__( 'Powered by TheTopPercentile', 'thetoppercentile-coupons' ),
				'plugin_logo'      => esc_url( $admin_img_path . 'thetoppercentile-logo-128.png' ),
				'menu'             => $header_items, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			),
		)
	);
	?>
	<div class="wt-sc-tab-container">
		
		<?php
		// inside the settings form.
		$setting_views_a = array(
			'wt-sc-help'      => 'admin-help.php',
			'wbte-sc-develop' => '-admin-develop.php',
		);

		// outside the settings form.
		$setting_views_b = array(
			'wt-sc-help' => 'admin-settings-help.php',
		);
		if ( isset( $_GET['debug'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$setting_views_b['wt-sc-debug'] = 'admin-settings-debug.php';
		}
		?>
		<form method="post" class="wt_sc_settings_form">
			<input type="hidden" value="main" class="wt_sc_settings_base" />
			<?php

			// Set nonce:.
			if ( function_exists( 'wp_nonce_field' ) ) {
				wp_nonce_field( WT_SC_PLUGIN_NAME );
			}
			foreach ( $setting_views_a as $target_id => $value ) {
				$settings_view = $wt_sc_admin_view_path . $value;
				if ( file_exists( $settings_view ) ) {
					include $settings_view;
				}
			}

			/**
			 * Action to add content to the settings form.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wt_sc_plugin_settings_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			?>
					</form>
		<?php
		foreach ( $setting_views_b as $target_id => $value ) {
			$settings_view = $wt_sc_admin_view_path . $value;
			if ( file_exists( $settings_view ) ) {
				include $settings_view;
			}
		}
		?>
		<?php
		/**
		 * Action to add content outside the settings form.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wt_sc_plugin_out_settings_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		?>
			</div>

	<?php
	Wt_Smart_Coupon_Admin::admin_right_sidebar();
	?>
</div>
<?php
/**
 * Action to add content after the settings tab.
 *
 * @since 1.0.0
 */
do_action( 'wt_sc_plugin_after_settings_tab' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'help-widget',
	array(
		'values' => array(
			'items'      => array(
				array(
					'title'  => esc_html__( 'Setup Guide', 'thetoppercentile-coupons' ),
					'icon'   => 'book',
					'href'   => esc_url( 'https://www.thetoppercentile.com/setup-smart-coupons-for-woocommerce/' ),
					'target' => '_blank',
				),
				array(
					'title'  => esc_html__( 'Contact support', 'thetoppercentile-coupons' ),
					'icon'   => 'headphone',
					'target' => '_blank',
					'href'   => esc_url( 'https://www.thetoppercentile.com/support/' ),
				),
			),
			'hover_text' => esc_html__( 'Help', 'thetoppercentile-coupons' ),
		),
		'class'  => array( 'wbte_sc_admin_settings_help_widget' ),
	)
);
?>