<?php
/**
 * BOGO edit page content
 *
 * @since   2.0.0
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

require_once plugin_dir_path( __FILE__ ) . '---wbte-header.php';

$trash_icon = '<span style="height: 24px;"  class="wbte_sc_bogo_edit_trash">' . wp_kses_post( $wbte_ds_obj->render_html( array( 'html' => '{{wbte-ds-icon-trash}}' ) ) ) . '</span>';
?>

<form id="wbte_sc_bogo_coupon_save" method="POST">
	<input type="hidden" id="wt_sc_bogo_coupon_id" name="wt_sc_bogo_coupon_id" value="<?php echo esc_attr( $wbte_coupon_id ); ?>">
	<div class="wbte_sc_bogo_edit_main">
		<div class="wbte_sc_bogo_edit_content">
			<div class="wbte_sc_bogo_edit_head">
				<img class="wbte_sc_bogo_goback_btn" src="
				<?php
				echo esc_url(
					$wbte_ds_obj->get_asset(
						array(
							'name' => 'left-arrow-1',
							'type' => 'icon',
						)
					)
				);
				?>
				" onclick="window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=' . self::$bogo_page_name ) ); ?>'">
				<h3><?php esc_html_e( 'Buy product X, get product X/Y', 'thetoppercentile-coupons' ); ?></h3>
			</div>
			<?php

				$step_content = array(
					'step1' => array(
						'step_title'      => __( 'Step 1', 'thetoppercentile-coupons' ),
						'step_head_title' => __( 'Customer gets', 'thetoppercentile-coupons' ),
					),
					'step2' => array(
						'step_title'      => __( 'Step 2', 'thetoppercentile-coupons' ),
						'step_head_title' => __( 'Trigger', 'thetoppercentile-coupons' ),
					),
					'step3' => array(
						'step_title'      => __( 'Step 3', 'thetoppercentile-coupons' ),
						'step_head_title' => __( 'Apply offer', 'thetoppercentile-coupons' ),
					),
				);

				foreach ( $step_content as $step_key => $step_content_data ) {
					?>
					<div class="wbte_sc_bogo_edit_step <?php echo 'step1' === $step_key ? ' wbte_sc_bogo_step_container_opened' : ''; ?>">
						<div class="wbte_sc_bogo_edit_step_head">
							<p class="wbte_sc_bogo_edit_step_title"><?php echo esc_html( $step_content_data['step_title'] ); ?></p>
							<p><?php echo esc_html( $step_content_data['step_head_title'] ); ?></p>
							<span class="wbte_sc_bogo_step_arrow dashicons"></span>
						</div>
						<?php
						/**
						 * Action hook to render the content for each step.
						 *
						 * @since 2.2.0
						 *
						 * @param int $wbte_coupon_id The ID of the coupon.
						 */
						do_action( "wbte_sc_bogo_edit_{$step_key}_content", $wbte_coupon_id );
						?>
					</div>
					<?php
				}

				$selected_triggers_when = self::get_coupon_meta_value( $wbte_coupon_id, 'wbte_sc_bogo_triggers_when' );
				?>
		</div>
		<?php require_once plugin_dir_path( __FILE__ ) . '---edit-general.php'; ?>
	</div>