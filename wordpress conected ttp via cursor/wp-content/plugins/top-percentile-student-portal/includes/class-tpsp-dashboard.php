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

        /*
         * Prefer WooCommerce’s own URL builder so /my-account/orders/, /my-account/my-courses/, etc.
         * match rewrite rules and avoid 404s when plain query fallback was used incorrectly.
         */
        if (function_exists('wc_get_account_endpoint_url')) {
            $wc_url = wc_get_account_endpoint_url($endpoint);
            if (is_string($wc_url) && '' !== $wc_url) {
                return $wc_url;
            }
        }

        return add_query_arg($endpoint, '1', $base_url);
    }
}

class TPSP_Dashboard {

    /**
     * Prevents infinite recursion when replacing /login/ content.
     *
     * @var bool
     */
    private static $rendering_login_panel = false;

    /**
     * Ensures the student panel is only injected once per request.
     *
     * @var bool
     */
    private static $login_panel_injected = false;

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
        add_filter('woocommerce_account_menu_items', [$this, 'strip_extra_account_tabs'], 999);
        add_action('woocommerce_account_my-profile_endpoint', [$this, 'render_profile_endpoint']);
        add_action('woocommerce_account_my-courses_endpoint', [$this, 'render_courses_endpoint']);
        add_action('woocommerce_account_my-tests_endpoint', [$this, 'render_tests_endpoint']);
        add_action('woocommerce_account_my-results_endpoint', [$this, 'render_results_endpoint']);
        add_action('woocommerce_account_tpsp-settings_endpoint', [$this, 'render_settings_endpoint']);
        add_shortcode('tpsp_student_dashboard', [$this, 'render_dashboard_shortcode']);
        add_shortcode('tpsp_login_student_panel', [$this, 'render_login_student_panel_shortcode']);
        add_action('template_redirect', [$this, 'maybe_redirect_away_from_user_registration_my_account'], 5);
        add_action('template_redirect', [$this, 'redirect_wc_account_dashboard_to_login'], 6);
        add_action('template_redirect', [$this, 'handle_profile_update']);
        add_filter('login_redirect', [$this, 'redirect_after_login'], 10, 3);
        add_filter('user_registration_login_redirect', [$this, 'user_registration_login_redirect_to_wc_account'], 20, 2);
        add_filter('woocommerce_login_redirect', [$this, 'woocommerce_login_redirect_to_login_page'], 20, 2);
        add_filter('the_content', [$this, 'strip_user_registration_my_account_from_wc_page'], 4);
        add_action('template_redirect', [$this, 'force_uncached_login_page_for_members'], 0);
        add_action('wp', [$this, 'boot_logged_in_login_page_panel'], 20);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_login_edit_profile_assets'], 25);

        add_action('woocommerce_account_dashboard', [$this, 'render_custom_dashboard_intro'], 1);
    }

    /**
     * Whether the current request is the public /login/ page.
     *
     * @return bool
     */
    public static function is_login_page_request() {
        if (function_exists('is_page') && is_page('login')) {
            return true;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || '' === $path) {
            return false;
        }

        return (bool) preg_match('#/login/?$#i', $path);
    }

    /**
     * Never serve a cached guest copy of /login/ to a logged-in member.
     *
     * @return void
     */
    public function force_uncached_login_page_for_members() {
        if (!self::is_login_page_request() || !is_user_logged_in()) {
            return;
        }

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        if (has_action('litespeed_control_set_nocache')) {
            do_action('litespeed_control_set_nocache', 'tpsp-login-student-profile');
        }
    }

    /**
     * On /login/, show the student profile panel instead of the login form when already signed in.
     *
     * @return void
     */
    public function boot_logged_in_login_page_panel() {
        if (!is_user_logged_in() || !self::is_login_page_request()) {
            return;
        }

        add_action('wp_head', [$this, 'hide_login_form_when_logged_in_on_login_page'], 1);
        add_action('wp_body_open', [$this, 'print_login_page_loading_placeholder'], 1);
        add_filter('the_content', [$this, 'replace_login_content_for_logged_in_users'], 999);
        add_action('woocommerce_before_main_content', [$this, 'prepend_login_student_panel'], 4);
        add_action('astra_content_before', [$this, 'prepend_login_student_panel'], 4);
        add_action('elementor/theme/before_do_content', [$this, 'prepend_login_student_panel'], 4);

        if (did_action('elementor/loaded')) {
            add_filter('elementor/frontend/the_content', [$this, 'replace_login_content_for_logged_in_users'], 999);
        }
    }

    /**
     * True on /login/?tpsp_edit_profile=1 or WooCommerce my-profile endpoint.
     *
     * @return bool
     */
    private function is_edit_profile_context() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view switch.
        if (self::is_login_page_request() && !empty($_GET['tpsp_edit_profile'])) {
            return true;
        }

        if (function_exists('is_account_page') && is_account_page()) {
            $value = get_query_var('my-profile', false);
            if ($value !== false && $value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * User Registration scripts/styles for edit profile (login page + my-account/my-profile/).
     *
     * @return void
     */
    public function maybe_enqueue_login_edit_profile_assets() {
        if (!is_user_logged_in() || !$this->is_edit_profile_context()) {
            return;
        }

        if (function_exists('tpsp_ensure_user_registration_form_id')) {
            tpsp_ensure_user_registration_form_id();
        }

        $user_id = get_current_user_id();
        $form_id = function_exists('ur_get_form_id_by_userid') ? (int) ur_get_form_id_by_userid($user_id) : 0;
        if ($form_id < 1 && function_exists('tpsp_get_default_ur_form_id')) {
            $form_id = (int) tpsp_get_default_ur_form_id();
        }

        if ($form_id > 0) {
            do_action('user_registration_my_account_enqueue_scripts', [], $form_id);
            do_action('user_registration_enqueue_scripts', [], $form_id);
        }

        if (defined('UR_PLUGIN_URL') && defined('UR_VERSION')) {
            wp_enqueue_style('user-registration-general', UR_PLUGIN_URL . '/assets/css/user-registration.css', [], UR_VERSION);
        }
    }

    /**
     * Fallback when Elementor/theme skips the_content (panel prints once).
     *
     * @return void
     */
    public function prepend_login_student_panel() {
        if (self::$login_panel_injected || self::$rendering_login_panel) {
            return;
        }
        if (!is_user_logged_in() || !self::is_login_page_request()) {
            return;
        }

        $panel = $this->render_login_student_panel_shortcode();
        if (!is_string($panel) || '' === $panel) {
            return;
        }

        self::$login_panel_injected = true;
        echo '<div class="tpsp-login-page-panel-wrap">' . $panel . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Brief loading line while the student panel is fetched (logged-in members only).
     *
     * @return void
     */
    public function print_login_page_loading_placeholder() {
        if (!self::is_login_page_request()) {
            return;
        }

        $has_auth_cookie = false;
        foreach ($_COOKIE as $name => $value) {
            if (0 === strpos((string) $name, 'wordpress_logged_in')) {
                $has_auth_cookie = true;
                break;
            }
        }

        if (!$has_auth_cookie && !is_user_logged_in()) {
            return;
        }

        echo '<div class="tpsp-login-loading-placeholder" aria-live="polite">' . esc_html__('Loading your account…', 'tpsp') . '</div>';
    }

    /**
     * Hide UR/WC login forms when the student panel is shown on /login/.
     *
     * @return void
     */
    public function hide_login_form_when_logged_in_on_login_page() {
        if (!is_user_logged_in() || !self::is_login_page_request()) {
            return;
        }
        echo '<style id="tpsp-hide-login-form-when-logedin">'
            . 'body.logged-in .user-registration-form-login,'
            . 'body.logged-in form.login,'
            . 'body.logged-in .woocommerce-form-login,'
            . 'body.logged-in .woocommerce-form-register,'
            . 'body.tpsp-show-student-panel .user-registration-form-login,'
            . 'body.tpsp-show-student-panel form.login,'
            . 'body.tpsp-show-student-panel .woocommerce-form-login,'
            . 'body.tpsp-show-student-panel .woocommerce-form-register,'
            . 'body.logged-in .elementor-widget-shortcode .user-registration-form-login,'
            . 'body.tpsp-show-student-panel .elementor-widget-shortcode .user-registration-form-login{display:none!important;visibility:hidden!important;}'
            . 'body.logged-in .tpsp-login-dashboard--edit-profile .ur-frontend-form,'
            . 'body.tpsp-show-student-panel .tpsp-login-dashboard--edit-profile .ur-frontend-form,'
            . 'body.logged-in .tpsp-ur-edit-profile-wrap .ur-frontend-form,'
            . 'body.tpsp-show-student-panel .tpsp-ur-edit-profile-wrap .ur-frontend-form{display:block!important;visibility:visible!important;}'
            . '</style>' . "\n";
    }

    /**
     * User Registration “My Account” shows a separate profile UI; force WooCommerce account (TPSP yellow dashboard).
     *
     * @return void
     */
    public function maybe_redirect_away_from_user_registration_my_account() {
        if (!apply_filters('tpsp_redirect_ur_my_account_to_woocommerce', true)) {
            return;
        }
        if (!is_user_logged_in() || !function_exists('ur_get_page_id') || !function_exists('wc_get_page_permalink')) {
            return;
        }

        $ur_page_id = (int) ur_get_page_id('myaccount');
        $wc_page_id = (int) get_option('woocommerce_myaccount_page_id');
        if ($ur_page_id <= 0 || $wc_page_id <= 0 || $ur_page_id === $wc_page_id) {
            return;
        }

        if (!is_page($ur_page_id)) {
            return;
        }

        wp_safe_redirect(tpsp_get_login_page_url());
        exit;
    }

    /**
     * After UR login, send members to WooCommerce My Account (same as TPSP portal).
     *
     * @param string  $redirect Redirect URL from UR.
     * @param WP_User $user     User.
     * @return string
     */
    public function user_registration_login_redirect_to_wc_account($redirect, $user) {
        unset($user);
        if (!apply_filters('tpsp_redirect_ur_my_account_to_woocommerce', true)) {
            return $redirect;
        }

        return tpsp_get_login_page_url();
    }

    /**
     * Send students to /login/ instead of the default WooCommerce account dashboard.
     *
     * @return void
     */
    public function redirect_wc_account_dashboard_to_login() {
        if (!is_user_logged_in() || !function_exists('is_account_page') || !is_account_page()) {
            return;
        }

        if (current_user_can('manage_options')) {
            return;
        }

        $allowed_endpoints = apply_filters('tpsp_my_account_allowed_endpoints', [
            'orders',
            'my-courses',
            'my-profile',
            'view-order',
            'edit-account',
        ]);

        foreach ($allowed_endpoints as $endpoint) {
            $value = get_query_var($endpoint, false);
            if ($value !== false && $value !== '') {
                return;
            }
        }

        wp_safe_redirect(tpsp_get_login_page_url());
        exit;
    }

    /**
     * WooCommerce login form redirect.
     *
     * @param string  $redirect Redirect URL.
     * @param WP_User $user     User.
     * @return string
     */
    public function woocommerce_login_redirect_to_login_page($redirect, $user) {
        if (!($user instanceof WP_User) || user_can($user, 'manage_options')) {
            return $redirect;
        }

        return tpsp_normalize_student_login_redirect($redirect);
    }

    /**
     * If WooCommerce My Account page also embeds [user_registration_my_account], remove it so only WC/TPSP renders.
     *
     * @param string $content Post content.
     * @return string
     */
    public function strip_user_registration_my_account_from_wc_page($content) {
        if (!apply_filters('tpsp_strip_ur_my_account_shortcode_on_wc_page', true)) {
            return $content;
        }
        if (!is_user_logged_in() || !function_exists('is_account_page') || !is_account_page()) {
            return $content;
        }

        $wc_id = (int) get_option('woocommerce_myaccount_page_id');
        if ($wc_id <= 0) {
            return $content;
        }

        global $post;
        if (!$post instanceof WP_Post || (int) $post->ID !== $wc_id) {
            return $content;
        }

        $content = (string) preg_replace('/\[\s*user_registration_my_account[^\]]*\]/i', '', $content);
        $content = (string) preg_replace('/<!--\s*wp:user-registration\/myaccount[^>]*-->/', '', $content);

        return $content;
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
            'dashboard'       => __('Dashboard', 'tpsp'),
            'my-courses'      => __('My Courses', 'tpsp'),
            'orders'          => __('Orders', 'tpsp'),
            'customer-logout' => __('Logout', 'tpsp'),
        ];
    }

    /**
     * Remove referral/notification tabs added by other plugins.
     *
     * @param array $items Menu items.
     * @return array
     */
    public function strip_extra_account_tabs($items) {
        unset(
            $items['referrals'],
            $items['affiliate-referrals'],
            $items['notifications'],
            $items['my-profile']
        );

        return $items;
    }

    /**
     * Render profile page.
     *
     * @return void
     */
    public function render_profile_endpoint() {
        if (function_exists('tpsp_ensure_user_registration_form_id')) {
            tpsp_ensure_user_registration_form_id();
        }
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
     * Safe wc_get_orders — never throws into the shortcode output.
     *
     * @param array $args Query args.
     * @return array<int,\WC_Order>
     */
    private function tpsp_safe_wc_get_orders(array $args) {
        if (!function_exists('wc_get_orders')) {
            return [];
        }
        try {
            $out = wc_get_orders($args);
            return is_array($out) ? $out : [];
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('TPSP login panel wc_get_orders: ' . $e->getMessage());
            }
            if (class_exists('TPSP_Logger', false)) {
                TPSP_Logger::log('login_student_panel wc_get_orders: ' . $e->getMessage(), 'warning');
            }

            return [];
        }
    }

    /**
     * @return array<int,string>
     */
    private function tpsp_all_order_status_slugs() {
        if (!function_exists('wc_get_order_statuses')) {
            return ['wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending', 'wc-failed', 'wc-cancelled', 'wc-refunded'];
        }
        $keys = array_keys(wc_get_order_statuses());

        return !empty($keys) ? $keys : ['wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending'];
    }

    /**
     * Logged-in student view for login page.
     *
     * @return string
     */
    public function render_login_student_panel_shortcode() {
        if (!is_user_logged_in()) {
            if (function_exists('shortcode_exists') && shortcode_exists('woocommerce_my_account')) {
                return do_shortcode('[woocommerce_my_account]');
            }

            return '<p>' . esc_html__('Please log in.', 'tpsp') . '</p>';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view switch.
        if (!empty($_GET['tpsp_edit_profile'])) {
            $tpl = TPSP_PLUGIN_DIR . 'templates/section-login-edit-profile.php';
            if (is_readable($tpl)) {
                ob_start();
                include $tpl;

                return (string) ob_get_clean();
            }
        }

        if (!function_exists('wc_get_orders') || !class_exists('WC_Customer', false)) {
            $user = wp_get_current_user();
            $out  = '<div class="tpsp-login-dashboard"><p class="tpsp-login-dashboard__muted">';
            $out .= esc_html(sprintf(
                /* translators: %s: username */
                __('You are signed in as %s. WooCommerce is not available to show the full student panel.', 'tpsp'),
                $user->user_login
            ));
            $out .= '</p><p><a class="button button-primary" href="' . esc_url(admin_url()) . '">' . esc_html__('Dashboard', 'tpsp') . '</a> ';
            $out .= '<a class="button" href="' . esc_url(wp_logout_url(home_url('/login/'))) . '">' . esc_html__('Log out', 'tpsp') . '</a></p></div>';

            return $out;
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();

        if (function_exists('ttp_link_guest_orders_to_user_by_email')) {
            ttp_link_guest_orders_to_user_by_email($user_id);
        }
        if (function_exists('ttp_sync_tcy_enrollments_for_user_on_account_view')) {
            delete_transient('ttp_acct_sync_' . $user_id);
            ttp_sync_tcy_enrollments_for_user_on_account_view($user_id);
        }

        $paid_statuses = function_exists('wc_get_is_paid_statuses') ? wc_get_is_paid_statuses() : ['processing', 'completed', 'on-hold'];
        if (!is_array($paid_statuses) || empty($paid_statuses)) {
            $paid_statuses = ['processing', 'completed', 'on-hold'];
        }

        $order_list = function_exists('ttp_get_qualifying_orders_for_user')
            ? ttp_get_qualifying_orders_for_user($user_id)
            : $this->tpsp_safe_wc_get_orders([
                'customer_id' => $user_id,
                'limit'       => 200,
                'status'      => $paid_statuses,
            ]);

        $orders = array_slice($order_list, 0, 5);

        $billing_html = '';
        try {
            $wc_customer  = new WC_Customer($user_id);
            $billing_html = $wc_customer->get_formatted_billing_address();
            if (!is_string($billing_html) || trim(wp_strip_all_tags($billing_html)) === '') {
                $billing_html = '';
            }
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('TPSP login panel WC_Customer: ' . $e->getMessage());
            }
            if (class_exists('TPSP_Logger', false)) {
                TPSP_Logger::log('login_student_panel WC_Customer: ' . $e->getMessage(), 'warning');
            }
        }

        $edit_account_url = '';
        if (function_exists('wc_get_page_permalink') && function_exists('wc_get_endpoint_url')) {
            try {
                $myaccount = wc_get_page_permalink('myaccount');
                if (is_string($myaccount) && '' !== $myaccount) {
                    $edit_account_url = (string) wc_get_endpoint_url('edit-account', '', $myaccount);
                }
            } catch (\Throwable $e) {
                unset($e);
            }
        }

        $paid_statuses = function_exists('wc_get_is_paid_statuses') ? wc_get_is_paid_statuses() : ['processing', 'completed', 'on-hold'];
        if (!is_array($paid_statuses) || empty($paid_statuses)) {
            $paid_statuses = ['processing', 'completed', 'on-hold'];
        }

        $order_list = $order_list ?? ( function_exists('ttp_get_qualifying_orders_for_user')
            ? ttp_get_qualifying_orders_for_user($user_id)
            : $this->tpsp_safe_wc_get_orders([
                'customer_id' => $user_id,
                'limit'       => 200,
                'status'      => $paid_statuses,
            ]) );

        if (function_exists('ttp_repair_user_names_if_email_for_user')) {
            ttp_repair_user_names_if_email_for_user($user_id);
        }

        $enrolled_courses = function_exists('ttp_collect_enrolled_course_rows_for_user')
            ? ttp_collect_enrolled_course_rows_for_user($user_id)
            : ( function_exists('ttp_get_user_enrolled_courses_for_login_panel')
                ? ttp_get_user_enrolled_courses_for_login_panel($user_id)
                : [] );

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

        $tpl = TPSP_PLUGIN_DIR . 'templates/section-login-student-panel.php';
        if (!is_readable($tpl)) {
            return '<div class="tpsp-login-dashboard"><p>' . esc_html__('Student panel template is missing on the server. Re-upload the plugin.', 'tpsp') . '</p></div>';
        }

        try {
            ob_start();
            include $tpl;

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('TPSP login panel template: ' . $e->getMessage());
            }
            if (class_exists('TPSP_Logger', false)) {
                TPSP_Logger::log('login_student_panel template: ' . $e->getMessage(), 'warning');
            }

            return '<div class="tpsp-login-dashboard"><p class="tpsp-login-dashboard__muted">' . esc_html__(
                'Your session is active, but the student dashboard could not load. Use My Account or try again in a moment.',
                'tpsp'
            ) . '</p><p><a class="button button-primary" href="' . esc_url(function_exists('tpsp_get_account_endpoint_url') ? tpsp_get_account_endpoint_url('dashboard') : home_url('/my-account/')) . '">' . esc_html__('My account', 'tpsp') . '</a> '
            . '<a class="button" href="' . esc_url(wp_logout_url(home_url('/login/'))) . '">' . esc_html__('Log out', 'tpsp') . '</a></p></div>';
        }
    }

    /**
     * Replace /login page content with student panel when logged in.
     *
     * @param string $content Original content.
     * @return string
     */
    public function replace_login_content_for_logged_in_users($content) {
        if (self::$rendering_login_panel || self::$login_panel_injected) {
            return $content;
        }
        if (!is_user_logged_in() || !self::is_login_page_request() || is_admin() || wp_doing_ajax()) {
            return $content;
        }
        if (is_preview() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return $content;
        }

        $login_page_id = (int) get_queried_object_id();
        if ($login_page_id < 1) {
            return $content;
        }

        $current_id = (int) get_the_ID();
        if ($current_id > 0 && $current_id !== $login_page_id) {
            return $content;
        }

        if (!in_the_loop() && !doing_filter('elementor/frontend/the_content')) {
            return $content;
        }

        self::$rendering_login_panel = true;
        $panel = $this->render_login_student_panel_shortcode();
        self::$rendering_login_panel = false;

        if (!is_string($panel) || '' === $panel) {
            return $content;
        }

        self::$login_panel_injected = true;

        return $panel;
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

        $paid_statuses = function_exists('wc_get_is_paid_statuses') ? wc_get_is_paid_statuses() : ['processing', 'completed'];
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit'       => -1,
            'status'      => $paid_statuses,
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

        if (!isset($_POST['tpsp_profile_submit']) && !isset($_POST['tpsp_avatar_submit'])) {
            return;
        }

        if (!isset($_POST['tpsp_profile_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tpsp_profile_nonce'])), 'tpsp_profile_update')) {
            wc_add_notice(__('Security check failed. Please try again.', 'tpsp'), 'error');
            return;
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();

        if (!isset($_POST['tpsp_avatar_submit'])) {
            $display_name = isset($_POST['display_name']) ? sanitize_text_field(wp_unslash($_POST['display_name'])) : $user->display_name;
            if (function_exists('ttp_sanitize_person_name_value')) {
                $display_name = ttp_sanitize_person_name_value($display_name);
            }
            if ($display_name === '' || is_email($display_name)) {
                wc_add_notice(__('Please enter your real name (not an email address).', 'tpsp'), 'error');
                return;
            }

            wp_update_user([
                'ID'           => $user_id,
                'display_name' => $display_name,
                'first_name'   => $display_name,
                'user_email'   => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : $user->user_email,
            ]);
            update_user_meta($user_id, 'first_name', $display_name);
            update_user_meta($user_id, 'billing_first_name', $display_name);

            if (function_exists('ttp_resolve_customer_full_name')) {
                global $wpdb;
                $full_name = ttp_resolve_customer_full_name($user_id);
                if ($full_name !== '') {
                    $wpdb->update(
                        $wpdb->prefix . 'ttp_students',
                        ['full_name' => $full_name],
                        ['wp_user_id' => $user_id]
                    );
                }
            }
        }

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

        if (!isset($_POST['tpsp_avatar_submit'])) {
            foreach ($meta_fields as $meta_key => $field_key) {
                $value = isset($_POST[$field_key]) ? sanitize_text_field(wp_unslash($_POST[$field_key])) : '';
                update_user_meta($user_id, $meta_key, $value);
            }
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

        $redirect = !empty($_POST['tpsp_from_edit_profile']) && function_exists('tpsp_get_edit_profile_url')
            ? tpsp_get_edit_profile_url()
            : (!empty($_POST['tpsp_from_login']) || !empty($_POST['tpsp_from_edit_profile'])
                ? (function_exists('tpsp_get_login_page_url') ? tpsp_get_login_page_url() : home_url('/login/'))
                : wc_get_account_endpoint_url('my-profile'));

        wp_safe_redirect($redirect);
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

        // Honor checkout / exam flow; query param can be more reliable than the default $redirect.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only routing URL.
        if (!empty($_GET['redirect_to'])) {
            $explicit = wp_validate_redirect(wp_unslash((string) $_GET['redirect_to']), '');
            if (!empty($explicit)) {
                return tpsp_normalize_student_login_redirect($explicit);
            }
        }

        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        return tpsp_normalize_student_login_redirect($redirect);
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
