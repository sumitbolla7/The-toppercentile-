<?php
/**
 * Plugin Name: 00 TTP Emergency Plugin Disable
 * Description: Temporarily disables custom TTP plugins when the site fatals. Remove after recovery.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set to true only while recovering from a critical error.
 * Flip back to false once the site is stable.
 */
if ( ! defined( 'TTP_EMERGENCY_DISABLE_CUSTOM_PLUGINS' ) ) {
	define( 'TTP_EMERGENCY_DISABLE_CUSTOM_PLUGINS', false );
}

if ( ! TTP_EMERGENCY_DISABLE_CUSTOM_PLUGINS ) {
	return;
}

add_filter(
	'option_active_plugins',
	static function ( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			return $plugins;
		}
		$disable = array(
			'affiliate-notification-hub/affiliate-notification-hub.php',
			'ttp-affiliate/ttp-affiliate.php',
			'ttp-notifications/ttp-notifications.php',
		);
		return array_values( array_diff( $plugins, $disable ) );
	},
	1
);
