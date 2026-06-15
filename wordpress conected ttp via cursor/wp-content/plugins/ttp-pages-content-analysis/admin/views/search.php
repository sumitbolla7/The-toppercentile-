<?php
/**
 * Search Console performance (Site Kit bridge).
 *
 * @var array<string, mixed> $search
 * @var int                  $days
 * @var string               $query_filter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ttp-pca-wrap">
	<header class="ttp-pca-hero ttp-pca-hero--compact">
		<div>
			<h1><?php esc_html_e( 'Search performance', 'ttp-pca' ); ?></h1>
			<p class="ttp-pca-lead"><?php esc_html_e( 'Google Search Console data via Site Kit — clicks, impressions, queries.', 'ttp-pca' ); ?></p>
		</div>
		<form method="get" class="ttp-pca-filters">
			<input type="hidden" name="page" value="ttp-pca-search"/>
			<select name="days">
				<?php foreach ( [ 7, 14, 28, 90 ] as $d ) : ?>
					<option value="<?php echo (int) $d; ?>" <?php selected( $days, $d ); ?>><?php echo esc_html( sprintf( __( 'Last %d days', 'ttp-pca' ), $d ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="search" name="q" value="<?php echo esc_attr( $query_filter ); ?>" placeholder="<?php esc_attr_e( 'Filter query e.g. ttp mocks', 'ttp-pca' ); ?>"/>
			<button type="submit" class="ttp-pca-btn"><?php esc_html_e( 'Apply', 'ttp-pca' ); ?></button>
			<a class="ttp-pca-btn ttp-pca-btn--ghost" href="<?php echo esc_url( TTP_PCA_SiteKit::setup_url() ); ?>"><?php esc_html_e( 'Site Kit', 'ttp-pca' ); ?></a>
		</form>
	</header>

	<?php TTP_PCA_Admin_UI::render_tabs( 'search' ); ?>

	<?php if ( empty( $search['connected'] ) ) : ?>
		<div class="notice notice-warning ttp-pca-notice">
			<p>
				<?php
				printf(
					wp_kses_post( __( 'Search Console is not connected. <a href="%s">Set up Google Site Kit</a> first, then return here.', 'ttp-pca' ) ),
					esc_url( TTP_PCA_SiteKit::setup_url() )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<div class="ttp-pca-metrics">
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Total clicks', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value ttp-pca-metric__value--blue"><?php echo esc_html( number_format_i18n( (int) ( $search['clicks'] ?? 0 ) ) ); ?></strong>
		</div>
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Total impressions', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value ttp-pca-metric__value--purple"><?php echo esc_html( number_format_i18n( (int) ( $search['impressions'] ?? 0 ) ) ); ?></strong>
		</div>
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Average CTR', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( (float) ( $search['ctr'] ?? 0 ) ); ?>%</strong>
		</div>
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Average position', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( (float) ( $search['position'] ?? 0 ) ); ?></strong>
		</div>
	</div>

	<?php if ( $query_filter !== '' ) : ?>
		<p class="ttp-pca-tag"><?php echo esc_html( sprintf( __( 'Showing queries containing: %s', 'ttp-pca' ), $query_filter ) ); ?></p>
	<?php endif; ?>

	<div class="ttp-pca-panel">
		<h2><?php esc_html_e( 'Clicks & impressions over time', 'ttp-pca' ); ?></h2>
		<?php if ( ! empty( $search['rows'] ) ) : ?>
			<div class="ttp-pca-gsc-chart ttp-pca-gsc-chart--tall">
				<?php
				$max = 1;
				foreach ( $search['rows'] as $r ) {
					$max = max( $max, (int) ( $r['clicks'] ?? 0 ), (int) ( $r['impressions'] ?? 0 ) );
				}
				foreach ( $search['rows'] as $r ) :
					$c = (int) ( $r['clicks'] ?? 0 );
					$i = (int) ( $r['impressions'] ?? 0 );
					?>
					<div class="ttp-pca-gsc-chart__day">
						<div class="ttp-pca-gsc-chart__bars">
							<div class="ttp-pca-gsc-chart__bar ttp-pca-gsc-chart__bar--imp" style="height:<?php echo $max > 0 ? (int) round( ( $i / $max ) * 100 ) : 0; ?>%"></div>
							<div class="ttp-pca-gsc-chart__bar ttp-pca-gsc-chart__bar--clk" style="height:<?php echo $max > 0 ? (int) round( ( $c / $max ) * 100 ) : 0; ?>%"></div>
						</div>
						<span class="ttp-pca-gsc-chart__label"><?php echo esc_html( gmdate( 'M j', strtotime( $r['date'] ?? 'now' ) ) ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="ttp-pca-legend">
				<span class="ttp-pca-legend__item ttp-pca-legend__item--clk"><?php esc_html_e( 'Clicks', 'ttp-pca' ); ?></span>
				<span class="ttp-pca-legend__item ttp-pca-legend__item--imp"><?php esc_html_e( 'Impressions', 'ttp-pca' ); ?></span>
			</p>
		<?php else : ?>
			<p class="ttp-pca-muted"><?php esc_html_e( 'No data for this range.', 'ttp-pca' ); ?></p>
		<?php endif; ?>
	</div>

	<div class="ttp-pca-panel">
		<h2><?php esc_html_e( 'Queries', 'ttp-pca' ); ?></h2>
		<table class="ttp-pca-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Query', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Clicks', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Impressions', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'CTR', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Position', 'ttp-pca' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $search['queries'] ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No queries.', 'ttp-pca' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $search['queries'] as $q ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $q['query'] ); ?></strong></td>
							<td><?php echo esc_html( number_format_i18n( (int) $q['clicks'] ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $q['impressions'] ) ); ?></td>
							<td><?php echo esc_html( $q['ctr'] ); ?>%</td>
							<td><?php echo esc_html( $q['position'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
