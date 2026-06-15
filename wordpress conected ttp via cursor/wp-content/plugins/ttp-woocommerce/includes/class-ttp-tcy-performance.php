<?php
/**
 * TCY API performance: fast checkout, cached study-portal login, deferred full sync.
 *
 * @package TTP_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ttp_tcy_is_fast_access_request' ) ) {
	/**
	 * Fast path for "open course" / AJAX login (skip re-syncing every historical purchase).
	 *
	 * @return bool
	 */
	function ttp_tcy_is_fast_access_request() {
		$forced = apply_filters( 'ttp_tcy_fast_access', null );
		if ( null !== $forced ) {
			return (bool) $forced;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$action = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : '';
			if ( in_array( $action, [ 'ttp_tcy_login', 'ttp_buy_now' ], true ) ) {
				return true;
			}
		}
		if ( ! empty( $_GET['ttp_open_course'] ) ) {
			return true;
		}
		return (bool) apply_filters( 'ttp_tcy_fast_access_default', true );
	}
}

if ( ! function_exists( 'ttp_get_cached_study_login_url_for_order' ) ) {
	/**
	 * Cached magic-login URL stored on order mapping after a successful login.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	function ttp_get_cached_study_login_url_for_order( $order_id ) {
		$order_id = (int) $order_id;
		if ( $order_id < 1 ) {
			return '';
		}
		global $wpdb;
		$url = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT login_link FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND status = %s AND login_link IS NOT NULL AND login_link <> '' ORDER BY id DESC LIMIT 1",
				$order_id,
				'registered'
			)
		);
		if ( $url === '' || ! function_exists( 'ttp_is_permitted_study_portal_url' ) || ! ttp_is_permitted_study_portal_url( $url ) ) {
			return '';
		}
		return (string) apply_filters( 'ttp_cached_study_login_url', $url, $order_id );
	}
}

if ( ! function_exists( 'ttp_cache_study_login_url_for_order' ) ) {
	/**
	 * Persist study-portal login URL for an order (all mapping rows).
	 *
	 * @param int    $order_id Order ID.
	 * @param string $url      Final redirect URL.
	 */
	function ttp_cache_study_login_url_for_order( $order_id, $url ) {
		$order_id = (int) $order_id;
		$url      = (string) $url;
		if ( $order_id < 1 || $url === '' ) {
			return;
		}
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}ttp_order_mapping SET login_link = %s WHERE order_id = %d",
				$url,
				$order_id
			)
		);
	}
}

if ( ! function_exists( 'ttp_tcy_schedule_deferred_full_sync' ) ) {
	/**
	 * Schedule background sync of all purchased courses (does not block thank-you / login).
	 *
	 * @param string $tcy_user_id   TCY user id.
	 * @param int    $wp_user_id    WP user id.
	 * @param string $billing_email Billing email.
	 */
	function ttp_tcy_schedule_deferred_full_sync( $tcy_user_id, $wp_user_id = 0, $billing_email = '' ) {
		$tcy_user_id = function_exists( 'ttp_sanitize_tcy_user_id' ) ? ttp_sanitize_tcy_user_id( (string) $tcy_user_id ) : '';
		if ( $tcy_user_id === '' ) {
			return;
		}
		$wp_user_id    = (int) $wp_user_id;
		$billing_email = sanitize_email( (string) $billing_email );
		$key           = 'ttp_deferred_sync_' . md5( $tcy_user_id . '|' . $wp_user_id . '|' . strtolower( $billing_email ) );
		if ( get_transient( $key ) ) {
			return;
		}
		set_transient( $key, 1, 15 * MINUTE_IN_SECONDS );
		if ( ! wp_next_scheduled( 'ttp_tcy_deferred_customer_sync', [ $tcy_user_id, $wp_user_id, $billing_email ] ) ) {
			wp_schedule_single_event( time() + 3, 'ttp_tcy_deferred_customer_sync', [ $tcy_user_id, $wp_user_id, $billing_email ] );
		}
	}
}

if ( ! function_exists( 'ttp_tcy_api_delay_us' ) ) {
	/**
	 * Microsecond delay between TCY API calls (0 on fast access).
	 *
	 * @param string $context    remove_course|add_course_loop|add_course_variant.
	 * @param int    $default_us Default microseconds when not fast.
	 * @return int
	 */
	function ttp_tcy_api_delay_us( $context, $default_us ) {
		if ( function_exists( 'ttp_tcy_is_fast_access_request' ) && ttp_tcy_is_fast_access_request() ) {
			return 0;
		}
		return (int) apply_filters( 'ttp_tcy_' . $context . '_delay_us', (int) $default_us );
	}
}

if ( ! function_exists( 'ttp_tcy_run_deferred_customer_sync_handler' ) ) {
	/**
	 * @param string $tcy_user_id   TCY user id.
	 * @param int    $wp_user_id    WP user id.
	 * @param string $billing_email Billing email.
	 */
	function ttp_tcy_run_deferred_customer_sync_handler( $tcy_user_id, $wp_user_id, $billing_email ) {
		if ( function_exists( 'ttp_tcy_loop_add_all_courses_for_user_id' ) ) {
			ttp_tcy_loop_add_all_courses_for_user_id( (string) $tcy_user_id, (int) $wp_user_id, (string) $billing_email );
		}
	}
	add_action( 'ttp_tcy_deferred_customer_sync', 'ttp_tcy_run_deferred_customer_sync_handler', 10, 3 );
}
