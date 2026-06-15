<?php
/**
 * Google Site Kit bridge — Search Console & Analytics summaries inside TTP Analysis.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_SiteKit {

	/**
	 * @return bool
	 */
	public static function is_active() {
		return defined( 'GOOGLESITEKIT_VERSION' ) || class_exists( '\Google\Site_Kit\Plugin' );
	}

	/**
	 * @return bool
	 */
	public static function is_connected() {
		if ( ! self::is_active() ) {
			return false;
		}
		$settings = get_option( 'googlesitekit_search-console_settings', [] );
		if ( is_array( $settings ) && ! empty( $settings['propertyID'] ) ) {
			return true;
		}
		return (bool) get_option( 'googlesitekit_connected_proxy_url', '' );
	}

	/**
	 * @param string $datapoint Datapoint name.
	 * @param array  $params    Query params.
	 * @return array|null
	 */
	public static function fetch_module_data( $datapoint, array $params = [] ) {
		if ( ! self::is_active() || ! current_user_can( 'manage_options' ) ) {
			return null;
		}
		if ( ! did_action( 'rest_api_init' ) || ! class_exists( 'WP_REST_Request' ) ) {
			return null;
		}

		$routes = [
			'/google-site-kit/v1/modules/search-console/data/' . $datapoint,
			'/google-site-kit/v1/modules/search-console/data/' . $datapoint . '/',
		];

		foreach ( $routes as $route ) {
			$request = new WP_REST_Request( 'GET', $route );
			foreach ( $params as $key => $value ) {
				$request->set_param( $key, $value );
			}
			$response = rest_do_request( $request );
			if ( ! $response->is_error() ) {
				$data = $response->get_data();
				return is_array( $data ) ? $data : [ 'raw' => $data ];
			}
		}
		return null;
	}

	/**
	 * Search Console performance summary (like Site Kit / GSC screenshot).
	 *
	 * @param int $days Days.
	 * @return array<string, mixed>
	 */
	public static function search_performance_summary( $days = 28 ) {
		$days      = max( 7, min( 90, (int) $days ) );
		$end       = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
		$start     = gmdate( 'Y-m-d', strtotime( '-' . $days . ' days' ) );
		$empty     = [
			'connected'   => self::is_connected(),
			'active'      => self::is_active(),
			'clicks'      => 0,
			'impressions' => 0,
			'ctr'         => 0,
			'position'    => 0,
			'rows'        => [],
			'queries'     => [],
		];

		if ( ! self::is_connected() ) {
			return $empty;
		}

		$by_date = self::fetch_module_data(
			'searchanalytics',
			[
				'startDate'  => $start,
				'endDate'    => $end,
				'dimensions' => 'date',
				'rowLimit'   => 90,
			]
		);

		$by_query = self::fetch_module_data(
			'searchanalytics',
			[
				'startDate'  => $start,
				'endDate'    => $end,
				'dimensions' => 'query',
				'rowLimit'   => 25,
			]
		);

		$clicks = 0;
		$impr   = 0;
		$rows   = [];

		if ( ! empty( $by_date['rows'] ) && is_array( $by_date['rows'] ) ) {
			foreach ( $by_date['rows'] as $row ) {
				$c = (int) ( $row['clicks'] ?? 0 );
				$i = (int) ( $row['impressions'] ?? 0 );
				$clicks += $c;
				$impr   += $i;
				$rows[] = [
					'date'        => $row['keys'][0] ?? '',
					'clicks'      => $c,
					'impressions' => $i,
					'ctr'         => isset( $row['ctr'] ) ? (float) $row['ctr'] : 0,
					'position'    => isset( $row['position'] ) ? (float) $row['position'] : 0,
				];
			}
		}

		$queries = [];
		if ( ! empty( $by_query['rows'] ) && is_array( $by_query['rows'] ) ) {
			foreach ( $by_query['rows'] as $row ) {
				$queries[] = [
					'query'       => $row['keys'][0] ?? '',
					'clicks'      => (int) ( $row['clicks'] ?? 0 ),
					'impressions' => (int) ( $row['impressions'] ?? 0 ),
					'ctr'         => isset( $row['ctr'] ) ? round( (float) $row['ctr'] * 100, 1 ) : 0,
					'position'    => isset( $row['position'] ) ? round( (float) $row['position'], 1 ) : 0,
				];
			}
		}

		$ctr = $impr > 0 ? round( ( $clicks / $impr ) * 100, 1 ) : 0;

		return [
			'connected'   => true,
			'active'      => true,
			'clicks'      => $clicks,
			'impressions' => $impr,
			'ctr'         => $ctr,
			'position'    => self::average_position( $queries ),
			'rows'        => $rows,
			'queries'     => $queries,
			'start'       => $start,
			'end'         => $end,
		];
	}

	/**
	 * @param array<int, array<string, mixed>> $queries Queries.
	 * @return float
	 */
	private static function average_position( array $queries ) {
		if ( empty( $queries ) ) {
			return 0;
		}
		$sum = 0;
		$n   = 0;
		foreach ( $queries as $q ) {
			if ( ! empty( $q['position'] ) ) {
				$sum += (float) $q['position'];
				++$n;
			}
		}
		return $n > 0 ? round( $sum / $n, 1 ) : 0;
	}

	/**
	 * @return string
	 */
	public static function setup_url() {
		return admin_url( 'admin.php?page=googlesitekit-dashboard' );
	}
}
