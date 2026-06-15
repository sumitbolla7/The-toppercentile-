<?php
/**
 * Page open reports & aggregates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_Reports {

	/**
	 * @param int    $days   Lookback days.
	 * @param string $type   Post type filter or empty.
	 * @return array<string, mixed>
	 */
	public static function summary( $days = 30, $type = '' ) {
		global $wpdb;
		$table = TTP_PCA_DB::table_views();
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );
		$where = 'created_at >= %s';
		$args  = [ $since ];

		if ( $type !== '' ) {
			$where .= ' AND post_type = %s';
			$args[] = sanitize_key( $type );
		}

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$where}",
				$args
			)
		);

		$unique = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_hash) FROM {$table} WHERE {$where}",
				$args
			)
		);

		$pages = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT page_url) FROM {$table} WHERE {$where}",
				$args
			)
		);

		return [
			'total_views'   => $total,
			'unique_visits' => $unique,
			'pages_opened'  => $pages,
			'days'          => absint( $days ),
		];
	}

	/**
	 * Top pages by views.
	 *
	 * @param int    $days  Days.
	 * @param int    $limit Limit.
	 * @param string $type  Post type.
	 * @return array<int, object>
	 */
	public static function top_pages( $days = 30, $limit = 50, $type = '' ) {
		global $wpdb;
		$table = TTP_PCA_DB::table_views();
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days' ) );
		$limit = max( 1, min( 200, (int) $limit ) );

		$sql  = "SELECT post_id, post_type, page_url, page_title,
			COUNT(*) AS views,
			COUNT(DISTINCT session_hash) AS unique_sessions,
			MAX(created_at) AS last_opened
			FROM {$table} WHERE created_at >= %s";
		$args = [ $since ];

		if ( $type !== '' ) {
			$sql   .= ' AND post_type = %s';
			$args[] = sanitize_key( $type );
		}

		$sql .= " GROUP BY post_id, page_url, page_title, post_type
			ORDER BY views DESC LIMIT %d";
		$args[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/**
	 * Recent page opens (live feed).
	 *
	 * @param int $limit Limit.
	 * @return array<int, object>
	 */
	public static function recent_opens( $limit = 40 ) {
		global $wpdb;
		$table = TTP_PCA_DB::table_views();
		$limit = max( 1, min( 100, (int) $limit ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d",
				$limit
			)
		);
	}

	/**
	 * Views per day chart data.
	 *
	 * @param int $days Days.
	 * @return array<string, int>
	 */
	public static function views_by_day( $days = 14 ) {
		global $wpdb;
		$table = TTP_PCA_DB::table_views();
		$since = gmdate( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d, COUNT(*) AS c FROM {$table}
				WHERE created_at >= %s GROUP BY DATE(created_at) ORDER BY d ASC",
				$since . ' 00:00:00'
			)
		);

		$out = [];
		foreach ( $rows as $row ) {
			$out[ (string) $row->d ] = (int) $row->c;
		}
		return $out;
	}

	/**
	 * Export CSV string for top pages.
	 *
	 * @param int $days Days.
	 * @return string
	 */
	public static function export_csv( $days = 30 ) {
		$rows = self::top_pages( $days, 500, '' );
		$buf  = "post_id,post_type,page_title,page_url,views,unique_sessions,last_opened\n";
		foreach ( $rows as $r ) {
			$buf .= sprintf(
				"%d,%s,\"%s\",\"%s\",%d,%d,%s\n",
				(int) $r->post_id,
				$r->post_type,
				str_replace( '"', '""', (string) $r->page_title ),
				$r->page_url,
				(int) $r->views,
				(int) $r->unique_sessions,
				$r->last_opened
			);
		}
		return $buf;
	}
}
