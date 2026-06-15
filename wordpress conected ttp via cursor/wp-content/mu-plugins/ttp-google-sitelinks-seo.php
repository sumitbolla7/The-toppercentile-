<?php
/**
 * Plugin Name: TTP Google Sitelinks SEO
 * Description: Redirects thin author/category URLs to Enrol Now (/exam/), noindexes them, and outputs SiteNavigationElement schema so Google favors Enrol/Buy pages over blog archives.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Primary conversion URL (Enrol / Buy courses).
 *
 * @return string
 */
function ttp_seo_primary_enrol_url() {
	$url = home_url( '/exam/' );
	return (string) apply_filters( 'ttp_seo_primary_enrol_url', $url );
}

/**
 * Category slugs that should 301 to Enrol Now (Google sitelink cleanup).
 *
 * @return string[]
 */
function ttp_seo_redirect_category_slugs() {
	return array_map(
		'sanitize_title',
		(array) apply_filters(
			'ttp_seo_redirect_category_slugs',
			[
				'mah-mba-cet',
				'mah-mba-cet-archives',
			]
		)
	);
}

/**
 * Author nicenames to redirect (empty = redirect all author archives).
 *
 * @return string[] Empty array means all authors.
 */
function ttp_seo_redirect_author_nicenames() {
	$all = (bool) apply_filters( 'ttp_seo_redirect_all_author_archives', true );
	if ( $all ) {
		return [];
	}
	return array_map(
		'sanitize_title',
		(array) apply_filters(
			'ttp_seo_redirect_author_nicenames',
			[ 'marketing-sid' ]
		)
	);
}

/**
 * @return bool
 */
function ttp_seo_should_redirect_request() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}

	if ( is_author() ) {
		$allow = ttp_seo_redirect_author_nicenames();
		if ( empty( $allow ) ) {
			return true;
		}
		$author = get_queried_object();
		if ( $author && isset( $author->user_nicename ) ) {
			return in_array( sanitize_title( (string) $author->user_nicename ), $allow, true );
		}
		return false;
	}

	if ( is_category() ) {
		$term = get_queried_object();
		if ( $term && isset( $term->slug ) ) {
			return in_array( sanitize_title( (string) $term->slug ), ttp_seo_redirect_category_slugs(), true );
		}
	}

	return false;
}

/**
 * 301 redirect irrelevant archives to Enrol Now.
 *
 * @return void
 */
function ttp_seo_template_redirect_to_enrol() {
	if ( ! ttp_seo_should_redirect_request() ) {
		return;
	}
	$dest = ttp_seo_primary_enrol_url();
	if ( $dest === '' ) {
		return;
	}
	wp_safe_redirect( $dest, 301 );
	exit;
}
add_action( 'template_redirect', 'ttp_seo_template_redirect_to_enrol', 1 );

/**
 * Paths that should 301 to Enrol Now (main site or study subdomain on this WordPress install).
 *
 * @return string[]
 */
function ttp_seo_redirect_path_prefixes() {
	return array_map(
		static function ( $p ) {
			return '/' . trim( sanitize_title( (string) $p ), '/' );
		},
		(array) apply_filters(
			'ttp_seo_redirect_path_prefixes',
			[ 'skillbuilder' ]
		)
	);
}

/**
 * @return bool
 */
function ttp_seo_request_is_skillbuilder_path() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$uri = strtolower( strtok( $uri, '?' ) );
	foreach ( ttp_seo_redirect_path_prefixes() as $prefix ) {
		if ( $prefix !== '' && ( $uri === $prefix || strpos( $uri, $prefix . '/' ) === 0 ) ) {
			return true;
		}
	}
	return false;
}

/**
 * @return bool
 */
function ttp_seo_request_is_study_subdomain() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	$host = preg_replace( '/:\d+$/', '', $host );
	return $host === 'study.thetoppercentile.co.in' || false !== strpos( $host, 'study.thetoppercentile.co.in' );
}

/**
 * Redirect /skillbuilder (study portal or main site) → Enrol Now.
 *
 * @return void
 */
function ttp_seo_redirect_skillbuilder_to_enrol() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! ttp_seo_request_is_skillbuilder_path() ) {
		return;
	}
	$dest = ttp_seo_primary_enrol_url();
	if ( $dest === '' ) {
		return;
	}
	wp_safe_redirect( $dest, 301 );
	exit;
}
add_action( 'init', 'ttp_seo_redirect_skillbuilder_to_enrol', 0 );

/**
 * Replace study skillbuilder links in menus/content with Enrol Now URL.
 *
 * @param string $url URL.
 * @return string
 */
function ttp_seo_replace_skillbuilder_url( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return $url;
	}
	if ( false !== stripos( $url, 'skillbuilder' ) ) {
		return ttp_seo_primary_enrol_url();
	}
	return $url;
}
add_filter(
	'wp_nav_menu_objects',
	static function ( $items ) {
		if ( ! is_array( $items ) ) {
			return $items;
		}
		foreach ( $items as $item ) {
			if ( is_object( $item ) && ! empty( $item->url ) ) {
				$item->url = ttp_seo_replace_skillbuilder_url( (string) $item->url );
			}
		}
		return $items;
	},
	20
);
add_filter( 'the_content', function ( $html ) {
	if ( ! is_string( $html ) || $html === '' || false === stripos( $html, 'skillbuilder' ) ) {
		return $html;
	}
	$enrol = ttp_seo_primary_enrol_url();
	return preg_replace(
		'#https?://(?:www\.)?study\.thetoppercentile\.co\.in[^\s"\'<>]*skillbuilder[^\s"\'<>]*#i',
		esc_url( $enrol ),
		$html
	);
}, 20 );

/**
 * Robots noindex on author + redirected categories (belt-and-suspenders before redirect is cached).
 *
 * @return void
 */
function ttp_seo_noindex_low_value_archives() {
	if ( is_author() ) {
		echo '<meta name="robots" content="noindex, follow" />' . "\n";
		return;
	}
	if ( is_category() ) {
		$term = get_queried_object();
		if ( $term && isset( $term->slug ) && in_array( sanitize_title( (string) $term->slug ), ttp_seo_redirect_category_slugs(), true ) ) {
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		}
	}
}
add_action( 'wp_head', 'ttp_seo_noindex_low_value_archives', 1 );

/**
 * Yoast SEO robots override.
 *
 * @param string $robots Robots string.
 * @return string
 */
function ttp_seo_yoast_robots( $robots ) {
	if ( ttp_seo_should_redirect_request() || is_author() ) {
		return 'noindex, follow';
	}
	return $robots;
}
add_filter( 'wpseo_robots', 'ttp_seo_yoast_robots' );

/**
 * Rank Math robots override.
 *
 * @param array $robots Robots meta array.
 * @return array
 */
function ttp_seo_rankmath_robots( $robots ) {
	if ( ttp_seo_should_redirect_request() || is_author() ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}
add_filter( 'rank_math/frontend/robots', 'ttp_seo_rankmath_robots' );

/**
 * Sitelinks-friendly navigation schema (homepage).
 *
 * @return void
 */
function ttp_seo_site_navigation_schema() {
	if ( ! is_front_page() && ! is_home() ) {
		return;
	}

	$enrol = ttp_seo_primary_enrol_url();
	$items = (array) apply_filters(
		'ttp_seo_site_navigation_items',
		[
			[
				'name' => 'Enrol Now',
				'url'  => $enrol,
			],
			[
				'name' => 'Buy Now — MBA CET Courses',
				'url'  => $enrol,
			],
			[
				'name' => 'Free Resources',
				'url'  => home_url( '/free-resources/' ),
			],
			[
				'name' => 'About Us',
				'url'  => home_url( '/about-us/' ),
			],
			[
				'name' => 'Blog',
				'url'  => home_url( '/blog/' ),
			],
		]
	);

	$elements = [];
	$pos      = 1;
	foreach ( $items as $item ) {
		if ( empty( $item['url'] ) || empty( $item['name'] ) ) {
			continue;
		}
		$elements[] = [
			'@type'    => 'SiteNavigationElement',
			'position' => $pos++,
			'name'     => (string) $item['name'],
			'url'      => esc_url_raw( (string) $item['url'] ),
		];
	}

	if ( empty( $elements ) ) {
		return;
	}

	$schema = [
		'@context' => 'https://schema.org',
		'@graph'   => $elements,
	];

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'ttp_seo_site_navigation_schema', 20 );

/**
 * Canonical on enrol page.
 *
 * @return void
 */
function ttp_seo_enrol_canonical() {
	if ( ! is_page() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	$slugs = apply_filters( 'ttp_exam_landing_page_slugs', [ 'exam', 'enrol-now', 'enroll-now' ] );
	if ( ! in_array( $post->post_name, (array) $slugs, true ) ) {
		return;
	}
	$url = ttp_seo_primary_enrol_url();
	if ( $url !== '' ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'ttp_seo_enrol_canonical', 5 );
