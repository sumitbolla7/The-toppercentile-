<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Notification_Service {

    /**
     * Create an in-app notification.
     *
     * @param int    $user_id User ID.
     * @param string $title   Title.
     * @param string $message Message.
     * @param array  $args    type, link, meta, push.
     * @return int|false
     */
    public function create($user_id, $title, $message, $args = []) {
        global $wpdb;

        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }

        $defaults = [
            'type'  => 'general',
            'link'  => '',
            'meta'  => [],
            'push'  => true,
        ];
        $args = wp_parse_args($args, $defaults);

        $inserted = $wpdb->insert(
            TTPN_Database::notifications_table(),
            [
                'user_id'    => $user_id,
                'type'       => sanitize_key($args['type']),
                'title'      => sanitize_text_field($title),
                'message'    => wp_kses_post($message),
                'link'       => esc_url_raw($args['link']),
                'meta'       => wp_json_encode($args['meta']),
                'is_read'    => 0,
                'push_sent'  => 0,
                'created_at' => TTPN_Database::now(),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );

        if (!$inserted) {
            return false;
        }

        $notification_id = (int) $wpdb->insert_id;

        do_action('ttpn_notification_created', $notification_id, $user_id, $title, $message, $args);

        if (!empty($args['push'])) {
            if (!empty($args['push_immediate'])) {
                do_action('ttpn_send_push_for_notification', $notification_id);
            } elseif (empty($args['defer_push_schedule'])) {
                $this->schedule_push_queue();
            }
        }

        return $notification_id;
    }

    /**
     * Send the same notification to multiple users.
     *
     * @param int[]  $user_ids User IDs.
     * @param string $title    Title.
     * @param string $message  Message.
     * @param array  $args     Notification args.
     * @return int Number of notifications created.
     */
    public function create_for_users($user_ids, $title, $message, $args = []) {
        $sent = 0;
        $args = wp_parse_args($args, [
            'push_immediate'       => false,
            'defer_push_schedule'  => true,
        ]);

        foreach (array_unique(array_map('intval', (array) $user_ids)) as $user_id) {
            if ($user_id > 0 && $this->create($user_id, $title, $message, $args)) {
                ++$sent;
            }
        }

        if ($sent > 0 && !empty($args['push'])) {
            $this->schedule_push_queue();
        }

        return $sent;
    }

    /**
     * Send a notification to every registered user.
     *
     * @param string $title   Title.
     * @param string $message Message.
     * @param array  $args    Notification args.
     * @return int
     */
    public function create_for_all_users($title, $message, $args = []) {
        $args['push_immediate'] = false;
        $sent                   = 0;
        $offset                 = 0;
        $batch_size             = 100;

        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }

        @set_time_limit(300);

        while (true) {
            $user_ids = get_users([
                'fields' => 'ID',
                'number' => $batch_size,
                'offset' => $offset,
            ]);

            if (empty($user_ids)) {
                break;
            }

            $sent += $this->create_for_users($user_ids, $title, $message, $args);
            $offset += $batch_size;

            if (count($user_ids) < $batch_size) {
                break;
            }
        }

        return $sent;
    }

    /**
     * Send a notification to users with an affiliate referral code.
     *
     * @param string $title   Title.
     * @param string $message Message.
     * @param array  $args    Notification args.
     * @return int
     */
    public function create_for_affiliates($title, $message, $args = []) {
        global $wpdb;

        $args['push_immediate']      = false;
        $args['defer_push_schedule'] = true;

        $user_ids = $wpdb->get_col(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'ttpa_affiliate_enabled' AND meta_value = '1'"
        );

        return $this->create_for_users($user_ids ?: [], $title, $message, $args);
    }

    public function get_for_user($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'limit'   => 20,
            'offset'  => 0,
            'unread'  => null,
        ];
        $args = wp_parse_args($args, $defaults);

        $table = TTPN_Database::notifications_table();
        $where = 'WHERE user_id = %d';
        $params = [(int) $user_id];

        if (null !== $args['unread']) {
            $where .= ' AND is_read = %d';
            $params[] = $args['unread'] ? 1 : 0;
        }

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            array_merge($params, [(int) $args['limit'], (int) $args['offset']])
        );

        return array_map([$this, 'format_row'], $wpdb->get_results($sql, ARRAY_A) ?: []);
    }

    public function count_unread($user_id) {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . TTPN_Database::notifications_table() . ' WHERE user_id = %d AND is_read = 0',
                (int) $user_id
            )
        );
    }

    public function mark_read($notification_id, $user_id = 0) {
        global $wpdb;

        $where = ['id' => (int) $notification_id];
        if ($user_id > 0) {
            $where['user_id'] = (int) $user_id;
        }

        return (bool) $wpdb->update(
            TTPN_Database::notifications_table(),
            [
                'is_read' => 1,
                'read_at' => TTPN_Database::now(),
            ],
            $where,
            ['%d', '%s'],
            ['%d', '%d']
        );
    }

    public function mark_all_read($user_id) {
        global $wpdb;

        return (bool) $wpdb->update(
            TTPN_Database::notifications_table(),
            [
                'is_read' => 1,
                'read_at' => TTPN_Database::now(),
            ],
            ['user_id' => (int) $user_id, 'is_read' => 0],
            ['%d', '%s'],
            ['%d', '%d']
        );
    }

    public function get_by_id($notification_id) {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . TTPN_Database::notifications_table() . ' WHERE id = %d',
                (int) $notification_id
            ),
            ARRAY_A
        );

        return $row ? $this->format_row($row) : null;
    }

    public function get_admin_list($args = []) {
        global $wpdb;

        $defaults = [
            'limit'    => 50,
            'offset'   => 0,
            'user_id'  => 0,
            'type'     => '',
            'search'   => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $table  = TTPN_Database::notifications_table();
        $where  = ['1=1'];
        $params = [];

        if ($args['user_id'] > 0) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $args['user_id'];
        }

        if ($args['type'] !== '') {
            $where[]  = 'type = %s';
            $params[] = sanitize_key($args['type']);
        }

        if ($args['search'] !== '') {
            $where[]  = '(title LIKE %s OR message LIKE %s)';
            $like     = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
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

    public function count_all($args = []) {
        global $wpdb;

        $table = TTPN_Database::notifications_table();
        $where = ['1=1'];
        $params = [];

        if (!empty($args['user_id'])) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $args['user_id'];
        }

        if (!empty($args['search'])) {
            $where[]  = '(title LIKE %s OR message LIKE %s)';
            $like     = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);
        $sql       = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";

        if ($params) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return (int) $wpdb->get_var($sql);
    }

    public function get_stats() {
        global $wpdb;

        $table = TTPN_Database::notifications_table();

        return [
            'total'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"),
            'unread'       => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_read = 0"),
            'today'        => (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s",
                    wp_date('Y-m-d')
                )
            ),
            'push_pending' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE push_sent = 0"),
        ];
    }

    public function schedule_push_queue() {
        if (!wp_next_scheduled('ttpn_process_push_queue')) {
            wp_schedule_single_event(time() + 1, 'ttpn_process_push_queue');
        }

        if (function_exists('spawn_cron')) {
            spawn_cron();
        }
    }

    public function delete($notification_id) {
        global $wpdb;

        return (bool) $wpdb->delete(
            TTPN_Database::notifications_table(),
            ['id' => (int) $notification_id],
            ['%d']
        );
    }

    public function delete_many($notification_ids) {
        global $wpdb;

        $ids = array_values(array_filter(array_map('intval', (array) $notification_ids)));
        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        return (int) $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM ' . TTPN_Database::notifications_table() . " WHERE id IN ({$placeholders})",
                $ids
            )
        );
    }

    public function delete_all($args = []) {
        global $wpdb;

        $defaults = [
            'user_id' => 0,
            'type'    => '',
            'search'  => '',
        ];
        $args   = wp_parse_args($args, $defaults);
        $table  = TTPN_Database::notifications_table();
        $where  = ['1=1'];
        $params = [];

        if ($args['user_id'] > 0) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $args['user_id'];
        }

        if ($args['type'] !== '') {
            $where[]  = 'type = %s';
            $params[] = sanitize_key($args['type']);
        }

        if ($args['search'] !== '') {
            $where[]  = '(title LIKE %s OR message LIKE %s)';
            $like     = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);
        $sql       = "DELETE FROM {$table} WHERE {$where_sql}";

        if ($params) {
            return (int) $wpdb->query($wpdb->prepare($sql, $params));
        }

        return (int) $wpdb->query($sql);
    }

    public function mark_push_sent($notification_id) {
        global $wpdb;

        return (bool) $wpdb->update(
            TTPN_Database::notifications_table(),
            ['push_sent' => 1],
            ['id' => (int) $notification_id],
            ['%d'],
            ['%d']
        );
    }

    private function format_row($row) {
        if (!is_array($row)) {
            return $row;
        }

        $row['id']       = (int) $row['id'];
        $row['user_id']  = (int) $row['user_id'];
        $row['is_read']  = (bool) (int) $row['is_read'];
        $row['push_sent'] = (bool) (int) $row['push_sent'];
        $row['meta']     = json_decode($row['meta'] ?? '', true) ?: [];

        return $row;
    }
}
