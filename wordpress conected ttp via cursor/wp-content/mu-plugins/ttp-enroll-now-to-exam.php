<?php
/**
 * Plugin Name: TTP Enrol Now → Exam
 * Description: Sends /enrol-now/ and /enroll-now/ to /exam/ (same dark course catalog). Ensures menu and bookmarks use one page.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return void
 */
function ttp_enroll_now_redirect_to_exam() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( $uri === '' ) {
		return;
	}
	if ( preg_match( '#/(enrol-now|enroll-now)/?$#i', $uri ) ) {
		wp_safe_redirect( home_url( '/exam/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'ttp_enroll_now_redirect_to_exam', 0 );
