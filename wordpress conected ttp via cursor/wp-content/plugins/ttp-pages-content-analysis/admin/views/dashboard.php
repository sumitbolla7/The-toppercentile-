<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=ttp_pca_export_csv&days=' . (int) $days ), 'ttp_pca_export' );
?>
<div class="wrap ttp-pca-wrap">
	<header class="ttp-pca-hero">
		<div>
			<p class="ttp-pca-eyebrow"><?php esc_html_e( 'Top Percentile', 'ttp-pca' ); ?></p>
			<h1><?php esc_html_e( 'Pages & opens report', 'ttp-pca' ); ?></h1>
			<p class="ttp-pca-lead"><?php esc_html_e( 'Which URLs visitors open on your site — per page views, sessions, and last opened time.', 'ttp-pca' ); ?></p>
		</div>
		<form method="get" class="ttp-pca-filters">
			<input type="hidden" name="page" value="ttp-pca-opens"/>
			<select name="days">
				<?php foreach ( [ 7, 14, 30, 90 ] as $d ) : ?>
					<option value="<?php echo (int) $d; ?>" <?php selected( $days, $d ); ?>><?php echo esc_html( sprintf( __( 'Last %d days', 'ttp-pca' ), $d ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<select name="post_type">
				<option value=""><?php esc_html_e( 'All types', 'ttp-pca' ); ?></option>
				<?php foreach ( [ 'page', 'post', 'product' ] as $pt ) : ?>
					<option value="<?php echo esc_attr( $pt ); ?>" <?php selected( $type, $pt ); ?>><?php echo esc_html( $pt ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="ttp-pca-btn"><?php esc_html_e( 'Apply', 'ttp-pca' ); ?></button>
			<a href="<?php echo esc_url( $export_url ); ?>" class="ttp-pca-btn ttp-pca-btn--ghost"><?php esc_html_e( 'Export CSV', 'ttp-pca' ); ?></a>
		</form>
	</header>

	<?php TTP_PCA_Admin_UI::render_tabs( 'pages' ); ?>

	<div class="ttp-pca-metrics">
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Page opens', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( number_format_i18n( (int) $summary['total_views'] ) ); ?></strong>
		</div>
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Unique sessions', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( number_format_i18n( (int) $summary['unique_visits'] ) ); ?></strong>
		</div>
		<div class="ttp-pca-metric">
			<span class="ttp-pca-metric__label"><?php esc_html_e( 'Distinct pages opened', 'ttp-pca' ); ?></span>
			<strong class="ttp-pca-metric__value"><?php echo esc_html( number_format_i18n( (int) $summary['pages_opened'] ) ); ?></strong>
		</div>
	</div>

	<?php if ( ! empty( $chart ) ) : ?>
	<div class="ttp-pca-panel">
		<h2><?php esc_html_e( 'Opens per day', 'ttp-pca' ); ?></h2>
		<div class="ttp-pca-chart">
			<?php
			$max = max( 1, max( $chart ) );
			foreach ( $chart as $day => $count ) :
				$h = round( ( $count / $max ) * 100 );
				?>
				<div class="ttp-pca-chart__bar" title="<?php echo esc_attr( $day . ': ' . $count ); ?>">
					<div class="ttp-pca-chart__fill" style="height:<?php echo (int) $h; ?>%"></div>
					<span class="ttp-pca-chart__label"><?php echo esc_html( gmdate( 'M j', strtotime( $day ) ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="ttp-pca-panel">
		<h2><?php esc_html_e( 'Report: each page opened', 'ttp-pca' ); ?></h2>
		<table class="ttp-pca-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Page', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Type', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Opens', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Sessions', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Last opened', 'ttp-pca' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $top ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No page opens recorded yet. Visit the front-end to generate data.', 'ttp-pca' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $top as $row ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $row->page_title ?: __( '(no title)', 'ttp-pca' ) ); ?></strong>
								<a class="ttp-pca-link" href="<?php echo esc_url( $row->page_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( wp_parse_url( $row->page_url, PHP_URL_PATH ) ?: $row->page_url ); ?></a>
							</td>
							<td><span class="ttp-pca-tag"><?php echo esc_html( $row->post_type ?: '—' ); ?></span></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row->views ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( (int) $row->unique_sessions ) ); ?></td>
							<td><?php echo esc_html( $row->last_opened ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
