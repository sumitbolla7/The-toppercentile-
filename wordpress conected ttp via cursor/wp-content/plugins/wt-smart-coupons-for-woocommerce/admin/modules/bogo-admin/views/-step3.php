<?php
/**
 * BOGO edit page step 3
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$ds_obj         = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );
$admin_img_path = WT_SMARTCOUPON_MAIN_URL . 'admin/images/';
?>

<div class="wbte_sc_bogo_edit_step_content">
	<div class="wbte_sc_bogo_step_opened">
		<table class="wbte_sc_bogo_edit_table wbte_sc_bogo_apply_repeatedly_table">
			<tbody>
				<tr>
					<td class="wbte_sc_bogo_edit_radio_fields" >
						<?php
						$apply_offer_times_selected = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_apply_offer' );
						echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'radio-group inline',
							array(
								'values' => array(
									'name'  => 'wbte_sc_bogo_apply_offer',
									'items' => array(
										array(
											'label'      => esc_html__( 'Once', 'thetoppercentile-coupons' ),
											'value'      => 'wbte_sc_bogo_apply_once',
											'is_checked' => esc_attr( 'wbte_sc_bogo_apply_once' === $apply_offer_times_selected ),
										),
										array(
											'label'      => esc_html__( 'Repeatedly', 'thetoppercentile-coupons' ),
											'value'      => 'wbte_sc_bogo_apply_repeatedly',
											'is_checked' => esc_attr( 'wbte_sc_bogo_apply_repeatedly' === $apply_offer_times_selected ),
										),
										array(
											'label'       => esc_html__( 'Custom', 'thetoppercentile-coupons' ) . wp_kses_post( '<img class="wbte_sc_bogo_prem_crown_disabled" src="' . esc_url( $admin_img_path . 'prem_crown_2.svg' ) . '" alt="' . esc_attr__( 'premium', 'thetoppercentile-coupons' ) . '" />' ),
											'value'       => 'wbte_sc_bogo_apply_custom',
											'is_disabled' => true,
										),
									),
								),
							)
						);
						?>
					</td>
				</tr>
				<tr class="wbte_sc_bogo_apply_once_row">
					<td colspan="2">
						<p class="wbte_sc_bogo_repeatedly_once_msg" style="margin-top:10px;"></p>
					</td>
				</tr>
				<tr class="wbte_sc_bogo_apply_repeatedly_row">
					<td colspan="2">
						<p class="wbte_sc_bogo_repeatedly_msg"></p>
					</td>
				</tr>
				<tr class="wbte_sc_bogo_apply_repeatedly_row">
					<td colspan="2">
						<p class="wbte_sc_bogo_disabled_drop_down_btn">
						<?php
						printf(
							// Translators: 1: Apply repeatedly limit. 2: premium crown img.
							esc_html__( 'Limit for applying repeatedly %1$s time(s) %2$s', 'thetoppercentile-coupons' ),
							'<span><input type="text" class="wbte_sc_admin_number_input" style="cursor: not-allowed;" disabled value="∞"></span>',
							'<img class="wbte_sc_bogo_prem_crown_disabled" src="' . esc_url( $admin_img_path . 'prem_crown_2.svg' ) . '" alt="' . esc_attr__( 'premium', 'thetoppercentile-coupons' ) . '" />'
						);
						?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		$file_path = plugin_dir_path( __FILE__ ) . '----edit-hor-banner.php';
		if ( file_exists( $file_path ) ) {
			$banner_args = array(
				'content_head' => esc_html__( 'How about some additional features?', 'thetoppercentile-coupons' ),
				'content'      => esc_html__( 'You can set advanced cart conditions and create custom discount rules for giveaway products with the premium version!', 'thetoppercentile-coupons' ),
				'bg_color'     => '#F5EBFF',
				'dark_color'   => '#9A55E0',
				'url'          => 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=free_plugin_bogo_trigger&utm_medium=smart_coupons_basic&utm_campaign=smart_coupons&utm_content=' . WEBTOFFEE_SMARTCOUPON_VERSION,
				'dash_icon'    => 'dashicons-star-filled',
			);
			include $file_path;
		}
		?>
	</div>
	<div class="wbte_sc_bogo_step_short_description wbte_sc_bogo_custom_summary">
		<!-- Value assign in js -->
		<span class="wbte_sc_bogo_apply_repeatedly_short"></span>
		<span class="wbte_sc_bogo_repeatedly_additional_summary wbte_sc_bogo_no_style_span"></span>
	</div>
</div>