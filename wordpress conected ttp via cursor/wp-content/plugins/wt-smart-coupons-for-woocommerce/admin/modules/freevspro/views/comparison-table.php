<?php
/**
 * Free vs Pro comparison table.
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$no_icon  = '<span class="dashicons dashicons-dismiss" style="color:#ea1515;"></span>&nbsp;';
$yes_icon = '<span class="dashicons dashicons-yes-alt" style="color:#18c01d;"></span>&nbsp;';

global $wp_version;
if ( version_compare( $wp_version, '5.2.0' ) < 0 ) {
	$yes_icon = '<img src="' . plugin_dir_url( __DIR__ ) . 'assets/images/tick_icon_green.png" style="float:left;" />&nbsp;';
}

$comparison_data = array(
	array(
		__( 'Coupon Management Features', 'thetoppercentile-coupons' ),
		array(
			__( 'BOGO Coupons', 'thetoppercentile-coupons' ),
			__( 'Giveaway', 'thetoppercentile-coupons' ),
			__( 'URL coupons', 'thetoppercentile-coupons' ),
		),
		array(
			__( 'BOGO Coupons', 'thetoppercentile-coupons' ),
			__( 'Giveaway', 'thetoppercentile-coupons' ),
			__( 'URL coupons', 'thetoppercentile-coupons' ),
			__( 'Purchase history-based coupons', 'thetoppercentile-coupons' ),
			__( 'Store credit', 'thetoppercentile-coupons' ),
			__( 'Gift coupons', 'thetoppercentile-coupons' ),
			__( 'Sign-up coupons', 'thetoppercentile-coupons' ),
			__( 'Cart abandonment coupons', 'thetoppercentile-coupons' ),
			__( 'Combo coupons', 'thetoppercentile-coupons' ),
		),
	),
	array(
		array(
			__( 'BOGO Coupon options', 'thetoppercentile-coupons' ),
			__( 'The customers can buy one product and get:', 'thetoppercentile-coupons' ),
		),
		array(
			__( 'Specific product', 'thetoppercentile-coupons' ),
		),
		array(
			__( 'Specific product', 'thetoppercentile-coupons' ),
			__( 'Any product from a specific category', 'thetoppercentile-coupons' ),
			__( 'Any product in store', 'thetoppercentile-coupons' ),
			__( 'Same product as in the cart', 'thetoppercentile-coupons' ),
		),
	),
	array(
		__( 'Applicable coupon restrictions', 'thetoppercentile-coupons' ),
		array(
			__( 'Shipping method', 'thetoppercentile-coupons' ),
			__( 'Payment method', 'thetoppercentile-coupons' ),
			__( 'User roles', 'thetoppercentile-coupons' ),
			__( 'Product quantity', 'thetoppercentile-coupons' ),
			__( 'Country', 'thetoppercentile-coupons' ),
		),
		array(
			__( 'Shipping method', 'thetoppercentile-coupons' ),
			__( 'Payment method', 'thetoppercentile-coupons' ),
			__( 'User roles', 'thetoppercentile-coupons' ),
			__( 'Exclude user roles', 'thetoppercentile-coupons' ),
			__( 'Product quantity', 'thetoppercentile-coupons' ),
			__( 'Country', 'thetoppercentile-coupons' ),
			__( 'State', 'thetoppercentile-coupons' ),
			__( 'Pincode restriction', 'thetoppercentile-coupons' ),
		),
	),
	array(
		__( 'Coupon Automation', 'thetoppercentile-coupons' ),
		array(
			__( 'Apply coupon automatically', 'thetoppercentile-coupons' ),
			__( 'Set a coupon start date', 'thetoppercentile-coupons' ),
		),
		array(
			__( 'Apply coupon automatically', 'thetoppercentile-coupons' ),
			__( 'Set a coupon start date', 'thetoppercentile-coupons' ),
			__( 'Duplicate coupons', 'thetoppercentile-coupons' ),
			__( 'Supports custom coupon code format (prefix, suffix, length)', 'thetoppercentile-coupons' ),
		),
	),
	array(
		__( 'Advanced Coupon Display and Customization', 'thetoppercentile-coupons' ),
		array(
			__( 'Coupon styling', 'thetoppercentile-coupons' ),
			array(
				__( 'Coupon templates', 'thetoppercentile-coupons' ),
				__( 'Standard', 'thetoppercentile-coupons' ),
			),
		),
		array(
			__( 'Coupon styling', 'thetoppercentile-coupons' ),
			array(
				__( 'Coupon templates', 'thetoppercentile-coupons' ),
				__( 'Multiple options', 'thetoppercentile-coupons' ),
			),
			__( 'Display count down discount sales banner', 'thetoppercentile-coupons' ),
			__( 'Custom endpoints and endpoint title for coupon listing page', 'thetoppercentile-coupons' ),
		),
	),
);

?>

<table style="width:100%; background: linear-gradient(to right, #fff, #F1FFF4); padding:37px 46px; border: 1px solid #6ABE45; border-radius: 10px 10px 0px 0px;">
	<tr>
		<td>
			<img src="<?php echo esc_url( WT_SMARTCOUPON_MAIN_URL . 'admin/modules/other-solutions/assets/images/smart-coupons-plugin.png' ); ?>" style="float:left; width:51px;">
		</td>
		<td style="padding-left: 20px;">
			<p style="font-size:23px; font-weight:700;">⚡<?php esc_html_e( 'Supercharge your sales with', 'thetoppercentile-coupons' ); ?> ✨ <?php esc_html_e( 'TheTopPercentile Coupons Pro!', 'thetoppercentile-coupons' ); ?></p>
			<p><?php esc_html_e( 'Create offers on your store your customers can’t resist from irresistible BOGO deals to delightful giveaways.', 'thetoppercentile-coupons' ); ?></p>
			<span style="color:#6ABE45;" class="dashicons dashicons-saved"></span><span style="color:#616161; font-size:14px;"><?php esc_html_e( '99% Customer Satisfaction', 'thetoppercentile-coupons' ); ?></span>&ensp;&ensp;<span style="color:#6ABE45;" class="dashicons dashicons-saved"></span><span style="color:#616161; font-size:14px;"><?php esc_html_e( '30 Day money back guarantee', 'thetoppercentile-coupons' ); ?></span>
		</td>
		<td>
			<a style="background:#4750CB; font-size:16px; font-weight:500; border-radius:11px; line-height:48px; width:203px; color:#fff; border:none; text-align: center;" class="button button-secondary" href="<?php echo esc_attr( 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=free_plugin_comparison&utm_medium=smart_coupons_basic&utm_campaign=smart_coupons&utm_content=' . WEBTOFFEE_SMARTCOUPON_VERSION ); ?>" target="_blank"><?php esc_html_e( 'Unlock pro features', 'thetoppercentile-coupons' ); ?> <span class="dashicons dashicons-arrow-right-alt" style="line-height:48px;font-size:14px;"></span> </a>
		</td>
	</tr>
</table>
<table class="wt_smcpn_freevs_pro">
	<tr>
		<td style="width:400px;"><?php esc_html_e( 'FEATURES', 'thetoppercentile-coupons' ); ?></td>
		<td><?php esc_html_e( 'FREE', 'thetoppercentile-coupons' ); ?></td>
		<td><?php esc_html_e( 'PREMIUM', 'thetoppercentile-coupons' ); ?>&nbsp;<span><img src="<?php echo esc_url( WT_SMARTCOUPON_MAIN_URL . 'images/crown.svg' ); ?>" style="width:16px;"></span></td>
	</tr>
	<?php
	foreach ( $comparison_data as $index_i => $val_arr ) {
		?>
		<tr class="wt_sc_freevspro_table_hd_tr" data-index="<?php echo esc_attr( $index_i ); ?>" data-state='visible'>
			<td colspan="3"><span class="wt_sc_freevspro_table_hd_tr_dashicon<?php echo esc_attr( $index_i ); ?> dashicons dashicons-arrow-up-alt2"></span>&ensp;
				<?php
				if ( ! is_array( $val_arr[0] ) ) {
					echo esc_html( $val_arr[0] );
				} else {
					echo esc_html( $val_arr[0][0] );
					echo wp_kses_post( '<br><p style="font-size:15px; font-weight: 400; margin: 0px 0px 0px 30px;">' . $val_arr[0][1] . '</p>' );
				}
				?>
			</td>
		</tr>
		
		<?php
		foreach ( $val_arr[2] as $index_j => $val ) {
			?>
					<tr class = "wt_sc_freevspro_table_body_tr wt_sc_freevspro_table_details_body<?php echo esc_attr( $index_i ); ?>" data-index="<?php echo esc_attr( $index_i ); ?>">
						<td>
						<?php
							echo wp_kses_post( ! is_array( $val ) ? $val : $val[0] );
						?>
						</td>
						<td>
						<?php
						if ( ! is_array( $val ) ) {
							if ( in_array( $val, $val_arr[1], true ) ) {
								echo wp_kses_post( $yes_icon );
							} else {
								echo wp_kses_post( $no_icon );
							}
						} else {
							echo wp_kses_post( $comparison_data[ $index_i ][1][ $index_j ][1] );
						}

						?>
						</td>
						<td>
							<?php
							if ( ! is_array( $val ) ) {
								echo wp_kses_post( $yes_icon );
							} else {
								echo wp_kses_post( $val[1] );
							}
							?>
						</td>
					</tr>
				<?php
		}
		?>
		<?php
	}
	?>
</table>