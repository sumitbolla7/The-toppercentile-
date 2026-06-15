<?php

namespace TTP_CRM\Core;

defined('ABSPATH') || exit;

class Activator
{
    public static function activate()
    {
        global $wpdb;

        $contacts_table  = $wpdb->prefix . 'ttp_crm_contacts';
        $campaigns_table = $wpdb->prefix . 'ttp_crm_campaigns';
        $charset_collate = $wpdb->get_charset_collate();

        $contacts_sql = "CREATE TABLE {$contacts_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(100) NOT NULL DEFAULT '',
            last_name VARCHAR(100) NOT NULL DEFAULT '',
            email VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(50) NOT NULL DEFAULT '',
            stage VARCHAR(50) NOT NULL DEFAULT 'new',
            tags TEXT NULL,
            course_name VARCHAR(190) NOT NULL DEFAULT '',
            lead_source VARCHAR(120) NOT NULL DEFAULT '',
            revenue_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            student_profile LONGTEXT NULL,
            purchase_summary LONGTEXT NULL,
            progress_notes LONGTEXT NULL,
            follow_up_at DATETIME NULL,
            reminder_sent_at DATETIME NULL,
            internal_notes LONGTEXT NULL,
            communication_history LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY stage (stage),
            KEY course_name (course_name),
            KEY lead_source (lead_source),
            KEY follow_up_at (follow_up_at),
            KEY reminder_sent_at (reminder_sent_at)
        ) {$charset_collate};";

        $campaigns_sql = "CREATE TABLE {$campaigns_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            channel VARCHAR(20) NOT NULL DEFAULT 'email',
            title VARCHAR(190) NOT NULL DEFAULT '',
            message LONGTEXT NOT NULL,
            tags_filter VARCHAR(255) NOT NULL DEFAULT '',
            stage_filter VARCHAR(50) NOT NULL DEFAULT '',
            sent_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL,
            sent_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY channel (channel),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($contacts_sql);
        dbDelta($campaigns_sql);

        if (!wp_next_scheduled('ttp_crm_run_reminders')) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', 'ttp_crm_run_reminders');
        }
    }
}
