<?php
/**
 * Help links metabox html
 *
 * @since 1.4.4
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>
<div class="wt-sc-tab-content" data-id="<?php echo esc_attr( $target_id ); ?>">
	<h3>
		<?php esc_html_e( 'Help', 'thetoppercentile-coupons' ); ?>
	</h3> 
	<p><?php esc_html_e( 'TheTopPercentile Coupons settings are active.', 'thetoppercentile-coupons' ); ?></p>
</div>