<?php
/**
 * Smart coupon header
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wbte_sc_edit_header">
	<div class="wbte_sc_header_plugin_info">
		<img class="wbte_sc_header_plugin_logo" src="<?php echo esc_url( "{$admin_img_path}thetoppercentile-logo-128.png" ); ?>" alt="<?php esc_html_e( 'TheTopPercentile Coupons', 'thetoppercentile-coupons' ); ?>">
		<div class="wbte_sc_header_plugin_name">
			<?php esc_html_e( 'TheTopPercentile Coupons', 'thetoppercentile-coupons' ); ?>
		</div>
	</div>
	<div class="wbte_sc_header_dev_by">
		<span><?php esc_html_e( 'Powered by TheTopPercentile', 'thetoppercentile-coupons' ); ?></span>
	</div>
</div>