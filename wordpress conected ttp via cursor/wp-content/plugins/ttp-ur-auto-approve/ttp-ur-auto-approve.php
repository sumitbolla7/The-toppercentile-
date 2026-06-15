<?php
/**
 * Plugin Name: TTP UR Auto Approve
 * Plugin URI: https://thetoppercentile.co.in
 * Description: Forces User Registration “admin approval” users to approved on signup and before login; adds a menu under User Registration with status and bulk approve.
 * Version: 1.5.5
 * Author: The Top Percentile
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'TTP_UR_AUTO_APPROVE_LOADED' ) && TTP_UR_AUTO_APPROVE_LOADED ) {
	return;
}
define( 'TTP_UR_AUTO_APPROVE_LOADED', true );
define( 'TTP_UR_AUTO_APPROVE_VERSION', '1.5.5' );

if ( ! defined( 'TTP_UR_EMAIL_CONFIRM_AUTO_APPROVE_LOADED' ) ) {
	define( 'TTP_UR_EMAIL_CONFIRM_AUTO_APPROVE_LOADED', true );
}

/**
 * @return string
 */
function ttp_ur_auto_approve_ur_capability() {
	return current_user_can( 'manage_user_registration' ) ? 'manage_user_registration' : 'manage_options';
}

/**
 * Login option stored on the UR form assigned to this user (authoritative when user meta is empty).
 *
 * @param int $user_id User ID.
 * @return string
 */
function ttp_ur_assigned_form_login_option( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! function_exists( 'ur_get_single_post_meta' ) ) {
		return '';
	}
	$form_id = (int) get_user_meta( $user_id, 'ur_form_id', true );
	if ( $form_id < 1 && function_exists( 'ur_get_form_id_by_userid' ) ) {
		$form_id = (int) ur_get_form_id_by_userid( $user_id );
	}
	if ( $form_id < 1 ) {
		return '';
	}

	return (string) ur_get_single_post_meta(
		$form_id,
		'user_registration_form_setting_login_options',
		get_option( 'user_registration_general_setting_login_options', 'default' )
	);
}

/**
 * Resolve form login option for a UR user.
 *
 * @param int $user_id User ID.
 * @return string
 */
function ttp_ur_resolve_login_option( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! function_exists( 'ur_get_user_login_option' ) ) {
		return '';
	}
	$login_option = ur_get_user_login_option( $user_id );
	if ( '' !== (string) $login_option ) {
		return (string) $login_option;
	}

	return ttp_ur_assigned_form_login_option( $user_id );
}

/**
 * Approve via User Registration API when available.
 *
 * @param int  $user_id    User ID.
 * @param bool $alert_user Whether UR may email the user.
 * @return void
 */
function ttp_ur_call_user_registration_approve( $user_id, $alert_user = false ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! class_exists( 'UR_Admin_User_Manager', false ) ) {
		return;
	}
	try {
		$mgr = new UR_Admin_User_Manager( $user_id );
		$mgr->save_status( UR_Admin_User_Manager::APPROVED, $alert_user );
	} catch ( \Throwable $e ) {
		unset( $e );
		update_user_meta( $user_id, 'ur_user_status', 1 );
	}
}

/**
 * @param int $user_id User ID.
 */
function ttp_ur_max_unlock_ur_user_for_login( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	$form_uid = (int) get_user_meta( $user_id, 'ur_form_id', true );
	if ( $form_uid < 1 && function_exists( 'ur_get_form_id_by_userid' ) ) {
		$form_uid = (int) ur_get_form_id_by_userid( $user_id );
	}
	if ( $form_uid < 1 ) {
		return;
	}

	if ( ! function_exists( 'ur_string_to_bool' ) || ! function_exists( 'ur_get_user_login_option' ) ) {
		return;
	}

	if ( 'denied' === get_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', true ) ) {
		return;
	}

	$login_option         = ttp_ur_resolve_login_option( $user_id );
	$form_login_option    = ttp_ur_assigned_form_login_option( $user_id );
	$status_login_option  = $login_option;
	$user_status          = null;

	if ( class_exists( 'UR_Admin_User_Manager', false ) ) {
		try {
			$mgr          = new UR_Admin_User_Manager( $user_id );
			$status_array = $mgr->get_user_status();
			if ( ! empty( $status_array['login_option'] ) ) {
				$status_login_option = (string) $status_array['login_option'];
			}
			if ( isset( $status_array['user_status'] ) ) {
				$user_status = (int) $status_array['user_status'];
			}
		} catch ( \Throwable $e ) {
			unset( $e );
		}
	}

	$raw_meta_status = get_user_meta( $user_id, 'ur_user_status', true );
	if ( null === $user_status ) {
		$user_status = (int) $raw_meta_status;
	}

	$admin_modes      = array( 'admin_approval', 'admin_approval_after_email_confirmation' );
	$uses_admin       = in_array( $login_option, $admin_modes, true ) || in_array( $status_login_option, $admin_modes, true ) || in_array( $form_login_option, $admin_modes, true );
	$is_admin_after   = in_array( 'admin_approval_after_email_confirmation', array( $login_option, $status_login_option, $form_login_option ), true );
	$login_option_gate = $login_option;
	if ( '' === $login_option_gate && '' !== $form_login_option ) {
		$login_option_gate = $form_login_option;
	}

	if ( $uses_admin ) {
		if ( class_exists( 'UR_Admin_User_Manager', false ) ) {
			try {
				$mgr    = new UR_Admin_User_Manager( $user_id );
				$status = $mgr->get_user_status();
				if ( isset( $status['user_status'] ) && UR_Admin_User_Manager::DENIED === (int) $status['user_status'] ) {
					return;
				}
				if ( isset( $status['user_status'] ) && UR_Admin_User_Manager::APPROVED === (int) $status['user_status'] ) {
					// UR "admin after email" derives login state from ur_confirm_email + ur_admin_approval_after_email_confirmation; keep both in sync so check_status_on_login does not fall back to "verify email".
					if ( $is_admin_after ) {
						update_user_meta( $user_id, 'ur_confirm_email', 1 );
						update_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', 'true' );
						update_user_meta( $user_id, 'ur_user_status', UR_Admin_User_Manager::APPROVED );
					}
					update_user_meta( $user_id, 'tpsp_email_verified', 1 );
					delete_user_meta( $user_id, 'tpsp_email_verification_token' );
					delete_user_meta( $user_id, 'tpsp_email_verification_expiry' );
					return;
				}
			} catch ( \Throwable $e ) {
				unset( $e );
			}
		} elseif ( -1 === (int) $user_status ) {
			return;
		}

		if ( ! class_exists( 'UR_Admin_User_Manager', false ) || UR_Admin_User_Manager::APPROVED !== (int) $user_status ) {
			ttp_ur_call_user_registration_approve( $user_id, false );
		}

		// save_status() only updates these when ur_get_user_login_option() matches; ensure meta triplet UR uses in get_user_status() is coherent.
		if ( $is_admin_after ) {
			update_user_meta( $user_id, 'ur_confirm_email', 1 );
			update_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', 'true' );
			update_user_meta( $user_id, 'ur_user_status', UR_Admin_User_Manager::APPROVED );
		}

		update_user_meta( $user_id, 'tpsp_email_verified', 1 );
		delete_user_meta( $user_id, 'tpsp_email_verification_token' );
		delete_user_meta( $user_id, 'tpsp_email_verification_expiry' );

		return;
	}

	if ( ! ur_string_to_bool( get_user_meta( $user_id, 'ur_confirm_email', true ) ) ) {
		return;
	}

	if ( 'email_confirmation' === $login_option_gate ) {
		update_user_meta( $user_id, 'ur_user_status', 1 );
	}

	update_user_meta( $user_id, 'tpsp_email_verified', 1 );
	delete_user_meta( $user_id, 'tpsp_email_verification_token' );
	delete_user_meta( $user_id, 'tpsp_email_verification_expiry' );
}

/**
 * @param mixed $form_data Form payload (unused).
 * @param int   $form_id   Form ID.
 * @param int   $user_id   User ID.
 */
function ttp_ur_auto_approve_new_signup_admin_only( $form_data, $form_id, $user_id ) {
	unset( $form_data, $form_id );
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}
	$fid = (int) get_user_meta( $user_id, 'ur_form_id', true );
	if ( $fid < 1 && function_exists( 'ur_get_form_id_by_userid' ) ) {
		$fid = (int) ur_get_form_id_by_userid( $user_id );
	}
	if ( $fid < 1 ) {
		return;
	}
	$login_opt = ttp_ur_resolve_login_option( $user_id );
	$form_opt  = ttp_ur_assigned_form_login_option( $user_id );
	$admin_ok  = in_array( $login_opt, array( 'admin_approval', 'admin_approval_after_email_confirmation' ), true )
		|| in_array( $form_opt, array( 'admin_approval', 'admin_approval_after_email_confirmation' ), true );
	if ( ! $admin_ok ) {
		return;
	}
	ttp_ur_call_user_registration_approve( $user_id, false );
	if ( 'admin_approval_after_email_confirmation' === $login_opt || 'admin_approval_after_email_confirmation' === $form_opt ) {
		update_user_meta( $user_id, 'ur_confirm_email', 1 );
		update_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', 'true' );
	}
}
add_action( 'user_registration_after_register_user_action', 'ttp_ur_auto_approve_new_signup_admin_only', 20, 3 );

/**
 * @param WP_User|WP_Error|null $user     User.
 * @param string                $username Username.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function ttp_ur_authenticate_sync_tpsp_before_mo( $user, $username, $password ) {
	unset( $username, $password );
	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	ttp_ur_max_unlock_ur_user_for_login( $user->ID );

	return $user;
}
// After core username/password authenticate (default priority 20) so $user is a WP_User; before TPSP email gate at 99.
add_filter( 'authenticate', 'ttp_ur_authenticate_sync_tpsp_before_mo', 30, 3 );

/**
 * @param int  $user_id             User ID.
 * @param bool $user_reg_successful Success.
 */
function ttp_ur_get_public_login_page_url() {
	if ( function_exists( 'tpsp_get_login_page_url' ) ) {
		return tpsp_get_login_page_url();
	}

	$login_page = get_page_by_path( 'login' );
	if ( $login_page instanceof WP_Post ) {
		$url = get_permalink( $login_page );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return home_url( '/login/' );
}

/**
 * Never cache UR verification URLs (LiteSpeed can strip ?ur_token= processing).
 *
 * @return void
 */
function ttp_ur_disable_cache_for_verification_requests() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$has_verify_query = ! empty( $_GET['ur_token'] ) || ! empty( $_GET['ur_resend_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $has_verify_query ) {
		return;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	if ( function_exists( 'nocache_headers' ) ) {
		nocache_headers();
	}

	if ( has_action( 'litespeed_control_set_nocache' ) ) {
		do_action( 'litespeed_control_set_nocache', 'ttp-ur-email-verification' );
	}
}
add_action( 'template_redirect', 'ttp_ur_disable_cache_for_verification_requests', 0 );

/**
 * Point verification emails at /login/ instead of WooCommerce /my-account/.
 *
 * @param string $content    Email HTML.
 * @param array  $values     Smart-tag values.
 * @param array  $name_value Extra fields.
 * @return string
 */
function ttp_ur_fix_verification_email_urls( $content, $values = array(), $name_value = array() ) {
	unset( $values, $name_value );

	if ( ! is_string( $content ) || false === strpos( $content, 'ur_token' ) ) {
		return $content;
	}

	$login_url = trailingslashit( ttp_ur_get_public_login_page_url() );
	$home      = trailingslashit( home_url() );

	$content = preg_replace(
		'#https?://[^"\'\s>]+/my-account/\?ur_token=#i',
		$login_url . '?ur_token=',
		$content
	);

	$content = preg_replace(
		'#https?://[^"\'\s>]+/my-account\?ur_token=#i',
		rtrim( $login_url, '/' ) . '?ur_token=',
		$content
	);

	$content = str_replace( $home . 'my-account/?ur_token=', $login_url . '?ur_token=', $content );
	$content = str_replace( '{{home_url}}/' . 'my-account/?ur_token=', $login_url . '?ur_token=', $content );

	return $content;
}
add_filter( 'user_registration_process_smart_tags', 'ttp_ur_fix_verification_email_urls', 50, 3 );

/**
 * @param int  $user_id             User ID.
 * @param bool $user_reg_successful Success.
 */
function ttp_ur_email_confirm_auto_approve_handler( $user_id, $user_reg_successful ) {
	if ( ! $user_reg_successful ) {
		return;
	}

	$user_id = (int) $user_id;
	ttp_ur_max_unlock_ur_user_for_login( $user_id );

	$user = get_user_by( 'id', $user_id );
	if ( ! $user instanceof WP_User ) {
		return;
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id );

	if ( is_admin() ) {
		return;
	}

	$login_url = add_query_arg( 'ttp_email_verified', '1', ttp_ur_get_public_login_page_url() );
	wp_safe_redirect( $login_url );
	exit;
}
add_action( 'user_registration_check_token_complete', 'ttp_ur_email_confirm_auto_approve_handler', 999, 2 );

/**
 * Sync UR/TPSP meta immediately before UR blocks login for "email not verified".
 *
 * @param mixed   $email_status Email status from UR.
 * @param WP_User $user         User.
 * @return void
 */
function ttp_ur_before_check_email_status_on_login( $email_status, $user ) {
	unset( $email_status );
	if ( $user instanceof WP_User ) {
		ttp_ur_max_unlock_ur_user_for_login( (int) $user->ID );
	}
}
add_action( 'ur_user_before_check_email_status_on_login', 'ttp_ur_before_check_email_status_on_login', 1, 2 );

/**
 * @param mixed   $user_status User status from UR.
 * @param WP_User $user        User.
 * @return void
 */
function ttp_ur_before_check_status_on_login( $user_status, $user ) {
	unset( $user_status );
	if ( $user instanceof WP_User ) {
		ttp_ur_max_unlock_ur_user_for_login( (int) $user->ID );
	}
}
add_action( 'ur_user_before_check_status_on_login', 'ttp_ur_before_check_status_on_login', 1, 2 );

/**
 * @param array  $post        POST.
 * @param string $username    Username.
 * @param string $nonce_value Nonce.
 * @param array  $messages    Messages.
 */
function ttp_ur_approve_before_ur_process_login( $post, $username, $nonce_value, $messages ) {
	unset( $post, $nonce_value, $messages );
	$username = trim( (string) $username );
	if ( '' === $username ) {
		return;
	}

	$user = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
	if ( ! $user instanceof WP_User ) {
		return;
	}

	ttp_ur_max_unlock_ur_user_for_login( $user->ID );
}
add_action( 'user_registration_login_process_before_username_validation', 'ttp_ur_approve_before_ur_process_login', 1, 4 );

/**
 * @param WP_User|WP_Error $user     User.
 * @param string           $password Password.
 * @return WP_User|WP_Error
 */
function ttp_ur_approve_before_ur_login_check( $user, $password ) {
	unset( $password );
	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	ttp_ur_max_unlock_ur_user_for_login( $user->ID );

	return $user;
}
add_filter( 'wp_authenticate_user', 'ttp_ur_approve_before_ur_login_check', 0, 2 );

/**
 * @param string $message HTML error message.
 * @return string
 */
function ttp_ur_fix_resend_verification_href( $message ) {
	if ( ! is_string( $message ) || '' === $message ) {
		return $message;
	}
	if ( false === strpos( $message, 'ur_resend_id' ) ) {
		return $message;
	}

	$fixed = preg_replace( '#https?://([a-z0-9][a-z0-9.-]*\.[a-z]{2,})(?=https?://)#i', '', $message );
	if ( ! is_string( $fixed ) ) {
		return $message;
	}

	$fixed = preg_replace( '#([a-z0-9][a-z0-9.-]*\.[a-z]{2,})https//#i', '$1/https://', $fixed );

	return $fixed;
}
add_filter( 'login_errors', 'ttp_ur_fix_resend_verification_href', 999 );

/**
 * Ultimate Member's check_membership() (init priority 10) calls wp_logout() whenever a user's UM
 * "account_status" meta is "rejected" — its Secure module flips users to "rejected"/"inactive" on
 * heuristics that fire for User Registration members too. Sites that use UR (not UM) for auth then
 * auto-logout the user on every page load.
 *
 * Reset UM account_status to "approved" for any logged-in user whose UR status is already approved.
 */
function ttp_ur_neutralize_um_session_logout() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	if ( ! defined( 'um_url' ) && ! class_exists( 'UM', false ) ) {
		return;
	}

	$user_id = (int) get_current_user_id();
	if ( $user_id < 1 ) {
		return;
	}

	$ur_form_id = (int) get_user_meta( $user_id, 'ur_form_id', true );
	if ( $ur_form_id < 1 && function_exists( 'ur_get_form_id_by_userid' ) ) {
		$ur_form_id = (int) ur_get_form_id_by_userid( $user_id );
	}
	if ( $ur_form_id < 1 ) {
		return;
	}

	$status = get_user_meta( $user_id, 'account_status', true );
	if ( '' === $status || 'approved' === $status ) {
		return;
	}

	$ur_status = (int) get_user_meta( $user_id, 'ur_user_status', true );
	if ( class_exists( 'UR_Admin_User_Manager', false ) && UR_Admin_User_Manager::APPROVED === $ur_status ) {
		update_user_meta( $user_id, 'account_status', 'approved' );
		delete_user_meta( $user_id, 'um_user_blocked' );
		delete_user_meta( $user_id, 'um_user_blocked__metadata' );
		delete_user_meta( $user_id, 'um_user_blocked__timestamp' );
	}
}
add_action( 'init', 'ttp_ur_neutralize_um_session_logout', 1 );

/**
 * If UR considers the user approved but UM still logs them out, swap UM's check_membership for ours.
 *
 * @return void
 */
function ttp_ur_disable_um_membership_logout() {
	if ( ! class_exists( 'UM', false ) ) {
		return;
	}
	$um = function_exists( 'UM' ) ? UM() : null;
	if ( ! $um || ! is_callable( array( $um, 'user' ) ) ) {
		return;
	}
	$user_obj = $um->user();
	if ( ! is_object( $user_obj ) ) {
		return;
	}
	remove_action( 'init', array( $user_obj, 'check_membership' ), 10 );
}
add_action( 'init', 'ttp_ur_disable_um_membership_logout', 0 );

/**
 * Count UR members with pending status (best-effort).
 *
 * @return int
 */
function ttp_ur_auto_approve_count_pending() {
	global $wpdb;
	if ( ! isset( $wpdb->usermeta ) ) {
		return 0;
	}
	$sql = $wpdb->prepare(
		"SELECT COUNT(DISTINCT u.user_id) FROM {$wpdb->usermeta} u
		INNER JOIN {$wpdb->usermeta} s ON s.user_id = u.user_id AND s.meta_key = %s AND s.meta_value = %s
		WHERE u.meta_key = %s AND u.meta_value <> ''",
		'ur_user_status',
		'0',
		'ur_form_id'
	);

	return (int) $wpdb->get_var( $sql );
}

/**
 * Approve all users who look pending and use admin_approval (one-time cleanup).
 *
 * @return int Number approved.
 */
function ttp_ur_auto_approve_all_pending_admin_approval() {
	if ( ! class_exists( 'UR_Admin_User_Manager', false ) ) {
		return 0;
	}
	$q = new WP_User_Query(
		array(
			'number'     => 500,
			'fields'     => 'ID',
			'meta_query' => array(
				array(
					'key'     => 'ur_form_id',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'ur_user_status',
					'value'   => '0',
					'compare' => '=',
				),
			),
		)
	);
	$ids   = $q->get_results();
	$count = 0;
	foreach ( (array) $ids as $uid ) {
		$uid = (int) $uid;
		if ( $uid < 1 ) {
			continue;
		}
		$lo = ttp_ur_resolve_login_option( $uid );
		if ( ! in_array( $lo, array( 'admin_approval', 'admin_approval_after_email_confirmation' ), true ) ) {
			continue;
		}
		ttp_ur_call_user_registration_approve( $uid, false );
		++$count;
	}
	return $count;
}

/**
 * @return void
 */
function ttp_ur_auto_approve_register_ur_submenu() {
	if ( ! function_exists( 'ur_get_user_login_option' ) ) {
		return;
	}
	if ( ! current_user_can( ttp_ur_auto_approve_ur_capability() ) ) {
		return;
	}
	add_submenu_page(
		'user-registration',
		__( 'TTP Auto-approve', 'ttp-ur-auto-approve' ),
		__( 'TTP Auto-approve', 'ttp-ur-auto-approve' ),
		ttp_ur_auto_approve_ur_capability(),
		'ttp-ur-auto-approve',
		'ttp_ur_auto_approve_render_admin_page'
	);
}
add_action( 'admin_menu', 'ttp_ur_auto_approve_register_ur_submenu', 100 );

/**
 * @return void
 */
function ttp_ur_auto_approve_render_admin_page() {
	if ( ! current_user_can( ttp_ur_auto_approve_ur_capability() ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'ttp-ur-auto-approve' ) );
	}

	$done = 0;
	if ( isset( $_POST['ttp_ur_bulk_approve'] ) && check_admin_referer( 'ttp_ur_bulk_approve', 'ttp_ur_bulk_nonce' ) ) {
		$done = ttp_ur_auto_approve_all_pending_admin_approval();
	}

	$pending = ttp_ur_auto_approve_count_pending();

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'TTP UR Auto-approve', 'ttp-ur-auto-approve' ) . ' <span style="font-weight:400;color:#64748b">v' . esc_html( TTP_UR_AUTO_APPROVE_VERSION ) . '</span></h1>';
	echo '<p>' . esc_html__( 'This add-on keeps User Registration members from staying stuck on “Admin approval”. New signups are approved automatically; log-in also approves pending users before UR blocks them.', 'ttp-ur-auto-approve' ) . '</p>';
	echo '<ul style="list-style:disc;margin-left:1.25em">';
	echo '<li>' . esc_html__( 'Form setting “Admin approval” is still what UR uses; this tool overrides the pending block.', 'ttp-ur-auto-approve' ) . '</li>';
	echo '<li>' . esc_html__( 'Denied accounts are never auto-approved.', 'ttp-ur-auto-approve' ) . '</li>';
	echo '</ul>';

	echo '<h2>' . esc_html__( 'Status', 'ttp-ur-auto-approve' ) . '</h2>';
	echo '<p><strong>' . esc_html__( 'Users with UR form + pending-looking status (approx.):', 'ttp-ur-auto-approve' ) . '</strong> ' . (int) $pending . '</p>';

	if ( $done > 0 ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( /* translators: %d: number of users */ __( 'Approved %d user(s) that were pending under admin approval.', 'ttp-ur-auto-approve' ), $done ) ) . '</p></div>';
	}

	echo '<h2>' . esc_html__( 'One-time: approve all pending (admin approval only)', 'ttp-ur-auto-approve' ) . '</h2>';
	echo '<form method="post">';
	wp_nonce_field( 'ttp_ur_bulk_approve', 'ttp_ur_bulk_nonce' );
	submit_button( __( 'Approve up to 500 pending users now', 'ttp-ur-auto-approve' ), 'secondary', 'ttp_ur_bulk_approve', false );
	echo '</form>';
	echo '<p class="description">' . esc_html__( 'Runs only for users with the “admin approval” login option. Safe to click more than once.', 'ttp-ur-auto-approve' ) . '</p>';

	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=user-registration-users' ) ) . '">' . esc_html__( 'Open Members list', 'ttp-ur-auto-approve' ) . '</a></p>';
	echo '</div>';
}
