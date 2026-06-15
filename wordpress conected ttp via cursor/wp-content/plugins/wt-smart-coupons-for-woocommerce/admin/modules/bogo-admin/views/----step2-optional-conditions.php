<?php
/**
 * Optional conditions for BOGO in step 2
 *
 * @since 2.1.0 Moved from step2.php to this file
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<table class="wbte_sc_bogo_edit_table wbte_sc_bogo_additional_fields_table">
	<thead>
		<tr>
			<th colspan="2">
				<div class="wbte_sc_bogo_edit_custom_drop_down_head">
					<p><?php esc_html_e( 'Optional conditions', 'thetoppercentile-coupons' ); ?>
						<span class="wbte_sc_bogo_edit_add_button wbte_sc_bogo_edit_addition_conditions"><?php esc_html_e( '+ Add', 'thetoppercentile-coupons' ); ?></span>
					</p>
					<div class="wbte_sc_bogo_edit_additional_condition_select wbte_sc_bogo_edit_custom_drop_down">
						<p class="wbte_sc_bogo_edit_custom_select_head"><?php esc_html_e( 'Additional', 'thetoppercentile-coupons' ); ?></p>
						<p data-row="wbte_sc_bogo_qty_row" data-group="wbte_sc_qty"><?php esc_html_e( 'Total quantity', 'thetoppercentile-coupons' ); ?></p>
						<p data-row="wbte_sc_bogo_each_qty_row" data-group="wbte_sc_qty" class="wbte_sc_bogo_each_qty_row_to_hide"><?php esc_html_e( 'Quantity of each product', 'thetoppercentile-coupons' ); ?></p>
						<p data-row="wbte_sc_bogo_per_coupon_row"><?php esc_html_e( 'Usage limit per offer', 'thetoppercentile-coupons' ); ?></p>
						<p data-row="wbte_sc_bogo_per_user_row"><?php esc_html_e( 'Usage limit per user', 'thetoppercentile-coupons' ); ?></p>
						<p data-row="wbte_sc_bogo_email_row"><?php esc_html_e( 'Allowed emails', 'thetoppercentile-coupons' ); ?></p>
					</div>
				</div>
			</th>
		</tr>
	</thead>
	<tbody class="wbte_sc_bogo_additional_fields_contents">
		<tr class="<?php echo ( 0 >= self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty_add' ) && empty( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty_add' ) ) ) ? ' wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_qty_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields wbte_sc_bogo_qty_field">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p><?php esc_html_e( 'Minimum quantity', 'thetoppercentile-coupons' ); ?></p>
						<input type="text" name="_wbte_sc_bogo_min_qty_add" id="_wbte_sc_bogo_min_qty_add" class="wbte_sc_admin_number_input wbte_sc_admin_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_min_qty_add' ) ); ?>">
					</div>
					<br>
					<div class="wbte_sc_bogo_additional_flex">
						<p><?php esc_html_e( 'Maximum quantity', 'thetoppercentile-coupons' ); ?></p>
						<input type="text" name="_wbte_sc_bogo_max_qty_add" id="_wbte_sc_bogo_max_qty_add" placeholder="<?php esc_attr_e( 'Optional', 'thetoppercentile-coupons' ); ?>" class="wbte_sc_admin_number_input wbte_sc_admin_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_bogo_max_qty_add' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_min_qty_each' ) ) && empty( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_max_qty_each' ) ) ? ' wbte_sc_bogo_conditional_hidden ' : ' '; ?> wbte_sc_bogo_each_qty_row_to_hide" data-row="wbte_sc_bogo_each_qty_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields wbte_sc_bogo_qty_field">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p><?php esc_html_e( 'Min quantity of each item', 'thetoppercentile-coupons' ); ?></p>
						<input type="text" name="_wbte_sc_min_qty_each" id="_wbte_sc_min_qty_each" class="wbte_sc_admin_number_input wbte_sc_admin_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_min_qty_each' ) ); ?>">
					</div>
					<br>
					<div class="wbte_sc_bogo_additional_flex">
						<p><?php esc_html_e( 'Max quantity of each item', 'thetoppercentile-coupons' ); ?></p>
						<input type="text" name="_wbte_sc_max_qty_each" id="_wbte_sc_max_qty_each" placeholder=<?php esc_attr_e( 'Optional', 'thetoppercentile-coupons' ); ?> class="wbte_sc_admin_number_input wbte_sc_admin_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, '_wbte_sc_max_qty_each' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( self::get_coupon_meta_value( $coupon_id, 'usage_limit' ) ) ? ' wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_per_coupon_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p>
							<?php
							esc_html_e( 'Usage limit per offer', 'thetoppercentile-coupons' );
							echo ' ';
							echo wp_kses_post( wc_help_tip( __( 'The total number of times this offer can be used in the store, including multiple redemptions by the same user', 'thetoppercentile-coupons' ) ) );
							?>
						</p>
						<input type="text" name="usage_limit" id="usage_limit" class="wbte_sc_admin_number_input wbte_sc_admin_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'usage_limit' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( self::get_coupon_meta_value( $coupon_id, 'usage_limit_per_user' ) ) ? ' wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_per_user_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<div class="wbte_sc_bogo_additional_flex">
						<p>
							<?php
							esc_html_e( 'Usage limit per user', 'thetoppercentile-coupons' );
							echo ' ';
							echo wp_kses_post( wc_help_tip( __( 'The maximum number of times a single user can redeem this offer. It must be less than the overall usage limit per offer', 'thetoppercentile-coupons' ) ) );
							?>
						</p>
						<input type="text" name="usage_limit_per_user" id="usage_limit_per_user" class="wbte_sc_admin_number_input wbte_sc_admin_input_only_number" value="<?php echo esc_attr( self::get_coupon_meta_value( $coupon_id, 'usage_limit_per_user' ) ); ?>">
					</div>
				</div>
			</td>
		</tr>
		<tr class="<?php echo empty( $coupon->get_email_restrictions( 'edit' ) ) ? ' wbte_sc_bogo_conditional_hidden ' : ' '; ?>" data-row="wbte_sc_bogo_email_row">
			<td colspan="2">
				<div class="wbte_sc_bogo_additional_fields wbte_sc_bogo_email_flex">
					<?php echo wp_kses_post( $trash_icon ); ?>
					<label for="customer_email"><?php esc_html_e( 'Allowed emails', 'thetoppercentile-coupons' ); ?></label>
					<?php echo wp_kses_post( wc_help_tip( __( 'The BOGO deal is only valid for recipients of the selected emails.', 'thetoppercentile-coupons' ) ) ); ?>
					<div>
						<select style="width: 333px; height: 55px;" name="wbte_sc_bogo_emails[]" multiple="multiple" class="wbte_sc_bogo_email_search" data-placeholder="<?php echo esc_attr( 'mail@example.com' ); ?>">
						<?php
							$wbte_emails = $coupon->get_email_restrictions( 'edit' );
						foreach ( $wbte_emails as $wbte_email ) {
							echo '<option value="' . esc_attr( $wbte_email ) . '" selected="selected">' . esc_html( $wbte_email ) . '</option>';
						}
						?>
						</select>
						<p class="wbte_sc_bogo_email_field_caption"><?php echo wp_kses_post( __( 'Offer won’t be auto-applied for guest users when email restriction is enabled.', 'thetoppercentile-coupons' ) ); ?></p>
					</div>
				</div>
			</td>
		</tr>
	</tbody>
</table>

