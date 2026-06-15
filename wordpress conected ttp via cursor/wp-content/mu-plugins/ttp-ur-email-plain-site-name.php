<?php
/**
 * Plugin Name: TTP UR email — plain site name in confirmation
 * Description: Email confirmation: "The Top Percentile" as normal text, not a hyperlink.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $content Email HTML.
 * @return string
 */
function ttp_ur_email_plain_registering_site_name( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	$content = preg_replace(
		'#Thank you for registering at\s*<a[^>]*>\s*\{\{blog_info\}\}\s*</a>#i',
		'Thank you for registering at {{blog_info}}!',
		$content
	);

	$content = preg_replace(
		'#Thank you for registering at\s*<a[^>]*>([^<]+)</a>#i',
		'Thank you for registering at $1!',
		$content
	);

	return $content;
}

add_filter( 'user_registration_get_email_confirmation', 'ttp_ur_email_plain_registering_site_name', 20 );
add_filter( 'option_user_registration_email_confirmation', 'ttp_ur_email_plain_registering_site_name', 20 );
add_filter(
	'user_registration_process_smart_tags',
	static function ( $content ) {
		return ttp_ur_email_plain_registering_site_name( $content );
	},
	20,
	3
);
