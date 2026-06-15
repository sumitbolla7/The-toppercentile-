<?php
/**
 * Develop hook page.
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Code moved from admin-help.php to -admin-develop.php
 *
 *  @since 1.8.3
 */
?>

<div class="wt-sc-tab-content" data-id="<?php echo esc_attr( $target_id ); ?>">

	<?php require '-hooks-list.php'; ?>

</div>