<?php
/**
 * BOGO pro banner edit page in step container.
 *
 * @package    Wt_Smart_Coupon
 * @since      2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<style>
	.wbte_sc_pro_hor_banner { padding: 15px 24px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; border-left: 6px solid; box-sizing: border-box; }
	.wbte_sc_bogo_edit_main .wbte_sc_pro_hor_banner{ margin-left: -88px; }
	.wbte_sc_pro_hor_banner_content { display: flex; align-items: baseline; gap: 6px; width: 70%; }
	.wbte_sc_hor_banner_dash_icon { font-size: 13px; }
	.wbte_sc_pro_hor_banner_text { font-size: 13px !important; color: #333; font-weight: 400 !important; }
	.wbte_sc_pro_hor_banner_text_highlight { font-weight: 500; font-size: 13px; }
	.wbte_sc_pro_hor_banner_btn { border: none !important; font-weight: 500; text-decoration: none; }
</style>

<div class="wbte_sc_pro_hor_banner" style="background-color: <?php echo esc_attr( $banner_args['bg_color'] ); ?>; border-left-color: <?php echo esc_attr( $banner_args['dark_color'] ); ?>">
	<div class="wbte_sc_pro_hor_banner_content">
		<span class="dashicons wbte_sc_hor_banner_dash_icon <?php echo esc_attr( $banner_args['dash_icon'] ); ?>" style="color: <?php echo esc_attr( $banner_args['dark_color'] ); ?>;"></span>
		<div class="wbte_sc_pro_hor_banner_content_txt">
			<p class="wbte_sc_pro_hor_banner_text_highlight" style="color: <?php echo esc_attr( $banner_args['dark_color'] ); ?>;"><?php echo esc_html( $banner_args['content_head'] ); ?></p>
			<p class="wbte_sc_pro_hor_banner_text">
				<?php echo esc_html( $banner_args['content'] ); ?>
			</p>
		</div>
	</div>
	<a href="<?php echo esc_url( $banner_args['url'] ); ?>" target="_blank" class="wbte_sc_button wbte_sc_button-filled wbte_sc_button-small wbte_sc_pro_hor_banner_btn" style="background-color: <?php echo esc_attr( $banner_args['dark_color'] ); ?> !important">
		<?php
		// translators: 1: Right arrow icon.
		printf( esc_html__( 'Check out this plugin %s', 'thetoppercentile-coupons' ), wp_kses_post( '<span>' . $ds_obj->render_html( array( 'html' => '{{wbte-ds-icon-right-arrow}}' ) ) . '</span>' ) );
		?>
	</a>
</div>