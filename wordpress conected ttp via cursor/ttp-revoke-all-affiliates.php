<?php
/**
 * One-time: purge ALL affiliate access + stale flags. Delete after use.
 */
define( 'TTP_REVOKE_KEY', 'ttp_revoke_all_jun12_2026' );

$key = isset( $_GET['key'] ) ? (string) $_GET['key'] : '';
if ( $key === '' || ! hash_equals( TTP_REVOKE_KEY, $key ) ) {
	http_response_code( 403 );
	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( array( 'error' => 'Forbidden' ) );
	exit;
}

require_once __DIR__ . '/wp-load.php';

$referrals = class_exists( 'TTPA_Plugin' ) ? TTPA_Plugin::instance()->referrals() : null;
if ( ! $referrals || ! method_exists( $referrals, 'revoke_everyone' ) ) {
	http_response_code( 500 );
	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( array( 'error' => 'ttp-affiliate not loaded' ) );
	exit;
}

$revoke  = $referrals->revoke_everyone();
$purged  = method_exists( $referrals, 'purge_stale_affiliate_flags' ) ? $referrals->purge_stale_affiliate_flags() : 0;
$remain  = $referrals->count_enabled_affiliates();

header( 'Content-Type: application/json; charset=utf-8' );
echo wp_json_encode(
	array(
		'time'      => gmdate( 'c' ),
		'revoked'   => $revoke,
		'purged'    => $purged,
		'remaining' => $remain,
	),
	JSON_PRETTY_PRINT
);

// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
@unlink( __FILE__ );
