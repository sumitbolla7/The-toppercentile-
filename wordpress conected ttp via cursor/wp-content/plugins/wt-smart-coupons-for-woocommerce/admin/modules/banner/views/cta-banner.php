<?php
/**
 * CTA banner template.
 *
 * @since 2.2.8
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wbte_banner_id          = isset( $args['banner_id'] ) ? $args['banner_id'] : '';
$wbte_banner_title       = isset( $args['title'] ) ? $args['title'] : '';
$wbte_content            = isset( $args['content'] ) ? $args['content'] : '';
$wbte_primary_btn_url    = isset( $args['primary_btn_url'] ) ? $args['primary_btn_url'] : '';
$wbte_primary_btn_text   = isset( $args['primary_btn_text'] ) ? $args['primary_btn_text'] : '';
$wbte_secondary_btn_url  = isset( $args['secondary_btn_url'] ) ? $args['secondary_btn_url'] : '';
$wbte_secondary_btn_text = isset( $args['secondary_btn_text'] ) ? $args['secondary_btn_text'] : '';

?>

<div data-wbte-sc-promotion-banner-id="<?php echo esc_attr( $wbte_banner_id ); ?>" class="wbte_sc_promotion_banner_div">
	<span class="wbte_sc_promotion_banner_title"><?php echo wp_kses_post( $wbte_banner_title ); ?></span>
	<div class="wbte_sc_promotion_banner_content">
		<p style="margin: 0; font-size: 14px;"><?php echo wp_kses_post( $wbte_content ); ?></p>
		<div class="wbte_sc_promotion_banner_actions">
			<a class="button button-secondary wbte_sc_promotion_banner_primary_btn" href="<?php echo esc_url( $wbte_primary_btn_url ); ?>" target="_blank"><?php echo esc_html( $wbte_primary_btn_text ); ?> <span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px; line-height: 1.5;"></span></a>
			<?php if ( $wbte_secondary_btn_text ) : ?>
				&ensp;
				<a href="<?php echo esc_url( $wbte_secondary_btn_url ); ?>" target="_blank" class="button button-secondary wbte_sc_promotion_banner_secondary_btn <?php echo '' === $wbte_secondary_btn_url ? 'wbte_sc_promotion_banner_close' : ''; ?>"><?php echo esc_html( $wbte_secondary_btn_text ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<span class="dashicons dashicons-no-alt wbte_sc_promotion_banner_close wbte_sc_promotion_banner_close_btn"></span>
</div>