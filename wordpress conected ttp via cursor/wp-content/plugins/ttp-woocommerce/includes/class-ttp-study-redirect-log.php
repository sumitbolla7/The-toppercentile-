<?php
/**
 * Last 10 study-portal (TCY) redirect events for debugging.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_Study_Redirect_Log {

	const OPTION_KEY = 'ttp_study_redirect_logs';
	const MAX_LOGS   = 10;

	/**
	 * @param array $entry Log fields.
	 * @return void
	 */
	public static function add( array $entry ) {
		$entry = wp_parse_args(
			$entry,
			array(
				'time'        => current_time( 'mysql' ),
				'source'      => '',
				'order_id'    => 0,
				'user_id'     => 0,
				'tcy_user_id' => '',
				'raw_url'     => '',
				'final_url'   => '',
				'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				'note'        => '',
			)
		);

		$logs   = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}
		array_unshift( $logs, $entry );
		$logs = array_slice( $logs, 0, self::MAX_LOGS );
		update_option( self::OPTION_KEY, $logs, false );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'[TTP Study Redirect] ' . wp_json_encode(
					array(
						'source'    => $entry['source'],
						'order_id'  => (int) $entry['order_id'],
						'final_url' => $entry['final_url'],
					)
				)
			);
		}

		self::maybe_write_api_log( $entry );
	}

	/**
	 * @param array $entry Log entry.
	 * @return void
	 */
	private static function maybe_write_api_log( array $entry ) {
		global $wpdb;

		$table = $wpdb->prefix . 'ttp_api_logs';
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			return;
		}

		$wpdb->insert(
			$table,
			array(
				'order_id'      => (int) $entry['order_id'] > 0 ? (int) $entry['order_id'] : null,
				'action'        => 'study_redirect',
				'request_data'  => wp_json_encode(
					array(
						'source'      => $entry['source'],
						'user_id'     => (int) $entry['user_id'],
						'tcy_user_id' => $entry['tcy_user_id'],
						'raw_url'     => $entry['raw_url'],
						'ip'          => $entry['ip'],
					)
				),
				'response_data' => wp_json_encode(
					array(
						'final_url'  => $entry['final_url'],
						'note'       => $entry['note'],
						'user_agent' => substr( (string) $entry['user_agent'], 0, 200 ),
					)
				),
				'status'        => 'success',
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_all() {
		$logs = get_option( self::OPTION_KEY, array() );
		return is_array( $logs ) ? $logs : array();
	}

	/**
	 * @return void
	 */
	public static function clear() {
		delete_option( self::OPTION_KEY );
	}
}
