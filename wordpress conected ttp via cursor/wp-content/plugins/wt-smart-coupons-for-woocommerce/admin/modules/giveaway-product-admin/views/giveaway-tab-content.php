<?php
/**
 * Giveaway tab content.
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="wt_give_away_free_products" class="panel woocommerce_options_panel">
	<?php
	/**
	 *  Normal coupon type giveaway tab content
	 */
	require_once plugin_dir_path( __FILE__ ) . '-normal-coupon-giveaway-tab-content.php';

	/**
	 *  Bogo coupon type giveaway tab content
	 */
	require_once plugin_dir_path( __FILE__ ) . '-bogo-coupon-giveaway-tab-content.php';
	?>
</div>