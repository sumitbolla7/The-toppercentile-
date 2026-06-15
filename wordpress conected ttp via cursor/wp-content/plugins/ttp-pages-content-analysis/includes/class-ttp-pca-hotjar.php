<?php
/**
 * Hotjar bridge — tracking ID, dashboard links, behavior panel (like Hotjar plugin).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_Hotjar {

	/** @var bool|null */
	private static $hotjar_plugin_active = null;

	/**
	 * @return bool
	 */
	public static function plugin_active() {
		if ( null !== self::$hotjar_plugin_active ) {
			return self::$hotjar_plugin_active;
		}
		if ( ! did_action( 'plugins_loaded' ) ) {
			self::$hotjar_plugin_active = false;
			return false;
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		self::$hotjar_plugin_active = is_plugin_active( 'hotjar/hotjar.php' );
		return self::$hotjar_plugin_active;
	}

	/**
	 * @return string
	 */
	public static function get_site_id() {
		$own = (string) get_option( 'ttp_pca_hotjar_site_id', '' );
		if ( $own !== '' ) {
			return preg_replace( '/\D+/', '', $own );
		}
		if ( self::plugin_active() ) {
			return (string) get_option( 'hotjar_site_id', '' );
		}
		return '';
	}

	/**
	 * @return bool
	 */
	public static function is_tracking_enabled() {
		return self::get_site_id() !== '';
	}

	/**
	 * Front-end tracking (only if Hotjar plugin is not already loading it).
	 *
	 * @return void
	 */
	public static function maybe_enqueue_tracker() {
		if ( is_admin() || self::plugin_active() ) {
			return;
		}
		if ( (int) get_option( 'ttp_pca_hotjar_enabled', 1 ) !== 1 ) {
			return;
		}
		$site_id = self::get_site_id();
		if ( $site_id === '' ) {
			return;
		}

		$inline = self::tracking_script( $site_id );
		wp_register_script( 'ttp-pca-hotjar', false, [], TTP_PCA_VERSION, false );
		wp_enqueue_script( 'ttp-pca-hotjar' );
		wp_add_inline_script( 'ttp-pca-hotjar', $inline );
	}

	/**
	 * @param string $site_id Hotjar id.
	 * @return string
	 */
	public static function tracking_script( $site_id ) {
		$site_id = preg_replace( '/\D+/', '', (string) $site_id );
		return "(function(h,o,t,j,a,r){h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};h._hjSettings={hjid:" . absint( $site_id ) . ",hjsv:6};a=o.getElementsByTagName('head')[0];r=o.createElement('script');r.async=1;r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;a.appendChild(r);})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');";
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public static function dashboard_links() {
		$id = self::get_site_id();
		$base = $id !== '' ? 'https://insights.hotjar.com/sites/' . rawurlencode( $id ) : 'https://insights.hotjar.com/';
		return [
			[
				'label' => __( 'Heatmaps', 'ttp-pca' ),
				'desc'  => __( 'See where users click and scroll', 'ttp-pca' ),
				'url'   => $base,
				'icon'  => 'heatmap',
			],
			[
				'label' => __( 'Session recordings', 'ttp-pca' ),
				'desc'  => __( 'Watch real user sessions', 'ttp-pca' ),
				'url'   => $base,
				'icon'  => 'record',
			],
			[
				'label' => __( 'Feedback & surveys', 'ttp-pca' ),
				'desc'  => __( 'On-site polls and feedback', 'ttp-pca' ),
				'url'   => $base,
				'icon'  => 'feedback',
			],
			[
				'label' => __( 'Funnels', 'ttp-pca' ),
				'desc'  => __( 'Conversion drop-off analysis', 'ttp-pca' ),
				'url'   => $base,
				'icon'  => 'funnel',
			],
		];
	}

	/**
	 * @return string
	 */
	public static function settings_url() {
		return admin_url( 'admin.php?page=hotjar-settings' );
	}
}
