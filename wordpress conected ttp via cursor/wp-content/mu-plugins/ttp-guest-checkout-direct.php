<?php
/**
 * Plugin Name: TTP Guest Checkout Direct
 * Description: Buy Now and Proceed to Checkout go straight to /checkout/ for guests (no login detour).
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return bool
 */
function ttp_guest_checkout_is_exam_request() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return is_string( $uri ) && false !== strpos( $uri, '/exam' );
}

/**
 * @return bool
 */
function ttp_guest_checkout_direct_enabled() {
	if ( ttp_guest_checkout_is_exam_request() ) {
		return false;
	}
	return (bool) apply_filters( 'ttp_guest_checkout_direct', true );
}

add_filter(
	'tpsp_guest_cart_use_login_redirect',
	static function ( $enabled ) {
		if ( ! ttp_guest_checkout_direct_enabled() ) {
			return $enabled;
		}
		return false;
	},
	1
);

add_filter(
	'tpsp_disable_guest_checkout_login_redirect',
	static function ( $disabled ) {
		if ( ! ttp_guest_checkout_direct_enabled() ) {
			return $disabled;
		}
		return true;
	},
	1
);

add_filter(
	'tpsp_allow_guest_course_checkout',
	static function ( $allowed ) {
		if ( ! ttp_guest_checkout_direct_enabled() ) {
			return $allowed;
		}
		return true;
	},
	1
);

/**
 * Real WooCommerce checkout permalink (never the login URL from TPSP filters).
 *
 * @return string
 */
function ttp_guest_checkout_direct_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'checkout' );
		if ( ! empty( $url ) ) {
			return $url;
		}
	}

	return home_url( '/checkout/' );
}

/**
 * Checkout URL with ?add-to-cart= for the first product in the cart (matches exam Buy Now flow).
 *
 * @return string
 */
function ttp_guest_checkout_url_with_cart() {
	$base = ttp_guest_checkout_direct_url();

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return $base;
	}

	foreach ( WC()->cart->get_cart() as $item ) {
		$product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		if ( $product_id > 0 ) {
			return add_query_arg( 'add-to-cart', $product_id, $base );
		}
	}

	return $base;
}

add_filter(
	'pre_option_woocommerce_enable_guest_checkout',
	static function ( $value ) {
		if ( ! ttp_guest_checkout_direct_enabled() ) {
			return $value;
		}
		return 'yes';
	}
);
