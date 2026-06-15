<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Database {

    public static function referrals_table() {
        global $wpdb;
        return $wpdb->prefix . 'ttpa_referrals';
    }

    public static function commissions_table() {
        global $wpdb;
        return $wpdb->prefix . 'ttpa_commissions';
    }

    public static function payouts_table() {
        global $wpdb;
        return $wpdb->prefix . 'ttpa_payouts';
    }

    public static function clicks_table() {
        global $wpdb;
        return $wpdb->prefix . 'ttpa_referral_clicks';
    }

    public static function now() {
        return current_time('mysql');
    }
}
