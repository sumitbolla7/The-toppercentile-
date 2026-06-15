<?php
/**
 * Plugin Name: TTP Contentsquare / Hotjar
 * Description: Loads Contentsquare (Hotjar) UX analytics on every public page.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contentsquare script URL (Hotjar trial / tag installation).
 *
 * @return string
 */
function ttp_contentsquare_script_url() {
	return (string) apply_filters(
		'ttp_contentsquare_script_url',
		'https://t.contentsquare.net/uxa/5aaf9c8f056c2.js'
	);
}

/**
 * Whether to output the tracker on the current request.
 *
 * @return bool
 */
function ttp_contentsquare_should_load() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return false;
	}

	$load = true;

	// Optional: define( 'TTP_DISABLE_CONTENTSQUARE', true ); in wp-config.php to turn off.
	if ( defined( 'TTP_DISABLE_CONTENTSQUARE' ) && TTP_DISABLE_CONTENTSQUARE ) {
		$load = false;
	}

	return (bool) apply_filters( 'ttp_contentsquare_should_load', $load );
}

/**
 * Enqueue tracker in <head> on all front-end pages.
 *
 * @return void
 */
function ttp_contentsquare_enqueue_script() {
	if ( ! ttp_contentsquare_should_load() ) {
		return;
	}

	$url = esc_url( ttp_contentsquare_script_url() );
	if ( '' === $url ) {
		return;
	}

	wp_enqueue_script(
		'ttp-contentsquare',
		$url,
		array(),
		null,
		false
	);

	if ( function_exists( 'wp_script_add_data' ) ) {
		wp_script_add_data( 'ttp-contentsquare', 'async', true );
	}
}
add_action( 'wp_enqueue_scripts', 'ttp_contentsquare_enqueue_script', 1 );
