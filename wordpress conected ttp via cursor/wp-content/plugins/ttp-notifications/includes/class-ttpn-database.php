<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Database {

    public static function notifications_table() {
        global $wpdb;
        return $wpdb->prefix . 'ttpn_notifications';
    }

    public static function subscriptions_table() {
        global $wpdb;
        return $wpdb->prefix . 'ttpn_push_subscriptions';
    }

    public static function admin_alerts_table() {
        global $wpdb;
        return $wpdb->prefix . 'ttpn_admin_alerts';
    }

    public static function now() {
        return current_time('mysql');
    }
}
