<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Frontend {

    private $notifications;

    public function __construct($notifications) {
        $this->notifications = $notifications;
    }

    public function hooks() {
        if (!get_option('ttpn_enable_in_app', true)) {
            return;
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_body_open', [$this, 'render_notification_bell'], 99);
        add_action('wp_footer', [$this, 'render_notification_bell'], 99);
        add_shortcode('ttp_notifications', [$this, 'render_notifications_page']);
        add_shortcode('notification_bell', [$this, 'render_notification_bell_shortcode']);
    }

    public function register_endpoint() {
        add_rewrite_endpoint('notifications', EP_ROOT | EP_PAGES);
    }

    public function add_account_menu_item($items) {
        $new = [];
        foreach ($items as $key => $label) {
            $new[$key] = $label;
            if ('dashboard' === $key) {
                $new['notifications'] = __('Notifications', 'ttp-notifications');
            }
        }

        if (!isset($new['notifications'])) {
            $new['notifications'] = __('Notifications', 'ttp-notifications');
        }

        return $new;
    }

    public function enqueue_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        wp_enqueue_style('ttpn-frontend', TTPN_URL . 'assets/css/frontend.css', [], TTPN_VERSION);
        wp_enqueue_script('ttpn-frontend', TTPN_URL . 'assets/js/frontend.js', ['jquery'], TTPN_VERSION, true);

        $enable_push = get_option('ttpn_enable_push', true);
        wp_localize_script('ttpn-frontend', 'TTPN', [
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'restUrl'       => rest_url('ttpn/v1/'),
            'nonce'         => wp_create_nonce('ttpn_frontend'),
            'restNonce'     => wp_create_nonce('wp_rest'),
            'enablePush'    => (bool) $enable_push,
            'vapidPublic'   => TTPN_Plugin::instance()->push()->get_vapid_public_key(),
            'swUrl'         => rest_url('ttpn/v1/sw'),
            'unread'        => $this->notifications->count_unread(get_current_user_id()),
            'toastDuration' => 5500,
            'pollInterval'  => 45000,
        ]);
    }

    public function render_notification_bell() {
        if (!is_user_logged_in()) {
            return;
        }

        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;

        include TTPN_PATH . 'templates/notification-bell.php';
    }

    public function render_notifications_page() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to view notifications.', 'ttp-notifications') . '</p>';
        }

        ob_start();
        include TTPN_PATH . 'templates/notifications-page.php';
        return ob_get_clean();
    }

    public function render_account_endpoint() {
        echo do_shortcode('[ttp_notifications]');
    }

    public function render_notification_bell_shortcode() {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $unread = $this->notifications->count_unread(get_current_user_id());

        ob_start();
        include TTPN_PATH . 'templates/notification-bell.php';
        return ob_get_clean();
    }
}
