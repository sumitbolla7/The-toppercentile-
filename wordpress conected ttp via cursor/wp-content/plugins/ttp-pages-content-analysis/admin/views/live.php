<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ttp-pca-wrap">
	<header class="ttp-pca-hero ttp-pca-hero--compact">
		<div>
			<h1><?php esc_html_e( 'Live page opens', 'ttp-pca' ); ?></h1>
			<p class="ttp-pca-lead"><?php esc_html_e( 'Most recent URLs visitors opened (refresh to update).', 'ttp-pca' ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ttp-pca-live' ) ); ?>" class="ttp-pca-btn"><?php esc_html_e( 'Refresh', 'ttp-pca' ); ?></a>
	</header>

	<?php TTP_PCA_Admin_UI::render_tabs( 'live' ); ?>

	<div class="ttp-pca-panel">
		<table class="ttp-pca-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Page', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Device', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Referrer', 'ttp-pca' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $recent as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r->created_at ); ?></td>
						<td>
							<strong><?php echo esc_html( $r->page_title ); ?></strong><br/>
							<a class="ttp-pca-link" href="<?php echo esc_url( $r->page_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $r->page_url ); ?></a>
						</td>
						<td><span class="ttp-pca-tag"><?php echo esc_html( $r->device_type ); ?></span></td>
						<td class="ttp-pca-muted"><?php echo esc_html( $r->referrer ? wp_parse_url( $r->referrer, PHP_URL_HOST ) : '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
