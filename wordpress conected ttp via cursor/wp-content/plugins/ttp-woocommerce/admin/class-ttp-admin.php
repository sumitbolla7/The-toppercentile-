<?php
if (!defined('ABSPATH')) exit;

class TTP_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('wp_ajax_ttp_retry_api', [$this, 'retry_api']);
        add_action('wp_ajax_ttp_fetch_tcy_courses', [$this, 'fetch_courses']);
        add_action('wp_ajax_ttp_test_connection', [$this, 'test_connection']);
    }

    public function add_menu() {
        add_submenu_page('ttp-dashboard', 'API Logs', 'API Logs', 'manage_options', 'ttp-api-logs', [$this, 'render_logs']);
        add_submenu_page('ttp-dashboard', 'Students', 'Students', 'manage_options', 'ttp-students', [$this, 'render_students']);
        add_submenu_page('ttp-dashboard', 'TCY Courses', 'TCY Courses', 'manage_options', 'ttp-courses', [$this, 'render_courses']);
    }

    public function render_logs() {
        global $wpdb;
        $logs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ttp_api_logs ORDER BY created_at DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>📋 API Logs</h1>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Order ID</th><th>Action</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log->id; ?></td>
                        <td><?php echo $log->order_id ?: '-'; ?></td>
                        <td><?php echo esc_html($log->action); ?></td>
                        <td><span style="color:<?php echo $log->status === 'success' ? 'green' : 'red'; ?>;font-weight:bold;"><?php echo esc_html($log->status); ?></span></td>
                        <td><?php echo esc_html($log->created_at); ?></td>
                        <td>
                            <?php if ($log->status === 'failed' && $log->order_id): ?>
                                <button class="button ttp-retry-btn" data-order-id="<?php echo $log->order_id; ?>" data-nonce="<?php echo wp_create_nonce('ttp_nonce'); ?>">Retry</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_students() {
        global $wpdb;
        $students = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ttp_students ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>👥 Student — TCY Mapping</h1>
            <table class="widefat striped">
                <thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Username</th><th>TCY User ID</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?php echo esc_html($s->full_name); ?></td>
                        <td><?php echo esc_html($s->email); ?></td>
                        <td><?php echo esc_html($s->mobile); ?></td>
                        <td><?php echo esc_html($s->username); ?></td>
                        <td><?php echo $s->tcy_user_id ?: '<span style="color:orange;">Pending</span>'; ?></td>
                        <td><?php echo esc_html($s->created_at); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_courses() {
        ?>
        <div class="wrap">
            <h1>📚 TCY Courses</h1>
            <p>Fetch all courses from TCY to get Course IDs and Category IDs for your products.</p>
            <button class="button button-primary" id="ttp-fetch-btn">🔄 Fetch Courses from TCY</button>
            <button class="button" id="ttp-test-btn" style="margin-left:10px;">🔌 Test Connection</button>
            <div id="ttp-courses-output" style="margin-top:20px;background:#f5f5f5;padding:20px;border-radius:8px;display:none;"></div>
        </div>
        <script>
        document.getElementById('ttp-fetch-btn').onclick = function() {
            this.textContent = 'Fetching...';
            var btn = this;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'action=ttp_fetch_tcy_courses&nonce=<?php echo wp_create_nonce('ttp_nonce'); ?>'
            }).then(r=>r.json()).then(data=>{
                var out = document.getElementById('ttp-courses-output');
                out.style.display = 'block';
                out.innerHTML = '<pre style="overflow:auto;max-height:400px;">' + JSON.stringify(data, null, 2) + '</pre>';
                btn.textContent = '🔄 Fetch Courses from TCY';
            });
        };
        document.getElementById('ttp-test-btn').onclick = function() {
            this.textContent = 'Testing...';
            var btn = this;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'action=ttp_test_connection&nonce=<?php echo wp_create_nonce('ttp_nonce'); ?>'
            }).then(r=>r.json()).then(data=>{
                var out = document.getElementById('ttp-courses-output');
                out.style.display = 'block';
                if (data.success) {
                    out.innerHTML = '<p style="color:green;font-weight:bold;">✅ TCY API Connected Successfully!</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } else {
                    out.innerHTML = '<p style="color:red;font-weight:bold;">❌ Connection Failed</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
                }
                btn.textContent = '🔌 Test Connection';
            });
        };
        </script>
        <?php
    }

    public function retry_api() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
        check_ajax_referer('ttp_nonce', 'nonce');
        $order_id = intval($_POST['order_id']);
        $checkout = new TTP_Checkout();
        $checkout->trigger_tcy_registration($order_id);
        wp_send_json_success(['message' => 'API re-triggered for Order #' . $order_id]);
    }

    public function fetch_courses() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
        check_ajax_referer('ttp_nonce', 'nonce');
        $api = new TTP_TCY_API();
        wp_send_json($api->get_courses());
    }

    public function test_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
        check_ajax_referer('ttp_nonce', 'nonce');
        $api = new TTP_TCY_API();
        wp_send_json($api->test_connection());
    }
}
