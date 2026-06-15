<?php
/**
 * Content analysis per page/post.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_Analyzer {

	/**
	 * Analyze a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function analyze_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}

		$content = (string) $post->post_content;
		$text    = wp_strip_all_tags( $content );
		$words   = str_word_count( $text );

		$h1 = preg_match_all( '/<h1[^>]*>/i', $content, $m1 ) ? count( $m1[0] ) : 0;
		$h2 = preg_match_all( '/<h2[^>]*>/i', $content, $m2 ) ? count( $m2[0] ) : 0;
		$img = preg_match_all( '/<img[^>]+>/i', $content, $mi ) ? count( $mi[0] ) : 0;
		$links = preg_match_all( '/<a\s[^>]+href=/i', $content, $ml ) ? count( $ml[0] ) : 0;

		$seo_title = '';
		$seo_desc  = '';
		if ( defined( 'WPSEO_VERSION' ) ) {
			$seo_title = (string) get_post_meta( $post_id, '_yoast_wpseo_title', true );
			$seo_desc  = (string) get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		}

		$issues = [];
		if ( $words < 300 && $post->post_type === 'post' ) {
			$issues[] = __( 'Short content (under 300 words)', 'ttp-pca' );
		}
		if ( $h1 > 1 ) {
			$issues[] = __( 'Multiple H1 tags', 'ttp-pca' );
		}
		if ( $h1 === 0 && $post->post_type === 'page' ) {
			$issues[] = __( 'No H1 in content', 'ttp-pca' );
		}
		if ( $seo_desc === '' ) {
			$issues[] = __( 'Missing meta description', 'ttp-pca' );
		}

		$score = 100;
		$score -= min( 40, count( $issues ) * 12 );

		return [
			'post_id'    => $post_id,
			'title'      => get_the_title( $post ),
			'url'        => get_permalink( $post ),
			'post_type'  => $post->post_type,
			'word_count' => $words,
			'h1'         => $h1,
			'h2'         => $h2,
			'images'     => $img,
			'links'      => $links,
			'seo_title'  => $seo_title,
			'seo_desc'   => $seo_desc,
			'issues'     => $issues,
			'score'      => max( 0, $score ),
		];
	}

	/**
	 * Batch audit published content.
	 *
	 * @param string $post_type Post type.
	 * @param int    $limit     Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function audit_all( $post_type = 'page', $limit = 100 ) {
		$posts = get_posts(
			[
				'post_type'      => sanitize_key( $post_type ),
				'post_status'    => 'publish',
				'posts_per_page' => min( 200, max( 1, (int) $limit ) ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);
		$out = [];
		foreach ( $posts as $p ) {
			$out[] = self::analyze_post( (int) $p->ID );
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return ( $a['score'] ?? 0 ) <=> ( $b['score'] ?? 0 );
			}
		);
		return $out;
	}
}
