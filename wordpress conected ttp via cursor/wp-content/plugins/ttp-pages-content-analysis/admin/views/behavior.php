<?php
/**
 * Hotjar behavior hub.
 *
 * @var string $hotjar_id
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$links = TTP_PCA_Hotjar::dashboard_links();
?>
<div class="wrap ttp-pca-wrap">
	<header class="ttp-pca-hero ttp-pca-hero--compact">
		<div>
			<h1><?php esc_html_e( 'User behavior', 'ttp-pca' ); ?></h1>
			<p class="ttp-pca-lead"><?php esc_html_e( 'Heatmaps, recordings, and funnels — powered by Hotjar (same features as the Hotjar plugin).', 'ttp-pca' ); ?></p>
		</div>
		<?php if ( TTP_PCA_Hotjar::is_tracking_enabled() ) : ?>
			<span class="ttp-pca-pill ttp-pca-pill--on"><?php esc_html_e( 'Tracking active', 'ttp-pca' ); ?> · ID <?php echo esc_html( $hotjar_id ); ?></span>
		<?php else : ?>
			<span class="ttp-pca-pill ttp-pca-pill--off"><?php esc_html_e( 'Not configured', 'ttp-pca' ); ?></span>
		<?php endif; ?>
	</header>

	<?php TTP_PCA_Admin_UI::render_tabs( 'behavior' ); ?>

	<?php if ( ! TTP_PCA_Hotjar::is_tracking_enabled() ) : ?>
		<div class="notice notice-warning ttp-pca-notice">
			<p><?php esc_html_e( 'Add your Hotjar Site ID in Settings, or activate the official Hotjar plugin.', 'ttp-pca' ); ?></p>
		</div>
	<?php elseif ( TTP_PCA_Hotjar::plugin_active() ) : ?>
		<div class="notice notice-info ttp-pca-notice">
			<p>
				<?php
				printf(
					wp_kses_post( __( 'The <strong>Hotjar</strong> plugin is active and loads tracking on the site. TTP Analysis will not duplicate the script. <a href="%s">Hotjar settings</a>', 'ttp-pca' ) ),
					esc_url( TTP_PCA_Hotjar::settings_url() )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="ttp-pca-grid">
		<?php foreach ( $links as $link ) : ?>
			<div class="ttp-pca-card">
				<h3><?php echo esc_html( $link['label'] ); ?></h3>
				<p class="ttp-pca-muted"><?php echo esc_html( $link['desc'] ); ?></p>
				<a class="ttp-pca-btn" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Hotjar', 'ttp-pca' ); ?></a>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="ttp-pca-panel">
		<h2><?php esc_html_e( 'Combine with page opens', 'ttp-pca' ); ?></h2>
		<p class="ttp-pca-muted"><?php esc_html_e( 'Hotjar shows how users interact (clicks, scroll, recordings). TTP Analysis logs which WordPress pages were opened — use both to see traffic sources vs on-site URLs.', 'ttp-pca' ); ?></p>
		<a class="ttp-pca-btn ttp-pca-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=ttp-pca-opens' ) ); ?>"><?php esc_html_e( 'Page opens report', 'ttp-pca' ); ?></a>
	</div>
</div>
