<?php
/**
 * Plugin Name: TTP UR Auto Approve (loader)
 * Description: Loads the real implementation from ttp-ur-email-confirm-auto-approve.php so load order does not matter. You may delete this file if you only keep the other MU file.
 * Version: 1.4.0
 * Author: The Top Percentile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ttp_ur_mu_primary = __DIR__ . '/ttp-ur-email-confirm-auto-approve.php';
if ( is_readable( $ttp_ur_mu_primary ) ) {
	require_once $ttp_ur_mu_primary;
}
