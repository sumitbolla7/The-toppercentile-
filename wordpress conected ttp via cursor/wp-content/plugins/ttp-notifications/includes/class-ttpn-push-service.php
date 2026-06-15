<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once TTPN_PATH . 'includes/class-ttpn-web-push.php';

class TTPN_Push_Service {

    public function hooks() {
        add_action('ttpn_send_push_for_notification', [$this, 'queue_notification_push']);
    }

    public function queue_notification_push($notification_id) {
        $this->send_for_notification((int) $notification_id);
    }

    public function process_queue() {
        global $wpdb;

        $table = TTPN_Database::notifications_table();
        $ids   = $wpdb->get_col(
            "SELECT id FROM {$table} WHERE push_sent = 0 ORDER BY created_at ASC LIMIT 25"
        );

        foreach ($ids as $id) {
            $this->send_for_notification((int) $id);
        }
    }

    public function save_subscription($user_id, $subscription) {
        global $wpdb;

        if (empty($subscription['endpoint']) || empty($subscription['keys']['p256dh']) || empty($subscription['keys']['auth'])) {
            return false;
        }

        $table = TTPN_Database::subscriptions_table();
        $existing = $wpdb->get_var(
            $wpdb->prepare("SELECT id FROM {$table} WHERE endpoint = %s", $subscription['endpoint'])
        );

        $data = [
            'user_id'    => (int) $user_id,
            'endpoint'   => esc_url_raw($subscription['endpoint']),
            'p256dh'     => sanitize_text_field($subscription['keys']['p256dh']),
            'auth'       => sanitize_text_field($subscription['keys']['auth']),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'updated_at' => TTPN_Database::now(),
        ];

        if ($existing) {
            return (bool) $wpdb->update(
                $table,
                $data,
                ['id' => (int) $existing],
                ['%d', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );
        }

        $data['created_at'] = TTPN_Database::now();

        return (bool) $wpdb->insert(
            $table,
            $data,
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    public function remove_subscription($user_id, $endpoint) {
        global $wpdb;

        return (bool) $wpdb->delete(
            TTPN_Database::subscriptions_table(),
            [
                'user_id'  => (int) $user_id,
                'endpoint' => esc_url_raw($endpoint),
            ],
            ['%d', '%s']
        );
    }

    public function get_subscriptions_for_user($user_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TTPN_Database::subscriptions_table() . ' WHERE user_id = %d ORDER BY updated_at DESC',
                (int) $user_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public function get_all_subscriptions($limit = 100) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TTPN_Database::subscriptions_table() . ' ORDER BY updated_at DESC LIMIT %d',
                (int) $limit
            ),
            ARRAY_A
        ) ?: [];
    }

    public function count_subscriptions() {
        global $wpdb;

        return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . TTPN_Database::subscriptions_table());
    }

    public function send_for_notification($notification_id) {
        $service = TTPN_Plugin::instance()->notifications();
        $notification = $service->get_by_id($notification_id);

        if (!$notification) {
            return false;
        }

        $subscriptions = $this->get_subscriptions_for_user($notification['user_id']);
        if (empty($subscriptions)) {
            $service->mark_push_sent($notification_id);
            return false;
        }

        if (!get_option('ttpn_enable_push', true)) {
            $service->mark_push_sent($notification_id);
            return false;
        }

        $public_key  = get_option('ttpn_vapid_public_key', '');
        $private_key = get_option('ttpn_vapid_private_key', '');

        if (!$public_key || !$private_key) {
            return false;
        }

        $payload = wp_json_encode([
            'title' => $notification['title'],
            'body'  => wp_strip_all_tags($notification['message']),
            'url'   => $notification['link'] ?: home_url('/'),
            'tag'   => 'ttpn-' . $notification_id,
        ]);

        $pusher = new TTPN_Web_Push($public_key, $private_key);
        $sent   = false;

        foreach ($subscriptions as $sub) {
            try {
                $result = $pusher->send($sub['endpoint'], $sub['p256dh'], $sub['auth'], $payload);
            } catch (Throwable $e) {
                continue;
            }

            if (!is_wp_error($result)) {
                $sent = true;
            } elseif ($result->get_error_code() === 'gone') {
                $this->remove_subscription($notification['user_id'], $sub['endpoint']);
            }
        }

        if ($sent) {
            $service->mark_push_sent($notification_id);
        }

        return $sent;
    }

    public function get_vapid_public_key() {
        return get_option('ttpn_vapid_public_key', '');
    }
}
