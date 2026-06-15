<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Admin {

    private $notifications;
    private $push;
    private $admin_alerts;

    public function __construct($notifications, $push, $admin_alerts) {
        $this->notifications = $notifications;
        $this->push          = $push;
        $this->admin_alerts  = $admin_alerts;
    }

    public function hooks() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_bar_menu', [$this, 'admin_bar_alerts'], 100);
        add_action('admin_post_ttpn_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_ttpn_send_test_notification', [$this, 'handle_test_notification']);
        add_action('admin_post_ttpn_mark_all_admin_alerts_read', [$this, 'handle_mark_all_alerts_read']);
        add_action('admin_post_ttpn_delete_notification', [$this, 'handle_delete_notification']);
        add_action('admin_post_ttpn_bulk_delete_notifications', [$this, 'handle_bulk_delete_notifications']);
        add_action('admin_post_ttpn_delete_all_notifications', [$this, 'handle_delete_all_notifications']);
    }

    public function register_menu() {
        $cap = 'manage_options';

        add_menu_page(
            __('TTP Notifications', 'ttp-notifications'),
            __('TTP Notifications', 'ttp-notifications'),
            $cap,
            'ttp-notifications',
            [$this, 'render_dashboard'],
            'dashicons-bell',
            57
        );

        add_submenu_page('ttp-notifications', __('Dashboard', 'ttp-notifications'), __('Dashboard', 'ttp-notifications'), $cap, 'ttp-notifications', [$this, 'render_dashboard']);
        add_submenu_page('ttp-notifications', __('All Notifications', 'ttp-notifications'), __('All Notifications', 'ttp-notifications'), $cap, 'ttp-notifications-log', [$this, 'render_notifications_log']);
        add_submenu_page('ttp-notifications', __('Admin Alerts', 'ttp-notifications'), __('Admin Alerts', 'ttp-notifications'), $cap, 'ttp-notifications-alerts', [$this, 'render_admin_alerts']);
        add_submenu_page('ttp-notifications', __('Push Subscriptions', 'ttp-notifications'), __('Push Subscriptions', 'ttp-notifications'), $cap, 'ttp-notifications-push', [$this, 'render_push_subscriptions']);
        add_submenu_page('ttp-notifications', __('Settings', 'ttp-notifications'), __('Settings', 'ttp-notifications'), $cap, 'ttp-notifications-settings', [$this, 'render_settings']);
    }

    public function enqueue_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if (strpos($hook, 'ttp-notifications') === false && $hook !== 'index.php' && 'ttp-notifications-log' !== $page) {
            return;
        }

        wp_enqueue_style('ttpn-admin', TTPN_URL . 'assets/css/admin.css', [], TTPN_VERSION);
        wp_enqueue_script('ttpn-admin', TTPN_URL . 'assets/js/admin.js', ['jquery'], TTPN_VERSION, true);
        wp_localize_script('ttpn-admin', 'TTPNAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ttpn_admin'),
        ]);
    }

    public function admin_bar_alerts($admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $count = $this->admin_alerts->count_unread();
        $title = __('Alerts', 'ttp-notifications');
        if ($count > 0) {
            $title .= ' <span class="awaiting-mod count-' . esc_attr($count) . '"><span class="pending-count">' . esc_html($count) . '</span></span>';
        }

        $admin_bar->add_node([
            'id'    => 'ttpn-admin-alerts',
            'title' => $title,
            'href'  => admin_url('admin.php?page=ttp-notifications-alerts'),
            'meta'  => ['class' => 'ttpn-admin-bar-alerts'],
        ]);
    }

    public function render_dashboard() {
        $stats       = $this->notifications->get_stats();
        $alert_stats = $this->admin_alerts->get_stats();
        $push_count  = $this->push->count_subscriptions();
        include TTPN_PATH . 'templates/admin-dashboard.php';
    }

    public function render_notifications_log() {
        $search  = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $total   = $this->notifications->count_all([
            'user_id' => $user_id,
            'search'  => $search,
        ]);
        $items   = $this->notifications->get_admin_list([
            'search'  => $search,
            'user_id' => $user_id,
            'limit'   => 100,
        ]);
        include TTPN_PATH . 'templates/admin-notifications-log.php';
    }

    public function render_admin_alerts() {
        $items = $this->admin_alerts->get_list(['limit' => 100]);
        include TTPN_PATH . 'templates/admin-alerts.php';
    }

    public function render_push_subscriptions() {
        $items = $this->push->get_all_subscriptions(200);
        include TTPN_PATH . 'templates/admin-push-subscriptions.php';
    }

    public function render_settings() {
        $settings = [
            'enable_push'         => (bool) get_option('ttpn_enable_push', true),
            'enable_in_app'       => (bool) get_option('ttpn_enable_in_app', true),
            'enable_admin_alerts' => (bool) get_option('ttpn_enable_admin_alerts', true),
            'vapid_public'        => $this->push->get_vapid_public_key(),
        ];
        include TTPN_PATH . 'templates/admin-settings.php';
    }

    public function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-notifications'));
        }
        check_admin_referer('ttpn_save_settings');

        update_option('ttpn_enable_push', !empty($_POST['enable_push']));
        update_option('ttpn_enable_in_app', !empty($_POST['enable_in_app']));
        update_option('ttpn_enable_admin_alerts', !empty($_POST['enable_admin_alerts']));

        wp_safe_redirect(admin_url('admin.php?page=ttp-notifications-settings&updated=1'));
        exit;
    }

    public function handle_test_notification() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-notifications'));
        }
        check_admin_referer('ttpn_send_test');

        $user_id = get_current_user_id();
        $this->notifications->create(
            $user_id,
            __('Test notification', 'ttp-notifications'),
            __('This is a test in-app and push notification from TTP Notifications.', 'ttp-notifications'),
            ['type' => 'test', 'push' => true]
        );

        wp_safe_redirect(admin_url('admin.php?page=ttp-notifications-settings&test_sent=1'));
        exit;
    }

    public function handle_mark_all_alerts_read() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-notifications'));
        }
        check_admin_referer('ttpn_mark_all_alerts');

        $this->admin_alerts->mark_all_read();
        wp_safe_redirect(admin_url('admin.php?page=ttp-notifications-alerts&marked=1'));
        exit;
    }

    public function handle_delete_notification() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-notifications'));
        }

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        check_admin_referer('ttpn_delete_notification_' . $id);

        if ($id > 0) {
            $this->notifications->delete($id);
        }

        wp_safe_redirect(admin_url('admin.php?page=ttp-notifications-log&deleted=1'));
        exit;
    }

    public function handle_bulk_delete_notifications() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-notifications'));
        }

        check_admin_referer('ttpn_bulk_delete_notifications');

        $ids  = isset($_POST['notification_ids']) ? array_map('intval', (array) $_POST['notification_ids']) : [];
        $sent = $this->notifications->delete_many($ids);

        wp_safe_redirect(admin_url('admin.php?page=ttp-notifications-log&bulk_deleted=' . (int) $sent));
        exit;
    }

    public function handle_delete_all_notifications() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-notifications'));
        }

        check_admin_referer('ttpn_delete_all_notifications');

        $search  = isset($_POST['filter_search']) ? sanitize_text_field(wp_unslash($_POST['filter_search'])) : '';
        $user_id = isset($_POST['filter_user_id']) ? (int) $_POST['filter_user_id'] : 0;
        $scope   = sanitize_key($_POST['delete_scope'] ?? 'all');

        if ('filtered' === $scope) {
            $deleted = $this->notifications->delete_all([
                'search'  => $search,
                'user_id' => $user_id,
            ]);
        } else {
            $deleted = $this->notifications->delete_all([]);
        }

        wp_safe_redirect(admin_url('admin.php?page=ttp-notifications-log&all_deleted=' . (int) $deleted));
        exit;
    }
}
