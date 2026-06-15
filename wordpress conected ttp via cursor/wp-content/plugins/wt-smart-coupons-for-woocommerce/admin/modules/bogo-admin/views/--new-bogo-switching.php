<?php
/**
 * Information page while switching from Old BOGO to new
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;

$wbte_lookup_table = Wt_Smart_Coupon::get_lookup_table_name();

$wbte_old_bogo_coupon_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Only used when activating new bogo, so no need to cache.
	$wpdb->prepare( "SELECT COUNT(*) FROM $wbte_lookup_table WHERE discount_type = %s AND post_status = %s", 'wt_sc_bogo', 'publish' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
);
?>

<div class="wbte_sc_bogo_switching">
	<div class="wbte_sc_bogo_switching_content">
		<h3><?php esc_html_e( 'Discover our enhanced BOGO module', 'thetoppercentile-coupons' ); ?></h3>
		<p><?php esc_html_e( "We've separated BOGO from other coupon types to unlock endless possibilities  based on your feedback and market trends.", 'thetoppercentile-coupons' ); ?></p>
		<span>
			<p><?php esc_html_e( 'Why Switch', 'thetoppercentile-coupons' ); ?></p>
			<img src="<?php echo esc_url( $admin_img_path ); ?>bogo_switch_help.svg" alt="<?php esc_html_e( 'Why switch to new bogo', 'thetoppercentile-coupons' ); ?>">
		</span>
		<ul>
			<li><p><?php esc_html_e( 'Expanded Features: Unlock new possibilities with our focused BOGO module.', 'thetoppercentile-coupons' ); ?></p></li>
			<li><p><?php esc_html_e( 'Improved Experience: Simplified and more efficient coupon management.', 'thetoppercentile-coupons' ); ?></p></li>
		</ul>
	</div>
	<?php if ( 0 < $wbte_old_bogo_coupon_count ) { ?>
	<div class="wbte_sc_bogo_switching_warning">
		<span style="height: 24px;"><?php echo wp_kses_post( $wbte_ds_obj->render_html( array( 'html' => '{{wbte-ds-icon-exclamation-mark-1}}' ) ) ); ?></span>
		<p>
			<?php
			// translators: 1: Number of BOGO coupons.
			echo wp_kses_post( sprintf( __( 'Switching to the new BOGO module will disable %d BOGO coupons made using the old version.', 'thetoppercentile-coupons' ), $wbte_old_bogo_coupon_count ) );
			?>
		</p>
	</div>
	<?php } ?>
	<div class="wbte_sc_bogo_switching_btn_div">
		<?php
		echo $wbte_ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'button filled medium',
			array(
				'values' => array(
					'button_title' => esc_html__( 'Update now', 'thetoppercentile-coupons' ),
				),
				'class'  => array( 'wbte_sc_bogo_switching_btn' ),
				'attr'   => array(
					'data-old-bogo-count' => esc_attr( $wbte_old_bogo_coupon_count ),
				),
			)
		);
		?>
	</div>
</div>