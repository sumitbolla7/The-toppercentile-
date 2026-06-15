<?php
/**
 * Admin UI — unified analytics hub (Site Kit + Hotjar + page opens).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_Admin {

	/**
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		add_action( 'admin_post_ttp_pca_export_csv', [ __CLASS__, 'export_csv' ] );
		add_action( 'admin_init', [ __CLASS__, 'save_settings' ] );
	}

	/**
	 * @return void
	 */
	public static function menu() {
		add_menu_page(
			__( 'TTP Analytics', 'ttp-pca' ),
			__( 'TTP Analysis', 'ttp-pca' ),
			'manage_options',
			'ttp-pca',
			[ __CLASS__, 'render_overview' ],
			'dashicons-chart-area',
			58
		);
		add_submenu_page( 'ttp-pca', __( 'Overview', 'ttp-pca' ), __( 'Overview', 'ttp-pca' ), 'manage_options', 'ttp-pca', [ __CLASS__, 'render_overview' ] );
		add_submenu_page( 'ttp-pca', __( 'Search', 'ttp-pca' ), __( 'Search', 'ttp-pca' ), 'manage_options', 'ttp-pca-search', [ __CLASS__, 'render_search' ] );
		add_submenu_page( 'ttp-pca', __( 'Behavior', 'ttp-pca' ), __( 'Behavior', 'ttp-pca' ), 'manage_options', 'ttp-pca-behavior', [ __CLASS__, 'render_behavior' ] );
		add_submenu_page( 'ttp-pca', __( 'Page opens', 'ttp-pca' ), __( 'Page opens', 'ttp-pca' ), 'manage_options', 'ttp-pca-opens', [ __CLASS__, 'render_dashboard' ] );
		add_submenu_page( 'ttp-pca', __( 'Live feed', 'ttp-pca' ), __( 'Live feed', 'ttp-pca' ), 'manage_options', 'ttp-pca-live', [ __CLASS__, 'render_live' ] );
		add_submenu_page( 'ttp-pca', __( 'Content audit', 'ttp-pca' ), __( 'Content audit', 'ttp-pca' ), 'manage_options', 'ttp-pca-content', [ __CLASS__, 'render_content' ] );
		add_submenu_page( 'ttp-pca', __( 'Settings', 'ttp-pca' ), __( 'Settings', 'ttp-pca' ), 'manage_options', 'ttp-pca-settings', [ __CLASS__, 'render_settings' ] );
	}

	/**
	 * @param string $hook Hook.
	 * @return void
	 */
	public static function assets( $hook ) {
		if ( strpos( $hook, 'ttp-pca' ) === false ) {
			return;
		}
		wp_enqueue_style( 'ttp-pca-admin', TTP_PCA_URL . 'assets/css/admin.css', [], TTP_PCA_VERSION );
		wp_enqueue_script( 'ttp-pca-admin', TTP_PCA_URL . 'assets/js/admin.js', [], TTP_PCA_VERSION, true );
	}

	/**
	 * @return int
	 */
	private static function days_param() {
		$d = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 28;
		return in_array( $d, [ 7, 14, 28, 30, 90 ], true ) ? $d : 28;
	}

	/**
	 * Filter query rows by substring (e.g. "ttp mocks").
	 *
	 * @param array<string, mixed> $search Search data.
	 * @param string               $needle Needle.
	 * @return array<string, mixed>
	 */
	private static function filter_search_queries( array $search, $needle ) {
		if ( $needle === '' || empty( $search['queries'] ) ) {
			return $search;
		}
		$needle   = strtolower( $needle );
		$filtered = [];
		foreach ( $search['queries'] as $q ) {
			if ( strpos( strtolower( (string) ( $q['query'] ?? '' ) ), $needle ) !== false ) {
				$filtered[] = $q;
			}
		}
		$search['queries'] = $filtered;
		$clicks            = 0;
		$impr              = 0;
		foreach ( $filtered as $q ) {
			$clicks += (int) ( $q['clicks'] ?? 0 );
			$impr   += (int) ( $q['impressions'] ?? 0 );
		}
		if ( ! empty( $filtered ) ) {
			$search['clicks']      = $clicks;
			$search['impressions'] = $impr;
			$search['ctr']         = $impr > 0 ? round( ( $clicks / $impr ) * 100, 1 ) : 0;
			$sum_pos               = 0;
			$n                     = 0;
			foreach ( $filtered as $q ) {
				if ( ! empty( $q['position'] ) ) {
					$sum_pos += (float) $q['position'];
					++$n;
				}
			}
			$search['position'] = $n > 0 ? round( $sum_pos / $n, 1 ) : 0;
		}
		return $search;
	}

	/**
	 * @return void
	 */
	public static function render_overview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$days          = self::days_param();
		$search        = TTP_PCA_SiteKit::search_performance_summary( $days );
		$page_summary  = TTP_PCA_Reports::summary( $days, '' );
		include TTP_PCA_DIR . 'admin/views/overview.php';
	}

	/**
	 * @return void
	 */
	public static function render_search() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$days          = self::days_param();
		$query_filter  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$search        = TTP_PCA_SiteKit::search_performance_summary( $days );
		$search        = self::filter_search_queries( $search, $query_filter );
		include TTP_PCA_DIR . 'admin/views/search.php';
	}

	/**
	 * @return void
	 */
	public static function render_behavior() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$hotjar_id = TTP_PCA_Hotjar::get_site_id();
		include TTP_PCA_DIR . 'admin/views/behavior.php';
	}

	/**
	 * @return void
	 */
	public static function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$days       = self::days_param();
		$type       = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		$summary    = TTP_PCA_Reports::summary( $days, $type );
		$top        = TTP_PCA_Reports::top_pages( $days, 50, $type );
		$chart      = TTP_PCA_Reports::views_by_day( min( 14, $days ) );
		$export_url = wp_nonce_url(
			add_query_arg(
				[
					'action' => 'ttp_pca_export_csv',
					'days'   => $days,
				],
				admin_url( 'admin-post.php' )
			),
			'ttp_pca_export'
		);
		include TTP_PCA_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * @return void
	 */
	public static function render_live() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$recent = TTP_PCA_Reports::recent_opens( 50 );
		include TTP_PCA_DIR . 'admin/views/live.php';
	}

	/**
	 * @return void
	 */
	public static function render_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$ptype = isset( $_GET['audit_type'] ) ? sanitize_key( wp_unslash( $_GET['audit_type'] ) ) : 'page';
		$audit = TTP_PCA_Analyzer::audit_all( $ptype, 80 );
		include TTP_PCA_DIR . 'admin/views/content-audit.php';
	}

	/**
	 * @return void
	 */
	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include TTP_PCA_DIR . 'admin/views/settings.php';
	}

	/**
	 * @return void
	 */
	public static function save_settings() {
		if ( ! isset( $_POST['ttp_pca_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ttp_pca_settings_nonce'] ) ), 'ttp_pca_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		update_option( 'ttp_pca_track_enabled', isset( $_POST['ttp_pca_track_enabled'] ) ? 1 : 0 );
		update_option( 'ttp_pca_skip_logged_in', isset( $_POST['ttp_pca_skip_logged_in'] ) ? 1 : 0 );
		update_option( 'ttp_pca_hotjar_enabled', isset( $_POST['ttp_pca_hotjar_enabled'] ) ? 1 : 0 );
		if ( isset( $_POST['ttp_pca_hotjar_site_id'] ) ) {
			update_option( 'ttp_pca_hotjar_site_id', sanitize_text_field( wp_unslash( $_POST['ttp_pca_hotjar_site_id'] ) ) );
		}
		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'admin.php?page=ttp-pca-settings' ) ) );
		exit;
	}

	/**
	 * @return void
	 */
	public static function export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Forbidden', 'ttp-pca' ) );
		}
		check_admin_referer( 'ttp_pca_export' );
		$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=ttp-page-opens-' . gmdate( 'Y-m-d' ) . '.csv' );
		echo TTP_PCA_Reports::export_csv( $days );
		exit;
	}
}
