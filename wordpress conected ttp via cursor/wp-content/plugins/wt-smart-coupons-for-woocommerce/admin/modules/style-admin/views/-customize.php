<?php
/**
 * Customize tab content
 *
 *  @since 1.4.7
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$ds_obj = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );

$coupon_types         = ( isset( $view_params['coupon_types'] ) ? $view_params['coupon_types'] : array() );
$current_coupon_style = ( isset( $view_params['current_coupon_style'] ) ? $view_params['current_coupon_style'] : array() );
$coupon_styles        = ( isset( $view_params['coupon_styles'] ) ? $view_params['coupon_styles'] : array() );
$coupon_data_dummy    = ( isset( $view_params['coupon_data_dummy'] ) ? $view_params['coupon_data_dummy'] : array() );
$general_settings     = ( isset( $view_params['general_settings'] ) ? $view_params['general_settings'] : array() );
?>
<style type="text/css">
.wt_sc_coupon_preview{ float:left; width:400px; }
.wt_sc_coupon_preview .wt_sc_single_coupon{ padding:0px ; }
.wt_sc_coupon_colors{ float:left; width:auto; margin-top:10px; }
.wt_sc_coupon_color_form_element .wt_sc_color_picker{ width:80px; height:30px; border:solid 1px #e5e5e5; border-radius:3px; }
.wt_sc_coupon_color_form_element .wp-picker-holder{ position:absolute; z-index:100; }
.wt_sc_coupon_color_form_element_label{ display:block; margin-bottom:3px; }
.wt_sc_coupon_change_theme_link{ cursor:pointer; font-size:80%; display:inline-block; color: #056BE7; padding:12px 20px; }
.wt_sc_coupon_change_theme_link:focus, .wt_sc_coupon_change_theme_link:hover{ color: #056BE7; }
.wt_sc_coupon_templates .wt_sc_popup_body{ padding: 20px; display: flex; flex-wrap: wrap; justify-content: center; }
.wt_sc_coupon_templates .wt_sc_single_template_box{ float:left; width:350px; min-height:150px; padding-top:10px; margin:10px; text-align:center; cursor:pointer;}
.wt_sc_coupon_templates .wt_sc_single_template_box:hover{ box-shadow:0px 0px 5px #ccc; }
.wt_sc_coupon_templates .wt_sc_single_template_box .wt-single-coupon{ float:none; margin:0px; display:inline-block; }
.wt_sc_coupon_templates .wt_sc_single_template_box .wt-single-coupon.stitched_padding{ margin-left:3px; margin-top:3px; }
.wt_sc_coupon_templates .wt_sc_single_template_box label{ float:left; width:100%; padding:5px 0px; }
.wt_sc_template_refer{ display: none; }
.wbte_sc_color_picker_field.hide{ display: none; }

.wt_sc_coupon_color_form_element{ display:inline-block; }
.wt_sc_coupon_color_form_element .wp-picker-container{ position:relative; }
.wt_sc_coupon_color_form_element .wp-color-result-text{ display:none; }
.wt_sc_coupon_color_form_element .wp-color-result,
.wt_sc_coupon_color_form_element .wp-color-result.button{ width:25px !important; height:18px !important; padding:0 !important; border:1px solid #ddd; border-radius:4px; min-width:25px !important; min-height:18px !important; box-sizing:border-box !important; margin:0; }
.wt_sc_coupon_color_form_element .wp-picker-container{ padding:0; margin:0; line-height:18px; }
.wt_sc_coupon_colors::before{ content:''; display:none; }
.wbte_sc_coupon_clr_picker_header{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
.wt_sc_coupon_colors.closed .wbte_sc_coupon_clr_picker_header{ margin-bottom:0; }
.wbte_sc_coupon_clr_picker_header_txt{ font-size:14px; color:#333; }
.wbte_sc_coupon_clr_picker_header_arr::before{ content:'\f343'; font-family:dashicons; font-size:16px; }
.wt_sc_coupon_colors.closed .wbte_sc_coupon_clr_picker_header_arr::before{ content:'\f347'; }
.wt_sc_coupon_colors.closed .wt_sc_coupon_color_form_element{ display:none; }
.wt_sc_coupon_colors{ position: relative; padding: 10px; background: #f8f9fa; border-radius: 6px; border: 1px solid #B2D5FF; cursor: pointer; background-color: #F1F8FE; }
.wt_sc_palette_preview{ display:flex; gap:3px; padding:10px; border:1px solid transparent; border-radius:6px; cursor:pointer; width:max-content; }
.wt_sc_palette_preview.selected{ border-color:#58A3FE; background-color:#F1F8FE; }
.wt_sc_coupon_colors.closed{ background:none; border:none; }
.wt_sc_color_swatch{ width:25px; height:18px; border-radius:3px; border:1px solid #ddd; box-sizing:border-box; }
.wt_sc_sub_tab_content{ padding:20px 0px; }
.wt_sc_sub_tab_content .wt_sc_sub_tab_content_left_section{ display: flex; flex-direction: column; gap: 10px; }
.wt_sc_sub_tab_content .wt_sc_sub_tab_content_left_section h3{ margin:0; }
<?php
// inject CSS for coupon block.
echo wp_kses_post( Wt_Smart_Coupon_Style::get_coupon_default_css() );
?>
</style>

<!-- Available coupon style popup start-->
<div class="wt_sc_coupon_templates wt_sc_popup" style="width:80%;">
	<div class="wt_sc_popup_hd">
		<div class="wt_sc_popup_title"><?php esc_html_e( 'Available coupon styles', 'thetoppercentile-coupons' ); ?></div>
		<div class="wt_sc_popup_close">X</div>
	</div>
	<div class="wt_sc_popup_body">
		<?php
		foreach ( $coupon_styles as $style_key => $coupon_style ) {
			$coupon_style['style'] = $style_key;
			$has_multiple_palettes = isset( $coupon_style['color'][0] ) && is_array( $coupon_style['color'][0] );
			?>
			<div class="wt_sc_single_template_box" 
				data-style_key="<?php echo esc_attr( $style_key ); ?>" 
				data-has-palettes="<?php echo esc_attr( $has_multiple_palettes ? 'true' : 'false' ); ?>"
				data-palettes='<?php echo $has_multiple_palettes ? esc_attr( wp_json_encode( $coupon_style['color'] ) ) : ''; ?>'
				title="<?php esc_attr_e( 'Click to choose', 'thetoppercentile-coupons' ); ?>">
				<div class="wt_sc_single_template_box_inner">
					<?php
					// If template has multiple palettes, use first palette as preview.
					$preview_colors = $has_multiple_palettes ? $coupon_style['color'][0] : $coupon_style['color'];
					$preview_style  = array_merge( $coupon_style, array( 'color' => $preview_colors ) );
					echo Wt_Smart_Coupon_Style::prepare_coupon_html( new WC_Coupon( 0 ), $coupon_data_dummy, '', true, $preview_style ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					?>
				</div>
				<label><?php echo esc_html( $coupon_style['name'] ); ?></label>
			
			</div>
			<?php
		}
		?>
			</div>
</div>
<!-- Available coupon style popup end-->

<div class="wt-sc-inner-content">

	<p><?php esc_html_e( 'Choose a style for the coupon types(status based: Active, Expired, Redeemed), from the available templates.', 'thetoppercentile-coupons' ); ?></p>

	<form method="post" class="wt_sc_coupon_style_form">
		<input type="hidden" name="action" value="wt_sc_customize_save">
		<?php
		// Set nonce:.
		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( WT_SC_PLUGIN_NAME );
		}
		?>
		<ul class="wt_sc_sub_tab wbte_sc_segments"> 
			<?php
			foreach ( $coupon_types as $type_key => $type_name ) {
				?>
					<a data-target="<?php echo esc_attr( $type_key ); ?>" class="wbte_sc_segment"><?php echo esc_html( $type_name ); ?></a>
					<?php
			}
			?>
					</ul>   
		<div class="wt_sc_sub_tab_container" style="min-height:230px;">
			<?php
			foreach ( $coupon_types as $type_key => $type_name ) {
				$selected_template = isset( $current_coupon_style[ $type_key ] ) ? $current_coupon_style[ $type_key ] : array();
				$style_name        = ( isset( $selected_template['style'] ) ? $selected_template['style'] : '' );
				?>
				<div class="wt_sc_sub_tab_content" data-id="<?php echo esc_attr( $type_key ); ?>">
					<div class="wt_sc_sub_tab_content_left_section">
						<input type="hidden" 
							class="wt_sc_selected_coupon_style_input" 
							name="wt_coupon_styles[<?php echo esc_attr( $type_key ); ?>][style]" 
							value="<?php echo esc_attr( $style_name ); ?>">
						<div class="wt_sc_coupon_preview" data-coupon_type="<?php echo esc_attr( $type_key ); ?>">
							<?php
							if ( ! empty( $selected_template ) ) {
								echo Wt_Smart_Coupon_Style::prepare_coupon_html( new WC_Coupon( 0 ), $coupon_data_dummy, $type_key, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
							}
							?>
						</div>
						<?php
						if ( 'used_coupon' === $type_key || 'expired_coupon' === $type_key ) {
							$field_key = ( 'used_coupon' === $type_key ? 'display_used_coupons_my_account' : 'display_expired_coupons_my_account' );
							$label     = ( 'used_coupon' === $type_key ? __( 'Display used coupons in My account?', 'thetoppercentile-coupons' ) : __( 'Display expired coupons in My account?', 'thetoppercentile-coupons' ) );
							$field_val = (bool) ( isset( $general_settings[ $field_key ] ) ? $general_settings[ $field_key ] : false );
							echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
								'checkbox normal',
								array(
									'values' => array(
										'name'       => esc_attr( $field_key ),
										'id'         => esc_attr( $field_key ),
										'label'      => esc_html( $label ),
										'is_checked' => esc_attr( $field_val ),
										'value'      => '1',
									),
								)
							);

						}
						?>
						<h3>
							<a class="wt_sc_coupon_change_theme_link" data-coupon_type="<?php echo esc_attr( $type_key ); ?>">
								<?php esc_html_e( 'Change layout', 'thetoppercentile-coupons' ); ?>
							</a>
						</h3>
					</div>

					<div class="wt_sc_sub_tab_content_right_section">
						<div class="wt_sc_color_container">
							<?php
							// Add palette options here, before the color picker.
							if ( ! empty( $selected_template ) ) {
								$style_key = $selected_template['style'];
								if ( isset( $coupon_styles[ $style_key ] ) ) {
									$coupon_style          = $coupon_styles[ $style_key ];
									$has_multiple_palettes = isset( $coupon_style['color'][0] ) && is_array( $coupon_style['color'][0] );

									if ( $has_multiple_palettes ) :
										?>
										<div class="wt_sc_palette_options">
											<?php foreach ( $coupon_style['color'] as $palette_index => $palette ) : ?>
												<div class="wt_sc_palette_preview" data-palette="<?php echo esc_attr( wp_json_encode( $palette ) ); ?>">
													<?php foreach ( $palette as $color ) : ?>
														<span class="wt_sc_color_swatch" style="background-color: <?php echo esc_attr( $color ); ?>"></span>
													<?php endforeach; ?>
												</div>
											<?php endforeach; ?>
										</div>
										<?php
									endif;
								}
							}
							?>

							<div class="wt_sc_coupon_colors" data-coupon_type="<?php echo esc_attr( $type_key ); ?>">
								<div class="wbte_sc_coupon_clr_picker_header">
									<span class="wbte_sc_coupon_clr_picker_header_txt"><?php esc_html_e( 'Custom color', 'thetoppercentile-coupons' ); ?></span>
									<span class="wbte_sc_coupon_clr_picker_header_arr"></span>
								</div>
								<?php
								if ( ! empty( $selected_template ) ) {
									$template_color = isset( $selected_template['color'] ) ? $selected_template['color'] : array();
									foreach ( $template_color as $k => $color ) {
										?>
										<div class="wt_sc_coupon_color_form_element wbte_sc_color_picker_field 
										<?php
										if ( 'plain_coupon' === $style_name && 2 === $k ) :
											?>
											hide<?php endif; ?>">
											<input 
												name="wt_coupon_styles[<?php echo esc_attr( $type_key ); ?>][color][]" 
												value="<?php echo esc_attr( $color ); ?>" 
												class="wt_sc_color_picker wt_sc_coupon_color" 
												data-style_type="<?php echo esc_attr( $style_name ); ?>" 
												data-coupon_type="<?php echo esc_attr( $type_key ); ?>" 
												data-index="<?php echo esc_attr( $k ); ?>" 
											/>
										</div>
										<?php
									}
								}
								?>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
		Wt_Smart_Coupon_Admin::add_settings_footer();
		?>
	</form>
</div>