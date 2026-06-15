<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Activator {

    public static function activate() {
        self::create_tables();
        self::ensure_vapid_keys();
        self::schedule_cron();

        flush_rewrite_rules(false);
    }

    public static function create_tables() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $notifications = $wpdb->prefix . 'ttpn_notifications';
        $subscriptions = $wpdb->prefix . 'ttpn_push_subscriptions';
        $admin_alerts  = $wpdb->prefix . 'ttpn_admin_alerts';

        $sql_notifications = "CREATE TABLE {$notifications} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            type VARCHAR(50) NOT NULL DEFAULT 'general',
            title VARCHAR(255) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            link VARCHAR(500) NOT NULL DEFAULT '',
            meta LONGTEXT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            push_sent TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY type (type),
            KEY created_at (created_at)
        ) {$charset};";

        $sql_subscriptions = "CREATE TABLE {$subscriptions} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            endpoint TEXT NOT NULL,
            p256dh VARCHAR(255) NOT NULL DEFAULT '',
            auth VARCHAR(255) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) {$charset};";

        $sql_admin_alerts = "CREATE TABLE {$admin_alerts} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            alert_type VARCHAR(50) NOT NULL DEFAULT 'general',
            title VARCHAR(255) NOT NULL DEFAULT '',
            message TEXT NOT NULL,
            actor_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            object_type VARCHAR(50) NOT NULL DEFAULT '',
            object_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            link VARCHAR(500) NOT NULL DEFAULT '',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY alert_type (alert_type),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_notifications);
        dbDelta($sql_subscriptions);
        dbDelta($sql_admin_alerts);

        update_option('ttpn_db_version', TTPN_VERSION);
    }

    public static function ensure_vapid_keys() {
        $public  = get_option('ttpn_vapid_public_key', '');
        $private = get_option('ttpn_vapid_private_key', '');

        if ($public && $private) {
            return;
        }

        if (!function_exists('openssl_pkey_new')) {
            return;
        }

        $key = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if (!$key) {
            return;
        }

        $details = openssl_pkey_get_details($key);
        if (empty($details['ec']['x']) || empty($details['ec']['y']) || empty($details['ec']['d'])) {
            return;
        }

        $public_key  = self::base64url_encode("\x04" . $details['ec']['x'] . $details['ec']['y']);
        $private_key = self::base64url_encode($details['ec']['d']);

        update_option('ttpn_vapid_public_key', $public_key, false);
        update_option('ttpn_vapid_private_key', $private_key, false);
    }

    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function schedule_cron() {
        if (!wp_next_scheduled('ttpn_process_push_queue')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'five_minutes', 'ttpn_process_push_queue');
        }
    }
}
