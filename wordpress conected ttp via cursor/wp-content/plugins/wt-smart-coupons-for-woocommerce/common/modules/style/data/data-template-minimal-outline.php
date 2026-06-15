<?php
/**
 * Minimal outline coupon template
 *
 * @package  Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<style type="text/css">
.wt-single-coupon.minimal_outline{ position: relative; padding: 12px 20px; border-radius: 6px; overflow: hidden; text-align: left; font-family: 'Inter',sans-serif; min-height: 100px; min-width: 314px; }
.wbte_sc_corner_circle{ position: absolute; top: -20px; right: -20px; width: 54px; height: 54px; border-radius: 50%; }
.wt-single-coupon.minimal_outline .wt-coupon-amount{ display: flex; align-items: baseline; margin-top: 5px; }
.wt-single-coupon.minimal_outline .wt_sc_coupon_amount{ font-size: 36px; font-weight: 600; }
.wt-single-coupon.minimal_outline .wt_sc_coupon_type{ font-size: 12px; font-weight: 500; }
.wt-single-coupon.minimal_outline .wbte_sc_coupon_desc{ font-size: 10px; font-weight: 400; margin-bottom: 30px; max-width: 70%; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; line-height: 1.5; max-height: 4.5em; overflow: hidden; }
.wt-single-coupon.minimal_outline .wt_sc_coupon_code{  display: table-cell; width: 70%; font-weight: 500;  border-radius: 4px;  overflow: hidden;  white-space: nowrap;  min-width: 0; height: 30px; flex: 1; text-align: center; }
.wt-single-coupon.minimal_outline .wt_sc_coupon_code code{ font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wt-single-coupon.minimal_outline .wt_sc_coupon_expiry{ display: table-cell; width: 30%; font-weight: 400;  font-size: 10px; }
.wt-single-coupon.minimal_outline .wbte_sc_footer{  display: table; width: calc(100% - 40px); position: absolute; bottom: 12px; }
@supports ( display: -webkit-box ){
	.wt-single-coupon.minimal_outline .wt_sc_coupon_expiry{ width: unset; display: flex; align-items: center; margin-left: 5px; }
	.wt-single-coupon.minimal_outline .wt_sc_coupon_code{ width: unset; display: flex; align-items: center; justify-content: center; }
	.wt-single-coupon.minimal_outline .wbte_sc_footer{  display: -webkit-flex; display: -ms-flexbox; display: flex; align-items: center; justify-content: space-between; }
}
</style>

<div class="wt_sc_single_coupon wt-single-coupon minimal_outline [wt_sc_single_coupon_class]" data-id="[wt_sc_coupon_id]" data-code="[wt_sc_coupon_code_attr]" title="[wt_sc_single_coupon_title]" style="background:[wt_sc_color_0];color:[wt_sc_color_1]; border: 1px solid [wt_sc_color_2];" tabindex="0" role="button" aria-label="<?php esc_attr_e( 'Click to apply coupon code [wt_sc_coupon_code_attr]', 'thetoppercentile-coupons' ); ?>" aria-pressed="false">
	<div class="wbte_sc_coupon_layout_expired_text" style="visibility: [wt_sc_coupon_exp_overlay_visibility];"><?php esc_html_e( 'Expired', 'thetoppercentile-coupons' ); ?></div>
	<div class="wbte_sc_coupon_layout_expired_overlay" style="visibility: [wt_sc_coupon_exp_overlay_visibility];"></div>
	<div class="wbte_sc_corner_circle" style="background-color: [wt_sc_color_2];"></div>
	<div class="wt-coupon-amount">
		<div class="wt_sc_coupon_amount amount" style="color: [wt_sc_color_2];">[wt_sc_coupon_amount]</div>
		<div class="wt_sc_coupon_type discount_type" style="color: [wt_sc_color_2];">[wt_sc_coupon_type]</div>
	</div>
	<div class="wbte_sc_coupon_desc" style="color: [wt_sc_color_1];">[wt_sc_coupon_desc]</div>
	<div class="wbte_sc_footer">
		<div class="wt_sc_coupon_code wt-coupon-code" style="color: [wt_sc_color_2]; background-color: [wt_sc_color_2]20;"><code style="color: [wt_sc_color_2];">[wt_sc_coupon_code]</code></div>
		<div class="wt_sc_coupon_expiry wt-coupon-expiry" style="color: [wt_sc_color_1]; display: [wt_sc_coupon_exp_clock_icon];">
			<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M6.41671 1.1665C5.26299 1.1665 4.13517 1.50862 3.17588 2.1496C2.2166 2.79057 1.46892 3.70161 1.02741 4.76752C0.585902 5.83342 0.470382 7.00631 0.695463 8.13786C0.920543 9.26942 1.47611 10.3088 2.29192 11.1246C3.10773 11.9404 4.14713 12.496 5.27868 12.7211C6.41024 12.9462 7.58313 12.8306 8.64903 12.3891C9.71493 11.9476 10.626 11.2 11.2669 10.2407C11.9079 9.28138 12.25 8.15356 12.25 6.99984C12.25 6.23379 12.0992 5.47525 11.806 4.76752C11.5129 4.05978 11.0832 3.41672 10.5415 2.87505C9.99982 2.33337 9.35676 1.90369 8.64903 1.61054C7.9413 1.31739 7.18275 1.1665 6.41671 1.1665ZM6.41671 11.6665C5.49373 11.6665 4.59148 11.3928 3.82405 10.88C3.05662 10.3672 2.45848 9.63841 2.10527 8.78569C1.75206 7.93297 1.65965 6.99466 1.83971 6.08942C2.01978 5.18417 2.46423 4.35265 3.11688 3.70001C3.76952 3.04736 4.60104 2.6029 5.50629 2.42284C6.41153 2.24277 7.34984 2.33519 8.20257 2.6884C9.05529 3.04161 9.78412 3.63975 10.2969 4.40718C10.8097 5.17461 11.0834 6.07686 11.0834 6.99984C11.0834 8.23751 10.5917 9.4245 9.71654 10.2997C8.84137 11.1748 7.65439 11.6665 6.41671 11.6665ZM7.00004 6.959V4.08317C7.00004 3.92846 6.93858 3.78009 6.82919 3.67069C6.71979 3.5613 6.57142 3.49984 6.41671 3.49984C6.262 3.49984 6.11363 3.5613 6.00423 3.67069C5.89483 3.78009 5.83338 3.92846 5.83338 4.08317V6.99984C5.83338 6.99984 5.83338 7.04067 5.83338 7.064C5.81902 7.18259 5.84142 7.30272 5.89754 7.40817L6.77254 8.92484C6.8499 9.05943 6.97755 9.15779 7.12743 9.19827C7.2773 9.23874 7.43711 9.21803 7.57171 9.14067C7.70631 9.06332 7.80466 8.93566 7.84514 8.78579C7.88562 8.63591 7.8649 8.4761 7.78754 8.3415L7.00004 6.959Z" fill="[wt_sc_color_1]"/>
			</svg>
			[wt_sc_coupon_expiry_ctm]
		</div>
	</div>
	<div class="wt_sc_credit_history coupon-history">
		<a title="__[Credit history]__" class="wt_sc_credit_history_url" href="[wt_sc_credit_history_url]"><span class="dashicons dashicons-backup"></span></a>
	</div>
</div>
