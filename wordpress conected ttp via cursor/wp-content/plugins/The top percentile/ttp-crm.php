<?php
/**
 * Plugin Name: TTP CRM
 * Description: Lightweight CRM plugin for managing contacts inside WordPress.
 * Version: 1.0.2
 * Author: The Top Percentile
 * Text Domain: ttp-crm
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('TTP_CRM_FILE')) {
    define('TTP_CRM_FILE', __FILE__);
}

if (!defined('TTP_CRM_PATH')) {
    define('TTP_CRM_PATH', plugin_dir_path(__FILE__));
}

if (!defined('TTP_CRM_URL')) {
    define('TTP_CRM_URL', plugin_dir_url(__FILE__));
}

if (!defined('TTP_CRM_VERSION')) {
    define('TTP_CRM_VERSION', '1.0.3');
}

spl_autoload_register(
    static function ($class_name) {
        $prefix = 'TTP_CRM\\';

        if (strpos($class_name, $prefix) !== 0) {
            return;
        }

        $relative_class = substr($class_name, strlen($prefix));
        $relative_path  = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);
        $file_path      = TTP_CRM_PATH . 'includes/' . $relative_path . '.php';

        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
);

register_activation_hook(TTP_CRM_FILE, array('TTP_CRM\\Core\\Activator', 'activate'));
register_deactivation_hook(TTP_CRM_FILE, array('TTP_CRM\\Core\\Deactivator', 'deactivate'));

add_action(
    'plugins_loaded',
    static function () {
        $plugin = new TTP_CRM\Core\Plugin();
        $plugin->boot();
    }
);