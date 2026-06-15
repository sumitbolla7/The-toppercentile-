<?php
/**
 * Save button HTML
 *
 * @since 1.0.0
 * @package Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
$wbte_settings_button_title = isset( $wbte_settings_button_title ) && '' !== $wbte_settings_button_title ? $wbte_settings_button_title : __( 'Save settings', 'thetoppercentile-coupons' );

// left and right HTML for settings footer.
$wbte_settings_footer_left  = isset( $wbte_settings_footer_left ) ? $wbte_settings_footer_left : '';
$wbte_settings_footer_right = isset( $wbte_settings_footer_right ) ? $wbte_settings_footer_right : '';
?>
<div style="clear: both;"></div>
<div class="bottom">
	<div class="left">
		<?php echo wp_kses_post( $wbte_settings_footer_left ); ?>
	</div>
	<div class="right">
		<input type="submit" name="wt_sc_update_admin_settings_form" value="<?php echo esc_attr( $wbte_settings_button_title ); ?>" class="wbte_sc_button wbte_sc_button-filled wbte_sc_button-medium" style="float:right;"/>
		<?php echo wp_kses_post( $wbte_settings_footer_right ); ?>
		<span class="spinner" style="margin-top:11px;"></span>
	</div>
</div>