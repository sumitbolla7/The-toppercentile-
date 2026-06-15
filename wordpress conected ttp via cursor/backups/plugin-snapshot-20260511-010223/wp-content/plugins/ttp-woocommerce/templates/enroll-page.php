<?php
/**
 * Enroll landing markup (included by TTP_Enroll_Page shortcode).
 *
 * Prefers products in the MBA CET 2027 category; falls back to legacy plan config.
 *
 * @package TTP_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve product ID from slug or fallback.
 *
 * @param string $slug Post name.
 * @param int    $fallback_id Product ID if slug missing.
 * @return int
 */
function ttp_enroll_resolve_product_id( $slug, $fallback_id ) {
	$slug = sanitize_title( $slug );
	if ( $slug ) {
		$q = new WP_Query(
			[
				'post_type'      => 'product',
				'name'           => $slug,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);
		if ( ! empty( $q->posts[0] ) ) {
			return (int) $q->posts[0];
		}
	}

	return (int) $fallback_id;
}

/**
 * Badge label for enroll card from product slug.
 *
 * @param string $post_name Product post_name.
 * @return string
 */
function ttp_enroll_badge_for_slug( $post_name ) {
	$map = [
		'cet-nmat-snap-elite-with-1-on-1-mentorship' => 'ELITE+',
		'cet-nmat-snap-elite'                        => 'NMAT/SNAP',
		'cet-elite-with-1-on-1-mentorship'           => 'ELITE+',
		'cet-elite'                                  => 'CET',
		'cet-solo-self-study'                        => 'SOLO',
	];
	$post_name = (string) $post_name;

	return isset( $map[ $post_name ] ) ? $map[ $post_name ] : 'COURSE';
}

/**
 * @param WC_Product $product Product.
 * @return string e.g. "25% OFF" or empty.
 */
function ttp_enroll_sale_badge_text( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
		return '';
	}
	$reg  = (float) $product->get_regular_price();
	$sale = (float) $product->get_sale_price();
	if ( $reg <= 0 || $sale < 0 ) {
		return '';
	}
	$pct = (int) round( 100 * ( 1 - $sale / $reg ) );

	return $pct > 0 ? sprintf( '%d%% OFF', $pct ) : '';
}

/**
 * Build plan rows from WooCommerce category (MBA CET 2027 catalog).
 *
 * @param string $category_slug product_cat slug.
 * @return array<int, array{product: WC_Product, badge: string, discount_text: string, features_html: string}>
 */
function ttp_enroll_rows_from_catalog_category( $category_slug ) {
	$category_slug = sanitize_title( $category_slug );
	if ( ! $category_slug ) {
		return [];
	}

	$q = new WP_Query(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'tax_query'      => [
				[
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $category_slug,
				],
			],
		]
	);

	$rows = [];
	foreach ( $q->posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$product = wc_get_product( $post );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$rows[] = [
			'product'        => $product,
			'badge'          => ttp_enroll_badge_for_slug( $post->post_name ),
			'discount_text'  => ttp_enroll_sale_badge_text( $product ),
			'features_html'  => $product->get_short_description(),
		];
	}

	return apply_filters( 'ttp_enroll_catalog_rows', $rows, $category_slug );
}

/**
 * Build rows from plugin catalog slugs (works even if products are not in product_cat).
 *
 * @return array<int, array{product: WC_Product, badge: string, discount_text: string, features_html: string}>
 */
function ttp_enroll_rows_from_seed_slugs() {
	if ( ! class_exists( 'TTP_Catalog_Seed' ) ) {
		return [];
	}

	$rows = [];
	foreach ( TTP_Catalog_Seed::get_definitions() as $def ) {
		$slug = isset( $def['slug'] ) ? sanitize_title( $def['slug'] ) : '';
		if ( ! $slug ) {
			continue;
		}

		$q = new WP_Query(
			[
				'post_type'      => 'product',
				'name'           => $slug,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			]
		);

		if ( empty( $q->posts[0] ) || ! $q->posts[0] instanceof WP_Post ) {
			continue;
		}

		$product = wc_get_product( $q->posts[0] );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$post = $q->posts[0];
		$rows[] = [
			'product'       => $product,
			'badge'         => ttp_enroll_badge_for_slug( $post->post_name ),
			'discount_text' => ttp_enroll_sale_badge_text( $product ),
			'features_html' => $product->get_short_description(),
		];
	}

	return apply_filters( 'ttp_enroll_catalog_rows', $rows, 'seed-slugs' );
}

$legacy_plans = apply_filters(
	'ttp_enroll_page_plans',
	[
		[
			'product_slug'  => 'mhcet',
			'fallback_id'   => 17074,
			'badge'         => 'COURSE',
			'discount_text' => '90% OFF',
			'features'      => [
				'Full course access',
				'Practice tests',
				'Doubt-solving WhatsApp group',
			],
		],
		[
			'product_slug'  => 'cet-mh-test-series-with-mba-topic-wise-tests-batch-2',
			'fallback_id'   => 17075,
			'badge'         => 'TEST SERIES',
			'discount_text' => '70% OFF',
			'features'      => [
				'67 topic-wise concept builders',
				'196 practice tests',
				'16 TTP Turbo Mocks + 40 sectional tests',
				'MBA Topic-wise tests included',
				'Detailed performance analytics',
				'Doubt-solving WhatsApp group',
			],
		],
	]
);

$catalog_slug = apply_filters( 'ttp_enroll_catalog_category_slug', 'mba-cet-2027' );
// Slug list first: products often stay "Uncategorized" but still match catalog slugs.
$catalog_rows = ttp_enroll_rows_from_seed_slugs();
if ( empty( $catalog_rows ) ) {
	$catalog_rows = ttp_enroll_rows_from_catalog_category( $catalog_slug );
}
$use_catalog = ! empty( $catalog_rows );
?>
<div class="ttp-enroll-page">
	<div class="ttp-enroll-hero">
		<span class="ttp-hero-eyebrow"><?php esc_html_e( 'Enroll Now', 'ttp-woocommerce' ); ?></span>
		<h1>
			<?php
			echo wp_kses(
				__( 'Crack MAH MBA CET 2026<br>with <span>The Top Percentile</span>', 'ttp-woocommerce' ),
				[
					'br'   => [],
					'span' => [],
				]
			);
			?>
		</h1>
		<p><?php esc_html_e( 'Join 1000+ aspirants who\'ve cracked JBIMS, IIM-A, SPJIMR & NMIMS with our proven crash courses and test series.', 'ttp-woocommerce' ); ?></p>
		<div class="ttp-trust-badges">
			<span class="ttp-trust-badge"><span class="ttp-dot"></span> <?php esc_html_e( 'Trusted by 1000+ Aspirants', 'ttp-woocommerce' ); ?></span>
			<span class="ttp-trust-badge"><span class="ttp-dot"></span> <?php esc_html_e( 'JBIMS Alumni Mentors', 'ttp-woocommerce' ); ?></span>
			<span class="ttp-trust-badge"><span class="ttp-dot"></span> <?php esc_html_e( 'Live + Recorded Sessions', 'ttp-woocommerce' ); ?></span>
		</div>
	</div>

	<div class="ttp-plans-section">
		<h2 class="ttp-plans-title"><?php esc_html_e( 'Choose Your Plan', 'ttp-woocommerce' ); ?></h2>
		<p class="ttp-plans-subtitle"><?php esc_html_e( 'All plans include access to our TCY portal — click Enroll Now to get started', 'ttp-woocommerce' ); ?></p>

		<div class="ttp-plans-grid">
			<?php if ( $use_catalog ) : ?>
				<?php foreach ( $catalog_rows as $row ) : ?>
					<?php
					$product         = $row['product'];
					$badge           = $row['badge'];
					$discount_text   = $row['discount_text'];
					$features_html   = $row['features_html'];
					$legacy_features = [];
					include TTP_DIR . 'templates/enroll-page-part-card.php';
					?>
				<?php endforeach; ?>
			<?php else : ?>
				<?php foreach ( $legacy_plans as $plan ) : ?>
					<?php
					$pid     = ttp_enroll_resolve_product_id( $plan['product_slug'], $plan['fallback_id'] );
					$product = $pid ? wc_get_product( $pid ) : null;
					if ( ! $product ) {
						continue;
					}
					$badge           = $plan['badge'];
					$discount_text   = $plan['discount_text'];
					$features_html   = '';
					$legacy_features = $plan['features'];
					include TTP_DIR . 'templates/enroll-page-part-card.php';
					?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
