<?php
/**
 * Help links metabox html
 *
 * @since 1.3.5
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}



$wbte_help_links = array(
	array(
		'title' => __( 'Set up BOGO offer', 'thetoppercentile-coupons' ),
		'link'  => 'https://www.thetoppercentile.com/how-to-create-woocommerce-bogo-coupons-with-smart-coupons-for-woocommerce/?utm_source=Smart_coupons_free_plugin&utm_medium=SC_free_plugin_documentation&utm_campaign=Smart_Coupons_Documentation',
	),
	array(
		'title' => __( "Create 'Seasonal Discount' offer", 'thetoppercentile-coupons' ),
		'link'  => 'https://www.thetoppercentile.com/how-to-offer-seasonal-discounts-in-woocommerce/?utm_source=Smart_coupons_free_plugin&utm_medium=SC_free_plugin_documentation&utm_campaign=Smart_Coupons_Documentation',
	),
	array(
		'title' => __( 'Auto apply coupons on checkout', 'thetoppercentile-coupons' ),
		'link'  => 'https://www.thetoppercentile.com/how-to-auto-apply-coupon-on-checkout-in-woocommerce/?utm_source=Smart_coupons_free_plugin&utm_medium=SC_free_plugin_documentation&utm_campaign=Smart_Coupons_Documentation',
	),
	array(
		'title' => __( 'Offer discount based on Shipping/Payment/User role', 'thetoppercentile-coupons' ),
		'link'  => 'https://www.thetoppercentile.com/how-to-offer-discounts-based-on-shipping-payment-or-user-role/?utm_source=Smart_coupons_free_plugin&utm_medium=SC_free_plugin_documentation&utm_campaign=Smart_Coupons_Documentation',
	),
);
?>
<style type="text/css">
.wt_sc_help_links{width:100%; }
.wt_sc_help_links li{ line-height:12px; box-sizing:border-box; width:100%; padding:3px 7px 3px 7px; margin-left:15px; list-style:square; line-height:16px; }
.wt_sc_help_link_more{ width:100%; text-align:right; margin-top:25px; }
.wt_sc_help_link_more .dashicons{ font-size:16px; line-height:20px; }
</style>
<p>
	<?php esc_html_e( 'Here are a few links that explains types of offers you can create.', 'thetoppercentile-coupons' ); ?> 
</p>
<ul class="wt_sc_help_links">
	<?php
	foreach ( $wbte_help_links as $wbte_help_link ) {
		?>
		<li>
			<a href="<?php echo esc_attr( $wbte_help_link['link'] ); ?>" target="_blank">
				<?php echo esc_html( $wbte_help_link['title'] ); ?>
			</a>
		</li>
		<?php
	}
	?>
</ul>
<div class="wt_sc_help_link_more">
	<?php esc_html_e( 'To know more, read ', 'thetoppercentile-coupons' ); ?> 
	<a href="https://www.thetoppercentile.com/smart-coupons-for-woocommerce-userguide/" target="_blank">
		<?php esc_html_e( 'documentation', 'thetoppercentile-coupons' ); ?>.
	</a>
</div>