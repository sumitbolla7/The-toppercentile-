<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Admin_Alerts {

    public function create($title, $message, $args = []) {
        global $wpdb;

        $defaults = [
            'alert_type'     => 'general',
            'actor_user_id'  => get_current_user_id(),
            'object_type'    => '',
            'object_id'      => 0,
            'link'           => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $inserted = $wpdb->insert(
            TTPN_Database::admin_alerts_table(),
            [
                'alert_type'      => sanitize_key($args['alert_type']),
                'title'           => sanitize_text_field($title),
                'message'         => wp_kses_post($message),
                'actor_user_id'   => (int) $args['actor_user_id'],
                'object_type'     => sanitize_key($args['object_type']),
                'object_id'       => (int) $args['object_id'],
                'link'            => esc_url_raw($args['link']),
                'is_read'         => 0,
                'created_at'      => TTPN_Database::now(),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%s']
        );

        if (!$inserted) {
            return false;
        }

        $alert_id = (int) $wpdb->insert_id;
        do_action('ttpn_admin_alert_created', $alert_id, $title, $message, $args);

        return $alert_id;
    }

    public function get_list($args = []) {
        global $wpdb;

        $defaults = [
            'limit'  => 50,
            'offset' => 0,
            'unread' => null,
            'type'   => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $table  = TTPN_Database::admin_alerts_table();
        $where  = ['1=1'];
        $params = [];

        if (null !== $args['unread']) {
            $where[]  = 'is_read = %d';
            $params[] = $args['unread'] ? 1 : 0;
        }

        if ($args['type'] !== '') {
            $where[]  = 'alert_type = %s';
            $params[] = sanitize_key($args['type']);
        }

        $where_sql = implode(' AND ', $where);
        $params[]  = (int) $args['limit'];
        $params[]  = (int) $args['offset'];

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $params
        );

        return array_map([$this, 'format_row'], $wpdb->get_results($sql, ARRAY_A) ?: []);
    }

    public function count_unread() {
        global $wpdb;

        return (int) $wpdb->get_var(
            'SELECT COUNT(*) FROM ' . TTPN_Database::admin_alerts_table() . ' WHERE is_read = 0'
        );
    }

    public function mark_read($alert_id) {
        global $wpdb;

        return (bool) $wpdb->update(
            TTPN_Database::admin_alerts_table(),
            [
                'is_read' => 1,
                'read_at' => TTPN_Database::now(),
            ],
            ['id' => (int) $alert_id],
            ['%d', '%s'],
            ['%d']
        );
    }

    public function mark_all_read() {
        global $wpdb;

        return (bool) $wpdb->update(
            TTPN_Database::admin_alerts_table(),
            [
                'is_read' => 1,
                'read_at' => TTPN_Database::now(),
            ],
            ['is_read' => 0],
            ['%d', '%s'],
            ['%d']
        );
    }

    public function get_stats() {
        global $wpdb;

        $table = TTPN_Database::admin_alerts_table();

        return [
            'total'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
            'unread' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_read = 0"),
            'today'  => (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s",
                    wp_date('Y-m-d')
                )
            ),
        ];
    }

    private function format_row($row) {
        $row['id']             = (int) $row['id'];
        $row['actor_user_id']  = (int) $row['actor_user_id'];
        $row['object_id']      = (int) $row['object_id'];
        $row['is_read']        = (bool) (int) $row['is_read'];

        return $row;
    }
}
