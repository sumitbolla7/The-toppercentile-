<?php
/**
 * Plugin Name: TTP WooCommerce - TCY Integrationdhb
 * Plugin URI: https://thetoppercentile.co.in
 * Description: WooCommerce + TCY platform integration for Top Percentile.
 * Version: 2.9.8
 * Author: Top Percentile
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TTP_VERSION',    '2.9.8' );

/** Max paid course rows on /login/ panel (supports 10+ distinct purchases). */
define( 'TTP_MAX_ENROLLED_COURSES', 50 );
define( 'TTP_DIR',        plugin_dir_path( __FILE__ ) );
define( 'TTP_URL',        plugin_dir_url( __FILE__ ) );

$ttp_performance_file = TTP_DIR . 'includes/class-ttp-tcy-performance.php';
if ( is_readable( $ttp_performance_file ) ) {
	require_once $ttp_performance_file;
}

/**
 * Fallbacks if performance include is missing on the server (prevents fatal "call to undefined function").
 */
if ( ! function_exists( 'ttp_tcy_api_delay_us' ) ) {
	function ttp_tcy_api_delay_us( $context, $default_us ) {
		return (int) $default_us;
	}
}
if ( ! function_exists( 'ttp_tcy_is_fast_access_request' ) ) {
	function ttp_tcy_is_fast_access_request() {
		return false;
	}
}
if ( ! function_exists( 'ttp_get_cached_study_login_url_for_order' ) ) {
	function ttp_get_cached_study_login_url_for_order( $order_id ) {
		return '';
	}
}
if ( ! function_exists( 'ttp_cache_study_login_url_for_order' ) ) {
	function ttp_cache_study_login_url_for_order( $order_id, $url ) {
		// no-op
	}
}
if ( ! function_exists( 'ttp_tcy_schedule_deferred_full_sync' ) ) {
	function ttp_tcy_schedule_deferred_full_sync( $tcy_user_id, $wp_user_id = 0, $billing_email = '' ) {
		if ( function_exists( 'ttp_tcy_loop_add_all_courses_for_user_id' ) ) {
			ttp_tcy_loop_add_all_courses_for_user_id( (string) $tcy_user_id, (int) $wp_user_id, (string) $billing_email );
		}
	}
}

/**
 * Rewrite TCY magic-login URLs from the default white-label host to your public study domain
 * (e.g. thetoppercentile.tcyonline.co.in → study.thetoppercentile.co.in).
 *
 * @param string $url Absolute URL from TCY login API.
 * @return string
 */
/**
 * Login page URL; after login, user is sent to checkout (optionally with ?add-to-cart=).
 *
 * @param int $product_id WooCommerce product ID.
 * @return string
 */
function ttp_get_login_url_with_checkout_redirect( $product_id = 0 ) {
	$checkout = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	$product_id = (int) $product_id;
	if ( $product_id > 0 ) {
		$checkout = add_query_arg( 'add-to-cart', $product_id, $checkout );
	}
	if ( function_exists( 'tpsp_get_login_redirect_url' ) ) {
		return tpsp_get_login_redirect_url( $checkout );
	}

	return add_query_arg( 'redirect_to', $checkout, home_url( '/login/' ) );
}

function ttp_rewrite_login_url_to_study_portal( $url ) {
    if ( ! is_string( $url ) || $url === '' ) {
        return $url;
    }
    if ( ! apply_filters( 'ttp_rewrite_study_portal_login_url', true, $url ) ) {
        return $url;
    }
    $target = rtrim( (string) get_option( 'ttp_study_portal_base_url', 'https://study.thetoppercentile.co.in' ), '/' );
    if ( $target === '' ) {
        return $url;
    }
    $parts = wp_parse_url( $url );
    if ( empty( $parts['host'] ) ) {
        return $url;
    }
    $target_parts = wp_parse_url( $target );
    $canonical_hosts = apply_filters(
        'ttp_tcy_login_url_hosts_to_rewrite',
        array(
            'thetoppercentile.tcyonline.co.in',
            'www.thetoppercentile.tcyonline.co.in',
        )
    );
    $host = strtolower( $parts['host'] );
    foreach ( (array) $canonical_hosts as $h ) {
        if ( strtolower( (string) $h ) === $host ) {
            $scheme   = ! empty( $target_parts['scheme'] ) ? $target_parts['scheme'] : 'https';
            $new_host = $target_parts['host'] ?? 'study.thetoppercentile.co.in';
            $path     = isset( $parts['path'] ) ? $parts['path'] : '';
            $query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
            $frag     = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
            return $scheme . '://' . $new_host . $path . $query . $frag;
        }
    }
    if ( apply_filters( 'ttp_rewrite_all_tcyonline_magic_hosts', true ) && preg_match( '/\.tcyonline\.(?:co\.in|com)$/i', $host ) ) {
        $scheme   = ! empty( $target_parts['scheme'] ) ? $target_parts['scheme'] : 'https';
        $new_host = $target_parts['host'] ?? 'study.thetoppercentile.co.in';
        $path     = isset( $parts['path'] ) ? $parts['path'] : '';
        $query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
        $frag     = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
        return $scheme . '://' . $new_host . $path . $query . $frag;
    }
    return $url;
}

/**
 * Successful checkout for TCY: paid / no balance due, and status is safe for enrollment.
 * Includes on-hold (often used after payment) — previously only processing/completed, which blocked many real orders.
 *
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function ttp_order_qualifies_for_tcy_actions( $order ) {
	if ( ! class_exists( 'WC_Order' ) || ! ( $order instanceof WC_Order ) ) {
		return false;
	}
	$status = $order->get_status();
	if ( in_array( $status, array( 'failed', 'cancelled', 'refunded', 'pending' ), true ) ) {
		return false;
	}
	if ( $order->needs_payment() ) {
		return false;
	}
	$allowed = apply_filters(
		'ttp_tcy_qualifying_order_statuses',
		array( 'processing', 'completed', 'on-hold' )
	);
	return in_array( $status, (array) $allowed, true );
}

/**
 * Gateway captured payment even when WooCommerce status is wrong (cancelled/failed).
 *
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function ttp_order_has_gateway_payment( $order ) {
	if ( ! class_exists( 'WC_Order' ) || ! ( $order instanceof WC_Order ) ) {
		return false;
	}
	if ( $order->get_date_paid() ) {
		return true;
	}
	if ( $order->is_paid() ) {
		return true;
	}
	$txn = trim( (string) $order->get_transaction_id() );
	if ( $txn !== '' ) {
		return true;
	}
	if ( 'yes' === (string) $order->get_meta( '_ttp_gateway_paid', true ) ) {
		return true;
	}
	return (bool) apply_filters( 'ttp_order_has_gateway_payment', false, $order );
}

/**
 * Promote a gateway-paid order stuck in cancelled/failed to processing and sync TCY.
 *
 * @param WC_Order|mixed $order Order.
 * @param string         $note  Optional order note.
 * @return bool True when status was promoted.
 */
function ttp_maybe_promote_gateway_paid_order( $order, $note = '' ) {
	if ( ! class_exists( 'WC_Order' ) || ! ( $order instanceof WC_Order ) ) {
		return false;
	}
	if ( function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ttp_order_qualifies_for_tcy_actions( $order ) ) {
		return false;
	}
	if ( ! function_exists( 'ttp_order_has_gateway_payment' ) || ! ttp_order_has_gateway_payment( $order ) ) {
		return false;
	}
	$bad_statuses = array( 'cancelled', 'failed' );
	if ( ! in_array( $order->get_status(), $bad_statuses, true ) ) {
		return false;
	}

	$order_id = (int) $order->get_id();
	$order->set_status(
		'processing',
		$note !== '' ? $note : __( 'Payment confirmed by gateway; order promoted for TCY enrollment.', 'ttp-woocommerce' ),
		true
	);
	if ( ! $order->get_date_paid() ) {
		$order->set_date_paid( time() );
	}
	$order->update_meta_data( '_ttp_gateway_paid', 'yes' );
	$order->save();

	if ( function_exists( 'ttp_get_checkout_instance' ) ) {
		$checkout = ttp_get_checkout_instance();
		if ( $checkout && method_exists( $checkout, 'trigger_tcy_registration' ) ) {
			$checkout->trigger_tcy_registration( $order_id );
		}
	}

	/**
	 * Fires after a gateway-paid order is promoted from cancelled/failed.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 */
	do_action( 'ttp_gateway_paid_order_promoted', $order_id, $order );

	return true;
}

/**
 * TCY JSON uses success as int 1, string "1", or boolean true depending on ERP version.
 *
 * @param array|mixed $response Decoded API body.
 * @return bool
 */
function ttp_tcy_api_is_success( $response ) {
	if ( ! is_array( $response ) ) {
		return false;
	}
	if ( array_key_exists( 'success', $response ) ) {
		$s = $response['success'];
		if ( true === $s || 1 === $s || '1' === $s ) {
			return true;
		}
		if ( is_numeric( $s ) && (int) $s === 1 ) {
			return true;
		}
	}
	if ( ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
		return ttp_tcy_api_is_success( $response['data'] );
	}
	foreach ( [ 'status', 'Status', 'result' ] as $key ) {
		if ( empty( $response[ $key ] ) || ! is_scalar( $response[ $key ] ) ) {
			continue;
		}
		$st = strtolower( (string) $response[ $key ] );
		if ( in_array( $st, [ 'ok', 'success', 'true', '1' ], true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * TCY often returns success=0 when the course is already on the account — treat as OK for sync.
 *
 * @param array|mixed $response Decoded API body.
 * @return bool
 */
function ttp_tcy_response_indicates_already_enrolled( $response ) {
	if ( ! is_array( $response ) ) {
		return false;
	}
	if ( ttp_tcy_response_is_pack_conflict( $response ) ) {
		return true;
	}
	$blob = strtolower( wp_json_encode( $response ) );
	if ( $blob === '' ) {
		return false;
	}
	$needles = apply_filters(
		'ttp_tcy_already_enrolled_message_needles',
		[ 'already', 'exist', 'duplicate', 'assigned', 'enrolled', 'added earlier' ]
	);
	foreach ( (array) $needles as $needle ) {
		if ( is_string( $needle ) && $needle !== '' && strpos( $blob, strtolower( $needle ) ) !== false ) {
			return true;
		}
	}
	return false;
}

/**
 * Classify add_course API result for admin + repair loops.
 *
 * @param array|mixed $response Decoded API body.
 * @return string success|already|failed
 */
function ttp_tcy_add_course_outcome( $response ) {
	if ( ttp_tcy_api_is_success( $response ) ) {
		return 'success';
	}
	if ( ttp_tcy_response_is_pack_conflict( $response ) ) {
		return 'pack_conflict';
	}
	if ( ttp_tcy_response_indicates_already_enrolled( $response ) ) {
		return 'already';
	}
	return 'failed';
}

/**
 * Short debug string for admin (full TCY JSON, truncated).
 *
 * @param array|mixed $response API body.
 * @return string
 */
function ttp_tcy_response_debug_summary( $response ) {
	if ( ! is_array( $response ) ) {
		return '';
	}
	$json = wp_json_encode( $response );
	if ( ! is_string( $json ) ) {
		return '';
	}
	return sanitize_text_field( substr( $json, 0, 500 ) );
}

/**
 * True when TCY explicitly returned success = 0 (e.g. course already on account).
 *
 * @param array|mixed $response Decoded API body.
 * @return bool
 */
function ttp_tcy_api_is_explicit_failure( $response ) {
	if ( ! is_array( $response ) || ! array_key_exists( 'success', $response ) ) {
		return false;
	}
	$s = $response['success'];
	return $s === 0 || $s === '0' || $s === false;
}

/**
 * Legacy pack IDs stored as category_id before v2.3.2 — migrate to MBA Entrance 100000.
 *
 * @param string $category_id Stored category id.
 * @return string
 */
function ttp_tcy_normalize_api_category_id( $category_id ) {
	$category_id = sanitize_text_field( (string) $category_id );
	$legacy_pack = array( '33599', '33605', '33598', '33604' );
	if ( in_array( $category_id, $legacy_pack, true ) ) {
		return '100000';
	}
	if ( $category_id === '' ) {
		return '100000';
	}
	return $category_id;
}

/**
 * TCY register rejects empty or invalid mobiles. Normalize to 10 digits (India-style).
 *
 * @param string $raw Phone from checkout.
 * @return string
 */
/**
 * Real customer name for TCY ERP (never pass a raw email as full_name).
 *
 * @param int            $user_id WP user ID.
 * @param WC_Order|null  $order   Optional order for billing fallback.
 * @return string
 */
function ttp_resolve_customer_full_name( $user_id, $order = null ) {
	$user_id = (int) $user_id;
	$name    = '';

	if ( $user_id > 0 && function_exists( 'ttp_user_get_name_parts' ) ) {
		$parts = ttp_user_get_name_parts( $user_id );
		$name  = trim( $parts['first_name'] . ' ' . $parts['last_name'] );
		if ( '' !== $name && ! is_email( $name ) ) {
			return function_exists( 'ttp_sanitize_tcy_full_name' ) ? ttp_sanitize_tcy_full_name( $name ) : $name;
		}
	}

	if ( $user_id > 0 ) {
		global $wpdb;
		$student_name = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT full_name FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d LIMIT 1",
				$user_id
			)
		);
		if ( '' !== $student_name && ! is_email( $student_name ) ) {
			return function_exists( 'ttp_sanitize_tcy_full_name' ) ? ttp_sanitize_tcy_full_name( $student_name ) : $student_name;
		}
		$user = get_userdata( $user_id );
		if ( $user instanceof WP_User && ! is_email( $user->display_name ) ) {
			$dn = trim( (string) $user->display_name );
			if ( '' !== $dn ) {
				return function_exists( 'ttp_sanitize_tcy_full_name' ) ? ttp_sanitize_tcy_full_name( $dn ) : $dn;
			}
		}
	}

	if ( $order instanceof WC_Order ) {
		$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
		if ( '' !== $name && ! is_email( $name ) ) {
			return function_exists( 'ttp_sanitize_tcy_full_name' ) ? ttp_sanitize_tcy_full_name( $name ) : $name;
		}
		$email = sanitize_email( (string) $order->get_billing_email() );
		if ( '' !== $email && is_email( $email ) && function_exists( 'ttp_sanitize_tcy_full_name' ) ) {
			$derived = ttp_sanitize_tcy_full_name( $email );
			if ( '' !== $derived && 'Student' !== $derived ) {
				return $derived;
			}
		}
	}

	return 'Student';
}

function ttp_tcy_normalize_mobile_for_api( $raw ) {
	$digits = preg_replace( '/\D+/', '', (string) $raw );
	if ( strlen( $digits ) >= 10 ) {
		return substr( $digits, -10 );
	}
	if ( strlen( $digits ) > 0 ) {
		return str_pad( $digits, 10, '0', STR_PAD_RIGHT );
	}
	$fallback = (string) apply_filters( 'ttp_tcy_default_mobile_number', '9999999999' );
	return preg_match( '/^\d{10}$/', $fallback ) ? $fallback : '9999999999';
}

/**
 * Extract TCY user id from register (and similar) responses; structure varies by ERP build.
 *
 * @param array|mixed $response Decoded JSON.
 * @return string Non-empty id or ''.
 */
function ttp_tcy_extract_register_user_id( $response ) {
	if ( ! is_array( $response ) ) {
		return '';
	}
	$keys = apply_filters(
		'ttp_tcy_register_user_id_keys',
		array( 'user_id', 'tcy_user_id', 'userid', 'student_id', 'erp_user_id', 'erp_userid', 'UserId', 'USER_ID', 'id' )
	);
	foreach ( $keys as $key ) {
		if ( ! empty( $response[ $key ] ) || ( isset( $response[ $key ] ) && (string) $response[ $key ] === '0' ) ) {
			$v = (string) $response[ $key ];
			if ( $v !== '' ) {
				return function_exists( 'ttp_sanitize_tcy_user_id' ) ? ttp_sanitize_tcy_user_id( $v ) : sanitize_text_field( $v );
			}
		}
	}
	if ( ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $response['data'][ $key ] ) || ( isset( $response['data'][ $key ] ) && (string) $response['data'][ $key ] === '0' ) ) {
				$v = (string) $response['data'][ $key ];
				if ( $v !== '' ) {
					return function_exists( 'ttp_sanitize_tcy_user_id' ) ? ttp_sanitize_tcy_user_id( $v ) : sanitize_text_field( $v );
				}
			}
		}
		if ( isset( $response['data'][0] ) && is_array( $response['data'][0] ) ) {
			foreach ( $keys as $key ) {
				if ( ! empty( $response['data'][0][ $key ] ) ) {
					$v = (string) $response['data'][0][ $key ];
					return function_exists( 'ttp_sanitize_tcy_user_id' ) ? ttp_sanitize_tcy_user_id( $v ) : sanitize_text_field( $v );
				}
			}
		}
	}
	return '';
}

/**
 * Preserve TCY user_id from register (base64 like ODEzOTgxOA==); do not strip trailing =.
 *
 * @param string $tcy_user_id Raw id from TCY JSON.
 * @return string
 */
function ttp_sanitize_tcy_user_id( $tcy_user_id ) {
	$id = trim( (string) $tcy_user_id );
	if ( $id === '' ) {
		return '';
	}
	// Only allow safe token chars used by TCY ERP ids.
	return preg_match( '/^[A-Za-z0-9+\/=_-]+$/', $id ) ? $id : sanitize_text_field( $id );
}

/**
 * Lookup existing TCY account by billing email (one ERP user per email).
 *
 * @param string $billing_email Email.
 * @return string TCY user_id or ''.
 */
function ttp_lookup_tcy_user_id_by_email( $billing_email ) {
	$billing_email = sanitize_email( (string) $billing_email );
	if ( $billing_email === '' ) {
		return '';
	}
	global $wpdb;
	$from_students = (string) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT tcy_user_id FROM {$wpdb->prefix}ttp_students WHERE email = %s AND tcy_user_id IS NOT NULL AND tcy_user_id <> '' ORDER BY id DESC LIMIT 1",
			$billing_email
		)
	);
	if ( $from_students !== '' ) {
		return ttp_sanitize_tcy_user_id( $from_students );
	}
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return '';
	}
	$statuses = function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : [ 'processing', 'completed', 'on-hold' ];
	$orders   = wc_get_orders(
		[
			'billing_email' => $billing_email,
			'status'        => $statuses,
			'limit'         => 20,
			'orderby'       => 'date',
			'order'         => 'DESC',
		]
	);
	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$meta = (string) $order->get_meta( '_ttp_tcy_user_id', true );
		if ( $meta !== '' ) {
			return ttp_sanitize_tcy_user_id( $meta );
		}
		$from_map = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT tcy_user_id FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND tcy_user_id IS NOT NULL AND tcy_user_id <> '' ORDER BY id DESC LIMIT 1",
				(int) $order->get_id()
			)
		);
		if ( $from_map !== '' ) {
			return ttp_sanitize_tcy_user_id( $from_map );
		}
	}
	return '';
}

/**
 * Every TCY user_id ever stored for this customer (split accounts = missing courses on login).
 *
 * @param int    $wp_user_id    WP user id.
 * @param string $billing_email Billing email.
 * @return string[] Sanitized unique ids.
 */
function ttp_collect_all_tcy_user_ids_for_customer( $wp_user_id = 0, $billing_email = '' ) {
	$wp_user_id    = (int) $wp_user_id;
	$billing_email = sanitize_email( (string) $billing_email );
	$found         = [];

	$push = static function ( $id ) use ( &$found ) {
		$id = ttp_sanitize_tcy_user_id( (string) $id );
		if ( $id !== '' ) {
			$found[ $id ] = true;
		}
	};

	if ( $wp_user_id > 0 ) {
		$push( get_user_meta( $wp_user_id, '_ttp_tcy_user_id', true ) );
		global $wpdb;
		$student = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT tcy_user_id FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d AND tcy_user_id <> '' ORDER BY id DESC LIMIT 1",
				$wp_user_id
			)
		);
		$push( $student );
	}

	if ( $billing_email !== '' && function_exists( 'wc_get_orders' ) ) {
		$statuses = apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] );
		$orders   = wc_get_orders(
			[
				'billing_email' => $billing_email,
				'status'        => $statuses,
				'limit'         => 50,
				'orderby'       => 'date',
				'order'         => 'DESC',
			]
		);
		if ( $wp_user_id > 0 ) {
			$user_orders = wc_get_orders(
				[
					'customer_id' => $wp_user_id,
					'status'      => $statuses,
					'limit'       => 50,
					'orderby'     => 'date',
					'order'       => 'DESC',
				]
			);
			$orders = array_merge( is_array( $user_orders ) ? $user_orders : [], is_array( $orders ) ? $orders : [] );
		}
		$seen_oid = [];
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$oid = (int) $order->get_id();
			if ( isset( $seen_oid[ $oid ] ) ) {
				continue;
			}
			$seen_oid[ $oid ] = true;
			$push( $order->get_meta( '_ttp_tcy_user_id', true ) );
			global $wpdb;
			$map_rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT tcy_user_id FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND tcy_user_id <> ''",
					$oid
				)
			);
			if ( is_array( $map_rows ) ) {
				foreach ( $map_rows as $mid ) {
					$push( $mid );
				}
			}
		}
	}

	$push( ttp_lookup_tcy_user_id_by_email( $billing_email ) );

	return array_keys( $found );
}

/**
 * One TCY ERP user per customer — prefer latest paid order meta (login must match add_course target).
 *
 * @param int    $wp_user_id    WP user id.
 * @param string $billing_email Billing email.
 * @return string
 */
function ttp_get_canonical_tcy_user_id( $wp_user_id = 0, $billing_email = '' ) {
	$wp_user_id    = (int) $wp_user_id;
	$billing_email = sanitize_email( (string) $billing_email );

	if ( function_exists( 'wc_get_orders' ) && $billing_email !== '' ) {
		$statuses = apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] );
		$args     = [
			'status'  => $statuses,
			'limit'   => 1,
			'orderby' => 'date',
			'order'   => 'DESC',
		];
		if ( $wp_user_id > 0 ) {
			$args['customer_id'] = $wp_user_id;
		} else {
			$args['billing_email'] = $billing_email;
		}
		$latest = wc_get_orders( $args );
		if ( ! empty( $latest[0] ) && $latest[0] instanceof WC_Order ) {
			$from_order = ttp_sanitize_tcy_user_id( (string) $latest[0]->get_meta( '_ttp_tcy_user_id', true ) );
			if ( $from_order !== '' ) {
				return $from_order;
			}
		}
	}

	if ( $wp_user_id > 0 ) {
		$meta = ttp_sanitize_tcy_user_id( (string) get_user_meta( $wp_user_id, '_ttp_tcy_user_id', true ) );
		if ( $meta !== '' ) {
			return $meta;
		}
		global $wpdb;
		$row = (string) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT tcy_user_id FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d AND tcy_user_id IS NOT NULL AND tcy_user_id <> '' ORDER BY id DESC LIMIT 1",
				$wp_user_id
			)
		);
		if ( $row !== '' ) {
			return ttp_sanitize_tcy_user_id( $row );
		}
	}

	$all = ttp_collect_all_tcy_user_ids_for_customer( $wp_user_id, $billing_email );
	return ! empty( $all ) ? $all[0] : '';
}

/**
 * Unify all TCY ids for a customer onto one canonical id (wp meta + orders + students).
 *
 * @param string $canonical     Target TCY user id.
 * @param int    $wp_user_id    WP user id.
 * @param string $billing_email Billing email.
 * @return void
 */
function ttp_tcy_unify_customer_to_canonical_id( $canonical, $wp_user_id = 0, $billing_email = '' ) {
	$canonical = ttp_sanitize_tcy_user_id( (string) $canonical );
	if ( $canonical === '' ) {
		return;
	}
	$wp_user_id = (int) $wp_user_id;
	if ( $wp_user_id > 0 ) {
		update_user_meta( $wp_user_id, '_ttp_tcy_user_id', $canonical );
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'ttp_students',
			[ 'tcy_user_id' => $canonical ],
			[ 'wp_user_id' => $wp_user_id ],
			[ '%s' ],
			[ '%d' ]
		);
	}
	$billing_email = sanitize_email( (string) $billing_email );
	if ( $billing_email !== '' && function_exists( 'wc_get_orders' ) ) {
		$statuses = apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] );
		$orders   = wc_get_orders(
			[
				'billing_email' => $billing_email,
				'status'        => $statuses,
				'limit'         => -1,
			]
		);
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$order->update_meta_data( '_ttp_tcy_user_id', $canonical );
				$order->save();
			}
		}
	}
	global $wpdb;
	if ( $billing_email !== '' && function_exists( 'wc_get_orders' ) ) {
		$statuses = apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] );
		$orders   = wc_get_orders(
			[
				'billing_email' => $billing_email,
				'status'        => $statuses,
				'limit'         => -1,
			]
		);
		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$wpdb->update(
				$wpdb->prefix . 'ttp_order_mapping',
				[ 'tcy_user_id' => $canonical ],
				[ 'order_id' => (int) $order->get_id() ],
				[ '%s' ],
				[ '%d' ]
			);
		}
	}
}

/**
 * Admin/support: orders, line items, resolved course_ids, and all TCY user_ids for one email.
 *
 * @param int    $wp_user_id    WP user id.
 * @param string $billing_email Billing email.
 * @return array<string, mixed>
 */
function ttp_tcy_diagnose_customer_enrollment( $wp_user_id = 0, $billing_email = '' ) {
	$wp_user_id    = (int) $wp_user_id;
	$billing_email = sanitize_email( (string) $billing_email );
	$diag          = [
		'email'           => $billing_email,
		'wp_user_id'      => $wp_user_id,
		'canonical_tcy'   => ttp_get_canonical_tcy_user_id( $wp_user_id, $billing_email ),
		'all_tcy_ids'     => ttp_collect_all_tcy_user_ids_for_customer( $wp_user_id, $billing_email ),
		'orders'          => [],
		'pairs'           => [],
		'distinct_course' => [],
	];

	if ( ! function_exists( 'wc_get_orders' ) || $billing_email === '' ) {
		return $diag;
	}

	$statuses = apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] );
	$args     = [
		'billing_email' => $billing_email,
		'status'        => $statuses,
		'limit'         => 20,
		'orderby'       => 'date',
		'order'         => 'DESC',
	];
	if ( $wp_user_id > 0 ) {
		unset( $args['billing_email'] );
		$args['customer_id'] = $wp_user_id;
	}
	$orders = wc_get_orders( $args );
	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$lines = [];
		foreach ( $order->get_items() as $item ) {
			$pid  = (int) $item->get_product_id();
			$ids  = function_exists( 'ttp_get_tcy_ids_for_product' ) ? ttp_get_tcy_ids_for_product( $pid, false ) : [ 'course_id' => '', 'category_id' => '' ];
			$cid  = isset( $ids['course_id'] ) ? (string) $ids['course_id'] : '';
			$lines[] = [
				'name'       => trim( (string) $item->get_name() ),
				'product_id' => $pid,
				'course_id'  => $cid,
			];
			if ( $cid !== '' ) {
				$diag['distinct_course'][ $cid ] = true;
			}
		}
		$diag['orders'][] = [
			'order_id'    => (int) $order->get_id(),
			'status'      => $order->get_status(),
			'tcy_user_id' => ttp_sanitize_tcy_user_id( (string) $order->get_meta( '_ttp_tcy_user_id', true ) ),
			'lines'       => $lines,
		];
	}

	$all_pairs              = ttp_tcy_collect_purchased_course_pairs( $wp_user_id, $billing_email );
	$filtered               = ttp_tcy_filter_pairs_one_per_pack( $all_pairs );
	$diag['pairs']          = $all_pairs;
	$diag['pairs_tcy_sync'] = ttp_tcy_should_limit_one_course_per_pack() ? $filtered['sync'] : $all_pairs;
	$diag['pairs_skipped']  = ttp_tcy_should_limit_one_course_per_pack() ? $filtered['skipped'] : [];
	$diag['tcy_max_courses'] = ttp_tcy_should_limit_one_course_per_pack() ? count( $filtered['sync'] ) : count( $all_pairs );
	$diag['invalid_course_ids'] = [];
	foreach ( $all_pairs as $pair ) {
		if ( ! is_array( $pair ) || empty( $pair['course_id'] ) ) {
			continue;
		}
		$cid = (string) $pair['course_id'];
		if ( ! empty( $pair['syncable'] ) ) {
			continue;
		}
		if ( in_array( $cid, ttp_tcy_blocked_product_ids_as_course(), true ) || ! ttp_tcy_get_course_api_profile( $cid, (string) ( $pair['line_name'] ?? '' ) ) ) {
			$diag['invalid_course_ids'][] = $cid;
		}
	}
	$diag['invalid_course_ids'] = array_values( array_unique( $diag['invalid_course_ids'] ) );
	$diag['pack_note']      = __( 'add_course loops every purchased course_id with category_id 100000 and sub_cat (33599 CET / 33605 NMAT). Stale product meta (e.g. 38053) is remapped from the order line title to 90069–90073 when you run sync.', 'ttp-woocommerce' );
	$diag['distinct_course'] = array_keys( $diag['distinct_course'] );
	sort( $diag['distinct_course'] );

	return $diag;
}

/**
 * Pick the best line item for TCY register (highest plan tier, not cart sort order).
 *
 * @param array<int, array{course_id: string, category_id: string, product_id: int}> $line_items Line items.
 * @return array{course_id: string, category_id: string, product_id: int}|null
 */
function ttp_tcy_pick_register_line_item( array $line_items ) {
	if ( empty( $line_items ) ) {
		return null;
	}
	$priority = apply_filters(
		'ttp_tcy_register_course_priority',
		[
			'90073' => 100,
			'90072' => 90,
			'90071' => 80,
			'90069' => 70,
			'90070' => 10,
		]
	);
	$best     = null;
	$best_p   = -1;
	foreach ( $line_items as $row ) {
		$cid = isset( $row['course_id'] ) ? (string) $row['course_id'] : '';
		$p   = isset( $priority[ $cid ] ) ? (int) $priority[ $cid ] : 50;
		if ( $p > $best_p ) {
			$best_p = $p;
			$best   = $row;
		}
	}
	return $best ? $best : $line_items[0];
}

/**
 * MBA CET catalog course_ids accepted by add_course (90069–90073).
 *
 * @return string[]
 */
function ttp_tcy_known_mba_course_ids() {
	return array_values(
		array_map(
			'sanitize_text_field',
			(array) apply_filters(
				'ttp_tcy_known_mba_course_ids',
				[ '90069', '90070', '90071', '90072', '90073', '90334', '90315' ]
			)
		)
	);
}

/**
 * TCY register/add_course reject legacy JBIMS ids (90235/90238) — canonical row is 90334.
 *
 * @param string $course_id TCY course id from Woo/meta.
 * @param string $label     Product or line title (optional).
 * @return string
 */
function ttp_tcy_canonical_course_id( $course_id, $label = '' ) {
	$course_id = sanitize_text_field( (string) $course_id );
	$legacy    = (array) apply_filters( 'ttp_tcy_legacy_invalid_course_ids', [ '90235', '90238' ] );
	if ( in_array( $course_id, $legacy, true ) ) {
		return '90334';
	}
	return $course_id;
}

/**
 * TCY register API already assigns the purchased course — add_course must not repeat it.
 *
 * @param int    $order_id  Woo order id.
 * @param string $course_id TCY course id.
 * @return bool
 */
function ttp_tcy_order_course_enrolled_via_register( $order_id, $course_id ) {
	$order_id  = (int) $order_id;
	$course_id = function_exists( 'ttp_tcy_canonical_course_id' )
		? ttp_tcy_canonical_course_id( (string) $course_id )
		: sanitize_text_field( (string) $course_id );
	if ( $order_id < 1 || $course_id === '' ) {
		return false;
	}
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	$reg_course = sanitize_text_field( (string) $order->get_meta( '_ttp_tcy_register_course_id' ) );
	if ( $reg_course !== '' ) {
		$reg_course = function_exists( 'ttp_tcy_canonical_course_id' )
			? ttp_tcy_canonical_course_id( $reg_course )
			: $reg_course;
		return $reg_course === $course_id;
	}
	global $wpdb;
	$table = $wpdb->prefix . 'ttp_api_logs';
	if ( ! $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) ) {
		return false;
	}
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT request_data FROM {$table} WHERE order_id = %d AND action = 'register' AND status = 'success' ORDER BY id DESC LIMIT 1",
			$order_id
		)
	);
	if ( ! $row || empty( $row->request_data ) ) {
		return false;
	}
	$req = json_decode( (string) $row->request_data, true );
	if ( ! is_array( $req ) || empty( $req['course_id'] ) ) {
		return false;
	}
	$reg_course = function_exists( 'ttp_tcy_canonical_course_id' )
		? ttp_tcy_canonical_course_id( (string) $req['course_id'] )
		: sanitize_text_field( (string) $req['course_id'] );
	if ( $reg_course === $course_id && 'yes' !== (string) $order->get_meta( '_ttp_tcy_fresh_register' ) ) {
		$order->update_meta_data( '_ttp_tcy_fresh_register', 'yes' );
		$order->update_meta_data( '_ttp_tcy_register_course_id', $reg_course );
		$order->save();
	}
	return $reg_course === $course_id;
}

/**
 * True when course_id is one of the five MBA CET API course_ids.
 *
 * @param string $course_id TCY course id.
 * @return bool
 */
function ttp_tcy_is_mba_catalog_course_id( $course_id ) {
	$course_id = sanitize_text_field( (string) $course_id );
	return $course_id !== '' && in_array( $course_id, ttp_tcy_known_mba_course_ids(), true );
}

/**
 * TCY get_courses mapping: course_id → category_id + sub_cat (TCY Product_id).
 * CUET uses category 902346, not MBA 100000.
 *
 * @return array<string, array{category_id: string, sub_cat: string, label?: string}>
 */
function ttp_tcy_get_course_api_profiles() {
	$mba = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE : '100000';
	$map = [
		'90069' => [ 'category_id' => $mba, 'sub_cat' => '33599', 'label' => 'CET Elite' ],
		'90070' => [ 'category_id' => $mba, 'sub_cat' => '33599', 'label' => 'CET Solo' ],
		'90072' => [ 'category_id' => $mba, 'sub_cat' => '33599', 'label' => 'CET Elite Mentorship' ],
		'90071' => [ 'category_id' => $mba, 'sub_cat' => '33605', 'label' => 'NMAT SNAP Elite' ],
		'90073' => [ 'category_id' => $mba, 'sub_cat' => '33605', 'label' => 'NMAT SNAP Elite Mentorship' ],
		'90315' => [ 'category_id' => '902346', 'sub_cat' => '38072', 'label' => 'CUET UG' ],
		'90334' => [ 'category_id' => $mba, 'sub_cat' => '38081', 'label' => 'JBIMS MFIN MHRD Bootcamp' ],
		'90238' => [ 'category_id' => $mba, 'sub_cat' => '38033', 'label' => 'JBIMS MFIN MHRD Bootcamp Elite (legacy id → 90334)' ],
		'90235' => [ 'category_id' => $mba, 'sub_cat' => '38081', 'label' => 'JBIMS MFIN MHRD Bootcamp Legacy' ],
	];
	return (array) apply_filters( 'ttp_tcy_course_api_profiles', $map );
}

/**
 * API profile for one course_id (from registry or line title).
 *
 * @param string $course_id TCY course id.
 * @param string $label     Order line or product title.
 * @return array{category_id: string, sub_cat: string, label: string}|null
 */
function ttp_tcy_get_course_api_profile( $course_id, $label = '' ) {
	$course_id = ttp_tcy_canonical_course_id( $course_id, $label );
	$profiles  = ttp_tcy_get_course_api_profiles();
	if ( $course_id !== '' && isset( $profiles[ $course_id ] ) ) {
		$profile = $profiles[ $course_id ];
		if ( '90334' === $course_id && preg_match( '/elite/i', (string) $label ) ) {
			$profile['sub_cat'] = '38033';
			$profile['label']   = 'JBIMS MFIN MHRD Bootcamp Elite';
		}
		return $profile;
	}
	$n = strtolower( trim( (string) $label ) );
	if ( $n !== '' && preg_match( '/\bcuet\b/i', $n ) ) {
		return $profiles['90315'] ?? null;
	}
	if ( $n !== '' && preg_match( '/jbims|mfin|mhrd|bootcamp/i', $n ) ) {
		if ( preg_match( '/elite/i', $n ) ) {
			return [ 'category_id' => class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE : '100000', 'sub_cat' => '38033', 'label' => 'JBIMS MFIN MHRD Bootcamp Elite' ];
		}
		return $profiles['90334'] ?? null;
	}
	return null;
}

/**
 * WooCommerce meta values that are TCY Product_id, not course_id (add_course error 006).
 *
 * @return string[]
 */
function ttp_tcy_blocked_product_ids_as_course() {
	return array_values(
		array_map(
			'sanitize_text_field',
			(array) apply_filters(
				'ttp_tcy_blocked_product_ids_as_course',
				[ '38053' ]
			)
		)
	);
}

/**
 * Remap mis-stored TCY pack / Product_id to real course_id (fixes add_course error 006).
 *
 * @param string $course_id   Stored value.
 * @param string $label       Product or line title.
 * @param bool   $repair_meta Update Woo product meta.
 * @param int    $product_id  Woo product id.
 * @return string Correct course_id or original when no fix.
 */
function ttp_tcy_fix_misstored_pack_as_course_id( $course_id, $label = '', $repair_meta = false, $product_id = 0 ) {
	$course_id  = sanitize_text_field( (string) $course_id );
	$product_id = (int) $product_id;
	if ( $course_id === '' || ! class_exists( 'TTP_Catalog_Seed' ) ) {
		return $course_id;
	}
	$def = TTP_Catalog_Seed::get_definition_for_misstored_course_id( $course_id, $label );
	if ( ! $def || empty( $def['tcy_course_id'] ) ) {
		return $course_id;
	}
	$fixed = sanitize_text_field( (string) $def['tcy_course_id'] );
	if ( $fixed === $course_id ) {
		return $course_id;
	}
	if ( $repair_meta && $product_id > 0 ) {
		TTP_Catalog_Seed::apply_tcy_meta_from_definition( $product_id, $def );
	}
	return $fixed;
}

/**
 * Whether add_course should run for this course_id.
 *
 * @param string $course_id TCY course id.
 * @param string $label     Line / product title.
 * @return bool
 */
function ttp_tcy_is_syncable_course_id( $course_id, $label = '' ) {
	$course_id = sanitize_text_field( (string) $course_id );
	if ( $course_id === '' || in_array( $course_id, ttp_tcy_blocked_product_ids_as_course(), true ) ) {
		return false;
	}
	if ( ttp_tcy_get_course_api_profile( $course_id, $label ) ) {
		return true;
	}
	if ( ttp_tcy_is_mba_catalog_course_id( $course_id ) ) {
		return true;
	}
	return (bool) apply_filters( 'ttp_tcy_is_syncable_course_id', false, $course_id, $label );
}

/**
 * Resolve course_id, category_id, sub_cat for one order line (TCY get_courses–aligned).
 *
 * @param string $course_id   Stored course id from Woo/meta.
 * @param string $label       Line item title.
 * @param int    $product_id  Woo product id.
 * @param bool   $repair_meta Update product meta when catalog/heuristic matches.
 * @return array{course_id: string, category_id: string, sub_cat: string, syncable: bool, stored_course_id: string, skip_reason: string}
 */
function ttp_tcy_resolve_line_course_api( $course_id, $label = '', $product_id = 0, $repair_meta = false ) {
	$stored      = sanitize_text_field( (string) $course_id );
	$course_id   = function_exists( 'ttp_tcy_canonical_course_id' ) ? ttp_tcy_canonical_course_id( $stored, $label ) : $stored;
	$product_id  = (int) $product_id;
	$skip_reason = '';

	if ( in_array( $course_id, ttp_tcy_blocked_product_ids_as_course(), true ) ) {
		return [
			'course_id'        => $course_id,
			'category_id'      => '',
			'sub_cat'          => '',
			'syncable'         => false,
			'stored_course_id' => $stored,
			'skip_reason'      => __( '38053 is a TCY Product_id, not a course_id — set the real course_id on the Woo product (TCY → Courses / Fetch Courses).', 'ttp-woocommerce' ),
		];
	}

	$course_id = ttp_tcy_fix_misstored_pack_as_course_id( $course_id, $label, $repair_meta, $product_id );

	if ( $course_id !== '' && ! ttp_tcy_is_mba_catalog_course_id( $course_id ) ) {
		$remapped = ttp_tcy_remap_unknown_course_id( $course_id, $label, $repair_meta, $product_id );
		if ( $remapped !== $course_id ) {
			$course_id = $remapped;
		}
	}

	$n = strtolower( trim( (string) $label ) );
	if ( preg_match( '/\bcuet\b/i', $n ) ) {
		$course_id = '90315';
	}

	$profile = ttp_tcy_get_course_api_profile( $course_id, $label );
	if ( $profile ) {
		return [
			'course_id'        => $course_id,
			'category_id'      => sanitize_text_field( (string) $profile['category_id'] ),
			'sub_cat'          => sanitize_text_field( (string) ( $profile['sub_cat'] ?? '' ) ),
			'syncable'         => true,
			'stored_course_id' => ( $stored !== '' && $stored !== $course_id ) ? $stored : '',
			'skip_reason'      => '',
		];
	}

	if ( ttp_tcy_is_mba_catalog_course_id( $course_id ) ) {
		$mba = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE : '100000';
		return [
			'course_id'        => $course_id,
			'category_id'      => $mba,
			'sub_cat'          => ttp_tcy_pack_id_for_course_id( $course_id ),
			'syncable'         => true,
			'stored_course_id' => ( $stored !== '' && $stored !== $course_id ) ? $stored : '',
			'skip_reason'      => '',
		];
	}

	if ( $course_id !== '' ) {
		$skip_reason = sprintf(
			/* translators: %s: course id */
			__( 'course_id %s is not in TCY get_courses for this client — fix Woo product TCY Course ID.', 'ttp-woocommerce' ),
			$course_id
		);
	}

	return [
		'course_id'        => $course_id,
		'category_id'      => '',
		'sub_cat'          => '',
		'syncable'         => false,
		'stored_course_id' => $stored,
		'skip_reason'      => $skip_reason,
	];
}

/**
 * Replace stale Woo meta (e.g. TCY Product_id 38053) using order/product title.
 *
 * @param string $course_id   Stored course id.
 * @param string $label       Product or line item title.
 * @param bool   $repair_meta Write catalog ids back to the product when possible.
 * @param int    $product_id  Woo product id (optional).
 * @return string
 */
function ttp_tcy_remap_unknown_course_id( $course_id, $label = '', $repair_meta = false, $product_id = 0 ) {
	$course_id  = sanitize_text_field( (string) $course_id );
	$product_id = (int) $product_id;
	if ( ttp_tcy_is_mba_catalog_course_id( $course_id ) ) {
		return $course_id;
	}
	$fixed_pack = ttp_tcy_fix_misstored_pack_as_course_id( $course_id, $label, $repair_meta, $product_id );
	if ( $fixed_pack !== $course_id ) {
		return $fixed_pack;
	}
	if ( ! class_exists( 'TTP_Catalog_Seed' ) || trim( (string) $label ) === '' ) {
		return $course_id;
	}
	$def = TTP_Catalog_Seed::get_definition_from_title_heuristic( $label );
	if ( ! $def || empty( $def['tcy_course_id'] ) ) {
		return $course_id;
	}
	if ( $repair_meta && $product_id > 0 ) {
		TTP_Catalog_Seed::apply_tcy_meta_from_definition( $product_id, $def );
	}
	return sanitize_text_field( (string) $def['tcy_course_id'] );
}

/**
 * Infer sub_cat (33599 / 33605) when pack map has no entry for course_id.
 *
 * @param string $course_id   TCY course id.
 * @param int    $product_id  Woo product id.
 * @param string $label       Product or line title.
 * @return string
 */
function ttp_tcy_infer_sub_cat_for_course( $course_id, $product_id = 0, $label = '' ) {
	$pack = ttp_tcy_pack_id_for_course_id( $course_id );
	if ( $pack !== '' ) {
		return $pack;
	}
	$product_id = (int) $product_id;
	if ( $product_id > 0 ) {
		$src  = ttp_tcy_meta_source_product_id( $product_id );
		$meta = (string) get_post_meta( $src, '_ttp_tcy_product_pack_id', true );
		if ( $meta !== '' && $meta !== '0' ) {
			return sanitize_text_field( $meta );
		}
	}
	$n = strtolower( trim( (string) $label ) );
	if ( $n === '' ) {
		return '';
	}
	if ( preg_match( '/non\s*cat|all[\s-]*in[\s-]*one|combo\s*pack|snap|nmat/i', $n ) ) {
		return '33605';
	}
	if ( preg_match( '/cet[\s-]*mh|test\s*series|topic\s*wise/i', $n ) ) {
		return '33599';
	}
	if ( preg_match( '/\bcuet\b/i', $n ) ) {
		return '38072';
	}
	return '';
}

/**
 * TCY product pack id for a course_id (one active course per pack per student).
 *
 * @param string $course_id TCY course id.
 * @return string Pack id e.g. 33599 or 33605.
 */
function ttp_tcy_pack_id_for_course_id( $course_id ) {
	$course_id = sanitize_text_field( (string) $course_id );
	$map       = apply_filters(
		'ttp_tcy_course_pack_map',
		[
			'90069' => '33599',
			'90070' => '33599',
			'90072' => '33599',
			'90071' => '33605',
			'90073' => '33605',
		]
	);
	return isset( $map[ $course_id ] ) ? sanitize_text_field( (string) $map[ $course_id ] ) : '';
}

/**
 * All TCY course_ids that share a product pack (33599 / 33605).
 *
 * @param string $pack_id Pack id.
 * @return string[]
 */
function ttp_tcy_course_ids_for_pack( $pack_id ) {
	$pack_id = sanitize_text_field( (string) $pack_id );
	$map     = apply_filters(
		'ttp_tcy_course_pack_map',
		[
			'90069' => '33599',
			'90070' => '33599',
			'90072' => '33599',
			'90071' => '33605',
			'90073' => '33605',
		]
	);
	$out = [];
	foreach ( $map as $cid => $pack ) {
		if ( (string) $pack === $pack_id ) {
			$out[] = (string) $cid;
		}
	}
	return $out;
}

/**
 * Try to clear other tiers in the same TCY pack so a different course_id can be assigned.
 *
 * @param string $tcy_user_id       TCY user id.
 * @param string $target_course_id Course to assign after removals.
 * @param int    $order_id         Order id for logs.
 * @return void
 */
function ttp_tcy_remove_other_courses_in_pack( $tcy_user_id, $target_course_id, $order_id = 0 ) {
	$tcy_user_id       = ttp_sanitize_tcy_user_id( (string) $tcy_user_id );
	$target_course_id  = sanitize_text_field( (string) $target_course_id );
	$pack              = ttp_tcy_pack_id_for_course_id( $target_course_id );
	if ( $tcy_user_id === '' || $pack === '' ) {
		return;
	}
	if ( function_exists( 'ttp_tcy_is_fast_access_request' ) && ttp_tcy_is_fast_access_request() ) {
		return;
	}
	if ( ! ttp_tcy_should_remove_siblings_before_add_course() ) {
		return;
	}
	$api = new TTP_TCY_API();
	$mba = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE : '100000';
	foreach ( ttp_tcy_course_ids_for_pack( $pack ) as $cid ) {
		if ( $cid === $target_course_id ) {
			continue;
		}
		$api->remove_course( $tcy_user_id, $cid, $mba, $order_id > 0 ? $order_id : null );
		$api->remove_course( $tcy_user_id, $cid, $pack, $order_id > 0 ? $order_id : null );
		usleep( ttp_tcy_api_delay_us( 'remove_course', 50000 ) );
	}
}

/**
 * Plan tier score for picking which course wins when multiple SKUs share a pack.
 *
 * @param string $course_id TCY course id.
 * @return int
 */
function ttp_tcy_course_priority_score( $course_id ) {
	$course_id = sanitize_text_field( (string) $course_id );
	$priority  = apply_filters(
		'ttp_tcy_register_course_priority',
		[
			'90073' => 100,
			'90072' => 90,
			'90071' => 80,
			'90069' => 70,
			'90070' => 10,
		]
	);
	return isset( $priority[ $course_id ] ) ? (int) $priority[ $course_id ] : 50;
}

/**
 * TCY error 604: only one course_id per product pack may be on an account.
 *
 * @param array|mixed $response Decoded API body.
 * @return bool
 */
function ttp_tcy_response_is_pack_conflict( $response ) {
	if ( ! is_array( $response ) ) {
		return false;
	}
	if ( isset( $response['error'] ) && in_array( (string) $response['error'], [ '604', '604.0' ], true ) ) {
		return true;
	}
	if ( isset( $response['error'] ) && is_numeric( $response['error'] ) && (int) $response['error'] === 604 ) {
		return true;
	}
	$msg = '';
	if ( ! empty( $response['message'] ) && is_scalar( $response['message'] ) ) {
		$msg = strtolower( (string) $response['message'] );
	}
	return $msg !== '' && ( strpos( $msg, 'same product' ) !== false || strpos( $msg, 'already assig' ) !== false );
}

/**
 * Reduce purchased pairs to what TCY can hold (max one course per pack 33599 / 33605).
 *
 * @param array<int|string, array<string, mixed>> $pairs From ttp_tcy_collect_purchased_course_pairs().
 * @return array{sync: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
 */
function ttp_tcy_filter_pairs_one_per_pack( array $pairs ) {
	$by_pack  = [];
	$skipped  = [];
	$no_pack  = [];

	foreach ( $pairs as $pair ) {
		if ( ! is_array( $pair ) ) {
			continue;
		}
		$cid  = isset( $pair['course_id'] ) ? (string) $pair['course_id'] : '';
		$pack = ttp_tcy_pack_id_for_course_id( $cid );
		if ( $pack === '' ) {
			$no_pack[] = $pair;
			continue;
		}
		$score = ttp_tcy_course_priority_score( $cid );
		if ( ! isset( $by_pack[ $pack ] ) || $score > ttp_tcy_course_priority_score( (string) $by_pack[ $pack ]['course_id'] ) ) {
			if ( isset( $by_pack[ $pack ] ) ) {
				$skipped[] = $by_pack[ $pack ];
			}
			$by_pack[ $pack ] = $pair;
		} else {
			$skipped[] = $pair;
		}
	}

	$sync = array_merge( array_values( $by_pack ), $no_pack );
	return [
		'sync'    => $sync,
		'skipped' => $skipped,
	];
}

/**
 * When true, sync only highest tier per TCY pack (33599/33605). Default false = add every purchased course_id.
 *
 * @return bool
 */
function ttp_tcy_should_limit_one_course_per_pack() {
	return (bool) apply_filters( 'ttp_tcy_limit_add_course_to_one_per_pack', false );
}

/**
 * Remove other course_ids in the same TCY pack before add_course (legacy 604 workaround).
 * Default false — TCY now allows multiple courses per pack; removal blocks a 3rd+ add in the loop.
 *
 * @return bool
 */
function ttp_tcy_should_remove_siblings_before_add_course() {
	$default = (int) get_option( 'ttp_tcy_remove_siblings', 0 ) === 1;
	return (bool) apply_filters( 'ttp_tcy_remove_siblings_before_add_course', $default );
}

/**
 * After register: add_course for every line on this order (same TCY user_id).
 *
 * @param WC_Order $order           Order.
 * @param string   $tcy_user_id     TCY user id from register.
 * @param string[] $skip_course_ids Course ids already assigned by register API on this order.
 * @return array<int, array<string, mixed>>
 */
function ttp_tcy_add_all_courses_for_order( $order, $tcy_user_id, $skip_course_ids = [] ) {
	if ( ! $order instanceof WC_Order ) {
		return [];
	}
	$tcy_user_id = ttp_sanitize_tcy_user_id( (string) $tcy_user_id );
	if ( $tcy_user_id === '' ) {
		return [];
	}

	$order_id  = (int) $order->get_id();
	$details   = [];
	$seen_cids = [];
	$skip_course_ids = array_map(
		static function ( $cid ) {
			return function_exists( 'ttp_tcy_canonical_course_id' )
				? ttp_tcy_canonical_course_id( (string) $cid )
				: sanitize_text_field( (string) $cid );
		},
		(array) $skip_course_ids
	);

	foreach ( $order->get_items() as $item ) {
		$product_id = (int) $item->get_product_id();
		if ( $product_id < 1 ) {
			continue;
		}
		$ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
			? ttp_get_tcy_ids_for_line_item( $item, true )
			: ( function_exists( 'ttp_get_tcy_ids_for_product' )
				? ttp_get_tcy_ids_for_product( $product_id, true )
				: [
					'course_id'   => (string) get_post_meta( $product_id, '_ttp_tcy_course_id', true ),
					'category_id' => (string) get_post_meta( $product_id, '_ttp_tcy_category_id', true ),
				] );
		$course_id = isset( $ids['course_id'] ) ? (string) $ids['course_id'] : '';
		if ( $course_id === '' || $course_id === '0' || isset( $seen_cids[ $course_id ] ) ) {
			continue;
		}
		$seen_cids[ $course_id ] = true;

		if ( in_array( $course_id, $skip_course_ids, true )
			|| ( function_exists( 'ttp_tcy_order_course_enrolled_via_register' )
				&& ttp_tcy_order_course_enrolled_via_register( $order_id, $course_id ) ) ) {
			$details[] = [
				'course_id'   => $course_id,
				'line_name'   => trim( (string) $item->get_name() ),
				'status'      => 'skipped',
				'variant'     => 'register_covers',
				'error'       => '',
			];
			continue;
		}

		$category_id = isset( $ids['category_id'] ) ? (string) $ids['category_id'] : '';
		$category_id = ttp_tcy_normalize_api_category_id( $category_id );
		if ( $category_id === '' && class_exists( 'TTP_Catalog_Seed' ) ) {
			$category_id = TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE;
		}

		ttp_tcy_remove_other_courses_in_pack( $tcy_user_id, $course_id, $order_id );

		$attempt  = ttp_tcy_call_add_course_with_retries( $tcy_user_id, $course_id, $category_id, $order_id, $product_id );
		$outcome  = isset( $attempt['outcome'] ) ? (string) $attempt['outcome'] : 'failed';
		$response = isset( $attempt['response'] ) && is_array( $attempt['response'] ) ? $attempt['response'] : [];

		$details[] = [
			'course_id'   => $course_id,
			'line_name'   => trim( (string) $item->get_name() ),
			'status'      => $outcome,
			'variant'     => isset( $attempt['variant'] ) ? (string) $attempt['variant'] : '',
			'error'       => ( $outcome === 'failed' ) ? ttp_tcy_response_error_message( $response ) : '',
		];

		usleep( ttp_tcy_api_delay_us( 'add_course_loop', 80000 ) );
	}

	return $details;
}

/**
 * Best-effort TCY error text for support (never includes security_code).
 *
 * @param array|mixed $decoded Decoded response_data JSON.
 * @return string
 */
function ttp_tcy_parse_api_error_from_response( $decoded ) {
	if ( ! is_array( $decoded ) ) {
		return '';
	}
	$blocks = array( $decoded );
	if ( ! empty( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
		$blocks[] = $decoded['data'];
	}
	$text_keys = array( 'error', 'message', 'msg', 'ErrorMessage', 'error_message', 'err_msg' );
	$code_keys = array( 'code', 'error_code', 'errorcode', 'err_code', 'errorCode' );
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}
		foreach ( $text_keys as $k ) {
			if ( ! isset( $block[ $k ] ) || null === $block[ $k ] || '' === $block[ $k ] ) {
				continue;
			}
			if ( is_scalar( $block[ $k ] ) ) {
				$t = trim( wp_strip_all_tags( (string) $block[ $k ] ) );
				if ( $t !== '' ) {
					return sanitize_text_field( substr( $t, 0, 240 ) );
				}
			}
		}
		foreach ( $code_keys as $k ) {
			if ( ! isset( $block[ $k ] ) || null === $block[ $k ] || '' === $block[ $k ] ) {
				continue;
			}
			if ( is_scalar( $block[ $k ] ) ) {
				$c = trim( (string) $block[ $k ] );
				if ( $c !== '' ) {
					return sanitize_text_field( substr( 'TCY API: ' . $c, 0, 240 ) );
				}
			}
		}
	}
	return '';
}

/**
 * Last TCY API error for an order (register / login / add_course), newest first.
 *
 * @param int $order_id Order ID.
 * @return string
 */
function ttp_tcy_get_last_api_error_message_for_order( $order_id ) {
	global $wpdb;
	$order_id = (int) $order_id;
	if ( $order_id < 1 ) {
		return '';
	}
	$table = $wpdb->prefix . 'ttp_api_logs';
	if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
		return '';
	}
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT action, response_data FROM {$table} WHERE order_id = %d AND action IN ('register','login','add_course') ORDER BY id DESC LIMIT 15",
			$order_id
		),
		ARRAY_A
	);
	if ( empty( $rows ) ) {
		return '';
	}
	foreach ( $rows as $row ) {
		$d = json_decode( $row['response_data'], true );
		if ( is_array( $d ) && function_exists( 'ttp_tcy_api_is_success' ) && ttp_tcy_api_is_success( $d ) ) {
			continue;
		}
		// Do not surface TCY codes (007, 101, etc.) when success is explicitly 0.
		if ( is_array( $d ) && function_exists( 'ttp_tcy_api_is_explicit_failure' ) && ttp_tcy_api_is_explicit_failure( $d ) ) {
			continue;
		}
		$err = ttp_tcy_parse_api_error_from_response( $d );
		if ( $err !== '' ) {
			return $err;
		}
	}
	return '';
}

/**
 * Customer-safe error text for study-portal login (never includes TCY success:0 codes).
 *
 * @param int    $order_id Order ID.
 * @param string $fallback Default message when no safe detail is available.
 * @return string Empty string means do not show an error banner.
 */
function ttp_tcy_customer_error_message_for_order( $order_id, $fallback = '' ) {
	$hint = ttp_tcy_get_last_api_error_message_for_order( (int) $order_id );
	if ( $hint === '' ) {
		return (string) apply_filters( 'ttp_tcy_customer_error_message', $fallback, (int) $order_id );
	}
	return (string) apply_filters( 'ttp_tcy_customer_error_message', $hint, (int) $order_id );
}

/**
 * WooCommerce line item may be a variation: TCY meta is often stored on the parent product only.
 *
 * @param int $product_id Line product or variation id.
 * @return int Product id to read meta from.
 */
function ttp_tcy_meta_source_product_id( $product_id ) {
	$product_id = (int) $product_id;
	if ( $product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
		return $product_id;
	}
	$p = wc_get_product( $product_id );
	if ( $p && $p->is_type( 'variation' ) ) {
		$parent = (int) $p->get_parent_id();
		return $parent > 0 ? $parent : $product_id;
	}
	return $product_id;
}

/**
 * Resolve TCY course_id + category_id (product id) for a WooCommerce product.
 * Prefers catalog seed by slug/SKU when stored meta does not match the canonical map.
 *
 * @param int  $product_id Line item product or variation ID.
 * @param bool $repair_meta When true, write corrected meta back to the product.
 * @return array{course_id: string, category_id: string, product_id: int, meta_src: int, repaired: bool}
 */
function ttp_get_tcy_ids_for_product( $product_id, $repair_meta = true ) {
	$product_id = (int) $product_id;
	$empty      = [
		'course_id'   => '',
		'category_id' => '',
		'product_id'  => $product_id,
		'meta_src'    => $product_id,
		'repaired'    => false,
		'plan_key'    => '',
	];
	if ( $product_id < 1 || ! function_exists( 'wc_get_product' ) ) {
		return $empty;
	}

	$product  = wc_get_product( $product_id );
	$meta_src = ttp_tcy_meta_source_product_id( $product_id );
	$course   = '';
	$category = '';
	$plan_key = '';
	$repaired = false;
	$def      = null;

	if ( $product && class_exists( 'TTP_Catalog_Seed' ) ) {
		$def = TTP_Catalog_Seed::get_definition_for_product( $product );
		if ( $def ) {
			$resolved = TTP_Catalog_Seed::resolve_tcy_api_ids( $def );
			$course   = $resolved['course_id'];
			$category = $resolved['category_id'];
			$plan_key = isset( $def['slug'] ) ? sanitize_title( (string) $def['slug'] ) : '';
			if ( $repair_meta ) {
				TTP_Catalog_Seed::apply_tcy_meta_from_definition( $product_id, $def );
				$repaired = true;
			}
		}
	}

	if ( $course === '' ) {
		$course = (string) get_post_meta( $meta_src, '_ttp_tcy_course_id', true );
		if ( '' === $course && $meta_src !== $product_id ) {
			$course = (string) get_post_meta( $product_id, '_ttp_tcy_course_id', true );
		}
	}
	if ( $course !== '' && $product ) {
		$fixed_pack = ttp_tcy_fix_misstored_pack_as_course_id( $course, $product->get_name(), $repair_meta, $product_id );
		if ( $fixed_pack !== $course ) {
			$course   = $fixed_pack;
			$repaired = true;
		}
	}
	if ( $course !== '' && function_exists( 'ttp_tcy_canonical_course_id' ) ) {
		$canonical = ttp_tcy_canonical_course_id( $course, $product ? $product->get_name() : '' );
		if ( $canonical !== $course ) {
			$course   = $canonical;
			$repaired = true;
		}
	}
	if ( $course !== '' && ! ttp_tcy_is_mba_catalog_course_id( $course ) && $product ) {
		$remapped = ttp_tcy_remap_unknown_course_id( $course, $product->get_name(), $repair_meta, $product_id );
		if ( $remapped !== $course ) {
			$course   = $remapped;
			$repaired = true;
		}
	}
	if ( $category === '' ) {
		$category = (string) get_post_meta( $meta_src, '_ttp_tcy_category_id', true );
		if ( '' === $category && $meta_src !== $product_id ) {
			$category = (string) get_post_meta( $product_id, '_ttp_tcy_category_id', true );
		}
	}
	$category = ttp_tcy_normalize_api_category_id( $category );
	if ( $course === '' || $course === '0' ) {
		$course = '';
	}
	if ( $repair_meta && $course !== '' && class_exists( 'TTP_Catalog_Seed' ) && ! empty( $def ) ) {
		TTP_Catalog_Seed::apply_tcy_meta_from_definition( $product_id, $def );
		$repaired = true;
	} elseif ( $repair_meta && $course !== '' && $category !== '' ) {
		update_post_meta( $meta_src, '_ttp_tcy_category_id', $category );
		if ( $meta_src !== $product_id ) {
			update_post_meta( $product_id, '_ttp_tcy_category_id', $category );
		}
		$repaired = true;
	}
	if ( $plan_key === '' ) {
		$plan_key = sanitize_title( (string) get_post_meta( $meta_src, '_ttp_tcy_plan_key', true ) );
	}

	return [
		'course_id'   => $course,
		'category_id' => $category,
		'product_id'  => $product_id,
		'meta_src'    => $meta_src,
		'repaired'    => $repaired,
		'plan_key'    => $plan_key,
	];
}

/**
 * TCY IDs from the order line title (NMAT vs CET Elite) — avoids wrong shared product meta.
 *
 * @param WC_Order_Item_Product|object $item        Order line item.
 * @param bool                         $repair_meta Write corrected meta to the product.
 * @return array{course_id: string, category_id: string, product_id: int, meta_src: int, repaired: bool, plan_key: string}
 */
function ttp_get_tcy_ids_for_line_item( $item, $repair_meta = true ) {
	$product_id = 0;
	if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
		$product_id = (int) $item->get_product_id();
	}
	$empty = [
		'course_id'   => '',
		'category_id' => '',
		'product_id'  => $product_id,
		'meta_src'    => $product_id,
		'repaired'    => false,
		'plan_key'    => '',
	];
	if ( $product_id < 1 && ( ! is_object( $item ) || ! method_exists( $item, 'get_name' ) ) ) {
		return $empty;
	}

	$def = null;
	if ( class_exists( 'TTP_Catalog_Seed' ) && is_object( $item ) ) {
		$def = TTP_Catalog_Seed::get_definition_for_order_line_item( $item );
	}
	if ( $def && $product_id > 0 && $repair_meta ) {
		TTP_Catalog_Seed::apply_tcy_meta_from_definition( $product_id, $def );
	}
	if ( $def ) {
		$resolved = TTP_Catalog_Seed::resolve_tcy_api_ids( $def );
		$line_name = is_object( $item ) && method_exists( $item, 'get_name' ) ? trim( (string) $item->get_name() ) : '';
		$course_id = function_exists( 'ttp_tcy_canonical_course_id' )
			? ttp_tcy_canonical_course_id( (string) $resolved['course_id'], $line_name )
			: (string) $resolved['course_id'];
		return [
			'course_id'   => $course_id,
			'category_id' => ttp_tcy_normalize_api_category_id( (string) $resolved['category_id'] ),
			'product_id'  => $product_id,
			'meta_src'    => ttp_tcy_meta_source_product_id( $product_id ),
			'repaired'    => (bool) $repair_meta,
			'plan_key'    => isset( $def['slug'] ) ? sanitize_title( (string) $def['slug'] ) : '',
		];
	}

	return ttp_get_tcy_ids_for_product( $product_id, $repair_meta );
}

/**
 * Expected TCY mapping for the first TCY-linked line item on an order.
 *
 * @param WC_Order $order Order.
 * @return array{course_id: string, category_id: string, product_id: int}
 */
function ttp_get_tcy_ids_for_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return [ 'course_id' => '', 'category_id' => '', 'product_id' => 0 ];
	}
	foreach ( $order->get_items() as $item ) {
		$pid = (int) $item->get_product_id();
		if ( $pid < 1 ) {
			continue;
		}
		$ids = ttp_get_tcy_ids_for_product( $pid, true );
		if ( ! empty( $ids['course_id'] ) ) {
			return [
				'course_id'   => $ids['course_id'],
				'category_id' => $ids['category_id'],
				'product_id'  => $pid,
			];
		}
	}
	return [ 'course_id' => '', 'category_id' => '', 'product_id' => 0 ];
}

/**
 * True when stored order mapping does not match what the customer purchased.
 *
 * @param WC_Order   $order   Order.
 * @param object|null $mapping Row from ttp_order_mapping.
 * @return bool
 */
function ttp_order_tcy_mapping_mismatch( $order, $mapping ) {
	if ( ! $order instanceof WC_Order || ! $mapping ) {
		return false;
	}
	$expected = ttp_get_tcy_ids_for_order( $order );
	if ( '' === $expected['course_id'] ) {
		return false;
	}
	$got_course = isset( $mapping->tcy_course_id ) ? (string) $mapping->tcy_course_id : '';
	$got_cat    = isset( $mapping->tcy_category_id ) ? (string) $mapping->tcy_category_id : '';
	if ( $got_course === '' ) {
		return true;
	}
	if ( $got_course !== $expected['course_id'] ) {
		return true;
	}
	if ( $got_cat !== '' && $expected['category_id'] !== '' && $got_cat !== $expected['category_id'] ) {
		return true;
	}
	return false;
}

/**
 * Distinct TCY course_ids expected on this order (one per line item with a course).
 *
 * @param WC_Order $order Order.
 * @return string[] course_id => true
 */
function ttp_order_expected_tcy_course_ids( $order ) {
	$expected = [];
	if ( ! $order instanceof WC_Order ) {
		return $expected;
	}
	foreach ( $order->get_items() as $item ) {
		$product_id = (int) $item->get_product_id();
		if ( $product_id < 1 ) {
			continue;
		}
		$ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
			? ttp_get_tcy_ids_for_line_item( $item, true )
			: ( function_exists( 'ttp_get_tcy_ids_for_product' )
				? ttp_get_tcy_ids_for_product( $product_id, true )
				: [
					'course_id'   => (string) get_post_meta( $product_id, '_ttp_tcy_course_id', true ),
					'category_id' => (string) get_post_meta( $product_id, '_ttp_tcy_category_id', true ),
				] );
		$cid = isset( $ids['course_id'] ) ? (string) $ids['course_id'] : '';
		if ( $cid !== '' && $cid !== '0' ) {
			$expected[ $cid ] = true;
		}
	}
	return $expected;
}

/**
 * True when every TCY course on the order has a registered mapping row (multi-course carts).
 *
 * @param WC_Order $order Order.
 * @return bool
 */
if ( ! function_exists( 'ttp_order_tcy_mapping_is_complete' ) ) {
function ttp_order_tcy_mapping_is_complete( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	$expected = ttp_order_expected_tcy_course_ids( $order );
	if ( empty( $expected ) ) {
		return true;
	}
	global $wpdb;
	$table = $wpdb->prefix . 'ttp_order_mapping';
	$rows  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT tcy_course_id FROM {$table} WHERE order_id = %d AND status = %s AND tcy_course_id IS NOT NULL AND tcy_course_id <> ''",
			(int) $order->get_id(),
			'registered'
		)
	);
	$mapped = [];
	foreach ( $rows as $row ) {
		if ( isset( $row->tcy_course_id ) && $row->tcy_course_id !== '' ) {
			$mapped[ (string) $row->tcy_course_id ] = true;
		}
	}
	foreach ( array_keys( $expected ) as $cid ) {
		if ( ! isset( $mapped[ $cid ] ) ) {
			return false;
		}
	}
	return true;
}
}

/**
 * Order marked enrolled but not every line item was add_course'd — allow TCY registration to run again.
 *
 * @param WC_Order $order Order.
 * @return bool
 */
function ttp_order_tcy_mapping_needs_repair( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	if ( ! ttp_order_tcy_mapping_is_complete( $order ) ) {
		return true;
	}
	global $wpdb;
	$table = $wpdb->prefix . 'ttp_order_mapping';
	$row   = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE order_id = %d AND tcy_user_id IS NOT NULL AND tcy_user_id <> '' ORDER BY id DESC LIMIT 1",
			(int) $order->get_id()
		)
	);
	if ( ! $row ) {
		return true;
	}
	return ttp_order_tcy_mapping_mismatch( $order, $row );
}

/**
 * Ensure the TCY account has the course from this order (idempotent add_course).
 *
 * @param string $tcy_user_id TCY user id.
 * @param string $course_id   TCY course id.
 * @param string $category_id TCY product id (category_id in API).
 * @param int    $order_id    WooCommerce order id for logging.
 * @return array API response.
 */
function ttp_tcy_ensure_course_on_account( $tcy_user_id, $course_id, $category_id, $order_id = 0, $product_id = 0 ) {
	$tcy_user_id = ttp_sanitize_tcy_user_id( $tcy_user_id );
	$product_id  = (int) $product_id;
	$order_id    = (int) $order_id;

	// Resolve from catalog only when course_id was not passed explicitly (Open course URL carries ttp_tcy_course).
	if ( $course_id === '' && $product_id > 0 && function_exists( 'ttp_get_tcy_ids_for_product' ) ) {
		$resolved = ttp_get_tcy_ids_for_product( $product_id, true );
		if ( ! empty( $resolved['course_id'] ) ) {
			$course_id   = (string) $resolved['course_id'];
			$category_id = (string) $resolved['category_id'];
		}
	}

	$course_id   = sanitize_text_field( (string) $course_id );
	$category_id = sanitize_text_field( (string) $category_id );
	$category_id = ttp_tcy_normalize_api_category_id( $category_id );
	if ( $category_id === '' && class_exists( 'TTP_Catalog_Seed' ) ) {
		$category_id = TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE;
	}

	if ( $tcy_user_id === '' || $course_id === '' || $course_id === '0' ) {
		return [ 'success' => 0, 'error' => 'Missing TCY user or course id.' ];
	}

	ttp_tcy_remove_other_courses_in_pack( $tcy_user_id, $course_id, $order_id );

	$result = ttp_tcy_call_add_course_with_retries( $tcy_user_id, $course_id, $category_id, $order_id, $product_id );
	return isset( $result['response'] ) && is_array( $result['response'] ) ? $result['response'] : [ 'success' => 0 ];
}

/**
 * Build add_course context (sub_cat = product pack per TCY API).
 *
 * @param int    $product_id Product id.
 * @param int    $order_id   Order id.
 * @param string $course_id  TCY course id.
 * @param bool   $force_sub_cat Force pack into sub_cat even if filter off.
 * @return array<string, mixed>
 */
function ttp_tcy_build_add_course_context( $product_id, $order_id = 0, $course_id = '', $force_sub_cat = false ) {
	$product_id = (int) $product_id;
	$context    = [
		'product_id' => $product_id,
		'order_id'   => (int) $order_id,
		'course_id'  => sanitize_text_field( (string) $course_id ),
	];

	$send_sub = $force_sub_cat || (bool) apply_filters(
		'ttp_tcy_send_sub_cat_in_add_course',
		(int) get_option( 'ttp_tcy_send_sub_cat', 1 ) === 1
	);
	if ( ! $send_sub || $product_id < 1 ) {
		return $context;
	}

	$course_id = sanitize_text_field( (string) $course_id );
	$profile   = $course_id !== '' ? ttp_tcy_get_course_api_profile( $course_id ) : null;
	if ( $profile && ! empty( $profile['sub_cat'] ) ) {
		$context['sub_cat'] = sanitize_text_field( (string) $profile['sub_cat'] );
		return $context;
	}

	$src  = ttp_tcy_meta_source_product_id( $product_id );
	$pack = (string) get_post_meta( $src, '_ttp_tcy_product_pack_id', true );
	if ( ( $pack === '' || $pack === '0' ) && class_exists( 'TTP_Catalog_Seed' ) ) {
		$def = TTP_Catalog_Seed::get_definition_for_product( $product_id );
		if ( $def && ! empty( $def['tcy_product_pack_id'] ) ) {
			$pack = sanitize_text_field( (string) $def['tcy_product_pack_id'] );
		}
	}
	if ( $pack !== '' && $pack !== '0' ) {
		$context['sub_cat'] = $pack;
	} elseif ( $course_id !== '' && $product_id > 0 && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );
		$label   = $product ? $product->get_name() : '';
		$inferred = ttp_tcy_infer_sub_cat_for_course( $course_id, $product_id, $label );
		if ( $inferred !== '' ) {
			$context['sub_cat'] = $inferred;
		}
	}

	return $context;
}

/**
 * Human-readable TCY API error from decoded JSON.
 *
 * @param array|mixed $response API body.
 * @return string
 */
function ttp_tcy_response_error_message( $response ) {
	if ( function_exists( 'ttp_tcy_parse_api_error_from_response' ) ) {
		$msg = ttp_tcy_parse_api_error_from_response( $response );
		if ( $msg !== '' ) {
			return $msg;
		}
	}
	if ( is_array( $response ) && ! empty( $response['error'] ) && is_scalar( $response['error'] ) ) {
		return sanitize_text_field( (string) $response['error'] );
	}
	if ( is_array( $response ) && isset( $response['success'] ) ) {
		$base = 'TCY success=' . (string) $response['success'];
		$dbg  = ttp_tcy_response_debug_summary( $response );
		return $dbg !== '' ? $base . ' — ' . $dbg : $base;
	}
	$dbg = ttp_tcy_response_debug_summary( $response );
	return $dbg !== '' ? $dbg : 'Unknown TCY error';
}

/**
 * add_course with payload variants (MBA 100000 ± sub_cat, pack id as category_id) until TCY accepts.
 *
 * @param string $tcy_user_id   TCY user id.
 * @param string $course_id      TCY course id.
 * @param string $category_id    Normalized MBA category (100000).
 * @param int    $order_id       Order id for logs.
 * @param int    $product_id     Woo product id (pack / sub_cat).
 * @param string $force_sub_cat  sub_cat from get_courses profile (overrides product meta).
 * @return array{outcome: string, response: array, variant: string, category_id: string, sub_cat: string}
 */
function ttp_tcy_call_add_course_with_retries( $tcy_user_id, $course_id, $category_id, $order_id = 0, $product_id = 0, $force_sub_cat = '' ) {
	$tcy_user_id = ttp_sanitize_tcy_user_id( (string) $tcy_user_id );
	$course_id   = sanitize_text_field( (string) $course_id );
	$category_id = ttp_tcy_normalize_api_category_id( (string) $category_id );
	$order_id    = (int) $order_id;
	$product_id  = (int) $product_id;

	if ( $category_id === '' && class_exists( 'TTP_Catalog_Seed' ) ) {
		$category_id = TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE;
	}

	$profile = ttp_tcy_get_course_api_profile( $course_id );
	$pack    = sanitize_text_field( (string) $force_sub_cat );
	if ( $pack === '' && $profile && ! empty( $profile['sub_cat'] ) ) {
		$pack = (string) $profile['sub_cat'];
	}
	if ( $pack === '' && $product_id > 0 ) {
		$ctx_pack = ttp_tcy_build_add_course_context( $product_id, $order_id, $course_id, true );
		if ( ! empty( $ctx_pack['sub_cat'] ) ) {
			$pack = (string) $ctx_pack['sub_cat'];
		}
	}

	$mba      = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE : '100000';
	$variants = [];
	// Per get_courses: CUET 90315 → category 902346 + sub_cat 38072; MBA → 100000 + 33599/33605.
	if ( $profile && ! empty( $profile['category_id'] ) ) {
		$p_cat = sanitize_text_field( (string) $profile['category_id'] );
		$p_sub = ! empty( $profile['sub_cat'] ) ? sanitize_text_field( (string) $profile['sub_cat'] ) : '';
		$variants[] = [ 'cat' => $p_cat, 'sub' => $p_sub, 'label' => 'tcy_profile' ];
	}
	if ( $pack !== '' && ( ! $profile || (string) $profile['category_id'] === $mba ) ) {
		$variants[] = [ 'cat' => $mba, 'sub' => $pack, 'label' => 'mba+sub_cat' ];
	}
	if ( ! $profile || (string) $profile['category_id'] === $mba ) {
		$variants[] = [ 'cat' => $mba, 'sub' => '', 'label' => 'mba' ];
		if ( $pack !== '' ) {
			$variants[] = [ 'cat' => $pack, 'sub' => '', 'label' => 'pack' ];
		}
	}
	if ( $category_id !== '' && $category_id !== $mba ) {
		$variants[] = [ 'cat' => $category_id, 'sub' => $pack, 'label' => 'stored_category' ];
	}
	$variants = apply_filters( 'ttp_tcy_add_course_payload_variants', $variants, $course_id, $product_id, $pack );
	if ( function_exists( 'ttp_tcy_is_fast_access_request' ) && ttp_tcy_is_fast_access_request() ) {
		$variants = array_slice( (array) $variants, 0, 2 );
	}

	$api            = new TTP_TCY_API();
	$last_response  = [ 'success' => 0, 'error' => 'No variant attempted' ];
	$last_variant   = '';

	foreach ( (array) $variants as $v ) {
		if ( empty( $v['cat'] ) ) {
			continue;
		}
		$ctx = [
			'product_id' => $product_id,
			'order_id'   => $order_id,
			'course_id'  => $course_id,
		];
		if ( ! empty( $v['sub'] ) ) {
			$ctx['sub_cat'] = sanitize_text_field( (string) $v['sub'] );
		}
		$last_variant  = isset( $v['label'] ) ? (string) $v['label'] : '';
		$last_response = $api->add_course(
			$tcy_user_id,
			$course_id,
			sanitize_text_field( (string) $v['cat'] ),
			$order_id > 0 ? $order_id : null,
			$ctx
		);
		$outcome = ttp_tcy_add_course_outcome( $last_response );
		if ( in_array( $outcome, [ 'success', 'already' ], true ) ) {
			return [
				'outcome'     => $outcome,
				'response'    => is_array( $last_response ) ? $last_response : [],
				'variant'     => $last_variant,
				'category_id' => sanitize_text_field( (string) $v['cat'] ),
				'sub_cat'     => ! empty( $v['sub'] ) ? sanitize_text_field( (string) $v['sub'] ) : '',
			];
		}
		usleep( ttp_tcy_api_delay_us( 'add_course_variant', 0 ) );
	}

	// Optional: one retry with sibling removal when every variant returned 604 (tier swap, not multi-hold).
	if ( ttp_tcy_should_remove_siblings_before_add_course() && ttp_tcy_response_is_pack_conflict( $last_response ) && $order_id > 0 ) {
		ttp_tcy_remove_other_courses_in_pack( $tcy_user_id, $course_id, $order_id );
		$retry_cat = $pack !== '' ? $mba : $category_id;
		$retry_ctx = [ 'product_id' => $product_id, 'order_id' => $order_id, 'course_id' => $course_id ];
		if ( $pack !== '' ) {
			$retry_ctx['sub_cat'] = $pack;
		}
		$last_variant  = $pack !== '' ? 'mba+sub_cat_after_remove' : 'mba_after_remove';
		$last_response = $api->add_course(
			$tcy_user_id,
			$course_id,
			sanitize_text_field( (string) $retry_cat ),
			$order_id > 0 ? $order_id : null,
			$retry_ctx
		);
		$retry_outcome = ttp_tcy_add_course_outcome( $last_response );
		if ( in_array( $retry_outcome, [ 'success', 'already' ], true ) ) {
			return [
				'outcome'     => $retry_outcome,
				'response'    => is_array( $last_response ) ? $last_response : [],
				'variant'     => $last_variant,
				'category_id' => sanitize_text_field( (string) $retry_cat ),
				'sub_cat'     => $pack,
			];
		}
	}

	$final_outcome = ttp_tcy_add_course_outcome( $last_response );
	if ( in_array( $final_outcome, [ 'already', 'pack_conflict' ], true ) ) {
		return [
			'outcome'     => $final_outcome,
			'response'    => is_array( $last_response ) ? $last_response : [],
			'variant'     => $last_variant,
			'category_id' => $category_id,
			'sub_cat'     => $pack,
		];
	}

	return [
		'outcome'     => 'failed',
		'response'    => is_array( $last_response ) ? $last_response : [],
		'variant'     => $last_variant,
		'category_id' => $category_id,
		'sub_cat'     => $pack,
	];
}

/**
 * Study portal "My courses" page after autologin.
 *
 * @return string
 */
function ttp_get_study_portal_courses_list_url() {
	$base = rtrim( (string) get_option( 'ttp_study_portal_base_url', 'https://study.thetoppercentile.co.in' ), '/' );
	return (string) apply_filters( 'ttp_study_portal_courses_list_url', $base . '/ViewClientCourses' );
}

/**
 * Append post-login destination (ViewClientCourses) to TCY autologin URL when supported.
 *
 * @param string $autologin_url Magic login URL.
 * @return string
 */
function ttp_finalize_study_portal_login_url( $autologin_url ) {
	if ( ! is_string( $autologin_url ) || $autologin_url === '' ) {
		return '';
	}
	$autologin_url = function_exists( 'ttp_rewrite_login_url_to_study_portal' )
		? ttp_rewrite_login_url_to_study_portal( $autologin_url )
		: $autologin_url;
	$dest = ttp_get_study_portal_courses_list_url();
	if ( $dest === '' ) {
		return $autologin_url;
	}
	// TCY white-label hosts vary; try common post-login destination keys.
	$args = (array) apply_filters(
		'ttp_study_portal_autologin_redirect_query_args',
		[
			'redirect'   => $dest,
			'returnurl'  => $dest,
			'return_url' => $dest,
			'url'        => '/ViewClientCourses',
		]
	);
	foreach ( $args as $key => $value ) {
		if ( $value !== '' && $value !== null ) {
			$autologin_url = add_query_arg( sanitize_key( (string) $key ), is_string( $value ) ? $value : rawurlencode( (string) $value ), $autologin_url );
		}
	}
	return (string) apply_filters( 'ttp_finalize_study_portal_login_url', $autologin_url, $dest );
}

/**
 * Whether a URL is an allowed external study-portal redirect target.
 *
 * @param string $url URL.
 * @return bool
 */
function ttp_is_permitted_study_portal_url( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return false;
	}
	$parts = wp_parse_url( $url );
	if ( empty( $parts['host'] ) || ! in_array( $parts['scheme'] ?? '', [ 'http', 'https' ], true ) ) {
		return false;
	}
	$host    = strtolower( (string) $parts['host'] );
	$allowed = [
		'study.thetoppercentile.co.in',
		'thetoppercentile.tcyonline.co.in',
		'www.thetoppercentile.tcyonline.co.in',
	];
	$study = wp_parse_url( (string) get_option( 'ttp_study_portal_base_url', 'https://study.thetoppercentile.co.in' ) );
	if ( ! empty( $study['host'] ) ) {
		$allowed[] = strtolower( (string) $study['host'] );
	}
	$allowed = array_unique( array_map( 'strtolower', apply_filters( 'ttp_allowed_magic_login_redirect_hosts', $allowed ) ) );
	if ( in_array( $host, $allowed, true ) ) {
		return true;
	}
	return (bool) preg_match( '/(?:^|\.)tcyonline\.(?:co\.in|com)$/i', $host );
}

/**
 * Redirect browser to TCY study portal (wp_safe_redirect often blocks external hosts).
 *
 * @param string $url Target URL.
 * @return void
 */
function ttp_redirect_to_study_portal( $url ) {
	if ( ! ttp_is_permitted_study_portal_url( $url ) ) {
		return;
	}
	nocache_headers();
	wp_redirect( $url, 302 );
	exit;
}

add_filter(
	'allowed_redirect_hosts',
	static function ( $hosts ) {
		$hosts[] = 'study.thetoppercentile.co.in';
		return $hosts;
	}
);

/**
 * Whether a paid order belongs to the logged-in WordPress user (by customer_id or billing email).
 *
 * @param WC_Order $order   Order.
 * @param int      $user_id WP user ID.
 * @return bool
 */
function ttp_order_belongs_to_wp_user( $order, $user_id ) {
	$user_id = (int) $user_id;
	if ( ! $order instanceof WC_Order || $user_id < 1 ) {
		return false;
	}
	if ( (int) $order->get_user_id() === $user_id ) {
		return true;
	}
	$user = get_userdata( $user_id );
	if ( ! $user instanceof WP_User ) {
		return false;
	}
	$bill = strtolower( trim( (string) $order->get_billing_email() ) );
	$acct = strtolower( trim( (string) $user->user_email ) );
	return $bill !== '' && $acct !== '' && $bill === $acct;
}

/**
 * Try TCY login API with multiple user_ids (split accounts) until a study URL is returned.
 *
 * @param string[] $tcy_ids     TCY user ids to try.
 * @param int      $order_id    Order id for logging.
 * @param string   $note        Redirect log note.
 * @return string Study portal URL or empty.
 */
function ttp_try_study_portal_login_with_tcy_ids( array $tcy_ids, $order_id = 0, $note = '' ) {
	$api = new TTP_TCY_API();
	foreach ( $tcy_ids as $raw_id ) {
		$tcy_user_id = ttp_sanitize_tcy_user_id( (string) $raw_id );
		if ( $tcy_user_id === '' ) {
			continue;
		}
		$response = $api->login_student( $tcy_user_id );
		if ( ! function_exists( 'ttp_tcy_api_is_success' ) || ! ttp_tcy_api_is_success( $response ) ) {
			continue;
		}
		$raw = function_exists( 'ttp_extract_login_url_from_tcy_response' ) ? ttp_extract_login_url_from_tcy_response( $response ) : '';
		if ( $raw === '' ) {
			continue;
		}
		$final = ttp_finalize_study_portal_login_url( $raw );
		if ( $final !== '' && (int) $order_id > 0 && function_exists( 'ttp_log_study_portal_redirect' ) ) {
			ttp_log_study_portal_redirect( 'study_access', (int) $order_id, $raw, $final, $tcy_user_id, $note );
		}
		return $final;
	}
	return '';
}

/**
 * Resolve TCY user id for a WooCommerce order.
 *
 * @param WC_Order $order Order.
 * @return string
 */
function ttp_get_tcy_user_id_for_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}
	$canonical = ttp_get_canonical_tcy_user_id( (int) $order->get_user_id(), (string) $order->get_billing_email() );
	if ( $canonical !== '' ) {
		return $canonical;
	}
	$meta = ttp_sanitize_tcy_user_id( (string) $order->get_meta( '_ttp_tcy_user_id', true ) );
	if ( $meta !== '' ) {
		return $meta;
	}
	global $wpdb;
	$tcy_id = (string) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT tcy_user_id FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND tcy_user_id IS NOT NULL AND tcy_user_id <> '' ORDER BY id DESC LIMIT 1",
			(int) $order->get_id()
		)
	);
	return ttp_sanitize_tcy_user_id( $tcy_id );
}

/**
 * add_course for every TCY line item on one order (fixes multi-course cart).
 *
 * @param WC_Order $order Order.
 * @return int Number of add_course calls.
 */
function ttp_sync_tcy_courses_for_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return 0;
	}
	$tcy_user_id = ttp_get_tcy_user_id_for_order( $order );
	if ( $tcy_user_id === '' ) {
		return 0;
	}
	$order_id = (int) $order->get_id();
	delete_transient( 'ttp_sync_order_' . $order_id );
	$count    = 0;
	$seen     = [];
	foreach ( $order->get_items() as $item ) {
		$product_id = (int) $item->get_product_id();
		if ( $product_id < 1 ) {
			continue;
		}
		$ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
			? ttp_get_tcy_ids_for_line_item( $item, true )
			: ttp_get_tcy_ids_for_product( $product_id, true );
		$cid = isset( $ids['course_id'] ) ? (string) $ids['course_id'] : '';
		$cat = isset( $ids['category_id'] ) ? (string) $ids['category_id'] : '';
		$cat = ttp_tcy_normalize_api_category_id( $cat );
		if ( $cat === '' && class_exists( 'TTP_Catalog_Seed' ) ) {
			$cat = TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE;
		}
		if ( $cid === '' || $cid === '0' ) {
			continue;
		}
		$key = $cid . '|' . $cat;
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		ttp_tcy_ensure_course_on_account( $tcy_user_id, $cid, $cat, $order_id, $product_id );
		++$count;
	}
	return $count;
}

/**
 * Build study-portal autologin URL (syncs order courses first).
 *
 * @param string   $tcy_user_id TCY user id.
 * @param int      $order_id    Order id for logging/sync.
 * @param int      $product_id  Optional: ensure this product's course before login.
 * @param string   $note            Redirect log note.
 * @param string   $force_course_id TCY course_id for this click (90072 vs 90073).
 * @return string
 */
function ttp_build_study_portal_access_url( $tcy_user_id, $order_id = 0, $product_id = 0, $note = '', $force_course_id = '' ) {
	$order_id   = (int) $order_id;
	$product_id = (int) $product_id;
	$fast       = function_exists( 'ttp_tcy_is_fast_access_request' ) && ttp_tcy_is_fast_access_request();

	if ( $fast && $order_id > 0 && function_exists( 'ttp_get_cached_study_login_url_for_order' ) ) {
		$cached = ttp_get_cached_study_login_url_for_order( $order_id );
		if ( $cached !== '' && sanitize_text_field( (string) $force_course_id ) === '' ) {
			return $cached;
		}
	}

	if ( $order_id > 0 ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$from_order = ttp_get_canonical_tcy_user_id( (int) $order->get_user_id(), (string) $order->get_billing_email() );
			if ( $from_order !== '' ) {
				$tcy_user_id = $from_order;
			}
		}
	}
	$tcy_user_id = ttp_sanitize_tcy_user_id( (string) $tcy_user_id );
	if ( $tcy_user_id === '' ) {
		return '';
	}
	$force_course_id = sanitize_text_field( (string) $force_course_id );
	if ( $order_id < 1 && ( $product_id > 0 || $force_course_id !== '' ) && function_exists( 'ttp_tcy_ensure_course_on_account' ) ) {
		$cat = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE : '100000';
		ttp_tcy_ensure_course_on_account( $tcy_user_id, $force_course_id, $cat, 0, $product_id );
	}
	if ( $order_id > 0 ) {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$mapping_done = function_exists( 'ttp_order_tcy_mapping_is_complete' ) && ttp_order_tcy_mapping_is_complete( $order );
			if ( ! $mapping_done && function_exists( 'ttp_sync_tcy_courses_for_order' ) ) {
				ttp_sync_tcy_courses_for_order( $order );
			} elseif ( $fast && $product_id > 0 && function_exists( 'ttp_tcy_ensure_course_on_account' ) ) {
				ttp_tcy_ensure_course_on_account( $tcy_user_id, '', '', $order_id, $product_id );
			}
			if ( ! $fast && function_exists( 'ttp_tcy_loop_add_all_courses_for_user_id' ) ) {
				$wp_uid = (int) $order->get_user_id();
				ttp_tcy_loop_add_all_courses_for_user_id(
					$tcy_user_id,
					$wp_uid,
					(string) $order->get_billing_email()
				);
			}
			$force_course_id = sanitize_text_field( (string) $force_course_id );
			if ( $force_course_id !== '' ) {
				$cat = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE : '100000';
				ttp_tcy_ensure_course_on_account( $tcy_user_id, $force_course_id, $cat, $order_id, $product_id );
			} elseif ( ! $fast && $product_id > 0 ) {
				ttp_tcy_ensure_course_on_account( $tcy_user_id, '', '', $order_id, $product_id );
			}
		}
	}
	$wp_uid        = 0;
	$billing_email = '';
	if ( $order_id > 0 ) {
		$order_for_user = wc_get_order( $order_id );
		if ( $order_for_user instanceof WC_Order ) {
			$wp_uid        = (int) $order_for_user->get_user_id();
			$billing_email = (string) $order_for_user->get_billing_email();
		}
	}
	$try_ids = [ $tcy_user_id ];
	if ( ! $fast ) {
		$all_ids = ttp_collect_all_tcy_user_ids_for_customer( $wp_uid, $billing_email );
		foreach ( $all_ids as $alt ) {
			if ( ! in_array( $alt, $try_ids, true ) ) {
				$try_ids[] = $alt;
			}
		}
	} else {
		$canonical = ttp_get_canonical_tcy_user_id( $wp_uid, $billing_email );
		if ( $canonical !== '' && ! in_array( $canonical, $try_ids, true ) ) {
			$try_ids[] = $canonical;
		}
		$try_ids = array_slice( array_values( array_unique( $try_ids ) ), 0, 2 );
	}

	$final = ttp_try_study_portal_login_with_tcy_ids( $try_ids, $order_id, $note );
	$force_course_id = sanitize_text_field( (string) $force_course_id );
	if ( $final !== '' && $force_course_id !== '' ) {
		$final = add_query_arg(
			[
				'course_id' => $force_course_id,
				'c_id'      => $force_course_id,
			],
			$final
		);
	}
	if ( $final !== '' && $order_id > 0 && function_exists( 'ttp_cache_study_login_url_for_order' ) && sanitize_text_field( (string) $force_course_id ) === '' ) {
		ttp_cache_study_login_url_for_order( $order_id, $final );
	}
	return $final;
}

/**
 * Collect unique TCY course + product pairs from all paid orders for a customer.
 *
 * @param int    $wp_user_id    WordPress user ID (0 for guest-only lookup).
 * @param string $billing_email Billing email (used when user_id is 0 or to include guest orders).
 * @return array<string, array{course_id: string, category_id: string, order_id: int}>
 */
function ttp_tcy_collect_purchased_course_pairs( $wp_user_id = 0, $billing_email = '' ) {
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return [];
	}

	$wp_user_id    = (int) $wp_user_id;
	$billing_email = sanitize_email( (string) $billing_email );
	$statuses      = apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] );
	$orders        = [];
	$seen_ids      = [];

	if ( $wp_user_id > 0 ) {
		$user_orders = wc_get_orders(
			[
				'customer_id' => $wp_user_id,
				'status'      => $statuses,
				'limit'       => -1,
				'orderby'     => 'date',
				'order'       => 'ASC',
			]
		);
		foreach ( $user_orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$orders[]                           = $order;
				$seen_ids[ (int) $order->get_id() ] = true;
			}
		}
	}

	if ( $billing_email !== '' ) {
		$email_orders = wc_get_orders(
			[
				'billing_email' => $billing_email,
				'status'        => $statuses,
				'limit'         => -1,
				'orderby'       => 'date',
				'order'         => 'ASC',
			]
		);
		foreach ( $email_orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$oid = (int) $order->get_id();
			if ( isset( $seen_ids[ $oid ] ) ) {
				continue;
			}
			$orders[]           = $order;
			$seen_ids[ $oid ] = true;
		}
	}

	$pairs = [];
	foreach ( $orders as $order ) {
		if ( ! function_exists( 'ttp_order_qualifies_for_tcy_actions' ) || ! ttp_order_qualifies_for_tcy_actions( $order ) ) {
			continue;
		}
		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();
			if ( $product_id < 1 ) {
				continue;
			}
			$ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
				? ttp_get_tcy_ids_for_line_item( $item, true )
				: ( function_exists( 'ttp_get_tcy_ids_for_product' )
					? ttp_get_tcy_ids_for_product( $product_id, true )
					: [
						'course_id'   => (string) get_post_meta( $product_id, '_ttp_tcy_course_id', true ),
						'category_id' => (string) get_post_meta( $product_id, '_ttp_tcy_category_id', true ),
					] );
			$raw_course = isset( $ids['course_id'] ) ? (string) $ids['course_id'] : '';
			$line_name  = trim( (string) $item->get_name() );
			$resolved   = ttp_tcy_resolve_line_course_api( $raw_course, $line_name, $product_id, true );
			$course_id  = (string) $resolved['course_id'];
			if ( $course_id === '' ) {
				continue;
			}
			$category_id = (string) $resolved['category_id'];
			if ( $category_id === '' && ! empty( $resolved['syncable'] ) ) {
				$category_id = ttp_tcy_normalize_api_category_id( isset( $ids['category_id'] ) ? (string) $ids['category_id'] : '' );
			}
			if ( $category_id === '' && class_exists( 'TTP_Catalog_Seed' ) && ! empty( $resolved['syncable'] ) ) {
				$category_id = TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE;
			}
			$line_id = method_exists( $item, 'get_id' ) ? (int) $item->get_id() : 0;
			$key     = $course_id . '|' . $category_id . '|' . (int) $order->get_id() . '|' . $line_id;
			if ( ! isset( $pairs[ $key ] ) ) {
				$pairs[ $key ] = [
					'course_id'        => $course_id,
					'stored_course_id' => (string) $resolved['stored_course_id'],
					'category_id'      => $category_id,
					'sub_cat'          => (string) ( $resolved['sub_cat'] ?? '' ),
					'syncable'         => ! empty( $resolved['syncable'] ),
					'skip_reason'      => (string) ( $resolved['skip_reason'] ?? '' ),
					'order_id'         => (int) $order->get_id(),
					'product_id'       => $product_id,
					'line_name'        => $line_name,
				];
			}
		}
	}

	return $pairs;
}

/**
 * Call TCY add_course for every distinct course the customer has paid for (fixes multiple purchases → one course).
 *
 * @param string $tcy_user_id    TCY user id.
 * @param int    $wp_user_id      WP user id.
 * @param string $billing_email   Billing email.
 * @return int Number of add_course API calls made.
 */
/**
 * Core TCY enrollment: one user_id → add_course in a loop for every purchased course (90069–90073).
 *
 * @param string $tcy_user_id   TCY user id e.g. ODE0MTAwMg==.
 * @param int    $wp_user_id    WordPress user ID.
 * @param string $billing_email Billing email (guest / cross-order lookup).
 * @return array{added: int, failed: int, total: int, course_ids: string[], tcy_user_id: string, details: array<int, array<string, mixed>>}
 */
function ttp_tcy_loop_add_all_courses_for_user_id( $tcy_user_id, $wp_user_id = 0, $billing_email = '' ) {
	$wp_user_id    = (int) $wp_user_id;
	$billing_email = sanitize_email( (string) $billing_email );

	$canonical = ttp_get_canonical_tcy_user_id( $wp_user_id, $billing_email );
	if ( $canonical !== '' ) {
		$tcy_user_id = $canonical;
	}
	$tcy_user_id = ttp_sanitize_tcy_user_id( (string) $tcy_user_id );

	$all_ids = ttp_collect_all_tcy_user_ids_for_customer( $wp_user_id, $billing_email );
	if ( $tcy_user_id !== '' && ! in_array( $tcy_user_id, $all_ids, true ) ) {
		array_unshift( $all_ids, $tcy_user_id );
	}
	if ( $tcy_user_id === '' && ! empty( $all_ids ) ) {
		$tcy_user_id = $all_ids[0];
	}

	$out = [
		'added'         => 0,
		'already'       => 0,
		'failed'        => 0,
		'skipped'       => 0,
		'total'         => 0,
		'course_ids'    => [],
		'tcy_user_id'   => $tcy_user_id,
		'all_tcy_ids'   => $all_ids,
		'split_account' => count( $all_ids ) > 1,
		'details'       => [],
		'diagnosis'     => ttp_tcy_diagnose_customer_enrollment( $wp_user_id, $billing_email ),
	];

	if ( $tcy_user_id === '' ) {
		$out['error'] = 'No TCY user_id for this customer (register first or check email).';
		return $out;
	}

	ttp_tcy_unify_customer_to_canonical_id( $tcy_user_id, $wp_user_id, $billing_email );

	$target_ids = [ $tcy_user_id ];
	if ( count( $all_ids ) > 1 ) {
		$target_ids = array_values( array_unique( array_merge( [ $tcy_user_id ], $all_ids ) ) );
	}

	delete_transient( 'ttp_sync_all_user_' . md5( $tcy_user_id ) );

	$all_pairs = ttp_tcy_collect_purchased_course_pairs( $wp_user_id, $billing_email );
	if ( empty( $all_pairs ) ) {
		$out['error'] = 'No paid orders with TCY course_id found for this email/user.';
		return $out;
	}

	$out['pairs_purchased'] = count( $all_pairs );
	$pairs                  = $all_pairs;
	$out['pairs_skipped']   = [];
	if ( ttp_tcy_should_limit_one_course_per_pack() ) {
		$pack_filter            = ttp_tcy_filter_pairs_one_per_pack( $all_pairs );
		$pairs                  = $pack_filter['sync'];
		$out['pairs_skipped']   = $pack_filter['skipped'];
		$out['pack_note']       = __( 'Pack limit enabled: syncing highest tier per pack only.', 'ttp-woocommerce' );
	} else {
		$out['pack_note'] = __( 'MBA courses use category_id 100000 + sub_cat 33599/33605; CUET 90315 uses 902346 + 38072. Invalid Product_ids (e.g. 38053) are skipped.', 'ttp-woocommerce' );
	}
	$syncable_pairs = array_filter(
		$pairs,
		static function ( $p ) {
			return is_array( $p ) && ! empty( $p['syncable'] );
		}
	);
	$out['pairs_tcy_sync'] = count( $syncable_pairs );

	if ( empty( $syncable_pairs ) ) {
		$out['error'] = 'No TCY-syncable courses found (check invalid course_id on Woo products).';
		return $out;
	}

	$seen_cids = [];
	$pairs     = array_values( $pairs );
	usort(
		$pairs,
		static function ( $a, $b ) {
			$ca = isset( $a['course_id'] ) ? (string) $a['course_id'] : '';
			$cb = isset( $b['course_id'] ) ? (string) $b['course_id'] : '';
			return ttp_tcy_course_priority_score( $ca ) - ttp_tcy_course_priority_score( $cb );
		}
	);

	foreach ( $pairs as $pair ) {
		$cid = isset( $pair['course_id'] ) ? (string) $pair['course_id'] : '';
		if ( $cid === '' || $cid === '0' || isset( $seen_cids[ $cid ] ) ) {
			continue;
		}
		$seen_cids[ $cid ] = true;

		$order_id         = isset( $pair['order_id'] ) ? (int) $pair['order_id'] : 0;
		$product_id       = isset( $pair['product_id'] ) ? (int) $pair['product_id'] : 0;
		$line_name        = isset( $pair['line_name'] ) ? (string) $pair['line_name'] : '';
		$stored_course_id = isset( $pair['stored_course_id'] ) ? (string) $pair['stored_course_id'] : '';
		$pair_sub_cat     = isset( $pair['sub_cat'] ) ? (string) $pair['sub_cat'] : '';

		if ( empty( $pair['syncable'] ) ) {
			++$out['skipped'];
			$out['details'][] = [
				'tcy_user_id'      => $tcy_user_id,
				'course_id'        => $cid,
				'stored_course_id' => $stored_course_id,
				'category_id'      => '',
				'sub_cat'          => '',
				'variant'          => '',
				'order_id'         => $order_id,
				'line_name'        => $line_name,
				'status'           => 'skipped',
				'error'            => isset( $pair['skip_reason'] ) ? (string) $pair['skip_reason'] : '',
			];
			continue;
		}

		if ( $order_id > 0 && function_exists( 'ttp_tcy_order_course_enrolled_via_register' )
			&& ttp_tcy_order_course_enrolled_via_register( $order_id, $cid ) ) {
			++$out['already'];
			$out['details'][] = [
				'tcy_user_id'      => $tcy_user_id,
				'course_id'        => $cid,
				'stored_course_id' => $stored_course_id,
				'category_id'      => '',
				'sub_cat'          => '',
				'variant'          => 'register_covers',
				'order_id'         => $order_id,
				'line_name'        => $line_name,
				'status'           => 'skipped',
				'error'            => '',
			];
			continue;
		}

		$out['course_ids'][] = $cid;

		$cat = isset( $pair['category_id'] ) ? (string) $pair['category_id'] : '';
		if ( $cat === '' || in_array( $cat, [ '33599', '33605', '33598', '33604' ], true ) ) {
			$cat = ttp_tcy_normalize_api_category_id( $cat );
		}
		if ( $cat === '' && class_exists( 'TTP_Catalog_Seed' ) ) {
			$cat = TTP_Catalog_Seed::TCY_CATEGORY_MBA_ENTRANCE;
		}

		foreach ( $target_ids as $target_tcy ) {
			++$out['total'];

			$attempt  = ttp_tcy_call_add_course_with_retries( $target_tcy, $cid, $cat, $order_id, $product_id, $pair_sub_cat );
			$outcome  = isset( $attempt['outcome'] ) ? (string) $attempt['outcome'] : 'failed';
			$response = isset( $attempt['response'] ) && is_array( $attempt['response'] ) ? $attempt['response'] : [];

			if ( $outcome === 'success' ) {
				++$out['added'];
			} elseif ( $outcome === 'already' || $outcome === 'pack_conflict' ) {
				++$out['already'];
			} else {
				++$out['failed'];
			}

			$out['details'][] = [
				'tcy_user_id'      => $target_tcy,
				'course_id'        => $cid,
				'stored_course_id' => $stored_course_id,
				'category_id'      => isset( $attempt['category_id'] ) ? (string) $attempt['category_id'] : $cat,
				'sub_cat'          => isset( $attempt['sub_cat'] ) ? (string) $attempt['sub_cat'] : '',
				'variant'          => isset( $attempt['variant'] ) ? (string) $attempt['variant'] : '',
				'order_id'         => $order_id,
				'line_name'        => $line_name,
				'status'           => $outcome,
				'error'            => ( $outcome === 'failed' ) ? ttp_tcy_response_error_message( $response ) : '',
			];

			usleep( ttp_tcy_api_delay_us( 'add_course_loop', 80000 ) );

			if ( $outcome !== 'failed' && $target_tcy === $tcy_user_id ) {
				break;
			}
		}
	}

	$sync_payload = [
		'time'        => current_time( 'mysql' ),
		'added'       => $out['added'],
		'already'     => $out['already'],
		'failed'      => $out['failed'],
		'total'       => $out['total'],
		'course_ids'  => $out['course_ids'],
		'tcy_user_id' => $tcy_user_id,
		'details'     => $out['details'],
	];

	if ( (int) $wp_user_id > 0 ) {
		update_user_meta( (int) $wp_user_id, '_ttp_tcy_last_course_sync', wp_json_encode( $sync_payload ) );
	}
	if ( $billing_email !== '' ) {
		update_option( 'ttp_tcy_last_sync_' . md5( strtolower( $billing_email ) ), $sync_payload, false );
	}

	return $out;
}

/**
 * Call TCY add_course for every distinct course the customer has paid for.
 *
 * @param string $tcy_user_id    TCY user id.
 * @param int    $wp_user_id      WP user id.
 * @param string $billing_email   Billing email.
 * @return int Number of add_course API attempts (distinct course_ids).
 */
function ttp_tcy_sync_all_purchased_courses_for_user( $tcy_user_id, $wp_user_id = 0, $billing_email = '' ) {
	$result = ttp_tcy_loop_add_all_courses_for_user_id( $tcy_user_id, $wp_user_id, $billing_email );
	return isset( $result['total'] ) ? (int) $result['total'] : 0;
}

/**
 * Re-run add_course for every paid course on a customer's TCY account (support / repair).
 *
 * @param int    $wp_user_id    WordPress user ID.
 * @param string $billing_email Billing email (guest orders).
 * @return int add_course calls made.
 */
function ttp_tcy_repair_customer_enrollments( $wp_user_id = 0, $billing_email = '' ) {
	return ttp_tcy_loop_add_all_courses_for_user_id( '', (int) $wp_user_id, (string) $billing_email );
}

/**
 * Parse TCY login API JSON for a redirect URL (field name varies by ERP version).
 *
 * @param array|mixed $response Decoded API response.
 * @return string
 */
function ttp_extract_login_url_from_tcy_response( $response ) {
    if ( ! is_array( $response ) ) {
        return '';
    }
    $keys = apply_filters( 'ttp_tcy_login_response_url_keys', array( 'link', 'login_link', 'url', 'redirect_url', 'redirect', 'magic_link', 'login_url', 'erp_link' ) );
    foreach ( $keys as $k ) {
        if ( ! empty( $response[ $k ] ) && is_string( $response[ $k ] ) ) {
            return $response[ $k ];
        }
    }
    if ( ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
        foreach ( $keys as $k ) {
            if ( ! empty( $response['data'][ $k ] ) && is_string( $response['data'][ $k ] ) ) {
                return $response['data'][ $k ];
            }
        }
        if ( isset( $response['data'][0] ) && is_array( $response['data'][0] ) ) {
            foreach ( $keys as $k ) {
                if ( ! empty( $response['data'][0][ $k ] ) && is_string( $response['data'][0][ $k ] ) ) {
                    return $response['data'][0][ $k ];
                }
            }
        }
    }
    return '';
}

/**
 * Whether the order has a TCY user mapping (enrollment actually linked).
 *
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function ttp_order_has_tcy_user_mapping( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	if ( 'yes' === $order->get_meta( '_tcy_enrolled' ) ) {
		return true;
	}
	global $wpdb;
	$n = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND tcy_user_id IS NOT NULL AND tcy_user_id <> ''",
			(int) $order->get_id()
		)
	);
	return $n > 0;
}

add_filter(
	'ttp_show_enrollment_confirmed_on_thankyou',
	static function ( $show, $order ) {
		return $show && ttp_order_has_tcy_user_mapping( $order );
	},
	10,
	2
);

/**
 * Latest paid order that contains a WooCommerce product (for study-portal open link).
 *
 * @param int $user_id    WP user ID.
 * @param int $product_id Product ID.
 * @return WC_Order|null
 */
function ttp_find_latest_order_with_product_for_user( $user_id, $product_id ) {
	$user_id    = (int) $user_id;
	$product_id = (int) $product_id;
	if ( $user_id < 1 || $product_id < 1 || ! function_exists( 'wc_get_orders' ) ) {
		return null;
	}
	$orders = function_exists( 'ttp_get_qualifying_orders_for_user' )
		? ttp_get_qualifying_orders_for_user( $user_id )
		: wc_get_orders(
			[
				'customer_id' => $user_id,
				'status'      => apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] ),
				'limit'       => 50,
				'orderby'     => 'date',
				'order'       => 'DESC',
			]
		);
	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		if ( function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ! ttp_order_qualifies_for_tcy_actions( $order ) ) {
			continue;
		}
		foreach ( $order->get_items() as $item ) {
			if ( (int) $item->get_product_id() === $product_id ) {
				return $order;
			}
		}
	}
	return null;
}

/**
 * TCY magic-login URL for a purchased course (used from /login/ enrolled list).
 *
 * @param int $user_id    WP user ID.
 * @param int $product_id WooCommerce product ID.
 * @return string Empty if not available.
 */
function ttp_resolve_study_portal_link_for_user_product( $user_id, $product_id, $order_id = 0, $force_course_id = '' ) {
	$user_id    = (int) $user_id;
	$product_id = (int) $product_id;
	$order_id   = (int) $order_id;
	$order      = null;
	if ( $order_id > 0 ) {
		$candidate = wc_get_order( $order_id );
		if ( $candidate instanceof WC_Order && ttp_order_belongs_to_wp_user( $candidate, $user_id ) ) {
			$order = $candidate;
		}
	}
	if ( ! $order instanceof WC_Order ) {
		$order = ttp_find_latest_order_with_product_for_user( $user_id, $product_id );
	}
	if ( ! $order instanceof WC_Order ) {
		$user_obj = get_userdata( $user_id );
		$billing  = ( $user_obj instanceof WP_User ) ? sanitize_email( (string) $user_obj->user_email ) : '';
		$tcy_user_id = ttp_get_canonical_tcy_user_id( $user_id, $billing );
		if ( $tcy_user_id !== '' ) {
			return ttp_build_study_portal_access_url(
				$tcy_user_id,
				0,
				$product_id,
				'Open course — TCY account (no Woo order)',
				$force_course_id
			);
		}
		return '';
	}
	$order_id = (int) $order->get_id();
	$order_for_sync = wc_get_order( $order_id );
	if ( $order_for_sync instanceof WC_Order ) {
		if ( function_exists( 'ttp_ensure_tcy_registered_for_order' ) && ttp_get_tcy_user_id_for_order( $order_for_sync ) === '' ) {
			ttp_ensure_tcy_registered_for_order( $order_for_sync );
		} elseif (
			( ! function_exists( 'ttp_order_tcy_mapping_is_complete' ) || ! ttp_order_tcy_mapping_is_complete( $order_for_sync ) )
			&& ttp_get_checkout_instance()
		) {
			ttp_get_checkout_instance()->sync_tcy_if_missing_mapping( $order_id );
		}
	}
	if ( function_exists( 'ttp_get_cached_study_login_url_for_order' ) ) {
		$cached = ttp_get_cached_study_login_url_for_order( $order_id );
		if ( $cached !== '' && sanitize_text_field( (string) $force_course_id ) === '' ) {
			return $cached;
		}
	}
	$tcy_user_id = ttp_get_tcy_user_id_for_order( $order );
	$billing     = (string) $order->get_billing_email();
	if ( $tcy_user_id !== '' && function_exists( 'ttp_tcy_unify_customer_to_canonical_id' ) ) {
		ttp_tcy_unify_customer_to_canonical_id( $tcy_user_id, $user_id, $billing );
		$tcy_user_id = ttp_get_canonical_tcy_user_id( $user_id, $billing );
	}
	if ( $tcy_user_id === '' ) {
		return '';
	}
	return ttp_build_study_portal_access_url(
		$tcy_user_id,
		$order_id,
		$product_id,
		'Open course — sync then ViewClientCourses',
		$force_course_id
	);
}

/**
 * Public URL to open a course (syncs order courses, then study portal login).
 *
 * @param int    $user_id    WP user ID.
 * @param int    $product_id Product ID.
 * @param int    $order_id   Optional order id.
 * @param string $order_key  Optional order key.
 * @return string
 */
function ttp_get_open_course_url_for_user( $user_id, $product_id, $order_id = 0, $order_key = '', $tcy_course_id = '' ) {
	$args = [
		'ttp_open_course' => (int) $product_id,
	];
	$tcy_course_id = sanitize_text_field( (string) $tcy_course_id );
	if ( $tcy_course_id !== '' && $tcy_course_id !== '0' ) {
		$args['ttp_tcy_course'] = $tcy_course_id;
	}
	if ( (int) $order_id > 0 ) {
		$args['ttp_order'] = (int) $order_id;
		if ( $order_key !== '' ) {
			$args['key'] = $order_key;
		}
	}
	// Use site root so TPSP /login/ page hooks do not send users to /my-account/.
	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * Paid / qualifying orders for a WP user (customer_id + matching billing_email guest orders).
 *
 * @param int $user_id WP user ID.
 * @return WC_Order[]
 */
function ttp_get_qualifying_orders_for_user( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! function_exists( 'wc_get_orders' ) ) {
		return [];
	}

	$statuses = apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] );
	$limit    = (int) apply_filters( 'ttp_enrolled_courses_order_limit', 500 );
	$by_id    = [];

	$customer_orders = wc_get_orders(
		[
			'customer_id' => $user_id,
			'limit'       => $limit,
			'status'      => $statuses,
			'orderby'     => 'date',
			'order'       => 'DESC',
		]
	);
	foreach ( (array) $customer_orders as $order ) {
		if ( $order instanceof WC_Order ) {
			$by_id[ (int) $order->get_id() ] = $order;
		}
	}

	$user = get_userdata( $user_id );
	$email = ( $user instanceof WP_User ) ? sanitize_email( (string) $user->user_email ) : '';
	if ( $email !== '' ) {
		$email_orders = wc_get_orders(
			[
				'billing_email' => $email,
				'limit'         => $limit,
				'status'        => $statuses,
				'orderby'       => 'date',
				'order'         => 'DESC',
			]
		);
		foreach ( (array) $email_orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			$oid = (int) $order->get_id();
			if ( ! isset( $by_id[ $oid ] ) ) {
				$by_id[ $oid ] = $order;
			}
		}
	}

	$orders = array_values( $by_id );
	usort(
		$orders,
		static function ( $a, $b ) {
			if ( ! $a instanceof WC_Order || ! $b instanceof WC_Order ) {
				return 0;
			}
			return $b->get_date_created() <=> $a->get_date_created();
		}
	);

	return $orders;
}

/**
 * Attach guest orders (same billing email) to the logged-in account.
 *
 * @param int $user_id WP user ID.
 * @return int Number of orders linked.
 */
function ttp_link_guest_orders_to_user_by_email( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! function_exists( 'wc_get_orders' ) ) {
		return 0;
	}
	$user = get_userdata( $user_id );
	if ( ! $user instanceof WP_User ) {
		return 0;
	}
	$email = sanitize_email( (string) $user->user_email );
	if ( $email === '' ) {
		return 0;
	}

	$linked = 0;
	$orders = wc_get_orders(
		[
			'billing_email' => $email,
			'customer_id'   => 0,
			'limit'         => 100,
			'status'        => apply_filters( 'ttp_tcy_qualifying_order_statuses', [ 'processing', 'completed', 'on-hold' ] ),
		]
	);
	foreach ( (array) $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$order->set_customer_id( $user_id );
		$order->save();
		++$linked;
	}

	return $linked;
}

/**
 * Repair names, link guest orders, and sync TCY when a student opens their account.
 *
 * @param int $user_id WP user ID.
 * @return void
 */
function ttp_sync_tcy_enrollments_for_user_on_account_view( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$cache_key = 'ttp_acct_sync_' . $user_id;
	if ( get_transient( $cache_key ) ) {
		return;
	}
	set_transient( $cache_key, 1, 5 * MINUTE_IN_SECONDS );

	if ( function_exists( 'ttp_repair_user_names_if_email_for_user' ) ) {
		ttp_repair_user_names_if_email_for_user( $user_id );
	}

	ttp_link_guest_orders_to_user_by_email( $user_id );

	if ( function_exists( 'ttp_purge_unauthorized_jbims_for_user' ) ) {
		$user = get_userdata( $user_id );
		$email = ( $user instanceof WP_User ) ? sanitize_email( (string) $user->user_email ) : '';
		ttp_purge_unauthorized_jbims_for_user( $user_id, $email );
	}

	foreach ( ttp_get_qualifying_orders_for_user( $user_id ) as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		if ( function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ! ttp_order_qualifies_for_tcy_actions( $order ) ) {
			continue;
		}
		$tcy_id = function_exists( 'ttp_get_tcy_user_id_for_order' ) ? ttp_get_tcy_user_id_for_order( $order ) : '';
		if ( $tcy_id !== '' ) {
			if ( function_exists( 'ttp_sync_tcy_courses_for_order' ) ) {
				ttp_sync_tcy_courses_for_order( $order );
			}
			continue;
		}
		if ( function_exists( 'ttp_ensure_tcy_registered_for_order' ) ) {
			ttp_ensure_tcy_registered_for_order( $order );
		}
	}

	if ( function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		ttp_sync_portal_enrollments_for_user( $user_id );
	}
}

/**
 * Merge registered TCY mapping rows (covers split accounts / repaired orders).
 *
 * @param int   $user_id WP user ID.
 * @param array $out     Existing rows.
 * @param int   $max     Max rows.
 * @return array<string, array<string, mixed>>
 */
function ttp_merge_enrolled_rows_from_order_mapping( $user_id, $out, $max ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return $out;
	}
	global $wpdb;
	$user  = get_userdata( $user_id );
	$email = ( $user instanceof WP_User ) ? sanitize_email( (string) $user->user_email ) : '';
	$tcy_ids = function_exists( 'ttp_collect_all_tcy_user_ids_for_customer' )
		? ttp_collect_all_tcy_user_ids_for_customer( $user_id, $email )
		: [];

	$where = [ 'wp_user_id = %d' ];
	$args  = [ $user_id ];
	if ( ! empty( $tcy_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $tcy_ids ), '%s' ) );
		$where[]      = "tcy_user_id IN ($placeholders)";
		$args         = array_merge( $args, $tcy_ids );
	}
	$sql = "SELECT order_id, tcy_course_id, tcy_category_id FROM {$wpdb->prefix}ttp_order_mapping
		WHERE status = 'registered' AND tcy_course_id <> '' AND (" . implode( ' OR ', $where ) . ')
		ORDER BY id DESC LIMIT 100';
	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	if ( empty( $rows ) ) {
		return $out;
	}

	foreach ( $rows as $row ) {
		if ( count( $out ) >= $max ) {
			break;
		}
		$order_id   = (int) $row->order_id;
		$tcy_course = sanitize_text_field( (string) $row->tcy_course_id );
		$row_key    = 'map-' . $order_id . '-' . $tcy_course;
		if ( isset( $out[ $row_key ] ) ) {
			continue;
		}
		foreach ( $out as $existing ) {
			if ( ! is_array( $existing ) ) {
				continue;
			}
			if ( (int) ( $existing['order_id'] ?? 0 ) === $order_id
				&& (string) ( $existing['tcy_course_id'] ?? '' ) === $tcy_course ) {
				continue 2;
			}
		}
		$name       = '';
		$product_id = 0;
		$order      = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
		if ( $order instanceof WC_Order ) {
			foreach ( $order->get_items() as $item ) {
				$ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
					? ttp_get_tcy_ids_for_line_item( $item, false )
					: ttp_get_tcy_ids_for_product( (int) $item->get_product_id(), false );
				if ( isset( $ids['course_id'] ) && (string) $ids['course_id'] === $tcy_course ) {
					$name       = trim( (string) $item->get_name() );
					$product_id = (int) $item->get_product_id();
					break;
				}
			}
			if ( '90334' === $tcy_course && ! ttp_order_has_jbims_line_item( $order ) ) {
				continue;
			}
		} elseif ( '90334' === $tcy_course ) {
			continue;
		}
		if ( $name === '' && function_exists( 'ttp_tcy_get_course_api_profile' ) ) {
			$profile = ttp_tcy_get_course_api_profile( $tcy_course );
			if ( is_array( $profile ) && ! empty( $profile['label'] ) ) {
				$name = (string) $profile['label'];
			}
		}
		if ( $name === '' ) {
			$name = 'Course ' . $tcy_course;
		}
		$out[ $row_key ] = [
			'name'          => $name,
			'product_id'    => $product_id,
			'tcy_course_id' => $tcy_course,
			'order_id'      => $order_id,
			'open_url'      => ttp_get_open_course_url_for_user( $user_id, $product_id, $order_id, '', $tcy_course ),
		];
	}

	return $out;
}

/**
 * JBIMS WooCommerce product IDs (Bootcamp + Elite).
 *
 * @return int[]
 */
function ttp_get_jbims_product_ids() {
	$ids = array();
	if ( ! class_exists( 'TTP_Catalog_Seed' ) ) {
		return $ids;
	}
	foreach ( array( 'jbims-mfin-mhrd-bootcamp', 'jbims-mfin-mhrd-bootcamp-elite' ) as $slug ) {
		$pid = (int) TTP_Catalog_Seed::get_product_id_by_slug( $slug );
		if ( $pid > 0 ) {
			$ids[] = $pid;
		}
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Whether a WooCommerce order line is JBIMS Bootcamp / Elite.
 *
 * @param WC_Order_Item_Product|mixed $item Line item.
 * @return bool
 */
function ttp_order_item_is_jbims_product( $item ) {
	if ( ! $item instanceof WC_Order_Item_Product ) {
		return false;
	}
	$name = (string) $item->get_name();
	if ( preg_match( '/jbims|mfin|mhrd|bootcamp/i', $name ) ) {
		return true;
	}
	$pid = (int) $item->get_product_id();
	return $pid > 0 && in_array( $pid, ttp_get_jbims_product_ids(), true );
}

/**
 * Whether this customer actually purchased JBIMS (Bootcamp or Elite).
 *
 * @param int    $user_id WP user ID.
 * @param string $email   Billing email.
 * @return bool
 */
function ttp_user_has_jbims_purchase( $user_id = 0, $email = '' ) {
	$user_id = (int) $user_id;
	$email   = strtolower( trim( sanitize_email( (string) $email ) ) );
	if ( $email === '' && $user_id > 0 ) {
		$user = get_userdata( $user_id );
		if ( $user instanceof WP_User ) {
			$email = sanitize_email( (string) $user->user_email );
		}
	}
	if ( ! function_exists( 'wc_get_orders' ) ) {
		return false;
	}

	$candidates = array();
	if ( $user_id > 0 ) {
		$candidates = array_merge(
			$candidates,
			(array) ttp_get_qualifying_orders_for_user( $user_id )
		);
	}
	if ( $email !== '' && is_email( $email ) ) {
		$candidates = array_merge(
			$candidates,
			(array) wc_get_orders(
				array(
					'billing_email' => $email,
					'limit'         => 20,
					'status'        => 'any',
					'orderby'       => 'date',
					'order'         => 'DESC',
				)
			)
		);
	}

	$seen = array();
	foreach ( $candidates as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$oid = (int) $order->get_id();
		if ( isset( $seen[ $oid ] ) ) {
			continue;
		}
		$seen[ $oid ] = true;

		$qualifies = function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ttp_order_qualifies_for_tcy_actions( $order );
		$gateway   = function_exists( 'ttp_order_has_gateway_payment' ) && ttp_order_has_gateway_payment( $order );
		if ( ! $qualifies && ! $gateway ) {
			continue;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ttp_order_item_is_jbims_product( $item ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Confirmed JBIMS access: WooCommerce purchase OR admin/TCY bulk grant (not every TCY user).
 *
 * @param int    $user_id WP user ID.
 * @param string $email   Billing email.
 * @return bool
 */
function ttp_user_has_jbims_entitlement( $user_id = 0, $email = '' ) {
	$user_id = (int) $user_id;
	if ( function_exists( 'ttp_user_has_jbims_purchase' ) && ttp_user_has_jbims_purchase( $user_id, $email ) ) {
		return true;
	}
	if ( $user_id > 0 && 'yes' === (string) get_user_meta( $user_id, '_ttp_jbims_entitlement', true ) ) {
		return true;
	}
	return false;
}

/**
 * Persist JBIMS entitlement and refresh portal rows (TCY-linked students without WC orders).
 *
 * @param int    $user_id WP user ID.
 * @param string $sub_cat JBIMS pack id (38081 Bootcamp / 38033 Elite).
 * @return bool
 */
function ttp_grant_jbims_entitlement( $user_id, $sub_cat = '38081' ) {
	$user_id = (int) $user_id;
	$sub_cat = sanitize_text_field( (string) $sub_cat );
	if ( $user_id < 1 ) {
		return false;
	}
	if ( $sub_cat === '' ) {
		$sub_cat = '38081';
	}
	update_user_meta( $user_id, '_ttp_jbims_entitlement', 'yes' );
	update_user_meta( $user_id, '_ttp_jbims_sub_cat', $sub_cat );
	if ( function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		ttp_sync_portal_enrollments_for_user( $user_id );
	}
	return true;
}

/**
 * Strip JBIMS from portal, mapping, and TCY when the student did not purchase or get admin grant.
 *
 * @param int    $user_id WP user ID.
 * @param string $email   Billing email.
 * @return bool True when a purge ran.
 */
function ttp_purge_unauthorized_jbims_for_user( $user_id = 0, $email = '' ) {
	if ( function_exists( 'ttp_user_has_jbims_entitlement' ) && ttp_user_has_jbims_entitlement( $user_id, $email ) ) {
		return false;
	}
	if ( ! function_exists( 'ttp_bulk_revoke_false_jbims_for_customer' ) ) {
		return false;
	}
	$user_id = (int) $user_id;
	if ( $email === '' && $user_id > 0 ) {
		$user = get_userdata( $user_id );
		if ( $user instanceof WP_User ) {
			$email = sanitize_email( (string) $user->user_email );
		}
	}
	if ( $email === '' || ! is_email( $email ) ) {
		return false;
	}
	ttp_bulk_revoke_false_jbims_for_customer( $email );
	return true;
}

/**
 * Whether a WooCommerce order contains a JBIMS line item.
 *
 * @param WC_Order|mixed $order Order.
 * @return bool
 */
function ttp_order_has_jbims_line_item( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	foreach ( $order->get_items() as $item ) {
		if ( ttp_order_item_is_jbims_product( $item ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Build / refresh login-panel course rows from orders, TCY mapping, and linked TCY accounts.
 *
 * @param int    $user_id WP user ID.
 * @param string $email   Optional billing email.
 * @return array<int, array<string, mixed>>
 */
function ttp_sync_portal_enrollments_for_user( $user_id, $email = '' ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return array();
	}

	$user = get_userdata( $user_id );
	if ( $email === '' && $user instanceof WP_User ) {
		$email = sanitize_email( (string) $user->user_email );
	}

	$rows = array();
	$seen = array();

	$push = static function ( $name, $tcy_course, $product_id, $sub_cat ) use ( &$rows, &$seen ) {
		$name       = trim( (string) $name );
		$tcy_course = function_exists( 'ttp_tcy_canonical_course_id' )
			? ttp_tcy_canonical_course_id( (string) $tcy_course, $name )
			: sanitize_text_field( (string) $tcy_course );
		if ( $name === '' || $tcy_course === '' ) {
			return;
		}
		if ( isset( $seen[ $tcy_course ] ) ) {
			return;
		}
		$seen[ $tcy_course ] = true;
		$rows[]              = array(
			'name'          => $name,
			'tcy_course_id' => $tcy_course,
			'product_id'    => (int) $product_id,
			'sub_cat'       => sanitize_text_field( (string) $sub_cat ),
		);
	};

	foreach ( ttp_get_qualifying_orders_for_user( $user_id ) as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		if ( function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ! ttp_order_qualifies_for_tcy_actions( $order ) ) {
			continue;
		}
		foreach ( $order->get_items() as $item ) {
			if ( ! ttp_order_item_is_jbims_product( $item ) ) {
				continue;
			}
			$pid  = (int) $item->get_product_id();
			$name = trim( (string) $item->get_name() );
			$ids  = function_exists( 'ttp_get_tcy_ids_for_line_item' )
				? ttp_get_tcy_ids_for_line_item( $item, true )
				: ( function_exists( 'ttp_get_tcy_ids_for_product' ) ? ttp_get_tcy_ids_for_product( $pid, true ) : array() );
			$course = (string) ( $ids['course_id'] ?? '90334' );
			$sub    = preg_match( '/elite/i', $name ) ? '38033' : '38081';
			$push( $name, $course, $pid, $sub );
		}
	}

	// Gateway-paid orders stuck in cancelled/failed still count for login-panel tier display.
	if ( function_exists( 'wc_get_orders' ) && $email !== '' && is_email( $email ) ) {
		$stuck_orders = wc_get_orders(
			array(
				'billing_email' => $email,
				'limit'         => 10,
				'status'        => array( 'cancelled', 'failed' ),
				'orderby'       => 'date',
				'order'         => 'DESC',
			)
		);
		foreach ( (array) $stuck_orders as $order ) {
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			if ( ! function_exists( 'ttp_order_has_gateway_payment' ) || ! ttp_order_has_gateway_payment( $order ) ) {
				continue;
			}
			foreach ( $order->get_items() as $item ) {
				if ( ! ttp_order_item_is_jbims_product( $item ) ) {
					continue;
				}
				$pid  = (int) $item->get_product_id();
				$name = trim( (string) $item->get_name() );
				$sub  = preg_match( '/elite/i', $name ) ? '38033' : '38081';
				$push( $name, '90334', $pid, $sub );
			}
		}
	}

	global $wpdb;
	$tcy_id = function_exists( 'ttp_get_canonical_tcy_user_id' )
		? ttp_get_canonical_tcy_user_id( $user_id, $email )
		: '';
	if ( $tcy_id !== '' && ttp_user_has_jbims_entitlement( $user_id, $email ) ) {
		$map_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tcy_course_id, order_id FROM {$wpdb->prefix}ttp_order_mapping
				WHERE status = 'registered' AND tcy_course_id <> '' AND (wp_user_id = %d OR tcy_user_id = %s)
				ORDER BY id DESC LIMIT 20",
				$user_id,
				$tcy_id
			)
		);
		foreach ( (array) $map_rows as $map_row ) {
			$tcy_course = function_exists( 'ttp_tcy_canonical_course_id' )
				? ttp_tcy_canonical_course_id( (string) $map_row->tcy_course_id )
				: (string) $map_row->tcy_course_id;
			if ( $tcy_course === '' || $tcy_course === '90334' ) {
				$tcy_course = '90334';
			}
			if ( '90334' !== $tcy_course ) {
				continue;
			}
			$name       = 'JBIMS MFIN MHRD Bootcamp';
			$product_id = 0;
			$sub_cat    = '38081';
			$order      = function_exists( 'wc_get_order' ) ? wc_get_order( (int) $map_row->order_id ) : null;
			if ( $order instanceof WC_Order ) {
				foreach ( $order->get_items() as $item ) {
					if ( ! ttp_order_item_is_jbims_product( $item ) ) {
						continue;
					}
					$name       = trim( (string) $item->get_name() );
					$product_id = (int) $item->get_product_id();
					$sub_cat    = preg_match( '/elite/i', $name ) ? '38033' : '38081';
					break;
				}
			}
			if ( $product_id < 1 && class_exists( 'TTP_Catalog_Seed' ) ) {
				$slug       = ( '38033' === $sub_cat ) ? 'jbims-mfin-mhrd-bootcamp-elite' : 'jbims-mfin-mhrd-bootcamp';
				$product_id = (int) TTP_Catalog_Seed::get_product_id_by_slug( $slug );
				$def        = TTP_Catalog_Seed::get_definition_by_slug( $slug );
				if ( is_array( $def ) && ! empty( $def['name'] ) ) {
					$name = (string) $def['name'];
				}
			}
			$push( $name, '90334', $product_id, $sub_cat );
		}
	}

	if ( ttp_user_has_jbims_entitlement( $user_id, $email ) && ! isset( $seen['90334'] ) ) {
		$sub_cat = (string) get_user_meta( $user_id, '_ttp_jbims_sub_cat', true );
		if ( $sub_cat === '' ) {
			$sub_cat = '38081';
		}
		$slug       = ( '38033' === $sub_cat ) ? 'jbims-mfin-mhrd-bootcamp-elite' : 'jbims-mfin-mhrd-bootcamp';
		$name       = ( '38033' === $sub_cat ) ? 'JBIMS MFIN MHRD Bootcamp Elite' : 'JBIMS MFIN MHRD Bootcamp';
		$product_id = 0;
		if ( class_exists( 'TTP_Catalog_Seed' ) ) {
			$product_id = (int) TTP_Catalog_Seed::get_product_id_by_slug( $slug );
			$def        = TTP_Catalog_Seed::get_definition_by_slug( $slug );
			if ( is_array( $def ) && ! empty( $def['name'] ) ) {
				$name = (string) $def['name'];
			}
		}
		$push( $name, '90334', $product_id, $sub_cat );
	}

	if ( ! empty( $rows ) ) {
		update_user_meta( $user_id, '_ttp_portal_enrollments', $rows );
	} elseif ( $user_id > 0 && ! ttp_user_has_jbims_entitlement( $user_id, $email ) ) {
		delete_user_meta( $user_id, '_ttp_portal_enrollments' );
	}

	return $rows;
}

/**
 * Show courses for TCY-linked students without a qualifying WooCommerce order (manual / ERP enrollments).
 *
 * @param int   $user_id WP user ID.
 * @param array $out     Existing rows.
 * @param int   $max     Max rows.
 * @return array<string, array<string, mixed>>
 */
function ttp_merge_enrolled_rows_from_tcy_account( $user_id, $out, $max ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return $out;
	}

	$user  = get_userdata( $user_id );
	$email = ( $user instanceof WP_User ) ? sanitize_email( (string) $user->user_email ) : '';
	$tcy_id = function_exists( 'ttp_get_canonical_tcy_user_id' )
		? ttp_get_canonical_tcy_user_id( $user_id, $email )
		: '';
	if ( $tcy_id === '' ) {
		return $out;
	}

	if ( function_exists( 'ttp_sync_portal_enrollments_for_user' ) ) {
		ttp_sync_portal_enrollments_for_user( $user_id, $email );
	}

	$has_jbims = function_exists( 'ttp_user_has_jbims_entitlement' ) && ttp_user_has_jbims_entitlement( $user_id, $email );
	$manual    = get_user_meta( $user_id, '_ttp_portal_enrollments', true );
	if ( ! is_array( $manual ) || empty( $manual ) ) {
		return $out;
	}

	foreach ( $manual as $row ) {
		if ( count( $out ) >= $max ) {
			break;
		}
		if ( ! is_array( $row ) ) {
			continue;
		}
		$name       = trim( (string) ( $row['name'] ?? '' ) );
		$tcy_course = sanitize_text_field( (string) ( $row['tcy_course_id'] ?? '' ) );
		$product_id = (int) ( $row['product_id'] ?? 0 );
		if ( $name === '' || $tcy_course === '' ) {
			continue;
		}
		if ( ! $has_jbims && ( '90334' === $tcy_course || preg_match( '/jbims|mfin|mhrd|bootcamp/i', $name ) ) ) {
			continue;
		}
		$row_key = 'tcy-account-' . $tcy_course;
		if ( isset( $out[ $row_key ] ) ) {
			continue;
		}
		$out[ $row_key ] = [
			'name'          => $name,
			'product_id'    => $product_id,
			'tcy_course_id' => $tcy_course,
			'order_id'      => 0,
			'open_url'      => ttp_get_open_course_url_for_user( $user_id, $product_id, 0, '', $tcy_course ),
		];
	}

	return $out;
}

/**
 * Every paid TCY line item for a customer (one row per order line — supports 10+ courses).
 *
 * @param int $user_id WP user ID.
 * @return array<string, array{name: string, open_url: string, product_id: int, tcy_course_id: string, order_id: int}>
 */
function ttp_collect_enrolled_course_rows_for_user( $user_id ) {
	$user_id = (int) $user_id;
	$out     = [];
	$max     = (int) apply_filters( 'ttp_max_enrolled_courses_for_login_panel', defined( 'TTP_MAX_ENROLLED_COURSES' ) ? TTP_MAX_ENROLLED_COURSES : 50 );
	if ( $user_id < 1 || ! function_exists( 'wc_get_orders' ) || $max < 1 ) {
		return $out;
	}

	if ( function_exists( 'ttp_sync_tcy_enrollments_for_user_on_account_view' ) ) {
		ttp_sync_tcy_enrollments_for_user_on_account_view( $user_id );
	}

	foreach ( ttp_get_qualifying_orders_for_user( $user_id ) as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		if ( function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ! ttp_order_qualifies_for_tcy_actions( $order ) ) {
			continue;
		}
		$order_id = (int) $order->get_id();
		foreach ( $order->get_items() as $item ) {
			if ( count( $out ) >= $max ) {
				break 2;
			}
			$pid = (int) $item->get_product_id();
			if ( $pid < 1 ) {
				continue;
			}
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
			if ( $product instanceof WC_Product && ! $product->is_virtual() && ! $product->is_downloadable() ) {
				$name_for_check = trim( (string) $item->get_name() );
				if ( $name_for_check === '' ) {
					$name_for_check = trim( (string) $product->get_name() );
				}
				$is_course = has_term( [ 'course', 'courses', 'test-series', 'tests', 'exam' ], 'product_cat', $pid );
				if ( ! $is_course && function_exists( 'ttp_tcy_is_syncable_course_id' ) ) {
					$ids_probe = function_exists( 'ttp_get_tcy_ids_for_line_item' )
						? ttp_get_tcy_ids_for_line_item( $item, false )
						: ttp_get_tcy_ids_for_product( $pid, false );
					$probe_cid = isset( $ids_probe['course_id'] ) ? (string) $ids_probe['course_id'] : '';
					$is_course = $probe_cid !== '' && ttp_tcy_is_syncable_course_id( $probe_cid, $name_for_check );
				}
				if ( ! $is_course ) {
					continue;
				}
			}
			$ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
				? ttp_get_tcy_ids_for_line_item( $item, true )
				: ( function_exists( 'ttp_get_tcy_ids_for_product' )
					? ttp_get_tcy_ids_for_product( $pid, true )
					: [ 'course_id' => (string) get_post_meta( $pid, '_ttp_tcy_course_id', true ) ] );
			$tcy_course = isset( $ids['course_id'] ) ? (string) $ids['course_id'] : '';
			$name = trim( (string) $item->get_name() );
			if ( $name === '' && $product instanceof WC_Product ) {
				$name = trim( (string) $product->get_name() );
			}
			if ( $name === '' ) {
				continue;
			}
			$line_id  = method_exists( $item, 'get_id' ) ? (int) $item->get_id() : 0;
			$row_key  = $order_id . '-' . ( $line_id > 0 ? $line_id : ( $pid . '-' . md5( $name ) ) );
			if ( isset( $out[ $row_key ] ) ) {
				continue;
			}
			$out[ $row_key ] = [
				'name'          => $name,
				'product_id'    => $pid,
				'tcy_course_id' => $tcy_course,
				'order_id'      => $order_id,
				'open_url'      => ttp_get_open_course_url_for_user( $user_id, $pid, $order_id, '', $tcy_course ),
			];
		}
	}

	$out = ttp_merge_enrolled_rows_from_order_mapping( $user_id, $out, $max );
	$out = ttp_merge_enrolled_rows_from_tcy_account( $user_id, $out, $max );

	uasort(
		$out,
		static function ( $a, $b ) {
			return strcasecmp( (string) ( $a['name'] ?? '' ), (string) ( $b['name'] ?? '' ) );
		}
	);
	return $out;
}

/**
 * Enrolled courses for the /login/ student panel (TCY-linked products only).
 *
 * @param int $user_id WP user ID.
 * @return array<string, array{name: string, open_url: string, product_id?: int, tcy_course_id?: string, order_id?: int}>
 */
function ttp_get_user_enrolled_courses_for_login_panel( $user_id ) {
	return ttp_collect_enrolled_course_rows_for_user( (int) $user_id );
}

/**
 * Checkout helper for TCY repair (works even if $GLOBALS was not set yet).
 *
 * @return TTP_Checkout|null
 */
function ttp_get_checkout_instance() {
	if ( isset( $GLOBALS['ttp_checkout'] ) && is_object( $GLOBALS['ttp_checkout'] ) ) {
		return $GLOBALS['ttp_checkout'];
	}
	if ( ! class_exists( 'TTP_Checkout', false ) ) {
		return null;
	}
	$GLOBALS['ttp_checkout'] = new TTP_Checkout();

	return $GLOBALS['ttp_checkout'];
}

/**
 * Register / repair TCY mapping when a student opens a course (self-heal no_tcy).
 *
 * @param WC_Order $order Order.
 * @return void
 */
function ttp_ensure_tcy_registered_for_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	$order_id = (int) $order->get_id();
	if ( $order_id < 1 ) {
		return;
	}
	$checkout = ttp_get_checkout_instance();
	if ( ! $checkout ) {
		return;
	}
	if ( ttp_get_tcy_user_id_for_order( $order ) === '' ) {
		$checkout->sync_tcy_if_missing_mapping( $order_id );
	}
	if ( ttp_get_tcy_user_id_for_order( $order ) === '' ) {
		$checkout->trigger_tcy_registration( $order_id );
	}
	if ( ttp_get_tcy_user_id_for_order( $order ) === '' ) {
		return;
	}
	// add_course is only for returning TCY users buying additional courses; register already enrolls on first purchase.
	$needs_add_course = 'yes' !== (string) $order->get_meta( '_ttp_tcy_fresh_register' );
	if ( ! $needs_add_course ) {
		foreach ( $order->get_items() as $item ) {
			$product_id = (int) $item->get_product_id();
			if ( $product_id < 1 ) {
				continue;
			}
			$ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
				? ttp_get_tcy_ids_for_line_item( $item, false )
				: ttp_get_tcy_ids_for_product( $product_id, false );
			$cid = isset( $ids['course_id'] ) ? (string) $ids['course_id'] : '';
			if ( $cid !== '' && ! ttp_tcy_order_course_enrolled_via_register( (int) $order->get_id(), $cid ) ) {
				$needs_add_course = true;
				break;
			}
		}
	}
	if ( $needs_add_course && function_exists( 'ttp_tcy_loop_add_all_courses_for_user_id' ) ) {
		ttp_tcy_loop_add_all_courses_for_user_id(
			ttp_get_tcy_user_id_for_order( $order ),
			(int) $order->get_user_id(),
			(string) $order->get_billing_email()
		);
	}
}

/**
 * Open course / study access: sync TCY courses then redirect to study portal (ViewClientCourses).
 *
 * @return void
 */
function ttp_maybe_redirect_open_course() {
	static $handled = false;
	if ( $handled || ! isset( $_GET['ttp_open_course'] ) ) {
		return;
	}
	if ( ! function_exists( 'wc_get_order' ) || ! class_exists( 'TTP_TCY_API', false ) ) {
		return;
	}
	$handled    = true;
	$product_id = absint( wp_unslash( $_GET['ttp_open_course'] ) );
	$order_id   = isset( $_GET['ttp_order'] ) ? absint( wp_unslash( $_GET['ttp_order'] ) ) : 0;
	if ( $product_id < 1 ) {
		return;
	}
	$order = $order_id > 0 ? wc_get_order( $order_id ) : null;
	$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
	$user_id = 0;
	if ( $order instanceof WC_Order && $key !== '' && $order->key_is_valid( $key ) ) {
		$user_id = (int) $order->get_user_id();
		if ( $user_id < 1 && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}
	} elseif ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		$login_target = add_query_arg(
			array_filter(
				[
					'ttp_open_course' => $product_id,
					'ttp_order'       => $order_id > 0 ? $order_id : null,
				]
			),
			home_url( '/login/' )
		);
		wp_safe_redirect( function_exists( 'tpsp_get_login_redirect_url' ) ? tpsp_get_login_redirect_url( $login_target ) : wp_login_url( $login_target ) );
		exit;
	}
	if ( $user_id < 1 ) {
		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}
	$force_course = isset( $_GET['ttp_tcy_course'] ) ? sanitize_text_field( wp_unslash( $_GET['ttp_tcy_course'] ) ) : '';
	$fail_reason  = 'failed';
	$order_check  = $order_id > 0 ? wc_get_order( $order_id ) : ttp_find_latest_order_with_product_for_user( (int) $user_id, $product_id );
	if ( ! $order_check instanceof WC_Order ) {
		$user_obj = get_userdata( (int) $user_id );
		$billing  = ( $user_obj instanceof WP_User ) ? sanitize_email( (string) $user_obj->user_email ) : '';
		$tcy_direct = ttp_get_canonical_tcy_user_id( (int) $user_id, $billing );
		if ( $tcy_direct !== '' ) {
			$link = ttp_build_study_portal_access_url( $tcy_direct, 0, $product_id, 'Open course — TCY account', $force_course );
			if ( $link !== '' ) {
				ttp_redirect_to_study_portal( $link );
			}
			$fail_reason = 'login_failed';
		} else {
			$fail_reason = 'no_order';
		}
	} else {
		if ( function_exists( 'ttp_ensure_tcy_registered_for_order' ) ) {
			ttp_ensure_tcy_registered_for_order( $order_check );
		}
		$tcy_check = ttp_get_tcy_user_id_for_order( $order_check );
		if ( $tcy_check === '' ) {
			$fail_reason = 'no_tcy';
		}
	}
	$link = ttp_resolve_study_portal_link_for_user_product( (int) $user_id, $product_id, $order_id, $force_course );
	if ( $link !== '' ) {
		ttp_redirect_to_study_portal( $link );
	}
	if ( 'failed' === $fail_reason && $order_check instanceof WC_Order && ttp_get_tcy_user_id_for_order( $order_check ) !== '' ) {
		$fail_reason = 'login_failed';
	}
	wp_safe_redirect( add_query_arg( 'ttp_course_open', $fail_reason, home_url( '/login/' ) ) );
	exit;
}

/**
 * One-time defaults after plugin update (enable_online_tab = 0 for new registrations).
 */
function ttp_maybe_apply_tcy_option_defaults() {
	if ( get_option( 'ttp_tcy_defaults_v', '' ) === TTP_VERSION ) {
		return;
	}
	update_option( 'ttp_tcy_enable_online_tab', 0 );
	update_option( 'ttp_tcy_send_sub_cat', 1 );
	update_option( 'ttp_tcy_defaults_v', TTP_VERSION );
}
add_action( 'init', 'ttp_maybe_apply_tcy_option_defaults', 1 );

/* ── Autoload classes ── */
require_once TTP_DIR . 'includes/class-ttp-settings.php';
require_once TTP_DIR . 'includes/class-ttp-tcy-api.php';
require_once TTP_DIR . 'includes/class-ttp-student.php';
require_once TTP_DIR . 'includes/class-ttp-checkout.php';
require_once TTP_DIR . 'includes/class-ttp-products.php';
require_once TTP_DIR . 'includes/class-ttp-single-course.php';
require_once TTP_DIR . 'includes/class-ttp-enroll-page.php';
require_once TTP_DIR . 'includes/class-ttp-klaviyo-campaigns.php';
require_once TTP_DIR . 'includes/class-ttp-catalog-seed.php';
require_once TTP_DIR . 'includes/class-ttp-study-redirect-log.php';

/**
 * Record a study-portal redirect (keeps last 10 in WP admin → TTP Dashboard → Study Redirect Logs).
 *
 * @param string $source     e.g. ajax_button, thankyou_redirect, cached_link.
 * @param int    $order_id   WooCommerce order ID.
 * @param string $raw_url    URL from TCY before rewrite.
 * @param string $final_url  URL sent to the browser.
 * @param string $tcy_user_id TCY user id.
 * @param string $note       Optional note.
 * @return void
 */
function ttp_log_study_portal_redirect( $source, $order_id, $raw_url, $final_url, $tcy_user_id = '', $note = '' ) {
	if ( ! class_exists( 'TTP_Study_Redirect_Log', false ) ) {
		return;
	}
	TTP_Study_Redirect_Log::add(
		array(
			'source'      => sanitize_key( (string) $source ),
			'order_id'    => (int) $order_id,
			'user_id'     => get_current_user_id(),
			'tcy_user_id' => sanitize_text_field( (string) $tcy_user_id ),
			'raw_url'     => esc_url_raw( (string) $raw_url ),
			'final_url'   => esc_url_raw( (string) $final_url ),
			'note'        => sanitize_text_field( (string) $note ),
		)
	);
}
require_once TTP_DIR . 'includes/class-ttp-cart.php';
require_once TTP_DIR . 'admin/class-ttp-admin.php';

/* ── Activation: create DB tables + registration page ── */
register_activation_hook( __FILE__, 'ttp_activate' );
function ttp_activate() {
    global $wpdb;
    $c = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ttp_students (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        wp_user_id bigint(20) NOT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        mobile varchar(20) NOT NULL,
        username varchar(100) NOT NULL,
        tcy_user_id varchar(100) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $c;" );

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ttp_api_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) DEFAULT NULL,
        action varchar(100) NOT NULL,
        request_data longtext,
        response_data longtext,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $c;" );

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ttp_order_mapping (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        wp_user_id bigint(20) NOT NULL,
        tcy_user_id varchar(100) DEFAULT NULL,
        tcy_course_id varchar(100) DEFAULT NULL,
        tcy_category_id varchar(100) DEFAULT NULL,
        login_link text DEFAULT NULL,
        status varchar(50) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $c;" );

    if ( ! get_page_by_path( 'student-registration' ) ) {
        wp_insert_post( [
            'post_title'   => 'Student Registration',
            'post_name'    => 'student-registration',
            'post_content' => '[ttp_student_registration]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
    }
}

/* ── Boot after WooCommerce loads ── */
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    if ( '' === (string) get_option( 'ttp_tcy_client_id', '' ) ) {
        update_option( 'ttp_tcy_client_id', '7716' );
    }
    new TTP_Settings();
    new TTP_Student();
    new TTP_Checkout();
    new TTP_Products();
    new TTP_Single_Course();
    new TTP_Enroll_Page();
    new TTP_Cart();
    new TTP_Admin();

    if ( class_exists( 'TTP_Catalog_Seed' ) ) {
        $v = get_option( 'ttp_catalog_mba_cet_2027_version', '' );
        if ( $v !== TTP_Catalog_Seed::CATALOG_VERSION ) {
            TTP_Catalog_Seed::seed( false );
            TTP_Catalog_Seed::repair_all_tcy_meta();
            TTP_Catalog_Seed::repair_all_product_display_content();
        }
    }

    add_action( 'template_redirect', 'ttp_maybe_redirect_open_course', 1 );

    add_filter(
        'ttp_tcy_register_request_body',
        static function ( $payload, $data ) {
            if ( ! empty( $payload['course_id'] ) && function_exists( 'ttp_tcy_canonical_course_id' ) ) {
                $payload['course_id'] = ttp_tcy_canonical_course_id( (string) $payload['course_id'], (string) ( $data['full_name'] ?? '' ) );
            }
            return $payload;
        },
        10,
        2
    );
}, 11 );

/* ── Frontend assets ── */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(  'ttp-style',  TTP_URL . 'assets/css/ttp-style.css',  [], TTP_VERSION );
    wp_enqueue_script( 'ttp-script', TTP_URL . 'assets/js/ttp-script.js', ['jquery'], TTP_VERSION, true );
    $checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
    wp_localize_script( 'ttp-script', 'ttp_ajax', [
        'ajax_url'     => admin_url( 'admin-ajax.php' ),
        'nonce'        => wp_create_nonce( 'ttp_nonce' ),
        'login_url'    => function_exists( 'tpsp_get_login_page_url' ) ? tpsp_get_login_page_url() : home_url( '/login/' ),
        'checkout_url' => $checkout_url,
        'is_logged_in' => is_user_logged_in() ? 1 : 0,
    ] );
} );
