<?php
/**
 * Student dashboard and endpoints.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('tpsp_get_account_endpoint_url')) {
    /**
     * Build robust account endpoint URL with fallback query args.
     *
     * @param string $endpoint Endpoint key.
     * @return string
     */
    function tpsp_get_account_endpoint_url($endpoint = 'dashboard') {
        $endpoint = sanitize_key($endpoint);
        $base_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
        if (!$base_url) {
            $base_url = home_url('/my-account/');
        }

        if ('' === $endpoint || 'dashboard' === $endpoint) {
            return $base_url;
        }

        // Always use query-based fallback to avoid permalink endpoint 404 issues.
        return add_query_arg($endpoint, '1', $base_url);
    }
}

class TPSP_Dashboard {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        add_action('init', [$this, 'register_endpoints']);
        add_action('init', [$this, 'remove_default_dashboard_paragraph'], 20);
        add_filter('query_vars', [$this, 'register_query_vars']);
        add_filter('woocommerce_account_menu_items', [$this, 'customize_account_menu']);
        add_action('woocommerce_account_my-profile_endpoint', [$this, 'render_profile_endpoint']);
        add_action('woocommerce_account_my-courses_endpoint', [$this, 'render_courses_endpoint']);
        add_action('woocommerce_account_my-tests_endpoint', [$this, 'render_tests_endpoint']);
        add_action('woocommerce_account_my-results_endpoint', [$this, 'render_results_endpoint']);
        add_action('woocommerce_account_tpsp-settings_endpoint', [$this, 'render_settings_endpoint']);
        add_shortcode('tpsp_student_dashboard', [$this, 'render_dashboard_shortcode']);
        add_shortcode('tpsp_login_student_panel', [$this, 'render_login_student_panel_shortcode']);
        add_action('template_redirect', [$this, 'handle_profile_update']);
        add_filter('login_redirect', [$this, 'redirect_after_login'], 10, 3);
        add_filter('the_content', [$this, 'replace_login_content_for_logged_in_users'], 20);
        add_action('woocommerce_account_dashboard', [$this, 'render_custom_dashboard_intro'], 1);
    }

    /**
     * Remove WooCommerce default dashboard paragraph block.
     *
     * @return void
     */
    public function remove_default_dashboard_paragraph() {
        remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard', 10);
    }

    /**
     * Register custom endpoints.
     *
     * @return void
     */
    public function register_endpoints() {
        add_rewrite_endpoint('my-profile', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('my-courses', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('my-tests', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('my-results', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('tpsp-settings', EP_ROOT | EP_PAGES);
    }

    /**
     * Register custom query vars.
     *
     * @param array $vars Vars.
     * @return array
     */
    public function register_query_vars($vars) {
        $vars[] = 'my-profile';
        $vars[] = 'my-courses';
        $vars[] = 'my-tests';
        $vars[] = 'my-results';
        $vars[] = 'tpsp-settings';
        return $vars;
    }

    /**
     * Build a custom account sidebar menu.
     *
     * @param array $items Existing items.
     * @return array
     */
    public function customize_account_menu($items) {
        // Keep the left menu minimal (premium portal style).
        return [
            'dashboard'     => __('Dashboard', 'tpsp'),
            'my-courses'    => __('My Courses', 'tpsp'),
            'orders'        => __('Orders', 'tpsp'),
            'customer-logout' => __('Logout', 'tpsp'),
        ];
    }

    /**
     * Render profile page.
     *
     * @return void
     */
    public function render_profile_endpoint() {
        echo $this->get_dashboard_template('profile');
    }

    /**
     * Render courses page.
     *
     * @return void
     */
    public function render_courses_endpoint() {
        echo $this->get_dashboard_template('courses');
    }

    /**
     * Render tests page.
     *
     * @return void
     */
    public function render_tests_endpoint() {
        echo $this->get_dashboard_template('tests');
    }

    /**
     * Render results page.
     *
     * @return void
     */
    public function render_results_endpoint() {
        echo $this->get_dashboard_template('results');
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings_endpoint() {
        echo $this->get_dashboard_template('settings');
    }

    /**
     * Render whole dashboard via shortcode.
     *
     * @return string
     */
    public function render_dashboard_shortcode() {
        if (!is_user_logged_in()) {
            return do_shortcode('[woocommerce_my_account]');
        }

        ob_start();
        wc_get_template(
            'dashboard.php',
            ['context' => $this],
            '',
            TPSP_PLUGIN_DIR . 'templates/'
        );
        return (string) ob_get_clean();
    }

    /**
     * Logged-in student view for login page.
     *
     * @return string
     */
    public function render_login_student_panel_shortcode() {
        if (!is_user_logged_in()) {
            return do_shortcode('[woocommerce_my_account]');
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();
        $orders  = wc_get_orders([
            'customer_id' => $user_id,
            'limit'       => 5,
            'status'      => array_keys(wc_get_order_statuses()),
        ]);

        $wc_customer = new WC_Customer($user_id);
        $billing_html = $wc_customer->get_formatted_billing_address();
        if (!is_string($billing_html) || trim(wp_strip_all_tags($billing_html)) === '') {
            $billing_html = '';
        }

        $edit_account_url = '';
        if (function_exists('wc_get_page_permalink')) {
            $edit_account_url = (string) wc_get_endpoint_url('edit-account', '', wc_get_page_permalink('myaccount'));
        }

        $enrolled_courses = [];
        if (function_exists('wc_get_orders')) {
            $order_statuses = function_exists('wc_get_is_paid_statuses') ? wc_get_is_paid_statuses() : ['processing', 'completed', 'on-hold'];
            $order_list     = wc_get_orders([
                'customer_id' => $user_id,
                'limit'       => 200,
                'status'      => $order_statuses,
            ]);
            foreach ($order_list as $ord) {
                foreach ($ord->get_items() as $item) {
                    $pid = (int) $item->get_product_id();
                    if ($pid <= 0) {
                        continue;
                    }
                    $name = trim((string) $item->get_name());
                    if ($name !== '') {
                        $enrolled_courses[$pid] = $name;
                    }
                }
            }
        }

        $profile = [
            'phone'   => get_user_meta($user_id, 'billing_phone', true),
            'gender'  => get_user_meta($user_id, 'tpsp_gender', true),
            'dob'     => get_user_meta($user_id, 'tpsp_dob', true),
            'address' => get_user_meta($user_id, 'billing_address_1', true),
            'city'    => get_user_meta($user_id, 'billing_city', true),
            'state'   => get_user_meta($user_id, 'billing_state', true),
            'country' => get_user_meta($user_id, 'billing_country', true),
            'pincode' => get_user_meta($user_id, 'billing_postcode', true),
            'avatar'  => get_user_meta($user_id, 'tpsp_avatar_id', true),
        ];

        ob_start();
        include TPSP_PLUGIN_DIR . 'templates/section-login-student-panel.php';
        return (string) ob_get_clean();
    }

    /**
     * Replace /login page content with student panel when logged in.
     *
     * @param string $content Original content.
     * @return string
     */
    public function replace_login_content_for_logged_in_users($content) {
        if (!is_user_logged_in() || !is_page('login') || !is_main_query() || !in_the_loop()) {
            return $content;
        }

        return do_shortcode('[tpsp_login_student_panel]');
    }

    /**
     * Get HTML for sections.
     *
     * @param string $section Section key.
     * @return string
     */
    private function get_dashboard_template($section) {
        $user_id = get_current_user_id();
        $user    = wp_get_current_user();

        if (!$user_id) {
            return '';
        }

        $profile = [
            'phone'   => get_user_meta($user_id, 'billing_phone', true),
            'gender'  => get_user_meta($user_id, 'tpsp_gender', true),
            'dob'     => get_user_meta($user_id, 'tpsp_dob', true),
            'address' => get_user_meta($user_id, 'billing_address_1', true),
            'city'    => get_user_meta($user_id, 'billing_city', true),
            'state'   => get_user_meta($user_id, 'billing_state', true),
            'country' => get_user_meta($user_id, 'billing_country', true),
            'pincode' => get_user_meta($user_id, 'billing_postcode', true),
            'avatar'  => get_user_meta($user_id, 'tpsp_avatar_id', true),
        ];

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit'       => -1,
            'status'      => array_keys(wc_get_order_statuses()),
        ]);

        $purchased_products = [];
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product_id = (int) $item->get_product_id();
                if ($product_id <= 0) {
                    continue;
                }

                // Keep first match from newest order so "Open Course" links to latest successful access page.
                if (isset($purchased_products[$product_id])) {
                    continue;
                }

                $order_received_url = wc_get_endpoint_url('order-received', (int) $order->get_id(), wc_get_checkout_url());
                $access_url         = add_query_arg('key', $order->get_order_key(), $order_received_url);

                $purchased_products[$product_id] = [
                    'name'       => $item->get_name(),
                    'access_url' => $access_url,
                ];
            }
        }

        $progress = get_user_meta($user_id, 'tpsp_progress', true);
        if (!is_array($progress)) {
            $progress = [];
        }

        $section_file = TPSP_PLUGIN_DIR . 'templates/section-' . $section . '.php';
        if (!file_exists($section_file)) {
            return '';
        }

        ob_start();
        include $section_file;
        return (string) ob_get_clean();
    }

    /**
     * Save profile and settings forms.
     *
     * @return void
     */
    public function handle_profile_update() {
        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_POST['tpsp_profile_submit'])) {
            return;
        }

        if (!isset($_POST['tpsp_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tpsp_profile_nonce'])), 'tpsp_profile_update')) {
            wc_add_notice(__('Security check failed. Please try again.', 'tpsp'), 'error');
            return;
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();

        wp_update_user([
            'ID'           => $user_id,
            'display_name' => isset($_POST['display_name']) ? sanitize_text_field(wp_unslash($_POST['display_name'])) : $user->display_name,
            'user_email'   => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : $user->user_email,
        ]);

        $meta_fields = [
            'billing_phone'     => 'phone',
            'tpsp_gender'       => 'gender',
            'tpsp_dob'          => 'dob',
            'billing_address_1' => 'address',
            'billing_city'      => 'city',
            'billing_state'     => 'state',
            'billing_country'   => 'country',
            'billing_postcode'  => 'pincode',
        ];

        foreach ($meta_fields as $meta_key => $field_key) {
            $value = isset($_POST[$field_key]) ? sanitize_text_field(wp_unslash($_POST[$field_key])) : '';
            update_user_meta($user_id, $meta_key, $value);
        }

        if (!empty($_FILES['avatar']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $uploaded = media_handle_upload('avatar', 0);
            if (!is_wp_error($uploaded)) {
                update_user_meta($user_id, 'tpsp_avatar_id', (int) $uploaded);
            }
        }

        wc_add_notice(__('Profile updated successfully.', 'tpsp'), 'success');
        wp_safe_redirect(wc_get_account_endpoint_url('my-profile'));
        exit;
    }

    /**
     * Login redirect to dashboard endpoint.
     *
     * @param string           $redirect Redirect URL.
     * @param string           $request  Request URL.
     * @param WP_User|WP_Error $user     User object.
     * @return string
     */
    public function redirect_after_login($redirect, $request, $user) {
        if (!($user instanceof WP_User)) {
            return $redirect;
        }

        if (user_can($user, 'manage_options')) {
            return $redirect;
        }

        // Honor checkout / exam flow: /login/?redirect_to=...
        $validated = wp_validate_redirect($redirect, '');
        if (!empty($validated)) {
            return $validated;
        }

        return wc_get_account_endpoint_url('dashboard');
    }

    /**
     * Render compact dashboard intro without shipping wording.
     *
     * @return void
     */
    public function render_custom_dashboard_intro() {
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        echo '<div class="tpsp-dashboard-intro">';
        echo '<h3>' . esc_html(sprintf(__('Welcome, %s', 'tpsp'), $user->display_name)) . '</h3>';
        echo '<ul>';
        echo '<li>' . esc_html__('View your recent orders', 'tpsp') . '</li>';
        echo '<li>' . esc_html__('Open your purchased courses', 'tpsp') . '</li>';
        echo '<li>' . esc_html__('Edit your profile details', 'tpsp') . '</li>';
        echo '</ul>';
        echo '</div>';
    }
}
