<?php
/**
 * Plugin Name: TTP Guest Checkout Persist
 * Description: Forces guest checkout for courses and preserves checkout/cart intent across login or email verification.
 * Version: 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the user already purchased the pending course product.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function ttp_gc_fix_user_already_owns_pending_product( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! function_exists( 'wc_customer_bought_product' ) ) {
		return false;
	}

	$product_id = ttp_gc_fix_get_pending_product();
	if ( $product_id < 1 ) {
		$target = ttp_gc_fix_get_pending_redirect();
		if ( '' !== $target ) {
			$product_id = ttp_gc_fix_extract_product_id_from_url( $target );
		}
	}

	return $product_id > 0 && wc_customer_bought_product( '', $user_id, $product_id );
}

/**
 * True when this request explicitly carries checkout intent (not a stale cookie alone).
 *
 * @return bool
 */
function ttp_gc_fix_request_has_explicit_checkout_intent() {
	if ( ! empty( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}

	return isset( $_REQUEST['add-to-cart'] ) || isset( $_REQUEST['buy-now'] ) || isset( $_REQUEST['product_id'] );
}

function ttp_gc_fix_checkout_url( $product_id = 0 ) {
	$checkout = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	$product_id = (int) $product_id;
	if ( $product_id > 0 ) {
		$checkout = add_query_arg( 'add-to-cart', $product_id, $checkout );
	}
	return $checkout;
}

function ttp_gc_fix_extract_product_id_from_url( $url ) {
	$url = (string) $url;
	if ( '' === $url ) {
		return 0;
	}
	$query = wp_parse_url( $url, PHP_URL_QUERY );
	if ( ! is_string( $query ) || '' === $query ) {
		return 0;
	}
	parse_str( $query, $args );
	return isset( $args['add-to-cart'] ) ? absint( $args['add-to-cart'] ) : 0;
}

function ttp_gc_fix_set_cookie( $name, $value ) {
	if ( headers_sent() ) {
		return;
	}
	setcookie( $name, (string) $value, time() + HOUR_IN_SECONDS * 3, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
}

function ttp_gc_fix_clear_cookie( $name ) {
	if ( headers_sent() ) {
		return;
	}
	setcookie( $name, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
}

function ttp_gc_fix_store_pending_product( $product_id ) {
	$product_id = absint( $product_id );
	if ( $product_id < 1 ) {
		return;
	}
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( 'ttp_pending_course_product_id', $product_id );
	}
	ttp_gc_fix_set_cookie( 'ttp_pending_course_product_id', (string) $product_id );
}

function ttp_gc_fix_get_pending_product() {
	if ( function_exists( 'WC' ) && WC()->session ) {
		$from_session = absint( WC()->session->get( 'ttp_pending_course_product_id', 0 ) );
		if ( $from_session > 0 ) {
			return $from_session;
		}
	}
	return isset( $_COOKIE['ttp_pending_course_product_id'] ) ? absint( wp_unslash( $_COOKIE['ttp_pending_course_product_id'] ) ) : 0;
}

function ttp_gc_fix_store_pending_redirect( $url ) {
	$url = wp_validate_redirect( (string) $url, '' );
	if ( '' === $url ) {
		return;
	}
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( 'ttp_pending_checkout_redirect', $url );
	}
	ttp_gc_fix_set_cookie( 'ttp_pending_checkout_redirect', rawurlencode( $url ) );
}

function ttp_gc_fix_get_pending_redirect() {
	if ( function_exists( 'WC' ) && WC()->session ) {
		$from_session = (string) WC()->session->get( 'ttp_pending_checkout_redirect', '' );
		if ( '' !== $from_session ) {
			$validated = wp_validate_redirect( $from_session, '' );
			if ( '' !== $validated ) {
				return $validated;
			}
		}
	}
	if ( empty( $_COOKIE['ttp_pending_checkout_redirect'] ) ) {
		return '';
	}
	$decoded = rawurldecode( (string) wp_unslash( $_COOKIE['ttp_pending_checkout_redirect'] ) );
	return wp_validate_redirect( $decoded, '' );
}

function ttp_gc_fix_clear_pending_intent() {
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->__unset( 'ttp_pending_course_product_id' );
		WC()->session->__unset( 'ttp_pending_checkout_redirect' );
	}
	ttp_gc_fix_clear_cookie( 'ttp_pending_course_product_id' );
	ttp_gc_fix_clear_cookie( 'ttp_pending_checkout_redirect' );
}

add_filter(
	'pre_option_woocommerce_enable_guest_checkout',
	static function () {
		return 'yes';
	}
);

add_filter(
	'pre_option_woocommerce_enable_signup_and_login_from_checkout',
	static function () {
		return 'yes';
	}
);

add_filter(
	'tpsp_allow_guest_course_checkout',
	static function () {
		return true;
	},
	1
);

add_filter(
	'tpsp_disable_guest_checkout_login_redirect',
	static function () {
		return true;
	},
	1
);

add_filter(
	'tpsp_guest_cart_use_login_redirect',
	static function () {
		return false;
	},
	1
);

add_action(
	'template_redirect',
	static function () {
		if ( is_admin() ) {
			return;
		}

		$requested_product = 0;
		if ( isset( $_REQUEST['add-to-cart'] ) ) {
			$requested_product = absint( wp_unslash( $_REQUEST['add-to-cart'] ) );
		} elseif ( isset( $_REQUEST['buy-now'] ) ) {
			$requested_product = absint( wp_unslash( $_REQUEST['buy-now'] ) );
		} elseif ( isset( $_REQUEST['product_id'] ) ) {
			$requested_product = absint( wp_unslash( $_REQUEST['product_id'] ) );
		}
		if ( $requested_product > 0 ) {
			ttp_gc_fix_store_pending_product( $requested_product );
			ttp_gc_fix_store_pending_redirect( ttp_gc_fix_checkout_url( $requested_product ) );
		}

		if ( ! empty( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$redirect_to = wp_validate_redirect( wp_unslash( (string) $_GET['redirect_to'] ), '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $redirect_to ) {
				ttp_gc_fix_store_pending_redirect( $redirect_to );
				$redirect_product = ttp_gc_fix_extract_product_id_from_url( $redirect_to );
				if ( $redirect_product > 0 ) {
					ttp_gc_fix_store_pending_product( $redirect_product );
				}
			}
		}
	},
	1
);

add_action(
	'template_redirect',
	static function () {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$product_id = ttp_gc_fix_get_pending_product();
		if ( $product_id < 1 ) {
			return;
		}

		$cart_id  = WC()->cart->generate_cart_id( $product_id );
		$existing = WC()->cart->find_product_in_cart( $cart_id );
		if ( ! $existing ) {
			WC()->cart->add_to_cart( $product_id, 1 );
		}
	},
	20
);

add_action(
	'template_redirect',
	static function () {
		if ( ! is_user_logged_in() ) {
			return;
		}
		if ( ! function_exists( 'is_page' ) || ( ! is_page( 'login' ) && ! is_page( 'register' ) ) ) {
			return;
		}

		// My Account / direct /login/ visit: do not bounce to checkout from stale cookies.
		if ( ! ttp_gc_fix_request_has_explicit_checkout_intent() ) {
			ttp_gc_fix_clear_pending_intent();
			if ( function_exists( 'wc_clear_notices' ) ) {
				wc_clear_notices();
			}
			return;
		}

		if ( ttp_gc_fix_user_already_owns_pending_product( get_current_user_id() ) ) {
			ttp_gc_fix_clear_pending_intent();
			if ( function_exists( 'wc_clear_notices' ) ) {
				wc_clear_notices();
			}
			return;
		}

		$target = ttp_gc_fix_get_pending_redirect();
		if ( '' === $target ) {
			$product_id = ttp_gc_fix_get_pending_product();
			if ( $product_id > 0 ) {
				$target = ttp_gc_fix_checkout_url( $product_id );
			}
		}

		if ( '' === $target ) {
			return;
		}

		ttp_gc_fix_clear_pending_intent();
		wp_safe_redirect( $target );
		exit;
	},
	30
);

add_filter(
	'login_redirect',
	static function ( $redirect_to, $requested_redirect_to, $user ) {
		if ( ! ( $user instanceof WP_User ) || user_can( $user, 'manage_options' ) ) {
			return $redirect_to;
		}

		$target = ttp_gc_fix_get_pending_redirect();
		if ( '' === $target ) {
			$product_id = ttp_gc_fix_get_pending_product();
			if ( $product_id > 0 ) {
				$target = ttp_gc_fix_checkout_url( $product_id );
			}
		}

		if ( '' === $target ) {
			$validated_request = wp_validate_redirect( (string) $requested_redirect_to, '' );
			if ( '' !== $validated_request ) {
				return $validated_request;
			}
			return $redirect_to;
		}

		if ( ttp_gc_fix_user_already_owns_pending_product( (int) $user->ID ) ) {
			ttp_gc_fix_clear_pending_intent();
			if ( function_exists( 'tpsp_get_login_page_url' ) ) {
				return tpsp_get_login_page_url();
			}
			return home_url( '/login/' );
		}

		ttp_gc_fix_clear_pending_intent();
		return $target;
	},
	50,
	3
);

add_filter(
	'user_registration_login_redirect',
	static function ( $redirect, $user ) {
		if ( ! ( $user instanceof WP_User ) ) {
			return $redirect;
		}

		$target = ttp_gc_fix_get_pending_redirect();
		if ( '' === $target ) {
			$product_id = ttp_gc_fix_get_pending_product();
			if ( $product_id > 0 ) {
				$target = ttp_gc_fix_checkout_url( $product_id );
			}
		}

		if ( '' === $target ) {
			return $redirect;
		}

		if ( ttp_gc_fix_user_already_owns_pending_product( (int) $user->ID ) ) {
			ttp_gc_fix_clear_pending_intent();
			if ( function_exists( 'tpsp_get_login_page_url' ) ) {
				return tpsp_get_login_page_url();
			}
			return home_url( '/login/' );
		}

		ttp_gc_fix_clear_pending_intent();
		return $target;
	},
	50,
	2
);

