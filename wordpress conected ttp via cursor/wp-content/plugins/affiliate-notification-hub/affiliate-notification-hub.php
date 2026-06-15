<?php
/**
 * Plugin Name: Affiliate & Notification Hub
 * Description: Unified affiliate referrals, commissions, payouts, and in-app / push notifications for WooCommerce.
 * Version: 1.0.0
 * Author: The Top Percentile
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: anh-hub
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ANH_VERSION', '1.0.8');
define('ANH_FILE', __FILE__);
define('ANH_PATH', plugin_dir_path(__FILE__));
define('ANH_URL', plugin_dir_url(__FILE__));

require_once ANH_PATH . 'includes/class-autoloader.php';
ANH\Autoloader::register();

register_activation_hook(ANH_FILE, ['ANH\\Activator', 'activate']);
register_deactivation_hook(ANH_FILE, ['ANH\\Deactivator', 'deactivate']);

add_action('plugins_loaded', static function () {
    ANH\Plugin::instance()->boot();
}, 5);

add_action('init', static function () {
    $stored = get_option('anh_rewrite_flush_version', '');
    if ($stored !== ANH_VERSION) {
        flush_rewrite_rules(false);
        update_option('anh_rewrite_flush_version', ANH_VERSION);
    }
}, 999);
