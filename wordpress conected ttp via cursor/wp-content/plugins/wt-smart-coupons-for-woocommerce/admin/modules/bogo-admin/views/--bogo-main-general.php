<?php
/**
 * BOGO general settings
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="wbte_sc_bogo_general_settings" class="wbte_sc_bogo_general_settings">     
	
	<div class="wbte_sc_bogo_general_settings_head">
		<h3><?php esc_html_e( 'General settings', 'thetoppercentile-coupons' ); ?></h3>
		<p class="wbte_sc_bogo_general_settings_close">&times;</p>
	</div>
	<div class="wbte_sc_bogo_general_settings_body">
		<form id="wbte_sc_bogo_general_settings_form" action="POST">
			<?php
			Wbte_Smart_Coupon_Bogo_Admin::generate_main_general_settings_form_field(
				array(
					array(
						'label'        => __( 'Auto add products for Buy X Get X/Y giveaways', 'thetoppercentile-coupons' ),
						'option_name'  => 'wbte_sc_bogo_auto_add_giveaway',
						'type'         => 'radio',
						'radio_type'   => 'multi-line',
						'radio_fields' => array(
							'wbte_sc_bogo_auto_add_full_giveaway' => esc_html__( 'Add only free products to cart', 'thetoppercentile-coupons' ),
							'wbte_sc_bogo_auto_add_all_giveaway' => esc_html__( 'Add all discounted products to cart', 'thetoppercentile-coupons' ),
						),
					),
					array(
						'label'          => __( 'Apply tax on', 'thetoppercentile-coupons' ),
						'option_name'    => 'wbte_sc_bogo_apply_tax_on',
						'type'           => 'radio',
						'radio_type'     => 'multi-line',
						'radio_fields'   => array(
							'wbte_sc_bogo_apply_tax_on_discount' => sprintf(
								// Translators: 1: Tooltip.
								esc_html__( 'Discounted price %s', 'thetoppercentile-coupons' ),
								wp_kses_post( wc_help_tip( __( 'Tax is calculated based on the price after the offer is applied', 'thetoppercentile-coupons' ) ) )
							),
							'wbte_sc_bogo_apply_tax_on_original' => sprintf(
								// Translators: 1: Premium icon, 2: Tooltip.
								esc_html__( 'Original price %1$s %2$s', 'thetoppercentile-coupons' ),
								wp_kses_post( '<img class="wbte_sc_bogo_prem_crown_disabled" src="' . esc_url( $admin_img_path . 'prem_crown_2.svg' ) . '" alt="' . esc_attr__( 'premium', 'thetoppercentile-coupons' ) . '" />' ),
								wp_kses_post( wc_help_tip( __( 'Tax is calculated on the original price before the offer is applied.', 'thetoppercentile-coupons' ) ) )
							),
						),
						'disabled_items' => array( 'wbte_sc_bogo_apply_tax_on_original' ),
					),
					array(
						'label'       => __( 'Primary color for BOGO product display', 'thetoppercentile-coupons' ),
						'option_name' => 'wbte_sc_bogo_prod_select_theme_color',
						'type'        => 'color',
					),
					array(
						'label'       => __( 'Offer applied message', 'thetoppercentile-coupons' ),
						'option_name' => 'wbte_sc_bogo_general_discount_apply_message',
						'type'        => 'text',
						'attr'        => 'placeholder="' . esc_attr__( 'Apply discount', 'thetoppercentile-coupons' ) . '"',
						'placeholder' => 'wbte_sc_bogo_general_discount_apply_message',
					),
					array(
						'label'       => __( 'Product added message', 'thetoppercentile-coupons' ),
						'option_name' => 'wbte_sc_bogo_general_product_added_message',
						'type'        => 'text',
						'attr'        => 'placeholder="' . esc_attr__( 'Product added...', 'thetoppercentile-coupons' ) . '"',
						'placeholder' => 'wbte_sc_bogo_general_product_added_message',
					),
					array(
						'label'       => __( 'Discount info under each item in cart', 'thetoppercentile-coupons' ),
						'option_name' => 'wbte_sc_bogo_general_discount_under_product_msg',
						'attr'        => 'placeholder="' . esc_attr__( 'Discount...', 'thetoppercentile-coupons' ) . '"',
						'placeholder' => 'wbte_sc_bogo_general_discount_under_product_msg',
					),
					array(
						'label'       => __( '"Choose product" title', 'thetoppercentile-coupons' ),
						'option_name' => 'wbte_sc_bogo_general_apply_choose_product_title',
						'attr'        => 'placeholder="' . esc_attr__( 'Choose product', 'thetoppercentile-coupons' ) . '"',
						'placeholder' => 'wbte_sc_bogo_general_apply_choose_product_title',
					),
				)
			);
			?>

			<div class="wbte_sc_bogo_general_settings_btn_div">
				<?php
				echo $wbte_ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'button filled medium',
					array(
						'values' => array(
							'button_title' => esc_html__( 'Update settings', 'thetoppercentile-coupons' ),
						),
						'class'  => array( 'wbte_sc_bogo_update_general_settings' ),
					)
				);
				?>
			</div>
		</form>
	</div>
</div>