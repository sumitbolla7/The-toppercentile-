<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ttp-pca-wrap">
	<header class="ttp-pca-hero ttp-pca-hero--compact">
		<div>
			<h1><?php esc_html_e( 'Content analysis', 'ttp-pca' ); ?></h1>
			<p class="ttp-pca-lead"><?php esc_html_e( 'SEO & structure checks per page (word count, headings, meta).', 'ttp-pca' ); ?></p>
		</div>
		<form method="get" class="ttp-pca-filters">
			<input type="hidden" name="page" value="ttp-pca-content"/>
			<select name="audit_type">
				<option value="page" <?php selected( $ptype, 'page' ); ?>><?php esc_html_e( 'Pages', 'ttp-pca' ); ?></option>
				<option value="post" <?php selected( $ptype, 'post' ); ?>><?php esc_html_e( 'Posts', 'ttp-pca' ); ?></option>
				<option value="product" <?php selected( $ptype, 'product' ); ?>><?php esc_html_e( 'Products', 'ttp-pca' ); ?></option>
			</select>
			<button type="submit" class="ttp-pca-btn"><?php esc_html_e( 'Run audit', 'ttp-pca' ); ?></button>
		</form>
	</header>

	<?php TTP_PCA_Admin_UI::render_tabs( 'content' ); ?>

	<div class="ttp-pca-panel">
		<table class="ttp-pca-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Score', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Title', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Words', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'H1/H2', 'ttp-pca' ); ?></th>
					<th><?php esc_html_e( 'Issues', 'ttp-pca' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $audit as $row ) : ?>
					<tr>
						<td><span class="ttp-pca-score ttp-pca-score--<?php echo (int) $row['score'] >= 70 ? 'ok' : 'warn'; ?>"><?php echo (int) $row['score']; ?></span></td>
						<td>
							<strong><?php echo esc_html( $row['title'] ); ?></strong>
							<?php if ( ! empty( $row['url'] ) ) : ?>
								<a class="ttp-pca-link" href="<?php echo esc_url( $row['url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'ttp-pca' ); ?></a>
							<?php endif; ?>
						</td>
						<td><?php echo (int) $row['word_count']; ?></td>
						<td><?php echo (int) $row['h1']; ?> / <?php echo (int) $row['h2']; ?></td>
						<td class="ttp-pca-muted"><?php echo esc_html( ! empty( $row['issues'] ) ? implode( '; ', $row['issues'] ) : '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
