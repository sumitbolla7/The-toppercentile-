<?php
/**
 * Plugin Name: TTP Pages & Content Analysis
 * Description: Unified analytics hub — Google Search (Site Kit), Hotjar behavior, page opens, and content audit in one WordPress dashboard.
 * Version: 2.0.1
 * Author: Top Percentile
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: ttp-pca
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TTP_PCA_VERSION', '2.0.1' );
define( 'TTP_PCA_FILE', __FILE__ );
define( 'TTP_PCA_DIR', plugin_dir_path( __FILE__ ) );
define( 'TTP_PCA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load includes safely (prevents white screen if a file failed to upload).
 *
 * @return bool
 */
function ttp_pca_load_includes() {
	$files = [
		'includes/class-ttp-pca-db.php',
		'includes/class-ttp-pca-whitelist.php',
		'includes/class-ttp-pca-sitekit.php',
		'includes/class-ttp-pca-hotjar.php',
		'includes/class-ttp-pca-admin-ui.php',
		'includes/class-ttp-pca-tracker.php',
		'includes/class-ttp-pca-analyzer.php',
		'includes/class-ttp-pca-reports.php',
		'includes/class-ttp-pca-admin.php',
	];

	foreach ( $files as $file ) {
		$path = TTP_PCA_DIR . $file;
		if ( ! is_readable( $path ) ) {
			add_action(
				'admin_notices',
				static function () use ( $file ) {
					if ( ! current_user_can( 'manage_options' ) ) {
						return;
					}
					printf(
						'<div class="notice notice-error"><p><strong>TTP Analysis:</strong> Missing file <code>%s</code>. Re-upload the plugin folder or deactivate the plugin.</p></div>',
						esc_html( $file )
					);
				}
			);
			return false;
		}
		require_once $path;
	}

	return true;
}

if ( ! ttp_pca_load_includes() ) {
	return;
}

/**
 * Bootstrap.
 */
function ttp_pca_init() {
	TTP_PCA_DB::maybe_create_tables();
	TTP_PCA_Tracker::init();
	add_action( 'wp', [ 'TTP_PCA_Hotjar', 'maybe_enqueue_tracker' ], 20 );
	if ( is_admin() ) {
		TTP_PCA_Admin_UI::init();
		TTP_PCA_Admin::init();
	}
}
add_action( 'plugins_loaded', 'ttp_pca_init' );

register_activation_hook( __FILE__, [ 'TTP_PCA_DB', 'maybe_create_tables' ] );
