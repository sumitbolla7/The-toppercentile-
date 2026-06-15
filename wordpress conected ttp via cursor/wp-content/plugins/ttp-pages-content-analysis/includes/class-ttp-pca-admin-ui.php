<?php
/**
 * Admin chrome: hide third-party notices on TTP Analysis screens.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_Admin_UI {

	/**
	 * @return void
	 */
	public static function init() {
		add_filter( 'admin_body_class', [ __CLASS__, 'body_class' ] );
		add_action( 'admin_print_styles', [ __CLASS__, 'hide_distractions' ] );
	}

	/**
	 * @param string $classes Classes.
	 * @return string
	 */
	public static function body_class( $classes ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( $page !== '' && strpos( $page, 'ttp-pca' ) === 0 ) {
			$classes .= ' ttp-pca-screen';
		}
		return $classes;
	}

	/**
	 * Hide UR survey, Updraft, etc. on our plugin pages only.
	 *
	 * @return void
	 */
	public static function hide_distractions() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( $page === '' || strpos( $page, 'ttp-pca' ) !== 0 ) {
			return;
		}
		?>
		<style id="ttp-pca-admin-clean">
			body.ttp-pca-screen #wpbody-content > .notice:not(.ttp-pca-notice),
			body.ttp-pca-screen #wpbody-content > .update-nag,
			body.ttp-pca-screen .user-registration-notice,
			body.ttp-pca-screen .notice.updraftmessage,
			body.ttp-pca-screen .notice.updraft_advert {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Tab nav for hub sections.
	 *
	 * @param string $current Current slug.
	 * @return void
	 */
	public static function render_tabs( $current = 'overview' ) {
		$tabs = [
			'overview' => [ 'label' => __( 'Overview', 'ttp-pca' ), 'page' => 'ttp-pca' ],
			'search'   => [ 'label' => __( 'Search', 'ttp-pca' ), 'page' => 'ttp-pca-search' ],
			'behavior' => [ 'label' => __( 'Behavior', 'ttp-pca' ), 'page' => 'ttp-pca-behavior' ],
			'pages'    => [ 'label' => __( 'Page opens', 'ttp-pca' ), 'page' => 'ttp-pca-opens' ],
			'live'     => [ 'label' => __( 'Live', 'ttp-pca' ), 'page' => 'ttp-pca-live' ],
			'content'  => [ 'label' => __( 'Content', 'ttp-pca' ), 'page' => 'ttp-pca-content' ],
			'settings' => [ 'label' => __( 'Settings', 'ttp-pca' ), 'page' => 'ttp-pca-settings' ],
		];
		echo '<nav class="ttp-pca-tabs">';
		foreach ( $tabs as $slug => $tab ) {
			$url    = admin_url( 'admin.php?page=' . $tab['page'] );
			$active = ( $current === $slug ) ? ' is-active' : '';
			printf(
				'<a class="ttp-pca-tabs__item%s" href="%s">%s</a>',
				esc_attr( $active ),
				esc_url( $url ),
				esc_html( $tab['label'] )
			);
		}
		echo '</nav>';
	}
}
