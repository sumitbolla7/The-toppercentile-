<?php
/**
 * Plugin Name: TTP Nav Login / My Account (disabled)
 * Description: Disabled — "My Account" nav is handled by Top Percentile Student Portal plugin (class-tpsp-assets.php). Keeping this file prevents an old copy from loading duplicate hooks.
 * Version: 1.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Always off when emergency recovery or explicit disable is set.
if ( ( defined( 'TTP_DISABLE_NAV_LOGIN_MU' ) && TTP_DISABLE_NAV_LOGIN_MU ) ) {
	return;
}
