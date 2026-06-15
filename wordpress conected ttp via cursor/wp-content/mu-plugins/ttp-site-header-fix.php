<?php
/**
 * Plugin Name: TTP Site Header Fix
 * Description: Sets a real site title when missing and hides the duplicate text next to the logo (Astra).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TTP_SITE_DISPLAY_NAME = 'The Top Percentile';

/**
 * Use a proper site name for <title>, feeds, logo alt, and schema when the option is empty or still the placeholder.
 */
add_filter(
	'option_blogname',
	function ( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';
		if ( $value === '' || false !== stripos( $value, 'No Blog Title' ) ) {
			return TTP_SITE_DISPLAY_NAME;
		}
		return $value;
	},
	10,
	1
);

/**
 * Hide the site title block beside the custom logo; the logo already carries the brand.
 */
add_action(
	'wp_head',
	function () {
		echo '<style id="ttp-hide-header-site-title">.site-header .ast-site-title-wrap{display:none!important;}</style>' . "\n";
	},
	100
);
