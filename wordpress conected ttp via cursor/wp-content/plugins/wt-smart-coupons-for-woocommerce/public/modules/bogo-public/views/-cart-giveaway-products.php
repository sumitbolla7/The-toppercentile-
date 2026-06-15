<?php
/**
 * BOGO free product choosing section
 *
 * @since 2.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>
<style>
	.wbte_get_away_product.selected { border-color: <?php echo esc_attr( $popup_theme_color ); ?> !important; background-color: <?php echo esc_attr( $popup_theme_color ); ?>10; }
	.wbte_get_away_product.selected .wbte_product_checkbox { border-color: <?php echo esc_attr( $popup_theme_color ); ?> !important; background-color: <?php echo esc_attr( $popup_theme_color ); ?> !important; }
</style>
<div class="wt_sc_giveaway_products_cart_page">
	<?php
	foreach ( $free_products as $coupon_code => $free_product_items ) {
		if ( empty( $free_product_items ) ) {
			continue;
		}

		$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

		$can_change_qty = $qty_alter_option[ $coupon_code ];
		$total_qty      = ( $can_change_qty || 'any' === self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' ) ) ? reset( $free_products_qty[ $coupon_code ] ) : array_sum( $free_products_qty[ $coupon_code ] );
		$total_qty      = (int) $total_qty;

		$message      = self::get_general_settings_value( 'wbte_sc_bogo_general_apply_choose_product_title' );
		$coupon_title = self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_coupon_name' );
		$qty_counter  = "(<span class='wbte_sc_bogo_products_selected_qty'>0</span>/$total_qty)";
		$message      = str_replace( array( '{bogo_title}', '{qty_counter}' ), array( $coupon_title, $qty_counter ), $message );
		$message_html = "<h4 class='giveaway-title' data-total-qty='$total_qty'>" . $message . ' </h4>';
		/**
		 * Filter to alter the giveaway message.
		 *
		 * @since 2.0.0
		 * @param string $message_html Message HTML.
		 * @param string $coupon_code Coupon code.
		 * @param int $coupon_id Coupon ID.
		 * @return string Message HTML.
		 */
		$message_html = apply_filters( 'wt_smartcoupon_give_away_message', $message_html, $coupon_code, $coupon_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		echo '<div class="wbte_sc_bogo_giveaway_products_container">';
			echo wp_kses_post( $message_html );
		?>
			<ul class="woocommcerce wbte_sc_bogo_products" coupon="<?php echo esc_attr( $coupon_id ); ?>" data-condition="<?php echo esc_attr( $can_change_qty ? 'multiple-any' : self::get_coupon_meta_value( $coupon_id, 'wbte_sc_bogo_gets_product_condition' ) ); ?>">
				<?php
				$total_purchasable = 0;
				foreach ( $free_product_items as $product_id ) {
					$_product = wc_get_product( $product_id );
					if ( ! $_product || ( $_product->get_stock_quantity() && $_product->get_stock_quantity() < 1 ) ) {
						continue;
					}

					/* product image */
					$image = wp_get_attachment_image_src( $_product->get_image_id(), 'woocommerce_thumbnail' );
					if ( $_product->is_type( 'variable' ) ) {
						$default_attributes           = $_product->get_default_attributes();
						$default_variation_id         = 0;
						$default_variation_attributes = array();
						$_variation_product           = $_product;
						if ( ! empty( $default_attributes ) ) {

							$default_variation_attributes = array_combine(
								array_map(
									function ( $key ) {
										return 'attribute_' . $key; },
									array_keys( $default_attributes )
								),
								$default_attributes
							);

							$default_variation_id = self::find_matching_product_variation_id( $product_id, $default_variation_attributes );
							if ( $default_variation_id ) {
								$_variation_product = wc_get_product( $default_variation_id );
							}
						}
						$image = wp_get_attachment_image_src( $_variation_product->get_image_id(), 'woocommerce_thumbnail' );
					}
					if ( ! $image ) {
						$parent_product = wc_get_product( $_product->get_parent_id() );
						if ( $parent_product ) {
							$image = wp_get_attachment_image_src( $parent_product->get_image_id(), 'woocommerce_thumbnail' );
						}
					}

					if ( ! $image ) {
						$dimensions = wc_get_image_size( 'woocommerce_thumbnail' );
						$image      = array( wc_placeholder_img_src( 'woocommerce_thumbnail' ), $dimensions['width'], $dimensions['height'], false );
					}
					$variation_attributes = array(); /* this applicable only for variable products */
					$is_purchasable       = self::is_purchasable( $_product, $variation_attributes );
					if ( $is_purchasable ) {
						++$total_purchasable;
					}
					$temp_product_id              = $product_id;
					$variation_without_attributes = false;
					if ( $_product->is_type( 'variation' ) ) {
						foreach ( $_product->get_variation_attributes() as $attribute_name => $options ) {
							if ( '' === $options ) {
								$temp_product_id              = $_product->get_parent_id();
								$variation_without_attributes = true;
								break;
							}
						}
					}
					?>
					<li class="wbte_get_away_product" title="<?php echo esc_attr( $_product->get_name() ); ?>" data-is_purchasable="<?php echo esc_attr( $is_purchasable ? 1 : 0 ); ?>" product-id="<?php echo esc_attr( $temp_product_id ); ?>" data-free-qty="<?php echo esc_attr( $free_products_qty[ $coupon_code ][ $product_id ] ); ?>" <?php echo ! $can_change_qty ? 'data-free-qty-on-select="' . esc_attr( $free_products_qty[ $coupon_code ][ $product_id ] ) . '"' : ''; ?>>
						<button type="button" class="wbte_product_checkbox"></button>
						<div class="wbte_product_image">
						<?php
						if ( $image && is_array( $image ) && isset( $image[0] ) ) {
							?>
							<img src="<?php echo esc_attr( $image[0] ); ?>" data-id="<?php echo esc_attr( $product_id ); ?>" alt="<?php echo esc_attr( $_product->get_name() ); ?>" />
							<?php
						} else {
							?>
							<div class="wt_sc_dummy_img"></div>
							<?php
						}
						?>
						</div>
						<div class="wbte_sc_free_prod_details">
							<div class="wbte_sc_prod_name_qty_price">
								<div class="wbte_sc_prod_name_qty_price_left">
									<div class="wbte_product_name">
										<?php echo esc_html( $_product->get_name() ); ?>
									</div>
									<?php
									if ( $is_purchasable ) {
										?>
										<div class="wbte_product_discount">
											<div>
												<?php
												$_discount = self::get_available_discount_for_giveaway_product( $coupon_id, $_product );
												$_price    = $_product->get_price();
												echo '<del><span class="wbte_sc_bogo_product_price">' . wp_kses_post( wc_price( $_price ) ) . '</span></del>&nbsp;<span class="wbte_sc_bogo_product_discount_price">' . wp_kses_post( wc_price( $_price - $_discount ) ) . '</span>';
												?>
											</div>
										</div> 
										<?php
									} else {
										?>
										<p class="wt_sc_product_out_of_stock stock out-of-stock"><?php echo wp_kses_post( self::get_customized_text( 'non_purchasable_giveaway_varaition' ) ); ?></p>
										<?php
									}
									?>
								</div>
								
								<div class="wbte_sc_bogo_quantity">
									<input type="number" name="wbte_sc_bogo_quantity" min="0" step="1" max="<?php echo esc_attr( $free_products_qty[ $coupon_code ][ $product_id ] ); ?>" value="<?php echo ! $can_change_qty ? esc_attr( $free_products_qty[ $coupon_code ][ $product_id ] ) : 0; ?>" <?php echo $can_change_qty ? '' : ' disabled'; ?>>
								</div>  
							</div>
							
							<?php
							if ( $_product->is_type( 'variable' ) ) {
								if ( $is_purchasable ) {
									?>
									<table class="variations wt_variations" cellspacing="0">
										<tbody>
										<?php
										foreach ( $_product->get_variation_attributes() as $attribute_name => $options ) {
											?>
											<tr>
												<td class="value">
													<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo esc_html( wc_attribute_label( $attribute_name ) ); ?></label>
													<?php
													wc_dropdown_variation_attribute_options(
														array(
															'options'           => $options,
															'attribute'         => $attribute_name,
															'product'           => $_product,
															'class'             => 'wbte_give_away_product_attr',
															'show_option_none'  => wc_attribute_label( $attribute_name ),
														)
													);
													?>
												</td>
											</tr>
											<?php
										}
										?>
										</tbody>
									</table>
									<input type="hidden" name="variation_id" value="<?php echo esc_attr( $default_variation_id ); ?>" />
									<input type="hidden" name="wt_product_id" value="<?php echo esc_attr( $product_id ); ?>" />
									<input type="hidden" name="wt_variation_options" value='<?php echo esc_attr( wp_json_encode( $default_variation_attributes ) ); ?>' />
									<?php
								}
							}
							if ( $variation_without_attributes && $is_purchasable ) {
								$variation_id = 0;
								?>
								<div class="wt_choose_button_box">
									<?php

									if ( $_product->is_type( 'variation' ) ) {

										$variation_attributes = isset( $product_data['attributes'] ) ? $product_data['attributes'] : array();
										?>
										<input type="hidden" name="variation_id" value="<?php echo esc_attr( $variation_id ); ?>">
										<input type="hidden" name="wt_product_id" value="<?php echo esc_attr( $product_id ); ?>" />
										<input type="hidden" name="wt_variation_options" value='<?php echo esc_attr( wp_json_encode( $variation_attributes ) ); ?>' />
										<?php
										$variation_id = $product_id;
										$product_id   = $_product->get_parent_id();
										if ( empty( $variation_attributes ) && $variation_without_attributes ) {
											$parent_product       = wc_get_product( $product_id );
											$variation_attributes = $_product->get_variation_attributes();
											foreach ( $variation_attributes as $attribute_name => $options ) {

												$variation_attributes[ $attribute_name ] = '' === $options
												? explode( ', ', $parent_product->get_attribute( str_replace( 'attribute_', '', $attribute_name ) ) )
												: array( $options );

											}
											?>
												<table class="variations wt_variations" cellspacing="0">
													<tbody>
													<?php
													foreach ( $variation_attributes as $attribute_name => $options ) {
														?>
														<tr>
															<td class="value">
																<label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo esc_html( wc_attribute_label( str_replace( 'attribute_', '', $attribute_name ) ) ); ?></label>
																<select id=<?php echo esc_attr( $attribute_name ); ?> class="wbte_give_away_product_attr" name=<?php echo esc_attr( $attribute_name ); ?> data-attribute_name=<?php echo esc_attr( $attribute_name ); ?> data-show_option_none="no">
																<option value=""><?php esc_html_e( 'Choose an option', 'thetoppercentile-coupons' ); ?></option>
																<?php
																foreach ( $options as $key => $value ) {
																	?>
																			<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></option>
																		<?php
																}

																?>
																</select>
															</td>
														</tr>
														<?php
													}
													?>
													</tbody>
												</table>
											<?php
										}
									}

									?>
								</div>
								<?php
							}
							?>
						</div>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<?php
	}
	?>
	<button class="button wbte_sc_bogo_add_to_cart" disabled><?php esc_html_e( 'Add to cart', 'thetoppercentile-coupons' ); ?></button>
</div>