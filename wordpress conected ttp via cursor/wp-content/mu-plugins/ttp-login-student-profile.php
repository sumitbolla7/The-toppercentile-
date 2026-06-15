<?php
/**
 * Plugin Name: TTP Login Student Profile (URL helper only)
 * Description: Provides ttp_student_profile_url() only. No hooks (login panel swap caused site crashes).
 * Version: 1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ttp_student_profile_url' ) ) {
	/**
	 * @return string
	 */
	function ttp_student_profile_url() {
		if ( function_exists( 'tpsp_get_login_page_url' ) ) {
			return tpsp_get_login_page_url();
		}

		return home_url( '/login/' );
	}
}
