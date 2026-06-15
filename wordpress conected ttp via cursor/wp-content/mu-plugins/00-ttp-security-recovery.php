<?php
/**
 * Plugin Name: 00 TTP Security Recovery
 * Description: Blocks known malware plugins and unsafe file managers after a compromise.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Folder slugs to keep deactivated even if still in active_plugins.
 *
 * @return string[]
 */
function ttp_security_blocked_plugin_patterns() {
	return array(
		'analytics_1781073135',
		'backup_1781073135',
		'litspeed-beta',
		'wp-technology-periphericum',
		'fileorganizer/',
		'filester/',
		'wp-file-manager',
		'file-manager',
		'wp-file-manager-pro',
		'malware',
		'wp-db-backup',
		'wp-super-cache-manager',
		'wp-cache-manager',
		'wp-support-plus',
		'wp-engine',
		'wp-engine-s',
	);
}

/**
 * @param string $line Log line.
 * @return void
 */
function ttp_security_log( $line ) {
	$file = WP_CONTENT_DIR . '/security-block.log';
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	@file_put_contents( $file, gmdate( 'c' ) . ' ' . $line . PHP_EOL, FILE_APPEND );
}

/**
 * Remove executable PHP dropped in uploads (common malware vector).
 *
 * @return void
 */
function ttp_security_sweep_uploads_php() {
	$uploads = WP_CONTENT_DIR . '/uploads';
	if ( ! is_dir( $uploads ) ) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $uploads, FilesystemIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$path = $file->getPathname();
		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'php', 'phtml', 'php5', 'phar' ), true ) ) {
			continue;
		}
		$basename = basename( $path );
		if ( 'index.php' === $basename ) {
			continue;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		if ( @unlink( $path ) ) {
			ttp_security_log( 'Deleted rogue upload PHP: ' . $path );
		}
	}
}

/**
 * Deactivate plugins whose folder names match known malware patterns.
 *
 * @return void
 */
function ttp_security_deactivate_rogue_plugin_dirs() {
	if ( ! defined( 'WP_PLUGIN_DIR' ) || ! is_dir( WP_PLUGIN_DIR ) ) {
		return;
	}

	$patterns = ttp_security_blocked_plugin_patterns();
	$dirs     = glob( WP_PLUGIN_DIR . '/*', GLOB_ONLYDIR );
	if ( ! is_array( $dirs ) ) {
		return;
	}

	foreach ( $dirs as $dir ) {
		$slug = basename( $dir );
		foreach ( $patterns as $pattern ) {
			if ( $pattern !== '' && false !== stripos( $slug, str_replace( '/', '', $pattern ) ) ) {
				ttp_security_log( 'Rogue plugin folder present: ' . $slug );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
				if ( is_writable( $dir ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					@rename( $dir, $dir . '.quarantined-' . gmdate( 'Ymd' ) );
					ttp_security_log( 'Quarantined plugin folder: ' . $slug );
				}
				break;
			}
		}
	}
}

add_filter(
	'option_active_plugins',
	static function ( $plugins ) {
		if ( ! is_array( $plugins ) ) {
			return $plugins;
		}

		$patterns = ttp_security_blocked_plugin_patterns();
		$plugins  = array_values(
			array_filter(
				$plugins,
				static function ( $plugin_file ) use ( $patterns ) {
					foreach ( $patterns as $pattern ) {
						if ( false !== strpos( (string) $plugin_file, $pattern ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);

		$woocommerce = 'woocommerce/woocommerce.php';
		if ( ! in_array( $woocommerce, $plugins, true ) && file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
			$plugins[] = $woocommerce;
		}

		return $plugins;
	},
	1
);

add_filter(
	'pre_update_option_active_plugins',
	static function ( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$patterns = ttp_security_blocked_plugin_patterns();

		return array_values(
			array_filter(
				$value,
				static function ( $plugin_file ) use ( $patterns ) {
					foreach ( $patterns as $pattern ) {
						if ( false !== strpos( (string) $plugin_file, $pattern ) ) {
							return false;
						}
					}
					return true;
				}
			)
		);
	},
	1
);

add_filter(
	'wp_handle_upload_prefilter',
	static function ( $file ) {
		if ( empty( $file['name'] ) ) {
			return $file;
		}
		$ext = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );
		if ( in_array( $ext, array( 'php', 'phtml', 'php5', 'phar', 'cgi', 'asp', 'aspx', 'jsp' ), true ) ) {
			ttp_security_log( 'Blocked executable upload: ' . (string) $file['name'] );
			$file['error'] = __( 'Executable files are not allowed.', 'ttp-security' );
		}
		return $file;
	},
	1
);

add_action(
	'init',
	static function () {
		if ( get_transient( 'ttp_security_upload_sweep' ) ) {
			return;
		}
		ttp_security_sweep_uploads_php();
		ttp_security_deactivate_rogue_plugin_dirs();
		set_transient( 'ttp_security_upload_sweep', 1, 15 * MINUTE_IN_SECONDS );
	},
	5
);
