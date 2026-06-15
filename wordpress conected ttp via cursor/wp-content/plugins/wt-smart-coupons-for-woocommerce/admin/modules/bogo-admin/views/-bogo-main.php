<?php
/**
 * Content of new BOGO page
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$wbte_ds_obj = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );

if ( ! self::is_new_bogo_activated() ) {
	include_once plugin_dir_path( __FILE__ ) . '--new-bogo-switching.php';
	return;
}

if ( isset( $_GET['wbte_bogo_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$wbte_coupon    = new WC_Coupon( absint( wp_unslash( $_GET['wbte_bogo_id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$wbte_coupon_id = $wbte_coupon->get_id();
	if ( self::$bogo_coupon_type_name !== $wbte_coupon->get_discount_type() ) {
		echo '<h1 style="display: flex; justify-content: center; align-items: center; height: 100vh;">' . esc_html__( 'Provided ID is not a BOGO coupon', 'thetoppercentile-coupons' ) . '</h1>';
		exit;
	}
	include_once plugin_dir_path( __FILE__ ) . '--bogo-edit-page.php';
	return;
}

// Include common BOGO header.
echo $wbte_ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	'header',
	array(
		'values' => array(
			'plugin_name'      => 'Smart coupon',
			'developed_by_txt' => esc_html__( 'Developed by', 'thetoppercentile-coupons' ),
			'plugin_logo'      => esc_url( $admin_img_path . 'voucher_tag.svg' ),
		),
	)
);


$wbte_discount_tag_img = '<img src="' . esc_url( $admin_img_path ) . 'bogo_discount_tag.svg" alt="' . esc_attr__( 'Discount tag', 'thetoppercentile-coupons' ) . '">';
require_once plugin_dir_path( __FILE__ ) . '--bogo-main-general.php';
$wbte_all_bogo_coupon_count = self::get_total_bogo_counts();
?>
<div class="wbte_sc_bogo_body">
	<div class="wbte_sc_bogo_outer_box <?php echo ( 0 >= $wbte_all_bogo_coupon_count ) ? '' : 'wbte_sc_bogo_outer_box_listing'; ?>">
		<?php
		if ( 0 >= $wbte_all_bogo_coupon_count ) {
			include_once plugin_dir_path( __FILE__ ) . '--first-bogo-campaign.php';
		} else {
			include_once plugin_dir_path( __FILE__ ) . '--bogo-listing.php';
		}
		?>
	</div>
	<?php

	if ( 0 < $wbte_all_bogo_coupon_count ) {
		?>
		<div class="wbte_sc_bogo_sidebar">
			<?php
			$wbte_premium_url = esc_url( 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=free_plugin_bogo_sidebar&utm_medium=smart_coupons_basic&utm_campaign=smart_coupons&utm_content=' . WEBTOFFEE_SMARTCOUPON_VERSION );
			include_once WT_SMARTCOUPON_MAIN_PATH . 'admin/views/-premium-features-sidebar.php';

			// Newsletter sidebar.
			include_once WT_SMARTCOUPON_MAIN_PATH . 'admin/views/-wbte-newsletter-sidebar.php';
			?>
		</div>
		<?php

	}

	echo $wbte_ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'help-widget',
		array(
			'values' => array(
				'items'      => array(
					array(
						'title'  => esc_html__( 'Setup Guide', 'thetoppercentile-coupons' ),
						'icon'   => 'book',
						'href'   => esc_url( 'https://www.thetoppercentile.com/woocommerce-bogo-discounts/' ),
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
</div>




