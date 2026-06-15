<?php
/**
 * Template for displaying the my account coupons
 *
 * @package Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

$current_user_obj = wp_get_current_user();
$user_id          = $current_user_obj->ID;
$email            = $current_user_obj->user_email;
$printed_coupons  = array(
	'available_coupons' => array(),
	'used_coupons'      => array(),
	'expired_coupons'   => array(),
);

/**
 * Deprecated action hook to add additional content before the my account coupons
 *
 * @since 1.3.5
 */
do_action_deprecated( 'wt_smart_coupon_before_my_acocount_coupons', array(), '1.3.5', 'wt_smart_coupon_before_my_account_coupons' );

/**
 * Action hook to add additional content before the my account coupons
 *
 * @since 1.3.5
 */
do_action( 'wt_smart_coupon_before_my_account_coupons', $current_user_obj ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
Wt_Smart_Coupon_Public::print_coupon_default_css();
?>
<div class="wt-mycoupons">
	<h4><?php esc_html_e( 'Available Coupons', 'thetoppercentile-coupons' ); ?></h4>
	<div class="wt_sc_available_coupon_sort_by">
		<form>
			<span><?php esc_html_e( 'Sort by', 'thetoppercentile-coupons' ); ?> </span>
			<?php
			$sort_orderby = isset( $_GET['wt_sc_available_coupons_orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['wt_sc_available_coupons_orderby'] ) ) : Wt_Smart_Coupon_Public::get_available_coupons_sort_order(); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<select name="wt_sc_available_coupons_orderby" onchange="this.form.submit()">
				<option value="created_date:desc" <?php selected( 'created_date:desc', $sort_orderby ); ?>><?php esc_html_e( 'Latest first', 'thetoppercentile-coupons' ); ?></option>
				<option value="created_date:asc" <?php selected( 'created_date:asc', $sort_orderby ); ?>><?php esc_html_e( 'Latest last', 'thetoppercentile-coupons' ); ?></option>
				<option value="amount:desc" <?php selected( 'amount:desc', $sort_orderby ); ?>><?php esc_html_e( 'Price high to low', 'thetoppercentile-coupons' ); ?></option>
				<option value="amount:asc" <?php selected( 'amount:asc', $sort_orderby ); ?>><?php esc_html_e( 'Price low to high', 'thetoppercentile-coupons' ); ?></option>
			</select>
		</form>
	</div>
	<?php
	/**
	 * Filter hook to alter the limit of available coupons per page
	 *
	 * @since 1.3.5
	 */
	$limit = apply_filters( 'wt_sc_my_account_available_coupons_per_page', 20 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

	$offset                               = ( isset( $_GET['wt_sc_available_coupons_offset'] ) ? absint( wp_unslash( $_GET['wt_sc_available_coupons_offset'] ) ) : 0 ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$printed_available_coupons            = Wt_Smart_Coupon_Public::print_user_available_coupon( false, 'my_account', $offset, $limit );
	$printed_coupons['available_coupons'] = $printed_available_coupons;
	?>
</div>

<?php
/**
 * Action hook to add additional content after the my coupons
 *
 * @since 1.3.5
 */
do_action( 'wt_smart_coupon_after_my_coupons', $current_user_obj, $printed_available_coupons ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

$smart_coupon_options = Wt_Smart_Coupon::get_settings();

if ( isset( $smart_coupon_options['display_used_coupons_my_account'] ) && $smart_coupon_options['display_used_coupons_my_account'] ) {
	?>
	<div class="wt-used-coupons">
		<h4><?php esc_html_e( 'Used Coupons', 'thetoppercentile-coupons' ); ?></h4>
		<?php
		/**
		 *  Display used coupons by the current user
		 */
		$used_coupons = Wt_Smart_Coupon_Public::get_coupon_used_by_a_customer( $current_user_obj );
		if ( ! empty( $used_coupons ) ) {
			$i = 0;

			foreach ( $used_coupons as $coupon ) {
				$coupon_obj = new WC_Coupon( $coupon );

				if ( 0 === $coupon_obj->get_id() ) {
					continue;
				}


				$coupon_post = get_post( $coupon_obj->get_id() ); /* post object */

				if ( ! is_a( $coupon_post, 'WP_Post' ) || ( isset( $coupon_post->post_status ) && 'publish' !== $coupon_post->post_status ) ) {
					continue;
				}


				$coupon_data                    = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon_obj );
				$coupon_data['display_on_page'] = 'my_account';

				if ( 0 === $i ) {
					echo '<div class="wt_coupon_wrapper">';
				}

				echo Wt_Smart_Coupon_Public::get_coupon_html( $coupon_post, $coupon_data, 'used_coupon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 

				$printed_coupons['used_coupons'][] = $coupon_obj;

				++$i;
			}
			if ( $i > 0 ) {
				Wt_Smart_Coupon_Public::add_hidden_coupon_boxes();
				echo '</div>';
			}
		} else {
			echo '<div class="wt_sc_myaccount_no_used_coupons">';
			/**
			 * Filter to alter the my account no used coupons message
			 *
			 * @since 1.3.5
			 */
			echo wp_kses_post( apply_filters( 'wt_sc_alter_myaccount_no_used_coupons_msg', __( "Sorry, you don't have any used coupons", 'thetoppercentile-coupons' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			echo '</div>';
		}

		/**
		 * Action hook to add additional content after the used coupons
		 *
		 * @since 1.3.5
		 */
		do_action( 'wt_smart_coupon_after_used_coupons' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		?>
	</div>
	<?php
}

if ( isset( $smart_coupon_options['display_expired_coupons_my_account'] ) && $smart_coupon_options['display_expired_coupons_my_account'] ) {
	?>
	<div class="wt-expired-coupons">
		<h4><?php esc_html_e( 'Expired Coupons', 'thetoppercentile-coupons' ); ?></h4>
		<?php

		global $wpdb;
		$offset = 0;
		/**
		 * Filter hook to alter the limit of expired coupons per page
		 *
		 * @since 1.3.5
		 */
		$limit = apply_filters( 'wt_sc_my_account_expired_coupons_per_page', 50 ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		$coupon_ids = Wt_Smart_Coupon_Public::get_user_coupons( $current_user_obj, $offset, $limit, array( 'type' => 'expired_coupons' ) );

		if ( ! empty( $coupon_ids ) ) {
			echo '<div class="wt_coupon_wrapper">';
			foreach ( $coupon_ids as $coupon_index => $coupon_id ) {
				$post_obj                       = get_post( $coupon_id );
				$coupon_obj                     = new WC_Coupon( $post_obj->ID );
				$coupon_data                    = Wt_Smart_Coupon_Public::get_coupon_meta_data( $coupon_obj );
				$coupon_data['display_on_page'] = 'my_account_page';

				if ( 0 === $coupon_index ) {
					echo Wt_Smart_Coupon_Public::get_coupon_html( $post_obj, $coupon_data, 'expired_coupon', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
				} else {
					echo Wt_Smart_Coupon_Public::get_coupon_html( $post_obj, $coupon_data, 'expired_coupon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
				}
				$printed_coupons['expired_coupons'][] = $coupon_obj;
			}

			Wt_Smart_Coupon_Public::add_hidden_coupon_boxes();
			echo '</div>';
		} else {
			echo '<div class="wt_sc_myaccount_no_expired_coupons">';
			/**
			 * Filter to alter the my account no expired coupons message
			 *
			 * @since 1.3.5
			 */
			echo wp_kses_post( apply_filters( 'wt_sc_alter_myaccount_no_expired_coupons_msg', __( "Sorry, you don't have any expired coupons", 'thetoppercentile-coupons' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			echo '</div>';
		}

		/**
		 * Action hook to add additional content after the expired coupons
		 *
		 * @since 1.3.5
		 */
		do_action( 'wt_smart_coupon_after_expired_coupons' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		?>
	</div>
	<?php
}

/**
 * Action hook to add additional content after the my account coupons
 *
 * @since 1.3.5
 */
do_action( 'wt_smart_coupon_after_my_account_coupons', $printed_coupons ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound