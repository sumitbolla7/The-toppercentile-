<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once TTPN_PATH . 'includes/class-ttpn-database.php';
require_once TTPN_PATH . 'includes/class-ttpn-notification-service.php';
require_once TTPN_PATH . 'includes/class-ttpn-push-service.php';
require_once TTPN_PATH . 'includes/class-ttpn-admin-alerts.php';
require_once TTPN_PATH . 'includes/class-ttpn-rest-api.php';
require_once TTPN_PATH . 'includes/class-ttpn-admin.php';
require_once TTPN_PATH . 'includes/class-ttpn-frontend.php';
require_once TTPN_PATH . 'includes/class-ttpn-integration.php';

class TTPN_Plugin {

    /** @var self|null */
    private static $instance = null;

    /** @var TTPN_Notification_Service */
    private $notifications;

    /** @var TTPN_Push_Service */
    private $push;

    /** @var TTPN_Admin_Alerts */
    private $admin_alerts;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->notifications = new TTPN_Notification_Service();
        $this->push          = new TTPN_Push_Service();
        $this->admin_alerts  = new TTPN_Admin_Alerts();
    }

    public function boot() {
        add_filter('cron_schedules', [$this, 'register_cron_schedules']);

        (new TTPN_Rest_Api($this->notifications, $this->push, $this->admin_alerts))->hooks();
        (new TTPN_Admin($this->notifications, $this->push, $this->admin_alerts))->hooks();
        (new TTPN_Frontend($this->notifications))->hooks();
        (new TTPN_Integration($this->notifications, $this->admin_alerts))->hooks();

        add_action('ttpn_process_push_queue', [$this->push, 'process_queue']);
        add_action('rest_api_init', [$this, 'register_service_worker_route']);
    }

    public function register_cron_schedules($schedules) {
        if (!isset($schedules['five_minutes'])) {
            $schedules['five_minutes'] = [
                'interval' => 300,
                'display'  => __('Every 5 Minutes', 'ttp-notifications'),
            ];
        }

        return $schedules;
    }

    public function register_service_worker_route() {
        register_rest_route('ttpn/v1', '/sw', [
            'methods'             => 'GET',
            'callback'            => [$this, 'serve_service_worker'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function serve_service_worker() {
        $sw_path = TTPN_PATH . 'assets/js/push-sw.js';
        if (!is_readable($sw_path)) {
            return new WP_Error('not_found', 'Service worker not found.', ['status' => 404]);
        }

        $response = new WP_REST_Response(file_get_contents($sw_path), 200);
        $response->header('Content-Type', 'application/javascript; charset=utf-8');
        $response->header('Service-Worker-Allowed', '/');

        return $response;
    }

    /** @return TTPN_Notification_Service */
    public function notifications() {
        return $this->notifications;
    }

    /** @return TTPN_Push_Service */
    public function push() {
        return $this->push;
    }

    /** @return TTPN_Admin_Alerts */
    public function admin_alerts() {
        return $this->admin_alerts;
    }
}
