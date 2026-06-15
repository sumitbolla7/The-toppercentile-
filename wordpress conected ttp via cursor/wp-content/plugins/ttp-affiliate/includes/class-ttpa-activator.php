<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Activator {

    public static function activate() {
        self::create_tables();
        self::seed_defaults();
        self::ensure_roles();
        self::schedule_cron();
        flush_rewrite_rules(false);
    }

    public static function ensure_roles() {
        TTPA_Referral_Service::ensure_influencer_role_exists();
    }

    public static function create_tables() {
        global $wpdb;

        $charset     = $wpdb->get_charset_collate();
        $referrals   = $wpdb->prefix . 'ttpa_referrals';
        $commissions = $wpdb->prefix . 'ttpa_commissions';
        $payouts     = $wpdb->prefix . 'ttpa_payouts';
        $clicks      = $wpdb->prefix . 'ttpa_referral_clicks';

        $sql_referrals = "CREATE TABLE {$referrals} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            referred_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            referral_code VARCHAR(32) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            converted_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY referrer_user_id (referrer_user_id),
            KEY referred_user_id (referred_user_id),
            KEY referral_code (referral_code),
            KEY status (status)
        ) {$charset};";

        $sql_commissions = "CREATE TABLE {$commissions} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            referral_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            order_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            order_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
            commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            approved_at DATETIME NULL,
            paid_at DATETIME NULL,
            payout_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY referrer_user_id (referrer_user_id),
            KEY order_id (order_id),
            KEY status (status),
            KEY payout_id (payout_id)
        ) {$charset};";

        $sql_payouts = "CREATE TABLE {$payouts} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_method VARCHAR(50) NOT NULL DEFAULT '',
            payment_reference VARCHAR(190) NOT NULL DEFAULT '',
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            processed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) {$charset};";

        $sql_clicks = "CREATE TABLE {$clicks} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            referral_code VARCHAR(32) NOT NULL DEFAULT '',
            landing_url TEXT NOT NULL,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY referrer_user_id (referrer_user_id),
            KEY referral_code (referral_code),
            KEY created_at (created_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_referrals);
        dbDelta($sql_commissions);
        dbDelta($sql_payouts);
        dbDelta($sql_clicks);

        update_option('ttpa_db_version', TTPA_VERSION);
    }

    public static function seed_defaults() {
        add_option('ttpa_commission_rate', 10);
        add_option('ttpa_auto_approve_commissions', 1);
        add_option('ttpa_auto_payout_enabled', 0);
        add_option('ttpa_payout_threshold', 500);
        add_option('ttpa_payout_schedule', 'weekly');
        add_option('ttpa_referral_param', 'ref');
    }

    public static function schedule_cron() {
        if (!wp_next_scheduled('ttpa_process_auto_payouts')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'ttpa_process_auto_payouts');
        }
    }
}
