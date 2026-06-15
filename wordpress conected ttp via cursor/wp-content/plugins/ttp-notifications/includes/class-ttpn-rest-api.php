<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Rest_Api {

    private $notifications;
    private $push;
    private $admin_alerts;

    public function __construct($notifications, $push, $admin_alerts) {
        $this->notifications = $notifications;
        $this->push          = $push;
        $this->admin_alerts  = $admin_alerts;
    }

    public function hooks() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_ajax_ttpn_get_notifications', [$this, 'ajax_get_notifications']);
        add_action('wp_ajax_ttpn_mark_notification_read', [$this, 'ajax_mark_read']);
        add_action('wp_ajax_ttpn_mark_all_notifications_read', [$this, 'ajax_mark_all_read']);
        add_action('wp_ajax_ttpn_save_push_subscription', [$this, 'ajax_save_push_subscription']);
        add_action('wp_ajax_ttpn_remove_push_subscription', [$this, 'ajax_remove_push_subscription']);
        add_action('wp_ajax_ttpn_get_admin_alerts', [$this, 'ajax_get_admin_alerts']);
        add_action('wp_ajax_ttpn_mark_admin_alert_read', [$this, 'ajax_mark_admin_alert_read']);
    }

    public function register_routes() {
        register_rest_route('ttpn/v1', '/notifications', [
            'methods'             => 'GET',
            'callback'            => [$this, 'rest_get_notifications'],
            'permission_callback' => [$this, 'logged_in_permission'],
        ]);

        register_rest_route('ttpn/v1', '/notifications/(?P<id>\d+)/read', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_mark_read'],
            'permission_callback' => [$this, 'logged_in_permission'],
        ]);

        register_rest_route('ttpn/v1', '/push/subscribe', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_subscribe_push'],
            'permission_callback' => [$this, 'logged_in_permission'],
        ]);
    }

    public function logged_in_permission() {
        return is_user_logged_in();
    }

    public function rest_get_notifications(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $items   = $this->notifications->get_for_user($user_id, [
            'limit' => (int) $request->get_param('limit') ?: 20,
        ]);

        return rest_ensure_response([
            'items'  => $items,
            'unread' => $this->notifications->count_unread($user_id),
        ]);
    }

    public function rest_mark_read(WP_REST_Request $request) {
        $id      = (int) $request['id'];
        $user_id = get_current_user_id();
        $this->notifications->mark_read($id, $user_id);

        return rest_ensure_response(['success' => true]);
    }

    public function rest_subscribe_push(WP_REST_Request $request) {
        $subscription = $request->get_json_params();
        $saved = $this->push->save_subscription(get_current_user_id(), $subscription);

        return rest_ensure_response(['success' => (bool) $saved]);
    }

    public function ajax_get_notifications() {
        $this->verify_nonce();
        $user_id = get_current_user_id();

        wp_send_json_success([
            'items'  => $this->notifications->get_for_user($user_id, ['limit' => 30]),
            'unread' => $this->notifications->count_unread($user_id),
        ]);
    }

    public function ajax_mark_read() {
        $this->verify_nonce();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $this->notifications->mark_read($id, get_current_user_id());
        wp_send_json_success();
    }

    public function ajax_mark_all_read() {
        $this->verify_nonce();
        $this->notifications->mark_all_read(get_current_user_id());
        wp_send_json_success();
    }

    public function ajax_save_push_subscription() {
        $this->verify_nonce();
        $raw = isset($_POST['subscription']) ? wp_unslash($_POST['subscription']) : '';
        $subscription = json_decode($raw, true);
        if (!$subscription && !empty($_POST['subscription'])) {
            $subscription = json_decode(stripslashes($_POST['subscription']), true);
        }

        $saved = $this->push->save_subscription(get_current_user_id(), is_array($subscription) ? $subscription : []);
        wp_send_json_success(['saved' => (bool) $saved]);
    }

    public function ajax_remove_push_subscription() {
        $this->verify_nonce();
        $endpoint = isset($_POST['endpoint']) ? esc_url_raw(wp_unslash($_POST['endpoint'])) : '';
        $this->push->remove_subscription(get_current_user_id(), $endpoint);
        wp_send_json_success();
    }

    public function ajax_get_admin_alerts() {
        $this->verify_admin();
        wp_send_json_success([
            'items'  => $this->admin_alerts->get_list(['limit' => 30, 'unread' => null]),
            'unread' => $this->admin_alerts->count_unread(),
        ]);
    }

    public function ajax_mark_admin_alert_read() {
        $this->verify_admin();
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $this->admin_alerts->mark_read($id);
        wp_send_json_success();
    }

    private function verify_nonce() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Unauthorized'], 401);
        }

        check_ajax_referer('ttpn_frontend', 'nonce');
    }

    private function verify_admin() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }

        check_ajax_referer('ttpn_admin', 'nonce');
    }
}
