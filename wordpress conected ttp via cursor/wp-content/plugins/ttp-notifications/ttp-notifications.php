<?php
/**
 * Plugin Name: TTP Notifications
 * Description: Browser push, in-app dashboard alerts, and admin activity notifications for The Top Percentile.
 * Version: 1.0.2
 * Author: The Top Percentile
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: ttp-notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TTPN_VERSION', '1.0.3');
define('TTPN_FILE', __FILE__);
define('TTPN_PATH', plugin_dir_path(__FILE__));
define('TTPN_URL', plugin_dir_url(__FILE__));

require_once TTPN_PATH . 'includes/class-ttpn-activator.php';
require_once TTPN_PATH . 'includes/class-ttpn-deactivator.php';
require_once TTPN_PATH . 'includes/class-ttpn-plugin.php';

register_activation_hook(TTPN_FILE, ['TTPN_Activator', 'activate']);
register_deactivation_hook(TTPN_FILE, ['TTPN_Deactivator', 'deactivate']);

add_action('plugins_loaded', static function () {
    TTPN_Plugin::instance()->boot();
});

add_action('init', static function () {
    $stored = get_option('ttpn_rewrite_flush_version', '');
    if ($stored !== TTPN_VERSION) {
        flush_rewrite_rules(false);
        update_option('ttpn_rewrite_flush_version', TTPN_VERSION);
    }
}, 999);

/**
 * Send a notification to a user (in-app + optional push).
 *
 * @param int    $user_id User ID.
 * @param string $title   Title.
 * @param string $message Message body.
 * @param array  $args    Optional: type, link, meta, push (bool), admin_alert (bool).
 * @return int|false Notification ID.
 */
function ttpn_notify($user_id, $title, $message, $args = []) {
    return TTPN_Plugin::instance()->notifications()->create((int) $user_id, $title, $message, $args);
}

/**
 * Create an admin activity alert visible in wp-admin.
 *
 * @param string $title   Alert title.
 * @param string $message Alert message.
 * @param array  $args    Optional: alert_type, actor_user_id, object_type, object_id, link.
 * @return int|false
 */
function ttpn_admin_alert($title, $message, $args = []) {
    return TTPN_Plugin::instance()->admin_alerts()->create($title, $message, $args);
}
