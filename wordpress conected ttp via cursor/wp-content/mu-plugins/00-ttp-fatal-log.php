<?php
/**
 * Plugin Name: 00 TTP Fatal Log
 * Description: Logs PHP fatals to wp-content/fatal.log for recovery.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$log_file = WP_CONTENT_DIR . '/fatal.log';

register_shutdown_function(
	static function () use ( $log_file ) {
		$last = error_get_last();
		if ( ! is_array( $last ) ) {
			return;
		}
		$fatal_types = array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR );
		if ( ! in_array( (int) $last['type'], $fatal_types, true ) ) {
			return;
		}
		$line = gmdate( 'c' ) . ' ' . wp_json_encode( $last ) . PHP_EOL;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		@file_put_contents( $log_file, $line, FILE_APPEND );
	}
);
