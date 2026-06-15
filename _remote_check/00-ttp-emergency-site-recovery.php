<?php
/**
 * Plugin Name: 00 TTP Emergency Site Recovery
 * Description: Disables duplicate nav mu-plugin hooks only. Does not block the student profile on /login/.
 * Version: 1.0.5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TTP_DISABLE_NAV_LOGIN_MU' ) ) {
	define( 'TTP_DISABLE_NAV_LOGIN_MU', true );
}
