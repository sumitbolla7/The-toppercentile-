<?php
/**
 * Legacy stub (v1 integrations removed). Kept so older uploads do not fatal-error.
 *
 * @deprecated 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_PCA_Whitelist {

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function integration_status() {
		return [];
	}

	/**
	 * @return array<string, string>
	 */
	public static function advisory_notes() {
		return [];
	}
}
