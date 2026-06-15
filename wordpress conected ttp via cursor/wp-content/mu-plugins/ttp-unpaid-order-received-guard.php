<?php
/**
 * Plugin Name: TTP Unpaid Order Guard
 * Description: Hard-block TCY access and success UI on unpaid order-received pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve order from order key in request.
 *
 * @return WC_Order|null
 */
function ttp_guard_get_order_from_request_key() {
	if ( ! function_exists( 'wc_get_order_id_by_order_key' ) || ! function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
	if ( '' === $key ) {
		return null;
	}
	$order_id = wc_get_order_id_by_order_key( $key );
	if ( ! $order_id ) {
		return null;
	}
	return wc_get_order( $order_id );
}

/**
 * Resolve order from order-received endpoint path when key is missing.
 *
 * @return WC_Order|null
 */
function ttp_guard_get_order_from_endpoint_id() {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$endpoint_id = function_exists( 'get_query_var' ) ? absint( get_query_var( 'order-received' ) ) : 0;
	if ( $endpoint_id > 0 ) {
		$order = wc_get_order( $endpoint_id );
		if ( $order ) {
			return $order;
		}
	}
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( preg_match( '~\/checkout\/order-received\/([0-9]+)\/?~i', $request_uri, $m ) ) {
		$order = wc_get_order( absint( $m[1] ) );
		if ( $order ) {
			return $order;
		}
	}
	return null;
}

/**
 * Resolve order by key first, fallback to endpoint ID.
 *
 * @return WC_Order|null
 */
function ttp_guard_get_order_from_request() {
	$order = ttp_guard_get_order_from_request_key();
	if ( $order ) {
		return $order;
	}
	return ttp_guard_get_order_from_endpoint_id();
}

/**
 * @param WC_Order|null $order Order object.
 * @return bool
 */
function ttp_guard_is_unpaid_order( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}
	// Align with TTP: ₹0 / coupon orders can be processing with needs_payment() false while is_paid() is false.
	if ( function_exists( 'ttp_order_qualifies_for_tcy_actions' ) && ttp_order_qualifies_for_tcy_actions( $order ) ) {
		return false;
	}
	if ( ! $order->is_paid() ) {
		return true;
	}
	return ! in_array( $order->get_status(), [ 'processing', 'completed' ], true );
}

/**
 * Delete order-level mapping and login links for unpaid orders.
 *
 * @param int $order_id Order ID.
 * @return void
 */
function ttp_guard_purge_order_mapping( $order_id ) {
	global $wpdb;
	$order_id = (int) $order_id;
	if ( $order_id <= 0 ) {
		return;
	}
	$wpdb->delete( $wpdb->prefix . 'ttp_order_mapping', [ 'order_id' => $order_id ], [ '%d' ] );

	$order = wc_get_order( $order_id );
	if ( $order ) {
		$order->delete_meta_data( '_ttp_tcy_registered' );
		$order->save();
	}
}

/**
 * Early hard-block for AJAX TCY login, even if downstream plugin callbacks change.
 */
$ttp_guard_ajax_tcy_login = static function () {
	$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
	$order    = $order_id > 0 ? wc_get_order( $order_id ) : null;
	if ( ttp_guard_is_unpaid_order( $order ) ) {
		ttp_guard_purge_order_mapping( $order_id );
		wp_send_json_error( [ 'message' => 'Access is available only after successful payment.' ] );
	}
};
add_action( 'wp_ajax_ttp_tcy_login', $ttp_guard_ajax_tcy_login, 1 );
add_action( 'wp_ajax_nopriv_ttp_tcy_login', $ttp_guard_ajax_tcy_login, 1 );

/**
 * Unpaid order-received page: purge mapping, hide unsafe UI, and scrub rendered HTML.
 */
add_action(
	'template_redirect',
	static function () {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( false === strpos( strtolower( $request_uri ), '/checkout/order-received/' ) ) {
			return;
		}
		$order = ttp_guard_get_order_from_request();
		if ( ! ttp_guard_is_unpaid_order( $order ) ) {
			return;
		}

		ttp_guard_purge_order_mapping( $order->get_id() );
		nocache_headers();
		status_header( 200 );
		$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
		$account_url  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
		echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
		echo '<title>Payment Failed</title>';
		echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f8f8fb;color:#222;margin:0;padding:24px}';
		echo '.box{max-width:760px;margin:40px auto;background:#fff;border:1px solid #e8e8ee;border-radius:14px;padding:28px;box-shadow:0 8px 24px rgba(0,0,0,.06)}';
		echo 'h1{margin:0 0 10px;font-size:30px}p{line-height:1.6;color:#444}.btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}';
		echo '.btn{display:inline-block;padding:12px 18px;border-radius:10px;text-decoration:none;font-weight:700}';
		echo '.primary{background:#f5c518;color:#111}.secondary{background:#111;color:#fff}</style></head><body>';
		echo '<div class="box"><h1>Payment failed</h1>';
		echo '<p>Your payment was not successful, so course access is disabled for this order.</p>';
		echo '<p>Please retry checkout to complete payment.</p>';
		echo '<div class="btns"><a class="btn primary" href="' . esc_url( $checkout_url ) . '">Retry payment</a>';
		echo '<a class="btn secondary" href="' . esc_url( $account_url ) . '">My account</a></div></div>';
		echo '</body></html>';
		exit;
	},
	0
);

add_action(
	'wp_head',
	static function () {
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		$order = ttp_guard_get_order_from_request();
		if ( ! ttp_guard_is_unpaid_order( $order ) ) {
			return;
		}
		echo '<style id="ttp-unpaid-order-guard">.ttp-purchase-thankyou,.ttp-success-box,.tpsp-thankyou,.ttp-access-btn,a[href*="tcyonline"]{display:none!important;}</style>' . "\n";
	},
	999
);

add_action(
	'wp_footer',
	static function () {
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		$order = ttp_guard_get_order_from_request();
		if ( ! ttp_guard_is_unpaid_order( $order ) ) {
			return;
		}
		?>
		<script id="ttp-unpaid-order-guard-js">
		(function () {
			function stripUnsafeAccessUI() {
				var sels = ['.ttp-purchase-thankyou','.ttp-success-box','.tpsp-thankyou','.ttp-access-btn','a[href*="tcyonline"]'];
				sels.forEach(function (s) { document.querySelectorAll(s).forEach(function (el) { el.remove(); }); });
				document.querySelectorAll('h1,h2,h3,p,div,button,a').forEach(function (el) {
					var t = (el.textContent || '').toLowerCase();
					if (t.indexOf('payment successful') !== -1 || t.indexOf('login & access') !== -1 || t.indexOf('course is now active') !== -1 || t.indexOf('enrollment confirmed') !== -1) {
						el.remove();
					}
				});
			}
			document.addEventListener('click', function (e) {
				var target = e.target && e.target.closest ? e.target.closest('.ttp-access-btn,a[href*="tcyonline"]') : null;
				if (!target) return;
				e.preventDefault();
				e.stopPropagation();
				if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
				alert('Access is available only after successful payment.');
			}, true);
			stripUnsafeAccessUI();
			setTimeout(stripUnsafeAccessUI, 300);
			setTimeout(stripUnsafeAccessUI, 1000);
			setTimeout(stripUnsafeAccessUI, 2200);
		})();
		</script>
		<?php
	},
	999
);

