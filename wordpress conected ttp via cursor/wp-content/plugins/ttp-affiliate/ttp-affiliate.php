<?php
/**
 * Plugin Name: TTP Affiliate & Referral
 * Description: Referral links, commission tracking, auto payouts, and leaderboard for The Top Percentile.
 * Version: 1.0.0
 * Author: The Top Percentile
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: ttp-affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TTPA_VERSION', '1.0.8');
define('TTPA_FILE', __FILE__);
define('TTPA_PATH', plugin_dir_path(__FILE__));
define('TTPA_URL', plugin_dir_url(__FILE__));
define('TTPA_COOKIE', 'ttp_ref');
define('TTPA_COOKIE_DAYS', 30);

require_once TTPA_PATH . 'includes/class-ttpa-activator.php';
require_once TTPA_PATH . 'includes/class-ttpa-deactivator.php';
require_once TTPA_PATH . 'includes/class-ttpa-plugin.php';

register_activation_hook(TTPA_FILE, ['TTPA_Activator', 'activate']);
register_deactivation_hook(TTPA_FILE, ['TTPA_Deactivator', 'deactivate']);

add_action('plugins_loaded', static function () {
    TTPA_Plugin::instance()->boot();
});

add_action('init', static function () {
    $stored = get_option('ttpa_rewrite_flush_version', '');
    if ($stored !== TTPA_VERSION) {
        flush_rewrite_rules(false);
        update_option('ttpa_rewrite_flush_version', TTPA_VERSION);
    }
}, 999);

/**
 * Get referral link for a user.
 *
 * @param int $user_id User ID.
 * @return string
 */
function ttpa_get_referral_link($user_id = 0) {
    return TTPA_Plugin::instance()->referrals()->get_referral_link($user_id);
}

/**
 * Get affiliate code for user.
 *
 * @param int $user_id User ID.
 * @return string
 */
function ttpa_get_referral_code($user_id = 0) {
    return TTPA_Plugin::instance()->referrals()->get_or_create_code($user_id);
}

/**
 * Format money for display (WooCommerce-aware).
 *
 * @param float $amount Amount.
 * @return string
 */
function ttpa_format_money($amount) {
    if (function_exists('wc_price')) {
        return wc_price($amount);
    }

    return '₹' . number_format((float) $amount, 2);
}

/**
 * Whether a user is allowed to use the referral program on the frontend.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function ttpa_user_can_refer($user_id = 0) {
    if (!class_exists('TTPA_Plugin')) {
        return false;
    }

    $user_id = $user_id ?: get_current_user_id();

    return TTPA_Plugin::instance()->referrals()->is_affiliate_enabled($user_id);
}
