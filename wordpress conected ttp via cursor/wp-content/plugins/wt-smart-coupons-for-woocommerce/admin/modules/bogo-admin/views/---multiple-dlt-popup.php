<?php
/**
 * Selected BOGO delete popup
 *
 * @since 2.0.0
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
$wbte_ds_obj = Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );

$wbte_values = isset( $this->variables ) && is_array( $this->variables ) ? $this->variables : array();
?>
<p><?php echo esc_html( $wbte_values['popup_content'] ); ?></p>
<div data-class="popup-footer" style="text-align: right;">
	<?php
	echo $wbte_ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'button text medium',
		array(
			'values' => array(
				'button_title' => esc_html__( 'Cancel', 'thetoppercentile-coupons' ),
			),
			'class'  => array( 'wbte_sc_delete_bogo_cancel' ),
		)
	);
	echo '&ensp;';
	echo $wbte_ds_obj->get_component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'button danger medium',
		array(
			'values' => array(
				'button_title' => esc_html__( 'Delete permanently', 'thetoppercentile-coupons' ),
			),
			'class'  => array( 'wbte_sc_delete_perm_bogo_multiple' ),
		)
	);
	?>
</div>
<br />