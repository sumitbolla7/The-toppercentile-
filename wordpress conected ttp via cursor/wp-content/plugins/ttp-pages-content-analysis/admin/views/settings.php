<?php
/**
 * Settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$track    = (int) get_option( 'ttp_pca_track_enabled', 1 );
$skip_li  = (int) get_option( 'ttp_pca_skip_logged_in', 0 );
$hj_on    = (int) get_option( 'ttp_pca_hotjar_enabled', 1 );
$hj_id    = TTP_PCA_Hotjar::get_site_id();
?>
<div class="wrap ttp-pca-wrap">
	<header class="ttp-pca-hero ttp-pca-hero--compact">
		<h1><?php esc_html_e( 'Settings', 'ttp-pca' ); ?></h1>
	</header>

	<?php TTP_PCA_Admin_UI::render_tabs( 'settings' ); ?>

	<?php if ( ! empty( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success ttp-pca-notice"><p><?php esc_html_e( 'Settings saved.', 'ttp-pca' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="" class="ttp-pca-panel ttp-pca-form">
		<?php wp_nonce_field( 'ttp_pca_settings', 'ttp_pca_settings_nonce' ); ?>
		<h2><?php esc_html_e( 'Page tracking', 'ttp-pca' ); ?></h2>
		<label class="ttp-pca-check">
			<input type="checkbox" name="ttp_pca_track_enabled" value="1" <?php checked( $track, 1 ); ?>/>
			<?php esc_html_e( 'Track page opens on the front-end', 'ttp-pca' ); ?>
		</label>
		<label class="ttp-pca-check">
			<input type="checkbox" name="ttp_pca_skip_logged_in" value="1" <?php checked( $skip_li, 1 ); ?>/>
			<?php esc_html_e( 'Do not track logged-in users', 'ttp-pca' ); ?>
		</label>

		<h2><?php esc_html_e( 'Hotjar', 'ttp-pca' ); ?></h2>
		<p class="ttp-pca-muted"><?php esc_html_e( 'If the Hotjar plugin is active, its Site ID is used automatically. Otherwise enter your ID here.', 'ttp-pca' ); ?></p>
		<p>
			<label for="ttp_pca_hotjar_site_id"><?php esc_html_e( 'Hotjar Site ID', 'ttp-pca' ); ?></label><br/>
			<input type="text" id="ttp_pca_hotjar_site_id" name="ttp_pca_hotjar_site_id" value="<?php echo esc_attr( $hj_id ); ?>" class="regular-text"/>
		</p>
		<label class="ttp-pca-check">
			<input type="checkbox" name="ttp_pca_hotjar_enabled" value="1" <?php checked( $hj_on, 1 ); ?> <?php disabled( TTP_PCA_Hotjar::plugin_active() ); ?>/>
			<?php esc_html_e( 'Load Hotjar script from TTP Analysis (only when Hotjar plugin is off)', 'ttp-pca' ); ?>
		</label>

		<h2><?php esc_html_e( 'Google Site Kit', 'ttp-pca' ); ?></h2>
		<p class="ttp-pca-muted"><?php esc_html_e( 'Search Console and Analytics are read from Site Kit when connected. No duplicate Google tags are added.', 'ttp-pca' ); ?></p>
		<p><a class="ttp-pca-btn ttp-pca-btn--ghost" href="<?php echo esc_url( TTP_PCA_SiteKit::setup_url() ); ?>"><?php esc_html_e( 'Open Site Kit', 'ttp-pca' ); ?></a></p>

		<p><button type="submit" class="ttp-pca-btn"><?php esc_html_e( 'Save', 'ttp-pca' ); ?></button></p>
	</form>
</div>
