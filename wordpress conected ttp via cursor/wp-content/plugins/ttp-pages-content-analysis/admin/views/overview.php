<?php
/**
 * Unified hub — Search (Site Kit) + Behavior (Hotjar) + page opens.
 *
 * @var array<string, mixed> $search
 * @var array<string, mixed> $page_summary
 * @var int                  $days
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ttp-pca-wrap">
	<header class="ttp-pca-hero">
		<div>
			<p class="ttp-pca-eyebrow"><?php esc_html_e( 'Top Percentile', 'ttp-pca' ); ?></p>
			<h1><?php esc_html_e( 'Analytics hub', 'ttp-pca' ); ?></h1>
			<p class="ttp-pca-lead"><?php esc_html_e( 'Google Search, Hotjar behavior, and WordPress page opens — one dashboard.', 'ttp-pca' ); ?></p>
		</div>
		<form method="get" class="ttp-pca-filters">
			<input type="hidden" name="page" value="ttp-pca"/>
			<select name="days">
				<?php foreach ( [ 7, 14, 28, 90 ] as $d ) : ?>
					<option value="<?php echo (int) $d; ?>" <?php selected( $days, $d ); ?>><?php echo esc_html( sprintf( __( 'Last %d days', 'ttp-pca' ), $d ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="ttp-pca-btn"><?php esc_html_e( 'Apply', 'ttp-pca' ); ?></button>
		</form>
	</header>

	<?php TTP_PCA_Admin_UI::render_tabs( 'overview' ); ?>

	<div class="ttp-pca-metrics ttp-pca-metrics--quad">
		<div class="ttp-pca-metric ttp-pca-metric--search">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Search clicks', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( number_format_i18n( (int) ( $search['clicks'] ?? 0 ) ) ); ?></strong>
			<span class="ttp-pca-metric__hint"><?php esc_html_e( 'Google Search Console', 'ttp-pca' ); ?></span>
		</div>
		<div class="ttp-pca-metric ttp-pca-metric--search">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Impressions', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( number_format_i18n( (int) ( $search['impressions'] ?? 0 ) ) ); ?></strong>
			<span class="ttp-pca-metric__hint"><?php echo ! empty( $search['connected'] ) ? esc_html__( 'via Site Kit', 'ttp-pca' ) : esc_html__( 'Connect Site Kit', 'ttp-pca' ); ?></span>
		</div>
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Page opens', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( number_format_i18n( (int) ( $page_summary['total_views'] ?? 0 ) ) ); ?></strong>
			<span class="ttp-pca-metric__hint"><?php esc_html_e( 'On-site tracking', 'ttp-pca' ); ?></span>
		</div>
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Hotjar', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo TTP_PCA_Hotjar::is_tracking_enabled() ? esc_html__( 'On', 'ttp-pca' ) : esc_html__( 'Off', 'ttp-pca' ); ?></strong>
			<span class="ttp-pca-metric__hint"><?php echo TTP_PCA_Hotjar::plugin_active() ? esc_html__( 'Plugin active', 'ttp-pca' ) : esc_html__( 'Set site ID in Settings', 'ttp-pca' ); ?></span>
		</div>
	</div>

	<?php if ( empty( $search['connected'] ) ) : ?>
		<div class="notice notice-warning ttp-pca-notice">
			<p>
				<?php
				if ( TTP_PCA_SiteKit::is_active() ) {
					printf(
						/* translators: %s: Site Kit setup URL */
						wp_kses_post( __( 'Connect <strong>Search Console</strong> in Google Site Kit to see search performance here. <a href="%s">Open Site Kit</a>', 'ttp-pca' ) ),
						esc_url( TTP_PCA_SiteKit::setup_url() )
					);
				} else {
					esc_html_e( 'Install and activate Google Site Kit to pull Search Console data into this hub.', 'ttp-pca' );
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="ttp-pca-grid ttp-pca-grid--2">
		<div class="ttp-pca-panel">
			<h2><?php esc_html_e( 'Search performance', 'ttp-pca' ); ?></h2>
			<?php if ( ! empty( $search['rows'] ) ) : ?>
				<div class="ttp-pca-gsc-chart" data-ttp-pca-gsc>
					<?php
					$max = 1;
					foreach ( $search['rows'] as $r ) {
						$max = max( $max, (int) ( $r['clicks'] ?? 0 ), (int) ( $r['impressions'] ?? 0 ) );
					}
					foreach ( $search['rows'] as $r ) :
						$c = (int) ( $r['clicks'] ?? 0 );
						$i = (int) ( $r['impressions'] ?? 0 );
						$hc = $max > 0 ? round( ( $c / $max ) * 100 ) : 0;
						$hi = $max > 0 ? round( ( $i / $max ) * 100 ) : 0;
						?>
						<div class="ttp-pca-gsc-chart__day" title="<?php echo esc_attr( ( $r['date'] ?? '' ) . ' — ' . $c . ' clicks' ); ?>">
							<div class="ttp-pca-gsc-chart__bars">
								<div class="ttp-pca-gsc-chart__bar ttp-pca-gsc-chart__bar--imp" style="height:<?php echo (int) $hi; ?>%"></div>
								<div class="ttp-pca-gsc-chart__bar ttp-pca-gsc-chart__bar--clk" style="height:<?php echo (int) $hc; ?>%"></div>
							</div>
							<span class="ttp-pca-gsc-chart__label"><?php echo esc_html( gmdate( 'M j', strtotime( $r['date'] ?? 'now' ) ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<p class="ttp-pca-legend">
					<span class="ttp-pca-legend__item ttp-pca-legend__item--clk"><?php esc_html_e( 'Clicks', 'ttp-pca' ); ?></span>
					<span class="ttp-pca-legend__item ttp-pca-legend__item--imp"><?php esc_html_e( 'Impressions', 'ttp-pca' ); ?></span>
					<span class="ttp-pca-muted">CTR <?php echo esc_html( (float) ( $search['ctr'] ?? 0 ) ); ?>% · <?php esc_html_e( 'Avg position', 'ttp-pca' ); ?> <?php echo esc_html( (float) ( $search['position'] ?? 0 ) ); ?></span>
				</p>
			<?php else : ?>
				<p class="ttp-pca-muted"><?php esc_html_e( 'No search data for this period.', 'ttp-pca' ); ?></p>
			<?php endif; ?>
			<p><a class="ttp-pca-btn ttp-pca-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=ttp-pca-search&days=' . (int) $days ) ); ?>"><?php esc_html_e( 'Full search report', 'ttp-pca' ); ?></a></p>
		</div>

		<div class="ttp-pca-panel">
			<h2><?php esc_html_e( 'Top search queries', 'ttp-pca' ); ?></h2>
			<?php if ( ! empty( $search['queries'] ) ) : ?>
				<table class="ttp-pca-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Query', 'ttp-pca' ); ?></th>
							<th><?php esc_html_e( 'Clicks', 'ttp-pca' ); ?></th>
							<th><?php esc_html_e( 'CTR', 'ttp-pca' ); ?></th>
							<th><?php esc_html_e( 'Pos.', 'ttp-pca' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_slice( $search['queries'], 0, 8 ) as $q ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $q['query'] ); ?></strong></td>
								<td><?php echo esc_html( number_format_i18n( (int) $q['clicks'] ) ); ?></td>
								<td><?php echo esc_html( $q['ctr'] ); ?>%</td>
								<td><?php echo esc_html( $q['position'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p class="ttp-pca-muted"><?php esc_html_e( 'Connect Site Kit to list queries.', 'ttp-pca' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="ttp-pca-grid ttp-pca-grid--3">
		<?php foreach ( TTP_PCA_Hotjar::dashboard_links() as $link ) : ?>
			<a class="ttp-pca-card ttp-pca-card--link" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<h3><?php echo esc_html( $link['label'] ); ?></h3>
				<p class="ttp-pca-muted"><?php echo esc_html( $link['desc'] ); ?></p>
				<span class="ttp-pca-link"><?php esc_html_e( 'Open in Hotjar', 'ttp-pca' ); ?> →</span>
			</a>
		<?php endforeach; ?>
	</div>
</div>
