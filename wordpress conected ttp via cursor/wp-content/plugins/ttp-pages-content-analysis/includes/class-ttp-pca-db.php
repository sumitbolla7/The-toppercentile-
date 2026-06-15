<?php
/**
 * Database: page view events.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_DB {

	const TABLE_VIEWS = 'ttp_pca_page_views';

	/**
	 * @return string
	 */
	public static function table_views() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_VIEWS;
	}

	/**
	 * @return void
	 */
	public static function maybe_create_tables() {
		global $wpdb;
		$table   = self::table_views();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			post_type varchar(32) NOT NULL DEFAULT '',
			page_url varchar(512) NOT NULL DEFAULT '',
			page_title varchar(255) NOT NULL DEFAULT '',
			referrer varchar(512) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			session_hash char(32) NOT NULL DEFAULT '',
			device_type varchar(16) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY created_at (created_at),
			KEY session_hash (session_hash)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return bool
	 */
	public static function insert_view( array $row ) {
		global $wpdb;
		$ok = $wpdb->insert(
			self::table_views(),
			[
				'post_id'      => (int) ( $row['post_id'] ?? 0 ),
				'post_type'    => sanitize_key( (string) ( $row['post_type'] ?? '' ) ),
				'page_url'     => esc_url_raw( (string) ( $row['page_url'] ?? '' ) ),
				'page_title'   => sanitize_text_field( (string) ( $row['page_title'] ?? '' ) ),
				'referrer'     => esc_url_raw( (string) ( $row['referrer'] ?? '' ) ),
				'user_id'      => (int) ( $row['user_id'] ?? 0 ),
				'session_hash' => sanitize_text_field( (string) ( $row['session_hash'] ?? '' ) ),
				'device_type'  => sanitize_key( (string) ( $row['device_type'] ?? '' ) ),
				'created_at'   => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);
		return false !== $ok;
	}
}
