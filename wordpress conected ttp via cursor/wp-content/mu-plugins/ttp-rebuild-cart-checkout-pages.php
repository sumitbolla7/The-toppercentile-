<?php
/**
 * Plugin Name: TTP Rebuild Cart/Checkout Pages
 * Description: Recreates and reassigns WooCommerce Cart/Checkout pages with classic shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure a WooCommerce page exists and has the expected shortcode content.
 *
 * @param string $slug         Page slug.
 * @param string $title        Page title.
 * @param string $option_key   WooCommerce option key.
 * @param string $shortcode    Shortcode content.
 * @return int
 */
function ttp_wc_ensure_page( $slug, $title, $option_key, $shortcode ) {
	$page_id = (int) get_option( $option_key );
	$page    = $page_id > 0 ? get_post( $page_id ) : null;

	if ( ! $page instanceof WP_Post || 'trash' === $page->post_status ) {
		$page = get_page_by_path( $slug );
	}

	if ( ! $page instanceof WP_Post ) {
		$new_id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $shortcode,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			]
		);
		$page_id = is_wp_error( $new_id ) ? 0 : (int) $new_id;
	} else {
		$page_id = (int) $page->ID;
		$content = trim( (string) $page->post_content );
		if ( $content !== $shortcode ) {
			wp_update_post(
				[
					'ID'           => $page_id,
					'post_content' => $shortcode,
					'post_status'  => 'publish',
				]
			);
		}
	}

	if ( $page_id > 0 ) {
		update_option( $option_key, $page_id );
	}

	return $page_id;
}

/**
 * Force classic Woo pages so Cart/Checkout flow works reliably.
 */
add_action(
	'init',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$cart_id     = ttp_wc_ensure_page( 'cart', 'Cart', 'woocommerce_cart_page_id', '[woocommerce_cart]' );
		$checkout_id = ttp_wc_ensure_page( 'checkout', 'Checkout', 'woocommerce_checkout_page_id', '[woocommerce_checkout]' );
		ttp_wc_ensure_page( 'my-account', 'My account', 'woocommerce_myaccount_page_id', '[woocommerce_my_account]' );

		// Flush rewrites once if pages were repaired.
		if ( $cart_id > 0 && $checkout_id > 0 && ! get_option( 'ttp_wc_pages_rebuilt_once' ) ) {
			flush_rewrite_rules( false );
			update_option( 'ttp_wc_pages_rebuilt_once', 1 );
		}
	},
	20
);

