<?php
/**
 * Admin settings right sidebar
 *
 *  @since 1.4.0
 * @package Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

?>
<div class="wt_smart_coupon_admin_form_right_box">
	<?php
	/**
	 * Action to add content to the right sidebar
	 *
	 *  @since 1.4.0
	 */
	do_action( 'wt_smart_coupon_admin_form_right_box' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	?>
</div>