<?php
/**
 * BOGO bulk container in bulk generate page
 *
 * @since 2.2.0
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="wbte_sc_bulk_bogo_container">
	
	<div class="wbte_sc_admin_vrtl_nav">
		<div class="wbte_sc_admin_vrtl_nav_items">
			<div class="wbte_sc_admin_vrtl_nav_item active" data-section="general"><?php esc_html_e( 'General', 'thetoppercentile-coupons' ); ?></div>
			<div class="wbte_sc_admin_vrtl_nav_item" data-section="giveaway"><?php esc_html_e( 'Giveaway products', 'thetoppercentile-coupons' ); ?></div>
			<div class="wbte_sc_admin_vrtl_nav_item" data-section="trigger"><?php esc_html_e( 'Offer trigger', 'thetoppercentile-coupons' ); ?></div>
			<div class="wbte_sc_admin_vrtl_nav_item" data-section="limit"><?php esc_html_e( 'Limit', 'thetoppercentile-coupons' ); ?></div>
		</div>

		<div class="wbte_sc_admin_vrtl_nav_content">
			<div class="wbte_sc_admin_vrtl_nav_content_section active" data-section="general">
				<?php require plugin_dir_path( __FILE__ ) . '--bogo-bulk-general.php'; ?>
			</div>
			
			<div class="wbte_sc_admin_vrtl_nav_content_section" data-section="giveaway">
				<?php
				/**
				 * Action hook to render the content for step 1.
				 *
				 * @since 2.2.0
				 */
				do_action( 'wbte_sc_bogo_edit_step1_content' );
				?>
			</div>
			
			<div class="wbte_sc_admin_vrtl_nav_content_section" data-section="trigger">
				<?php
				/**
				 * Action hook to render the content for step 2.
				 *
				 * @since 2.2.0
				 */
				do_action( 'wbte_sc_bogo_edit_step2_content' );
				?>
			</div>
			
			<div class="wbte_sc_admin_vrtl_nav_content_section" data-section="limit">
				<p style="padding-left: 16px;"><?php esc_html_e( 'Apply offer', 'thetoppercentile-coupons' ); ?></p>
				<?php
				/**
				 * Action hook to render the content for step 3.
				 *
				 * @since 2.2.0
				 */
				do_action( 'wbte_sc_bogo_edit_step3_content' );
				?>
			</div>
		</div>
	</div>
</div>