<?php
/**
 * Front-end page open tracking.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_Tracker {

	/**
	 * @return void
	 */
	public static function init() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( self::should_skip_track() ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_tracker' ], 99 );
		add_action( 'rest_api_init', [ __CLASS__, 'register_rest' ] );
	}

	/**
	 * @return bool
	 */
	private static function should_skip_track() {
		if ( (int) get_option( 'ttp_pca_track_enabled', 1 ) !== 1 ) {
			return true;
		}
		if ( (int) get_option( 'ttp_pca_skip_logged_in', 0 ) === 1 && is_user_logged_in() ) {
			return true;
		}
		if ( is_feed() || is_trackback() || is_preview() ) {
			return true;
		}
		return (bool) apply_filters( 'ttp_pca_skip_track', false );
	}

	/**
	 * @return void
	 */
	public static function enqueue_tracker() {
		wp_enqueue_script(
			'ttp-pca-tracker',
			TTP_PCA_URL . 'assets/js/tracker.js',
			[],
			TTP_PCA_VERSION,
			true
		);
		wp_localize_script(
			'ttp-pca-tracker',
			'ttpPcaTrack',
			[
				'endpoint' => rest_url( 'ttp-pca/v1/page-view' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'postId'   => is_singular() ? (int) get_queried_object_id() : 0,
				'postType' => is_singular() ? get_post_type() : '',
				'title'    => wp_get_document_title(),
				'url'      => self::current_url(),
			]
		);
	}

	/**
	 * @return string
	 */
	private static function current_url() {
		if ( is_singular() ) {
			return get_permalink();
		}
		global $wp;
		return home_url( add_query_arg( [], $wp->request ?? '' ) );
	}

	/**
	 * @return void
	 */
	public static function register_rest() {
		register_rest_route(
			'ttp-pca/v1',
			'/page-view',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_record_view' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function rest_record_view( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_REST_Response( [ 'ok' => false ], 403 );
		}

		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = [];
		}

		$post_id = isset( $body['post_id'] ) ? absint( $body['post_id'] ) : 0;
		$url     = isset( $body['url'] ) ? esc_url_raw( (string) $body['url'] ) : '';
		if ( $url === '' ) {
			return new WP_REST_Response( [ 'ok' => false ], 400 );
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		$site = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( $host && $site && strtolower( $host ) !== strtolower( $site ) ) {
			return new WP_REST_Response( [ 'ok' => false, 'reason' => 'host' ], 403 );
		}

		$session = isset( $body['session'] ) ? sanitize_text_field( (string) $body['session'] ) : '';
		if ( strlen( $session ) > 32 ) {
			$session = substr( $session, 0, 32 );
		}

		TTP_PCA_DB::insert_view(
			[
				'post_id'      => $post_id,
				'post_type'    => isset( $body['post_type'] ) ? sanitize_key( (string) $body['post_type'] ) : '',
				'page_url'     => $url,
				'page_title'   => isset( $body['title'] ) ? sanitize_text_field( (string) $body['title'] ) : '',
				'referrer'     => isset( $body['referrer'] ) ? esc_url_raw( (string) $body['referrer'] ) : '',
				'user_id'      => get_current_user_id(),
				'session_hash' => $session !== '' ? $session : wp_hash( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) . date( 'Y-m-d' ) ),
				'device_type'  => isset( $body['device'] ) ? sanitize_key( (string) $body['device'] ) : 'unknown',
			]
		);

		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}
}
