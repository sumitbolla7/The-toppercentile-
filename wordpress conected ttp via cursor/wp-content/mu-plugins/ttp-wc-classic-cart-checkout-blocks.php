<?php
/**
 * Plugin Name: TTP — Force classic Cart & Checkout
 * Description: Block cart/checkout bypass PHP. This (1) short-circuits WC Cart/Checkout blocks and (2) replaces the Cart/Checkout page main content with shortcodes so classic templates always run.
 *
 * Disable: define('TTP_DISABLE_WC_BLOCK_TO_SHORTCODE', true); in wp-config.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string|null          $pre_render   Short-circuit return.
 * @param array<string, mixed> $parsed_block Block data.
 * @return string|null
 */
function ttp_wc_pre_render_classic_cart_checkout( $pre_render, $parsed_block ) {
	if ( defined( 'TTP_DISABLE_WC_BLOCK_TO_SHORTCODE' ) && TTP_DISABLE_WC_BLOCK_TO_SHORTCODE ) {
		return $pre_render;
	}

	if ( ! function_exists( 'WC' ) || ! function_exists( 'do_shortcode' ) ) {
		return $pre_render;
	}

	$name = isset( $parsed_block['blockName'] ) ? (string) $parsed_block['blockName'] : '';

	if ( 'woocommerce/cart' === $name ) {
		return '<div class="woocommerce ttp-wc-classic-cart-replace">' . do_shortcode( '[woocommerce_cart]' ) . '</div>';
	}

	if ( 'woocommerce/checkout' === $name ) {
		return '<div class="woocommerce ttp-wc-classic-checkout-replace">' . do_shortcode( '[woocommerce_checkout]' ) . '</div>';
	}

	return $pre_render;
}

add_filter( 'pre_render_block', 'ttp_wc_pre_render_classic_cart_checkout', 5, 2 );

/**
 * Last-resort: on the official Cart / Checkout pages, replace main post content output with shortcodes.
 * Catches Elementor, templates where pre_render_block never runs, etc.
 *
 * @param string $content Post content (may already be rendered).
 * @return string
 */
function ttp_wc_the_content_force_classic_cart_checkout( $content ) {
	if ( defined( 'TTP_DISABLE_WC_BLOCK_TO_SHORTCODE' ) && TTP_DISABLE_WC_BLOCK_TO_SHORTCODE ) {
		return $content;
	}

	if ( ! function_exists( 'wc_get_page_id' ) || ! function_exists( 'is_page' ) || ! function_exists( 'do_shortcode' ) ) {
		return $content;
	}

	static $ttp_wc_did_cart = false;
	static $ttp_wc_did_checkout = false;

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	global $post;
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}

	$cart_id     = (int) wc_get_page_id( 'cart' );
	$checkout_id = (int) wc_get_page_id( 'checkout' );

	if ( $cart_id > 0 && (int) $post->ID === $cart_id && ! $ttp_wc_did_cart ) {
		$ttp_wc_did_cart = true;
		return '<div class="woocommerce ttp-wc-classic-cart-page">' . do_shortcode( '[woocommerce_cart]' ) . '</div>';
	}

	if ( $checkout_id > 0 && (int) $post->ID === $checkout_id && ! $ttp_wc_did_checkout ) {
		$ttp_wc_did_checkout = true;
		return '<div class="woocommerce ttp-wc-classic-checkout-page">' . do_shortcode( '[woocommerce_checkout]' ) . '</div>';
	}

	return $content;
}

add_filter( 'the_content', 'ttp_wc_the_content_force_classic_cart_checkout', 99999 );
