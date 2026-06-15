<?php
/**
 * Debug tab HTML
 *
 * @since 1.4.5
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

?>
<div class="wt-sc-tab-content" data-id="<?php echo esc_attr( $target_id ); ?>">
	<h3><?php esc_html_e( 'Debug', 'thetoppercentile-coupons' ); ?></h3>
	<p><?php esc_html_e( 'Caution: Settings here are only for advanced users.', 'thetoppercentile-coupons' ); ?></p>
	<form method="post" style="border-bottom:dashed 1px #ccc;">
		<?php
		// Set nonce:.
		if ( function_exists( 'wp_nonce_field' ) ) {
			wp_nonce_field( WT_SC_PLUGIN_NAME );
		}
		?>
		<table class="wt-sc-form-table">
			<?php
			$wt_sc_public_modules = get_option( 'wt_sc_public_modules' );

			if ( false === $wt_sc_public_modules ) {
				$wt_sc_public_modules = array();
			}
			?>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Public modules', 'thetoppercentile-coupons' ); ?></th>
				<td>
					<?php
					foreach ( $wt_sc_public_modules as $k => $v ) {

						echo '<input type="checkbox" id="wt_sc_public_modules[' . esc_attr( $k ) . ']" name="wt_sc_public_modules[' . esc_attr( $k ) . ']" value="1" ' . ( 1 === $v ? 'checked' : '' ) . ' /> ';
						echo '<label for="wt_sc_public_modules[' . esc_attr( $k ) . ']">' . esc_html( $k ) . '</label>';
						echo '<br />';
					}
					?>
				</td>
			</tr>
			<?php
			$wt_sc_common_modules = get_option( 'wt_sc_common_modules' );

			if ( false === $wt_sc_common_modules ) {
				$wt_sc_common_modules = array();
			}
			?>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Common modules', 'thetoppercentile-coupons' ); ?></th>
				<td>
					<?php
					foreach ( $wt_sc_common_modules as $k => $v ) {

						echo '<input type="checkbox" id="wt_sc_common_modules[' . esc_attr( $k ) . ']" name="wt_sc_common_modules[' . esc_attr( $k ) . ']" value="1" ' . ( 1 === $v ? 'checked' : '' ) . ' /> ';
						echo '<label for="wt_sc_common_modules[' . esc_attr( $k ) . ']">' . esc_html( $k ) . '</label>';
						echo '<br />';
					}
					?>
				</td>
			</tr>
			<?php
			$wt_sc_admin_modules = get_option( 'wt_sc_admin_modules' );

			if ( false === $wt_sc_admin_modules ) {
				$wt_sc_admin_modules = array();
			}

			?>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Admin modules', 'thetoppercentile-coupons' ); ?></th>
				<td>
					<?php
					foreach ( $wt_sc_admin_modules as $k => $v ) {

						echo '<input type="checkbox" id="wt_sc_admin_modules[' . esc_attr( $k ) . ']" name="wt_sc_admin_modules[' . esc_attr( $k ) . ']" value="1" ' . ( 1 === $v ? 'checked' : '' ) . ' /> ';
						echo '<label for="wt_sc_admin_modules[' . esc_attr( $k ) . ']">' . esc_html( $k ) . '</label>';
						echo '<br />';
					}
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">&nbsp;</th>
				<td>
					<input type="submit" name="wt_sc_admin_modules_btn" value="Save" class="button-primary">
				</td>
			</tr>
		</table>
	</form>
<?php

/**
 * Action to add content to the debug tab.
 *
 * @since 1.4.5
 */
do_action( 'wt_sc_module_settings_debug' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
?>
</div>