<?php
/**
 * Plugin Name: Top Percentile Student Portal
 * Description: Premium student portal for WooCommerce education websites with dashboard, email verification, and cart/checkout stability improvements.
 * Version: 1.1.2
 * Author: Top Percentile
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: tpsp
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TPSP_VERSION', '1.1.2');
define('TPSP_PLUGIN_FILE', __FILE__);
define('TPSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TPSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TPSP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Public branded login page URL (page slug `login`), not wp-login.php.
 *
 * @return string
 */
function tpsp_get_login_page_url() {
    $login_page = get_page_by_path('login');
    if ($login_page instanceof WP_Post) {
        $url = get_permalink($login_page);
        if (!empty($url)) {
            return $url;
        }
    }

    return home_url('/login/');
}

/**
 * Login page URL with redirect_to after successful authentication.
 *
 * @param string $redirect_target Absolute URL (e.g. checkout with add-to-cart).
 * @return string
 */
function tpsp_get_login_redirect_url($redirect_target) {
    $base = tpsp_get_login_page_url();
    if (empty($redirect_target)) {
        return $base;
    }

    return add_query_arg('redirect_to', $redirect_target, $base);
}

require_once TPSP_PLUGIN_DIR . 'includes/class-tpsp-activator.php';
require_once TPSP_PLUGIN_DIR . 'includes/class-tpsp-deactivator.php';
require_once TPSP_PLUGIN_DIR . 'includes/class-tpsp.php';

register_activation_hook(__FILE__, ['TPSP_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['TPSP_Deactivator', 'deactivate']);

/**
 * Boot plugin.
 *
 * @return TPSP
 */
function tpsp_bootstrap() {
    static $plugin = null;

    if (null === $plugin) {
        $plugin = new TPSP();
        $plugin->run();
    }

    return $plugin;
}

tpsp_bootstrap();
