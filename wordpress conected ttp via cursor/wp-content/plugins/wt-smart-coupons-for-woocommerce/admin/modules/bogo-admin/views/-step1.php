<?php
/**
 * BOGO edit page step 1
 *
 * @since 2.0.0
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$admin_img_path = WT_SMARTCOUPON_MAIN_URL . 'admin/images/';
$ds_obj         = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );
if ( function_exists( 'wp_nonce_field' ) ) {
	wp_nonce_field( WT_SC_PLUGIN_NAME, WT_SC_PLUGIN_NAME . '-bogo' );
}
?>

<div class="wbte_sc_bogo_edit_step_content">
	<div class="wbte_sc_bogo_step_opened">
		<table class="wbte_sc_bogo_edit_table">
			<tbody>
				<tr>
					<th colspan="2">
						<div class="wbte_sc_bogo_edit_custom_drop_down_head">
							<div class="wbte_sc_bogo_customer_gets_select_btn wbte_sc_bogo_edit_custom_drop_down_btn ">
								<p><?php esc_html_e( 'Specific product(s)', 'thetoppercentile-coupons' ); ?></p>
								<span class="dashicons dashicons-arrow-down-alt2"></span>
							</div>
							<div class="wbte_sc_bogo_customer_gets_select_option wbte_sc_bogo_edit_custom_drop_down">
								<?php
								echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									'radio-group multi-line',
									array(
										'values' => array(
											'name'  => 'wbte_sc_bogo_customer_gets',
											'items' => array(
												array(
													'label' => esc_html__( 'Specific product(s)', 'thetoppercentile-coupons' ),
													'value' => 'specific_product',
													'is_checked' => true,
												),
												array(
													'label' => esc_html__( 'Same product', 'thetoppercentile-coupons' ) . wp_kses_post( '<img class="wbte_sc_bogo_prem_crown_disabled" src="' . esc_url( $admin_img_path . 'prem_crown_2.svg' ) . '" alt="' . esc_attr__( 'premium', 'thetoppercentile-coupons' ) . '" />' ),
													'value' => 'same_product_in_the_cart',
													'is_checked' => false,
													'is_disabled' => true,
												),
												array(
													'label' => esc_html__( 'Product from specific category', 'thetoppercentile-coupons' ) . wp_kses_post( '<img class="wbte_sc_bogo_prem_crown_disabled" src="' . esc_url( $admin_img_path . 'prem_crown_2.svg' ) . '" alt="' . esc_attr__( 'premium', 'thetoppercentile-coupons' ) . '" />' ),
													'value' => 'any_product_from_category',
													'is_checked' => false,
													'is_disabled' => true,
												),
												array(
													'label' => esc_html__( 'Any product in store', 'thetoppercentile-coupons' ) . wp_kses_post( '<img class="wbte_sc_bogo_prem_crown_disabled" src="' . esc_url( $admin_img_path . 'prem_crown_2.svg' ) . '" alt="' . esc_attr__( 'premium', 'thetoppercentile-coupons' ) . '" />' ),
													'value' => 'any_product_from_store',
													'is_checked' => false,
													'is_disabled' => true,
												),
											),
										),
										'class'  => array( 'wbte_sc_bogo_customer_gets_dropdown' ),
									)
								);
								?>
							</div>
						</div>
					</th>
				</tr>
				<tr class="wbte_sc_bogo_customer_gets_specific_prod_row">
					<td colspan="2">
						<div class="wbte_sc_bogo_edit_products_tab">
							<p><?php esc_html_e( 'Specific product(s)', 'thetoppercentile-coupons' ); ?></p>
							<select id="wbte_sc_bogo_free_product_ids" class="wc-product-search" multiple="multiple" style="width: 95%;" name="wbte_sc_bogo_free_product_ids[]" data-placeholder="<?php esc_attr_e( 'Search for product', 'thetoppercentile-coupons' ); ?>" data-action="woocommerce_json_search_products_and_variations">
							<?php
								$product_ids = explode( ',', self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_free_product_ids' ) );

							foreach ( $product_ids as $product_id ) {
								$product = wc_get_product( $product_id );
								if ( is_object( $product ) ) {
									echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>';
								}
							}
							?>
							</select>
						</div>
					</td>
				</tr>
				<tr valign="top" class="wbte_sc_bogo_customer_gets_product_condition_row">
					<th>
						<p><?php esc_html_e( 'Customer gets ', 'thetoppercentile-coupons' ); ?></p>
					</th>
					<td class="wbte_sc_bogo_edit_radio_fields" >
						<?php
						echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'radio-group multi-line',
							array(
								'values' => array(
									'name'  => 'wbte_sc_bogo_gets_product_condition',
									'items' => array(
										array(
											'label'      => esc_html__( 'Any of the above', 'thetoppercentile-coupons' ),
											'value'      => 'any',
											'is_checked' => esc_attr( 'any' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' ) ),
										),
										array(
											'label'      => esc_html__( 'All of the above', 'thetoppercentile-coupons' ),
											'value'      => 'all',
											'is_checked' => esc_attr( 'all' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' ) ),
										),
									),
								),
							)
						);
						?>
					</td>
				</tr>
				<tr class="wbte_sc_bogo_customer_gets_qty_row">
					<th>
						<label for="wbte_sc_bogo_customer_gets_qty"><?php esc_html_e( 'In quantity of', 'thetoppercentile-coupons' ); ?></label>
					</th>
					<td>
						<input type="text" id="wbte_sc_bogo_customer_gets_qty" name="wbte_sc_bogo_customer_gets_qty" class="wbte_sc_admin_number_input wbte_sc_admin_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_qty' ) ); ?>">
					</td>
				</tr>
				<tr valign="top" class="wbte_sc_bogo_customer_gets_discount_type_row">
					<th>
						<p><?php esc_html_e( 'Discount type ', 'thetoppercentile-coupons' ); ?></p>
					</th>
					<td class="wbte_sc_bogo_edit_radio_fields" >
						<?php
						$discount_type_selected = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_type' );
						echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'radio-group multi-line',
							array(
								'values' => array(
									'name'  => 'wbte_sc_bogo_customer_gets_discount_type',
									'items' => array(
										array(
											'label'      => esc_html__( 'Free', 'thetoppercentile-coupons' ),
											'value'      => 'wbte_sc_bogo_customer_gets_free',
											'is_checked' => esc_attr( 'wbte_sc_bogo_customer_gets_free' === $discount_type_selected ),
										),
										array(
											'label'      => esc_html__( 'Percentage', 'thetoppercentile-coupons' ),
											'value'      => 'wbte_sc_bogo_customer_gets_with_perc_discount',
											'is_checked' => esc_attr( 'wbte_sc_bogo_customer_gets_with_perc_discount' === $discount_type_selected ),
										),
										array(
											'label'      => esc_html__( 'Fixed', 'thetoppercentile-coupons' ),
											'value'      => 'wbte_sc_bogo_customer_gets_with_fixed_discount',
											'is_checked' => esc_attr( 'wbte_sc_bogo_customer_gets_with_fixed_discount' === $discount_type_selected ),
										),
									),
								),
							)
						);
						?>
					</td>
				</tr>
				<tr class="wbte_sc_bogo_customer_gets_discount_type_perc_row wbte_sc_bogo_customer_gets_discount_type_row wbte_sc_bogo_conditional_hidden">
					<th>
						<label for="wbte_sc_bogo_customer_gets_discount_perc">
							<?php
								esc_html_e( 'Discount ', 'thetoppercentile-coupons' );
								echo wp_kses_post( '<span style="color:#9DA3AA;" >(%)</span>' );
							?>
						</label>
					</th>
					<td>
						<div class="wbte_sc_bogo_icon_input">
							<input type="text" id="wbte_sc_bogo_customer_gets_discount_perc" class="wbte_sc_admin_input_only_numbers_with_decimal" name="wbte_sc_bogo_customer_gets_discount_perc" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_perc' ) ); ?>">
							<div class="wbte_sc_bogo_icon_input_symbol">%</div>
						</div>
					</td>
				</tr>
				<tr class="wbte_sc_bogo_customer_gets_discount_type_fixed_row wbte_sc_bogo_customer_gets_discount_type_row wbte_sc_bogo_conditional_hidden">
					<th>
						<label for="wbte_sc_bogo_customer_gets_discount_price">
							<?php
							esc_html_e( 'Discount ', 'thetoppercentile-coupons' );
							echo wp_kses_post( '<span style="color:#9DA3AA;" >(' . esc_html( get_woocommerce_currency_symbol() ) . ')</span>' );
							?>
						</label>
					</th>
					<td>
						<div class="wbte_sc_bogo_icon_input">
							<input type="text" id="wbte_sc_bogo_customer_gets_discount_price" name="wbte_sc_bogo_customer_gets_discount_price" class="wbte_sc_admin_input_only_numbers_with_decimal" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_customer_gets_discount_price' ) ); ?>">
							<div class="wbte_sc_bogo_icon_input_symbol">
								<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
							</div>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th>
						<label for="free_shipping"><?php esc_html_e( 'Include free shipping', 'thetoppercentile-coupons' ); ?></label>
					</th>
					<td>
						<?php
						echo $ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'checkbox normal',
							array(
								'values' => array(
									'name'       => 'free_shipping',
									'id'         => 'free_shipping',
									'value'      => 'yes',
									'is_checked' => esc_attr( 'yes' === self::get_coupon_meta_value( $coupon_id, 'free_shipping' ) ),
									'label'      => esc_html__( 'Combine free shipping with the offer', 'thetoppercentile-coupons' ),
								),
							)
						);

						$shipping_zones_url = admin_url( 'admin.php?page=wc-settings&tab=shipping' );

						$free_shipping_enabled = false;

						$shipping_zones   = WC_Shipping_Zones::get_zones();
						$shipping_zones[] = WC_Shipping_Zones::get_zone( 0 );
						foreach ( $shipping_zones as $zone ) {
							$zone_object      = is_array( $zone ) ? new WC_Shipping_Zone( $zone['id'] ) : $zone;
							$shipping_methods = $zone_object->get_shipping_methods();

							foreach ( $shipping_methods as $method ) {
								if ( 'free_shipping' === $method->id && 'yes' === $method->enabled ) {
									$free_shipping_enabled = true;
									break;
								}
							}
						}
						?>
						<div class="wbte_sc_bogo_free_shipping_warning" data-free-shipp-enabled = "<?php echo esc_attr( $free_shipping_enabled ); ?>">
							<img src="<?php echo esc_url( $admin_img_path ); ?>exclamation_red_filled.svg" alt="<?php esc_attr_e( 'Caution', 'thetoppercentile-coupons' ); ?>">
							<p>
								<?php
								// translators: 1: Link to shipping zones, 2: Closing link tag.
								printf( esc_html__( 'Enable free shipping in WooCommerce %1$s shipping zones %2$s to use this option!', 'thetoppercentile-coupons' ), '<a href="' . esc_url( $shipping_zones_url ) . '" target="_blank">', '</a>' );
								?>
							</p>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="wbte_sc_bogo_step_short_description wbte_sc_bogo_step1_short_description">
		<p></p>
	</div>
</div>