<?php
/**
 * Plugin Name: TTP Classic Cart helper (admin notice)
 * Description: Warns when the WooCommerce Cart page uses Blocks — Customizer cart options and classic proceed-to-checkout hooks do not apply. Use shortcode [woocommerce_cart] instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect block-based Cart page content (WooCommerce Cart block).
 *
 * @return bool
 */
function ttp_wc_cart_page_is_block_based() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return false;
	}
	$cart_id = (int) wc_get_page_id( 'cart' );
	if ( $cart_id <= 0 ) {
		return false;
	}
	$post = get_post( $cart_id );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	$html = (string) $post->post_content;

	return ( false !== strpos( $html, 'wp:woocommerce/cart' ) )
		|| ( false !== strpos( $html, 'woocommerce/cart' ) );
}

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'edit_pages' ) || ! function_exists( 'wc_get_page_id' ) ) {
			return;
		}
		if ( ! ttp_wc_cart_page_is_block_based() ) {
			return;
		}
		$cart_id = (int) wc_get_page_id( 'cart' );
		$edit    = get_edit_post_link( $cart_id, 'raw' );
		if ( ! $edit ) {
			return;
		}
		echo '<div class="notice notice-warning is-dismissible"><p><strong>WooCommerce Cart (blocks):</strong> ';
		echo esc_html__( 'This cart uses the block editor. Theme Customizer cart options (button text, etc.) and classic checkout buttons often do not work. Switch the page content to a single Shortcode block:', 'ttp-classic-cart' );
		echo ' <code>[woocommerce_cart]</code> ';
		echo esc_html__( 'then update the page.', 'ttp-classic-cart' );
		if ( $edit ) {
			echo ' <a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit Cart page', 'ttp-classic-cart' ) . '</a>';
		}
		echo '</p></div>';
	}
);
