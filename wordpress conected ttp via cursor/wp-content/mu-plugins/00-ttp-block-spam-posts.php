<?php
/**
 * Plugin Name: 00 TTP Block Spam Posts
 * Description: Stops casino/spam blog posts via REST/XML-RPC/wp-admin and quarantines suspicious content.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emergency: block every new blog post from publishing (coaching site does not use posts).
 * Set to false in wp-config.php once the breach is fully cleaned: define( 'TTP_BLOCK_ALL_BLOG_POSTS', false );
 */
if ( ! defined( 'TTP_BLOCK_ALL_BLOG_POSTS' ) ) {
	define( 'TTP_BLOCK_ALL_BLOG_POSTS', true );
}

/**
 * @return string[]
 */
function ttp_spam_post_title_needles() {
	return array(
		'casino',
		'jokersino',
		'izzi online',
		'dragon money',
		'dogg house login',
		'online casino',
		'slot',
		'gambling',
		'bet365',
		'poker online',
		'dddscom',
		'tc-check-http',
		'gaming platform',
		'monopoly live',
		'mellstroy',
		'live dealer',
		'wagering',
		'withdraw safely',
		'online gambling',
		'live casino',
		'казино',
		'онлайн-казино',
	);
}

/**
 * @param string $title Post title.
 * @param string $content Post content.
 * @return bool
 */
function ttp_spam_post_looks_malicious( $title, $content = '' ) {
	$haystack = strtolower( wp_strip_all_tags( (string) $title . ' ' . (string) $content ) );
	foreach ( ttp_spam_post_title_needles() as $needle ) {
		if ( $needle !== '' && str_contains( $haystack, $needle ) ) {
			return true;
		}
	}
	if ( preg_match( '/[\x{0400}-\x{04FF}]/u', $haystack ) && preg_match( '/казино|dragon|money|онлайн/u', $haystack ) ) {
		return true;
	}
	return false;
}

/**
 * @param string $line Log line.
 * @return void
 */
function ttp_spam_post_log( $line ) {
	$file = WP_CONTENT_DIR . '/spam-block.log';
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	@file_put_contents( $file, gmdate( 'c' ) . ' ' . $line . PHP_EOL, FILE_APPEND );
}

/**
 * @param int $post_id Post ID.
 * @return void
 */
function ttp_spam_post_quarantine( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id < 1 ) {
		return;
	}
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	ttp_spam_post_log( 'Quarantined spam post id=' . $post_id );
}

add_filter( 'xmlrpc_enabled', '__return_false' );

add_filter(
	'rest_pre_insert_post',
	static function ( $prepared, $request ) {
		unset( $request );

		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_forbidden', __( 'You cannot create posts via the API.', 'ttp-spam' ), array( 'status' => 403 ) );
		}

		$title   = is_array( $prepared ) ? (string) ( $prepared['post_title'] ?? '' ) : '';
		$content = is_array( $prepared ) ? (string) ( $prepared['post_content'] ?? '' ) : '';

		if ( ttp_spam_post_looks_malicious( $title, $content ) ) {
			ttp_spam_post_log( 'REST spam post blocked uid=' . get_current_user_id() . ' title=' . substr( $title, 0, 80 ) );
			return new WP_Error( 'rest_forbidden', __( 'This post was blocked as spam.', 'ttp-spam' ), array( 'status' => 403 ) );
		}

		if ( TTP_BLOCK_ALL_BLOG_POSTS && is_array( $prepared ) && ( $prepared['post_status'] ?? '' ) === 'publish' ) {
			$prepared['post_status'] = 'draft';
			ttp_spam_post_log( 'REST blog publish forced to draft uid=' . get_current_user_id() );
		}

		return $prepared;
	},
	10,
	2
);

add_filter(
	'wp_insert_post_data',
	static function ( $data, $postarr ) {
		if ( ! is_array( $data ) || ( $data['post_type'] ?? '' ) !== 'post' ) {
			return $data;
		}

		$title   = (string) ( $data['post_title'] ?? '' );
		$content = (string) ( $data['post_content'] ?? '' );

		if ( ttp_spam_post_looks_malicious( $title, $content ) ) {
			$data['post_status'] = 'draft';
			ttp_spam_post_log(
				sprintf(
					'Quarantined spam post draft uid=%d title=%s',
					get_current_user_id(),
					substr( $title, 0, 120 )
				)
			);
			return $data;
		}

		if ( TTP_BLOCK_ALL_BLOG_POSTS && ( $data['post_status'] ?? '' ) === 'publish' ) {
			$data['post_status'] = 'draft';
			ttp_spam_post_log(
				sprintf(
					'Blocked blog publish uid=%d title=%s',
					get_current_user_id(),
					substr( $title, 0, 120 )
				)
			);
		}

		return $data;
	},
	1,
	2
);

add_action(
	'transition_post_status',
	static function ( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
			return;
		}
		if ( ttp_spam_post_looks_malicious( $post->post_title, $post->post_content ) || TTP_BLOCK_ALL_BLOG_POSTS ) {
			remove_action( 'transition_post_status', __FUNCTION__, 10 );
			ttp_spam_post_quarantine( (int) $post->ID );
			add_action( 'transition_post_status', __FUNCTION__, 10, 3 );
		}
	},
	10,
	3
);

add_filter(
	'map_meta_cap',
	static function ( $caps, $cap, $user_id, $args ) {
		if ( 'publish_posts' !== $cap ) {
			return $caps;
		}
		$user = get_userdata( (int) $user_id );
		if ( ! $user instanceof WP_User ) {
			return $caps;
		}
		if ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'editor', (array) $user->roles, true ) ) {
			return $caps;
		}
		return array( 'do_not_allow' );
	},
	10,
	4
);

add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'ttp_spam_post_sweep' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'ttp_spam_post_sweep' );
		}

		if ( get_option( 'ttp_spam_sweep_v11_done' ) ) {
			return;
		}

		do_action( 'ttp_spam_post_sweep' );
		update_option( 'ttp_spam_sweep_v11_done', 1, false );
	},
	20
);

add_action(
	'ttp_spam_post_sweep',
	static function () {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'date_query'     => array(
					array(
						'after' => '30 days ago',
					),
				),
				'fields'         => 'ids',
			)
		);
		foreach ( $posts as $post_id ) {
			$post = get_post( (int) $post_id );
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			if ( ttp_spam_post_looks_malicious( $post->post_title, $post->post_content ) || TTP_BLOCK_ALL_BLOG_POSTS ) {
				ttp_spam_post_quarantine( (int) $post_id );
			}
		}
	}
);
