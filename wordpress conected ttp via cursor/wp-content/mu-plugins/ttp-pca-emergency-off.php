<?php
/**
 * Emergency: disables TTP Pages & Content Analysis if the site white-screens.
 * Upload to wp-content/mu-plugins/ via Hostinger File Manager (FTP).
 * Delete this file after the plugin is fixed and re-activated.
 *
 * @package TTP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'option_active_plugins', 'ttp_pca_emergency_deactivate_plugin' );
add_filter( 'site_option_active_sitewide_plugins', 'ttp_pca_emergency_deactivate_network' );

/**
 * @param array<int, string>|false $plugins Active plugins.
 * @return array<int, string>|false
 */
function ttp_pca_emergency_deactivate_plugin( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return $plugins;
	}
	$slug = 'ttp-pages-content-analysis/ttp-pages-content-analysis.php';
	$key  = array_search( $slug, $plugins, true );
	if ( false !== $key ) {
		unset( $plugins[ $key ] );
	}
	return $plugins;
}

/**
 * @param array<string, int>|false $plugins Network plugins.
 * @return array<string, int>|false
 */
function ttp_pca_emergency_deactivate_network( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return $plugins;
	}
	unset( $plugins['ttp-pages-content-analysis/ttp-pages-content-analysis.php'] );
	return $plugins;
}
