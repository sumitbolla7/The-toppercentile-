<?php

namespace ANH;

if (!defined('ABSPATH')) {
    exit;
}

class Activator {

    public static function activate() {
        self::create_tables();
        self::backfill_referral_codes();
        flush_rewrite_rules(false);
        update_option('anh_db_version', ANH_VERSION);
    }

    public static function create_tables() {
        global $wpdb;

        $charset   = $wpdb->get_charset_collate();
        $affiliates = $wpdb->prefix . 'affiliates';

        $sql = "CREATE TABLE {$affiliates} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            referral_code VARCHAR(32) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            total_clicks BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            total_referrals BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            total_sales DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_commission DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id),
            UNIQUE KEY referral_code (referral_code),
            KEY status (status)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function backfill_referral_codes() {
        if (!class_exists('TTPA_Plugin')) {
            return;
        }

        global $wpdb;

        $affiliates = $wpdb->prefix . 'affiliates';
        $referrals  = \TTPA_Plugin::instance()->referrals();
        $users      = get_users(['fields' => 'ID']);

        foreach ($users as $user_id) {
            $code = $referrals->get_or_create_code((int) $user_id);
            if (!$code) {
                continue;
            }

            $exists = (int) $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM {$affiliates} WHERE user_id = %d", (int) $user_id)
            );

            $row = [
                'user_id'       => (int) $user_id,
                'referral_code' => $code,
                'status'        => 'active',
                'updated_at'    => current_time('mysql'),
            ];

            if ($exists) {
                $wpdb->update($affiliates, $row, ['id' => $exists], ['%d', '%s', '%s', '%s'], ['%d']);
            } else {
                $row['created_at'] = current_time('mysql');
                $wpdb->insert($affiliates, $row, ['%d', '%s', '%s', '%s', '%s']);
            }
        }
    }
}
