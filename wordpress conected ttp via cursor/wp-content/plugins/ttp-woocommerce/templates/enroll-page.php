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
if ( ! function_exists( 'ttp_enroll_resolve_product_id' ) ) {
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
}

/**
 * Badge label for enroll card from product slug.
 *
 * @param string $post_name Product post_name.
 * @return string
 */
if ( ! function_exists( 'ttp_enroll_badge_for_slug' ) ) {
function ttp_enroll_badge_for_slug( $post_name ) {
	$map = [
		'cet-nmat-snap-elite-with-1-on-1-mentorship' => 'ELITE+',
		'cet-nmat-snap-elite'                        => 'NMAT/SNAP',
		'cet-elite-with-1-on-1-mentorship'           => 'ELITE+',
		'cet-elite'                                  => 'CET',
		'cet-solo-self-study'                        => 'SOLO',
	];
	$post_name = (string) $post_name;

	if ( isset( $map[ $post_name ] ) ) {
		return $map[ $post_name ];
	}

	$q = new WP_Query(
		[
			'post_type'      => 'product',
			'name'           => sanitize_title( $post_name ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		]
	);
	if ( ! empty( $q->posts[0] ) ) {
		$custom = (string) get_post_meta( (int) $q->posts[0], '_ttp_course_badge_label', true );
		if ( $custom !== '' ) {
			return $custom;
		}
	}

	return 'COURSE';
}
}

/**
 * Marketing capsules/tags per catalog slug (shown under title).
 *
 * @param string $post_name Product slug.
 * @return string[]
 */
if ( ! function_exists( 'ttp_enroll_capsules_for_slug' ) ) {
function ttp_enroll_capsules_for_slug( $post_name ) {
	$map = [
		'cet-nmat-snap-elite-with-1-on-1-mentorship' => [ '1:1 Mentorship', 'Live Classes', 'Basic & Advance Modules', '65+ Mocks' ],
		'cet-nmat-snap-elite'                        => [ 'Live Classes', 'Basic & Advance Modules', '65+ Mocks' ],
		'cet-elite-with-1-on-1-mentorship'           => [ '1:1 Mentorship', 'Live Classes', 'Basic & Advance Modules', '35+ Mocks' ],
		'cet-elite'                                  => [ 'Live Classes', 'Basic & Advance Modules', '35+ Mocks' ],
		'cet-solo-self-study'                        => [ 'Recorded Classes', 'Basic & Advance Modules', '20+ Mocks' ],
		'jbims-mfin-mhrd-bootcamp'                   => [ 'Live Sessions', 'Mock GD and PI' ],
		'jbims-mfin-mhrd-bootcamp-elite'             => [ 'Live Sessions', 'Mock GD and PI' ],
	];
	$post_name = sanitize_title( (string) $post_name );

	return isset( $map[ $post_name ] ) ? $map[ $post_name ] : [ 'Live Classes', 'Mocks & Tests' ];
}
}

/**
 * Parse newline- or comma-separated admin text into trimmed lines.
 *
 * @param string $raw Meta or textarea value.
 * @return string[]
 */
if ( ! function_exists( 'ttp_enroll_parse_lines_meta' ) ) {
function ttp_enroll_parse_lines_meta( $raw ) {
	$lines = [];
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $raw ) as $line ) {
		$line = trim( (string) $line );
		if ( $line !== '' ) {
			$lines[] = $line;
		}
	}
	if ( empty( $lines ) && strpos( (string) $raw, ',' ) !== false ) {
		foreach ( explode( ',', (string) $raw ) as $part ) {
			$part = trim( (string) $part );
			if ( $part !== '' ) {
				$lines[] = $part;
			}
		}
	}
	return $lines;
}
}

/**
 * Capsules for enroll card: product meta → slug map → default.
 *
 * @param WC_Product $product Product.
 * @return string[]
 */
if ( ! function_exists( 'ttp_enroll_capsules_for_product' ) ) {
function ttp_enroll_capsules_for_product( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return [];
	}
	$src = function_exists( 'ttp_tcy_meta_source_product_id' )
		? ttp_tcy_meta_source_product_id( $product->get_id() )
		: $product->get_id();
	$meta_lines = ttp_enroll_parse_lines_meta( (string) get_post_meta( $src, '_ttp_enroll_capsules', true ) );
	if ( ! empty( $meta_lines ) ) {
		return $meta_lines;
	}
	return ttp_enroll_capsules_for_slug( $product->get_slug() );
}
}

/**
 * Short banner headline (reference-style caps on hero image).
 *
 * @param string $post_name Product slug.
 * @return string
 */
/**
 * Landscape banner image per product (uploads on thetoppercentile.co.in).
 *
 * @param string $post_name Product slug.
 * @return string Image URL or empty.
 */
if ( ! function_exists( 'ttp_enroll_banner_url_for_slug' ) ) {
function ttp_enroll_banner_url_for_slug( $post_name ) {
	$map = [
		'cet-solo-self-study'                        => 'https://thetoppercentile.co.in/wp-content/uploads/2026/05/cet-solo-2027.png',
		'cet-elite-with-1-on-1-mentorship'           => 'https://thetoppercentile.co.in/wp-content/uploads/2026/05/cet-elite-2027-with-onne-on-one-ementorhip.png',
		'cet-elite'                                  => 'https://thetoppercentile.co.in/wp-content/uploads/2026/05/cet-elite-2027.png',
		'cet-nmat-snap-elite'                        => 'https://thetoppercentile.co.in/wp-content/uploads/2026/05/cet-nmap-snamp-elite.png',
		'cet-nmat-snap-elite-with-1-on-1-mentorship' => 'https://thetoppercentile.co.in/wp-content/uploads/2026/05/cet-nmap-snap-elite-with-1-on-one-mentorship.png',
	];
	$post_name = sanitize_title( (string) $post_name );
	$url       = isset( $map[ $post_name ] ) ? $map[ $post_name ] : '';

	return apply_filters( 'ttp_enroll_banner_url', $url, $post_name );
}
}

/**
 * Enrol Now / product details card image attachment ID (WooCommerce product meta).
 *
 * @param WC_Product|int $product Product or ID.
 * @return int Attachment ID or 0.
 */
if ( ! function_exists( 'ttp_enroll_card_image_id_for_product' ) ) {
function ttp_enroll_card_image_id_for_product( $product ) {
	$product = is_numeric( $product ) ? wc_get_product( (int) $product ) : $product;
	if ( ! $product instanceof WC_Product ) {
		return 0;
	}
	$id = (int) get_post_meta( $product->get_id(), '_ttp_enroll_card_image_id', true );

	return (int) apply_filters( 'ttp_enroll_card_image_id', $id, $product );
}
}

/**
 * Card banner URL: custom upload → slug map → WooCommerce featured image.
 *
 * @param WC_Product $product Product.
 * @return string
 */
if ( ! function_exists( 'ttp_enroll_card_image_url_for_product' ) ) {
function ttp_enroll_card_image_url_for_product( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$custom_id = ttp_enroll_card_image_id_for_product( $product );
	if ( $custom_id > 0 ) {
		$url = (string) wp_get_attachment_image_url( $custom_id, 'large' );
		if ( $url === '' ) {
			$url = (string) wp_get_attachment_image_url( $custom_id, 'full' );
		}
		if ( $url !== '' ) {
			return (string) apply_filters( 'ttp_enroll_card_image_url', $url, $product );
		}
	}

	$slug = sanitize_title( $product->get_slug() );
	if ( function_exists( 'ttp_enroll_banner_url_for_slug' ) ) {
		$mapped = ttp_enroll_banner_url_for_slug( $slug );
		if ( $mapped !== '' ) {
			return (string) apply_filters( 'ttp_enroll_card_image_url', $mapped, $product );
		}
	}

	$image_id = (int) $product->get_image_id();
	if ( $image_id > 0 ) {
		$url = (string) wp_get_attachment_image_url( $image_id, 'large' );
		if ( $url === '' ) {
			$url = (string) wp_get_attachment_image_url( $image_id, 'medium_large' );
		}
		if ( $url !== '' ) {
			return (string) apply_filters( 'ttp_enroll_card_image_url', $url, $product );
		}
	}

	return (string) apply_filters( 'ttp_enroll_card_image_url', '', $product );
}
}

if ( ! function_exists( 'ttp_enroll_banner_title_for_slug' ) ) {
function ttp_enroll_banner_title_for_slug( $post_name ) {
	$map = [
		'cet-nmat-snap-elite-with-1-on-1-mentorship' => 'NMAT SNAP ELITE+',
		'cet-nmat-snap-elite'                        => 'NMAT SNAP ELITE',
		'cet-elite-with-1-on-1-mentorship'           => 'CET ELITE+',
		'cet-elite'                                  => 'CET ELITE',
		'cet-solo-self-study'                        => 'CET SOLO',
		'jbims-mfin-mhrd-bootcamp'                   => 'JBIMS GD-PI',
		'jbims-mfin-mhrd-bootcamp-elite'             => 'JBIMS ELITE',
	];
	$post_name = sanitize_title( (string) $post_name );

	return isset( $map[ $post_name ] ) ? $map[ $post_name ] : 'MBA CET';
}
}

/**
 * @param WC_Product $product Product.
 * @return string e.g. "25% OFF" or empty.
 */
if ( ! function_exists( 'ttp_enroll_sale_badge_text' ) ) {
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
}

/**
 * View Details URL for Enrol Now cards (default: WooCommerce product page).
 *
 * @param WC_Product $product Product.
 * @return string
 */
if ( ! function_exists( 'ttp_enroll_view_details_url' ) ) {
function ttp_enroll_view_details_url( $product ) {
	$url = 'https://thetoppercentile.co.in/details/';
	return (string) apply_filters( 'ttp_enroll_view_details_url', $url, $product );
}
}

/**
 * Review count label under rating.
 *
 * @param WC_Product $product Product.
 * @return string
 */
if ( ! function_exists( 'ttp_enroll_review_count_label' ) ) {
function ttp_enroll_review_count_label( $product ) {
	return (string) apply_filters( 'ttp_enroll_review_count_label', '(512 Reviews)', $product );
}
}

/**
 * Feature list HTML for enroll cards: prefer catalog seed bullets by slug so each product
 * always matches its defined bullets (avoids stale or copied short descriptions in WooCommerce).
 *
 * @param WC_Product $product Product.
 * @return string Short description HTML (expected: ul/li).
 */
if ( ! function_exists( 'ttp_enroll_features_html_for_product' ) ) {
function ttp_enroll_features_html_for_product( $product ) {
	if ( class_exists( 'TTP_Catalog_Seed' ) ) {
		return TTP_Catalog_Seed::get_features_html_for_product( $product );
	}
	if ( $product instanceof WC_Product ) {
		return (string) $product->get_short_description();
	}

	return '';
}
}

/**
 * Build plan rows from WooCommerce category (MBA CET 2027 catalog).
 *
 * @param string $category_slug product_cat slug.
 * @return array<int, array{product: WC_Product, badge: string, discount_text: string, features_html: string}>
 */
if ( ! function_exists( 'ttp_enroll_rows_from_catalog_category' ) ) {
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
			'features_html'  => ttp_enroll_features_html_for_product( $product ),
		];
	}

	return apply_filters( 'ttp_enroll_catalog_rows', $rows, $category_slug );
}
}

/**
 * All published WooCommerce products with a TCY Course ID (new products show automatically).
 *
 * @return array<int, array{product: WC_Product, badge: string, discount_text: string, features_html: string}>
 */
if ( ! function_exists( 'ttp_enroll_rows_from_tcy_meta_products' ) ) {
function ttp_enroll_rows_from_tcy_meta_products() {
	$q = new WP_Query(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'title' => 'ASC' ],
			'meta_query'     => [
				[
					'key'     => '_ttp_tcy_course_id',
					'compare' => 'EXISTS',
				],
			],
		]
	);

	$rows = [];
	foreach ( $q->posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$tcy = (string) get_post_meta( $post->ID, '_ttp_tcy_course_id', true );
		if ( $tcy === '' || $tcy === '0' ) {
			continue;
		}
		$product = wc_get_product( $post );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$rows[] = [
			'product'       => $product,
			'badge'         => ttp_enroll_badge_for_slug( $post->post_name ),
			'discount_text' => ttp_enroll_sale_badge_text( $product ),
			'features_html' => ttp_enroll_features_html_for_product( $product ),
		];
	}

	return apply_filters( 'ttp_enroll_catalog_rows', $rows, 'tcy-meta' );
}
}

/**
 * Build one enroll card row from a WooCommerce product.
 *
 * @param WC_Product $product Product.
 * @return array{product: WC_Product, badge: string, discount_text: string, features_html: string}|null
 */
if ( ! function_exists( 'ttp_enroll_row_from_product' ) ) {
function ttp_enroll_row_from_product( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return null;
	}
	if ( $product->get_parent_id() > 0 ) {
		return null;
	}
	if ( in_array( $product->get_catalog_visibility(), [ 'hidden', 'search' ], true ) ) {
		return null;
	}
	if ( '1' === (string) get_post_meta( $product->get_id(), '_ttp_hide_from_enroll_page', true ) ) {
		return null;
	}

	return [
		'product'       => $product,
		'badge'         => ttp_enroll_badge_for_slug( $product->get_slug() ),
		'discount_text' => ttp_enroll_sale_badge_text( $product ),
		'features_html' => ttp_enroll_features_html_for_product( $product ),
	];
}
}

/**
 * All published shop products eligible for Enrol Now (unless explicitly hidden).
 *
 * @return array<int, array{product: WC_Product, badge: string, discount_text: string, features_html: string}>
 */
if ( ! function_exists( 'ttp_enroll_rows_from_shop_catalog' ) ) {
function ttp_enroll_rows_from_shop_catalog() {
	$types = apply_filters( 'ttp_enroll_allowed_product_types', [ 'simple', 'variable', 'subscription' ] );

	$q = new WP_Query(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
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
		if ( ! in_array( $product->get_type(), $types, true ) ) {
			continue;
		}
		$row = ttp_enroll_row_from_product( $product );
		if ( $row ) {
			$rows[] = $row;
		}
	}

	return apply_filters( 'ttp_enroll_catalog_rows', $rows, 'shop-catalog' );
}
}

/**
 * Merged catalog: seed + category + TCY meta + all eligible published products.
 *
 * @return array<int, array{product: WC_Product, badge: string, discount_text: string, features_html: string}>
 */
if ( ! function_exists( 'ttp_enroll_get_all_catalog_rows' ) ) {
function ttp_enroll_get_all_catalog_rows() {
	static $cache = null;
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$by_id = [];
	$merge = static function ( array $rows ) use ( &$by_id ) {
		foreach ( $rows as $row ) {
			if ( empty( $row['product'] ) || ! $row['product'] instanceof WC_Product ) {
				continue;
			}
			$by_id[ (int) $row['product']->get_id() ] = $row;
		}
	};

	$merge( ttp_enroll_rows_from_seed_slugs() );
	$catalog_slug = apply_filters( 'ttp_enroll_catalog_category_slug', 'mba-cet-2027' );
	$merge( ttp_enroll_rows_from_catalog_category( $catalog_slug ) );
	$merge( ttp_enroll_rows_from_tcy_meta_products() );
	$merge( ttp_enroll_rows_from_shop_catalog() );

	$rows = array_values( $by_id );
	usort(
		$rows,
		static function ( $a, $b ) {
			$pa = $a['product'] instanceof WC_Product ? (int) $a['product']->get_menu_order() : 0;
			$pb = $b['product'] instanceof WC_Product ? (int) $b['product']->get_menu_order() : 0;
			if ( $pa !== $pb ) {
				return $pa - $pb;
			}
			$na = $a['product'] instanceof WC_Product ? $a['product']->get_name() : '';
			$nb = $b['product'] instanceof WC_Product ? $b['product']->get_name() : '';
			return strcasecmp( $na, $nb );
		}
	);

	$cache = apply_filters( 'ttp_enroll_all_catalog_rows', $rows );
	return $cache;
}
}

/**
 * Split rows into top (3) and bottom (rest) — known slugs first, then any new products.
 *
 * @param array<int, array<string, mixed>> $catalog_rows All rows.
 * @return array{tier_a: array, tier_b: array}
 */
if ( ! function_exists( 'ttp_enroll_partition_catalog_rows' ) ) {
function ttp_enroll_partition_catalog_rows( array $catalog_rows ) {
	$priority_top = [
		'cet-nmat-snap-elite-with-1-on-1-mentorship',
		'cet-nmat-snap-elite',
		'cet-elite-with-1-on-1-mentorship',
	];
	$priority_bottom = [
		'cet-elite',
		'cet-solo-self-study',
		'jbims-mfin-mhrd-bootcamp',
		'jbims-mfin-mhrd-bootcamp-elite',
	];

	$by_slug = [];
	foreach ( $catalog_rows as $row ) {
		if ( empty( $row['product'] ) || ! $row['product'] instanceof WC_Product ) {
			continue;
		}
		$key = sanitize_title( $row['product']->get_slug() );
		if ( $key ) {
			$by_slug[ $key ] = $row;
		}
	}

	$top    = [];
	$bottom = [];
	foreach ( $priority_top as $slug ) {
		if ( isset( $by_slug[ $slug ] ) ) {
			$top[] = $by_slug[ $slug ];
			unset( $by_slug[ $slug ] );
		}
	}
	foreach ( $priority_bottom as $slug ) {
		if ( isset( $by_slug[ $slug ] ) ) {
			$bottom[] = $by_slug[ $slug ];
			unset( $by_slug[ $slug ] );
		}
	}

	// New WooCommerce products (CUET, MBA PI, JBIMS, etc.) — same UI, after priority courses.
	foreach ( $by_slug as $row ) {
		$bottom[] = $row;
	}

	return apply_filters(
		'ttp_enroll_partitioned_rows',
		[
			'tier_a' => $top,
			'tier_b' => $bottom,
		],
		$catalog_rows
	);
}
}

/**
 * Fallback: load priority MBA CET products by slug (no TCY meta required for display).
 *
 * @return array<int, array<string, mixed>>
 */
if ( ! function_exists( 'ttp_enroll_fallback_priority_rows' ) ) {
function ttp_enroll_fallback_priority_rows() {
	$slugs = [
		'cet-nmat-snap-elite-with-1-on-1-mentorship',
		'cet-nmat-snap-elite',
		'cet-elite-with-1-on-1-mentorship',
		'cet-elite',
		'cet-solo-self-study',
		'jbims-mfin-mhrd-bootcamp',
		'jbims-mfin-mhrd-bootcamp-elite',
	];
	$rows  = [];
	foreach ( $slugs as $slug ) {
		$slug = sanitize_title( $slug );
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
		if ( empty( $q->posts[0] ) ) {
			continue;
		}
		$product = wc_get_product( (int) $q->posts[0] );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$rows[] = [
			'product'       => $product,
			'badge'         => ttp_enroll_badge_for_slug( $product->get_slug() ),
			'discount_text' => ttp_enroll_sale_badge_text( $product ),
			'features_html' => ttp_enroll_features_html_for_product( $product ),
		];
	}
	return $rows;
}
}

/**
 * Layout: 3 courses per row (row 1 = first 3, row 2 = next 3, then 3 per row).
 *
 * @param array<int, array<string, mixed>> $catalog_rows Product rows.
 * @param callable                         $render_card  Callback( array $row ): void.
 */
if ( ! function_exists( 'ttp_enroll_render_plans_layout' ) ) {
function ttp_enroll_render_plans_layout( array $catalog_rows, callable $render_card ) {
	$valid = [];
	foreach ( $catalog_rows as $row ) {
		if ( ! empty( $row['product'] ) && $row['product'] instanceof WC_Product ) {
			$valid[] = $row;
		}
	}

	if ( empty( $valid ) ) {
		echo '<p class="ttp-plans-empty">' . esc_html__( 'No courses found. Please refresh the page or contact support.', 'ttp-woocommerce' ) . '</p>';
		return;
	}

	$row_chunks = array_chunk( $valid, 3 );

	echo '<div class="ttp-plans-layout">';

	foreach ( $row_chunks as $index => $chunk ) {
		$row_class = 0 === $index ? 'ttp-plans-row--top' : 'ttp-plans-row--bottom';
		echo '<div class="ttp-plans-row ' . esc_attr( $row_class ) . '" role="list">';
		foreach ( $chunk as $row ) {
			echo '<div class="ttp-plans-grid__cell" role="listitem">';
			$render_card( $row );
			echo '</div>';
		}
		echo '</div>';
	}

	echo '</div>';
}
}

/**
 * Build rows from plugin catalog slugs (works even if products are not in product_cat).
 *
 * @return array<int, array{product: WC_Product, badge: string, discount_text: string, features_html: string}>
 */
if ( ! function_exists( 'ttp_enroll_rows_from_seed_slugs' ) ) {
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
		$seed_course = isset( $def['tcy_course_id'] ) ? sanitize_text_field( (string) $def['tcy_course_id'] ) : '';
		if ( $seed_course === '' || $seed_course === '0' ) {
			continue;
		}

		$product_id = class_exists( 'TTP_Catalog_Seed' ) ? TTP_Catalog_Seed::get_product_id_for_definition( $def ) : 0;
		if ( $product_id < 1 ) {
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
			$product_id = (int) $q->posts[0]->ID;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$wc_course = (string) get_post_meta( $product_id, '_ttp_tcy_course_id', true );
		if ( $wc_course === '' || $wc_course === '0' ) {
			continue;
		}

		$rows[] = [
			'product'       => $product,
			'badge'         => ttp_enroll_badge_for_slug( $product->get_slug() ? $product->get_slug() : $slug ),
			'discount_text' => ttp_enroll_sale_badge_text( $product ),
			'features_html' => ttp_enroll_features_html_for_product( $product ),
		];
	}

	return apply_filters( 'ttp_enroll_catalog_rows', $rows, 'seed-slugs' );
}
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

// Priority MBA CET row + every other published product (same card UI).
$catalog_rows = class_exists( 'TTP_Enroll_Page' )
	? TTP_Enroll_Page::get_exam_catalog_rows()
	: ( function_exists( 'ttp_enroll_get_all_catalog_rows' ) ? ttp_enroll_get_all_catalog_rows() : [] );
if ( empty( $catalog_rows ) && function_exists( 'ttp_enroll_fallback_priority_rows' ) ) {
	$catalog_rows = ttp_enroll_fallback_priority_rows();
}
$use_catalog  = ! empty( $catalog_rows );
$ttp_show_hero = ! isset( $GLOBALS['ttp_enroll_show_hero'] ) || $GLOBALS['ttp_enroll_show_hero'];
?>
<!-- TTP enroll UI <?php echo esc_attr( TTP_VERSION ); ?> -->
<div class="ttp-enroll-page ttp-enroll-page--marketplace ttp-enroll-page--light ttp-enroll-page--grid" data-ttp-enroll-version="<?php echo esc_attr( TTP_VERSION ); ?>">
	<?php if ( $ttp_show_hero ) : ?>
	<div class="ttp-enroll-hero">
		<span class="ttp-hero-eyebrow ttp-hero-eyebrow--hidden" aria-hidden="true"><?php esc_html_e( 'Enroll Now', 'ttp-woocommerce' ); ?></span>
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
			<span class="ttp-trust-badge"><span class="ttp-dot" aria-hidden="true"></span> <?php esc_html_e( 'Trusted by 1000+ aspirants', 'ttp-woocommerce' ); ?></span>
			<span class="ttp-trust-badge"><span class="ttp-dot" aria-hidden="true"></span> <?php esc_html_e( 'JBIMS alumni mentors', 'ttp-woocommerce' ); ?></span>
			<span class="ttp-trust-badge"><span class="ttp-dot" aria-hidden="true"></span> <?php esc_html_e( 'Live and recorded sessions', 'ttp-woocommerce' ); ?></span>
		</div>
	</div>
	<?php endif; ?>

	<div class="ttp-plans-section">
		<h2 class="ttp-plans-title"><?php esc_html_e( 'Choose Your Plan', 'ttp-woocommerce' ); ?></h2>
		<p class="ttp-plans-subtitle"><?php esc_html_e( 'Find the MBA prep plan that\'s right for you.', 'ttp-woocommerce' ); ?></p>
		<p class="ttp-plans-scroll-hint" aria-hidden="true"><?php esc_html_e( 'Scroll down to see more courses ↓', 'ttp-woocommerce' ); ?></p>

		<?php if ( $use_catalog ) : ?>
			<?php
			$ttp_render_enroll_card = static function ( $row ) {
				$product               = $row['product'];
				$badge                 = $row['badge'];
				$discount_text         = $row['discount_text'];
				$features_html         = $row['features_html'];
				$legacy_features       = [];
				$ttp_enroll_card_tier  = 'ref';
				$ttp_enroll_is_popular = ( 'cet-nmat-snap-elite-with-1-on-1-mentorship' === sanitize_title( $product->get_slug() ) );
				$ttp_enroll_capsules   = function_exists( 'ttp_enroll_capsules_for_product' )
					? ttp_enroll_capsules_for_product( $product )
					: ttp_enroll_capsules_for_slug( $product->get_slug() );
				$ttp_enroll_banner_title = ttp_enroll_banner_title_for_slug( $product->get_slug() );
				include TTP_DIR . 'templates/enroll-page-part-card.php';
			};
			?>
			<?php ttp_enroll_render_plans_layout( $catalog_rows, $ttp_render_enroll_card ); ?>
		<?php else : ?>
			<?php
			$legacy_rows = [];
			foreach ( $legacy_plans as $plan ) {
				$pid     = ttp_enroll_resolve_product_id( $plan['product_slug'], $plan['fallback_id'] );
				$product = $pid ? wc_get_product( $pid ) : null;
				if ( ! $product ) {
					continue;
				}
				$legacy_rows[] = [
					'product'       => $product,
					'badge'         => $plan['badge'],
					'discount_text' => $plan['discount_text'],
					'features_html' => '',
					'legacy_features' => $plan['features'],
				];
			}
			$ttp_render_legacy_card = static function ( $row ) {
				$product               = $row['product'];
				$badge                 = $row['badge'];
				$discount_text         = $row['discount_text'];
				$features_html         = $row['features_html'];
				$legacy_features       = isset( $row['legacy_features'] ) ? $row['legacy_features'] : [];
				$ttp_enroll_card_tier  = 'ref';
				$ttp_enroll_is_popular = false;
				$ttp_enroll_capsules   = function_exists( 'ttp_enroll_capsules_for_product' )
					? ttp_enroll_capsules_for_product( $product )
					: ttp_enroll_capsules_for_slug( $product->get_slug() );
				$ttp_enroll_banner_title = ttp_enroll_banner_title_for_slug( $product->get_slug() );
				include TTP_DIR . 'templates/enroll-page-part-card.php';
			};
			ttp_enroll_render_plans_layout( $legacy_rows, $ttp_render_legacy_card );
			?>
		<?php endif; ?>
	</div>
</div>
