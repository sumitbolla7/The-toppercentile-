<?php
/**
 * URL coupon tab data
 *
 * @link
 * @since 1.3.5
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wt_section_title">
	<h3><?php esc_html_e( 'URL coupon', 'thetoppercentile-coupons' ); ?></h3>
	<p><?php esc_html_e( 'The plugin auto generates a unique URL for all the coupons created in your store. Visiting the URL associated with a coupon will automatically redirect the users to the cart page by applying the coupon. You can embed a URL in a button, and your customer can click the button to apply the coupon.', 'thetoppercentile-coupons' ); ?></p>
	<p>
		<b><?php esc_html_e( 'Prerequisite:', 'thetoppercentile-coupons' ); ?> </b><?php esc_html_e( 'Ensure that you have created a coupon with the required configuration to use it as a URL coupon.', 'thetoppercentile-coupons' ); ?>
	</p>
	<p><b><?php esc_html_e( 'URL coupon format:', 'thetoppercentile-coupons' ); ?> {site_url}/?wt_coupon={coupon_code}</b> </p>
	
	<div style="background:#efefef; padding:5px 15px; color:#666">
		<p><?php esc_html_e( 'A sample URL coupon will be in the given format:', 'thetoppercentile-coupons' ); ?>, https://www.thetoppercentile.com/cart/?wt_coupon=flat30</p>
		<div>
			<?php esc_html_e( 'In the above example,', 'thetoppercentile-coupons' ); ?>
			<ul class="wt_sc_coupon_url_structure">
				<li>'https://www.thetoppercentile.com/cart/' <?php esc_html_e( 'corresponds to the site URL', 'thetoppercentile-coupons' ); ?></li>
				<li><?php esc_html_e( "'?wt_coupon' refers to the URL coupon key", 'thetoppercentile-coupons' ); ?></li>
				<li><?php esc_html_e( "'flat30' is the coupon code", 'thetoppercentile-coupons' ); ?></li>
			</ul>
		</div>
	</div>
</div>