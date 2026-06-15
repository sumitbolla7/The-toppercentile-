<?php
/**
 * Plugin Name: TTP Astra script compat
 * Description: Stubs missing ttp-main.js (404) and removes broken ttp-login.js on /login/ when DOM nodes are absent.
 * Version: 1.0.0
 * Author: The Top Percentile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace enqueued theme scripts that 404 or throw when markup is missing.
 *
 * @return void
 */
function ttp_astra_script_compat_fix() {
	$wp_scripts = wp_scripts();
	if ( ! $wp_scripts instanceof WP_Scripts ) {
		return;
	}

	// Stub ttp-main.js (theme references /themes/astra/ttp-main.js which often does not exist).
	if ( $wp_scripts->query( 'ttp-main-js', 'registered' ) || $wp_scripts->query( 'ttp-main-js', 'enqueued' ) ) {
		wp_deregister_script( 'ttp-main-js' );
		wp_register_script( 'ttp-main-js', false, array(), '1.0', true );
		wp_enqueue_script( 'ttp-main-js' );
		wp_add_inline_script( 'ttp-main-js', '/* ttp-main stub (MU compat) */' );
	}

	// On /login/, theme ttp-login.js expects nodes that may not exist — drop it to avoid console errors.
	if ( is_page( 'login' ) && ( $wp_scripts->query( 'ttp-login-js', 'registered' ) || $wp_scripts->query( 'ttp-login-js', 'enqueued' ) ) ) {
		wp_dequeue_script( 'ttp-login-js' );
		wp_deregister_script( 'ttp-login-js' );
	}
}
add_action( 'wp_enqueue_scripts', 'ttp_astra_script_compat_fix', 2000 );