<?php
/**
 * Plugin Name: TTP UR email confirm auto-approve
 * Description: Unblocks User Registration + TPSP login after UR email is confirmed; fixes bad resend URLs; recovers from TPSP false blocks.
 * Version: 1.4.0
 * Author: The Top Percentile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'ttp_ur_max_unlock_ur_user_for_login' ) ) {
	return;
}

if ( ! defined( 'TTP_UR_EMAIL_CONFIRM_AUTO_APPROVE_LOADED' ) ) {
	define( 'TTP_UR_EMAIL_CONFIRM_AUTO_APPROVE_LOADED', true );
}

/**
 * @param int $user_id User ID.
 */
function ttp_ur_unlock_tpsp_if_ur_email_confirmed( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 || ! function_exists( 'ur_string_to_bool' ) ) {
		return;
	}
	if ( ! ur_string_to_bool( get_user_meta( $user_id, 'ur_confirm_email', true ) ) ) {
		return;
	}
	update_user_meta( $user_id, 'tpsp_email_verified', 1 );
	delete_user_meta( $user_id, 'tpsp_email_verification_token' );
	delete_user_meta( $user_id, 'tpsp_email_verification_expiry' );
}

/**
 * UR approval meta + TPSP. TPSP unlock runs whenever UR email flag is set (even if ur_form_id is missing).
 *
 * @param int $user_id User ID.
 */
function ttp_ur_max_unlock_ur_user_for_login( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return;
	}

	if ( 'denied' === get_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', true ) ) {
		return;
	}

	ttp_ur_unlock_tpsp_if_ur_email_confirmed( $user_id );

	if ( ! get_user_meta( $user_id, 'ur_form_id', true ) ) {
		return;
	}

	if ( ! function_exists( 'ur_string_to_bool' ) || ! function_exists( 'ur_get_user_login_option' ) ) {
		return;
	}

	if ( ! ur_string_to_bool( get_user_meta( $user_id, 'ur_confirm_email', true ) ) ) {
		return;
	}

	$login_option = ur_get_user_login_option( $user_id );
	if ( '' === (string) $login_option && function_exists( 'ur_get_form_id_by_userid' ) && function_exists( 'ur_get_single_post_meta' ) ) {
		$form_id = ur_get_form_id_by_userid( $user_id );
		if ( $form_id ) {
			$login_option = ur_get_single_post_meta(
				$form_id,
				'user_registration_form_setting_login_options',
				get_option( 'user_registration_general_setting_login_options', 'default' )
			);
		}
	}

	if ( 'admin_approval_after_email_confirmation' === $login_option ) {
		update_user_meta( $user_id, 'ur_admin_approval_after_email_confirmation', 'true' );
		update_user_meta( $user_id, 'ur_user_status', 1 );
	} elseif ( 'email_confirmation' === $login_option ) {
		update_user_meta( $user_id, 'ur_user_status', 1 );
	}
}

/**
 * Before TPSP (priority 99): unlock when UR email already confirmed.
 *
 * @param WP_User|WP_Error|null $user     User.
 * @param string                $username Username or email.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function ttp_ur_authenticate_sync_tpsp_before_mo( $user, $username, $password ) {
	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	ttp_ur_max_unlock_ur_user_for_login( $user->ID );

	return $user;
}
add_filter( 'authenticate', 'ttp_ur_authenticate_sync_tpsp_before_mo', 98, 3 );

/**
 * After TPSP blocks (99): if password is correct but only TPSP blocked, allow users who are not UR-pending on email.
 *
 * @param WP_User|WP_Error|null $user     User or error.
 * @param string                $username Username or email.
 * @param string                $password Password.
 * @return WP_User|WP_Error|null
 */
function ttp_ur_authenticate_recover_after_tpsp( $user, $username, $password ) {
	if ( ! $user instanceof WP_Error || 'tpsp_email_unverified' !== $user->get_error_code() ) {
		return $user;
	}

	$username = trim( (string) $username );
	if ( '' === $username || '' === (string) $password ) {
		return $user;
	}

	$u = is_email( $username ) ? get_user_by( 'email', $username ) : get_user_by( 'login', $username );
	if ( ! $u instanceof WP_User ) {
		return $user;
	}

	if ( ! wp_check_password( $password, $u->user_pass, $u->ID ) ) {
		return $user;
	}

	if ( user_can( $u, 'manage_options' ) ) {
		return $u;
	}

	$ur_email_ok = function_exists( 'ur_string_to_bool' ) && ur_string_to_bool( get_user_meta( $u->ID, 'ur_confirm_email', true ) );
	$not_ur_reg  = ! (bool) get_user_meta( $u->ID, 'ur_form_id', true );

	if ( $ur_email_ok || $not_ur_reg ) {
		update_user_meta( $u->ID, 'tpsp_email_verified', 1 );
		delete_user_meta( $u->ID, 'tpsp_email_verification_token' );
		delete_user_meta( $u->ID, 'tpsp_email_verification_expiry' );
		ttp_ur_max_unlock_ur_user_for_login( $u->ID );

		return $u;
	}

	return $user;
}
add_filter( 'authenticate', 'ttp_ur_authenticate_recover_after_tpsp', 100, 3 );

/**
 * @param int  $user_id             User ID.
 * @param bool $user_reg_successful Whether the token matched and was not expired.
 */
function ttp_ur_email_confirm_auto_approve_handler( $user_id, $user_reg_successful ) {
	if ( ! $user_reg_successful ) {
		return;
	}

	ttp_ur_max_unlock_ur_user_for_login( (int) $user_id );

	$user = get_user_by( 'id', $user_id );
	if ( $user instanceof WP_User ) {
		wp_set_current_user( (int) $user_id );
		wp_set_auth_cookie( (int) $user_id );
	}
}
add_action( 'user_registration_check_token_complete', 'ttp_ur_email_confirm_auto_approve_handler', 999, 2 );

/**
 * @param array  $post        POST.
 * @param string $username    Username field.
 * @param string $nonce_value Nonce.
 * @param array  $messages    Messages.
 */
function ttp_ur_approve_before_ur_process_login( $post, $username, $nonce_value, $messages ) {
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
 * @param WP_User|WP_Error $user     User or error.
 * @param string           $password Password.
 * @return WP_User|WP_Error
 */
function ttp_ur_approve_before_ur_login_check( $user, $password ) {
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
