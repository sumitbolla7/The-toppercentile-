<?php
/**
 * Plain coupon template
 *
 * @package  Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<style type="text/css">
.wt-single-coupon.plain_coupon{ border:none; }
</style>
<div class="wt_sc_single_coupon wt-single-coupon plain_coupon [wt_sc_single_coupon_class]" data-id="[wt_sc_coupon_id]" data-code="[wt_sc_coupon_code_attr]" title="[wt_sc_single_coupon_title]" style="background:[wt_sc_color_0]; color:[wt_sc_color_1];" tabindex="0" role="button" aria-label="<?php esc_attr_e( 'Click to apply coupon code [wt_sc_coupon_code_attr]', 'thetoppercentile-coupons' ); ?>" aria-pressed="false">
	<div class="wbte_sc_coupon_layout_expired_text" style="visibility: [wt_sc_coupon_exp_overlay_visibility];"><?php esc_html_e( 'Expired', 'thetoppercentile-coupons' ); ?></div>
	<div class="wbte_sc_coupon_layout_expired_overlay" style="visibility: [wt_sc_coupon_exp_overlay_visibility];"></div>
	<div class="wt_sc_coupon_content wt-coupon-content">
		<div class="wt-coupon-amount">
			<span class="wt_sc_coupon_amount amount" style="color: [wt_sc_color_1];">[wt_sc_coupon_amount]</span>
			<span class="wt_sc_coupon_type discount_type">[wt_sc_coupon_type]</span>
		</div>
		<div class="wt_sc_coupon_code wt-coupon-code"> 
			<code style="color: [wt_sc_color_1];">[wt_sc_coupon_code]</code>
		</div>
		
		<div class="wt_sc_coupon_start wt-coupon-start">[wt_sc_coupon_start]</div>
		<div class="wt_sc_coupon_expiry wt-coupon-expiry">[wt_sc_coupon_expiry]</div>

		<div class="wt_sc_coupon_desc_wrapper coupon-desc-wrapper">
			<i class="info"> i </i>
			<div class="wt_sc_coupon_desc coupon-desc">[wt_sc_coupon_desc]</div>
		</div>
		<div class="wt_sc_credit_history coupon-history">
			<a title="__[Credit history]__" class="wt_sc_credit_history_url" href="[wt_sc_credit_history_url]"><span class="dashicons dashicons-backup"></span></a>
		</div>
	</div>
</div>