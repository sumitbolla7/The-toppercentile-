<?php
/**
 * WooCommerce-related functionality.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_WooCommerce {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        add_action('template_redirect', [$this, 'restrict_unverified_access'], 5);
        add_action('template_redirect', [$this, 'redirect_guest_checkout_to_login'], 6);
        add_action('template_redirect', [$this, 'redirect_guest_purchase_attempts_to_login'], 7);
        add_action('template_redirect', [$this, 'redirect_coupon_endpoint_to_courses'], 8);
        add_action('template_redirect', [$this, 'redirect_guest_purchase_404_to_login'], 9);
        add_filter('woocommerce_account_menu_items', [$this, 'remove_coupon_menu_items'], 999);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'restrict_unpurchased_course_access'], 10, 3);
        add_filter('woocommerce_registration_generate_username', '__return_false');
        add_filter('woocommerce_registration_generate_password', '__return_false');
        add_filter('woocommerce_is_sold_individually', [$this, 'force_single_quantity_for_courses'], 10, 2);
        add_filter('woocommerce_quantity_input_args', [$this, 'disable_quantity_input_for_courses'], 10, 2);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'safe_add_to_cart_redirect']);
        add_filter('woocommerce_get_checkout_url', [$this, 'normalize_checkout_url'], 20);
        add_filter('woocommerce_get_availability_text', [$this, 'custom_availability_text'], 10, 2);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'replace_loop_cta_for_purchased_courses'], 20, 3);
        add_action('wp', [$this, 'override_single_product_cta_for_purchased_courses']);
        add_action('init', [$this, 'ensure_wc_pages_assigned'], 20);
        add_filter('woocommerce_session_expiration', [$this, 'extend_session_expiration']);
        add_filter('woocommerce_session_expiring', [$this, 'extend_session_expiring']);
        add_filter('woocommerce_cart_item_quantity', [$this, 'lock_cart_quantity_display'], 10, 3);
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields'], 20);
        add_filter('body_class', [$this, 'add_page_body_classes']);
        add_action('woocommerce_before_cart', [$this, 'render_cart_notice']);
        add_action('woocommerce_before_checkout_form', [$this, 'render_checkout_notice']);
        add_action('woocommerce_thankyou', [$this, 'render_order_confirmation_message']);
        add_action('template_redirect', [$this, 'repair_rewrites_for_critical_pages'], 1);
        add_action('wp', [$this, 'maybe_replace_add_to_cart_with_cart_link'], 99);
    }

    /**
     * On single product, if this simple course is already in cart, show cart link instead of add-to-cart form (no duplicate notices).
     *
     * @return void
     */
    public function maybe_replace_add_to_cart_with_cart_link() {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        global $product;
        if (!$product instanceof WC_Product || !$product->is_type('simple')) {
            return;
        }

        if (!$this->is_course_product($product)) {
            return;
        }

        $pid = (int) $product->get_id();
        $found = false;
        foreach (WC()->cart->get_cart() as $item) {
            if ((int) ($item['product_id'] ?? 0) === $pid) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return;
        }

        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        add_action('woocommerce_single_product_summary', [$this, 'render_cart_link_instead_of_add_to_cart'], 30);
    }

    /**
     * Output primary CTA linking to the cart when product is already in cart.
     *
     * @return void
     */
    public function render_cart_link_instead_of_add_to_cart() {
        $url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
        echo '<div class="tpsp-in-cart-wrap">';
        echo '<a href="' . esc_url($url) . '" class="button alt single_add_to_cart_button tpsp-already-in-cart-btn">';
        esc_html_e('Already in cart — View cart', 'tpsp');
        echo '</a></div>';
    }

    /**
     * Restrict dashboard and account access for unverified users.
     *
     * @return void
     */
    public function restrict_unverified_access() {
        if (!is_user_logged_in()) {
            return;
        }

        $settings = get_option('tpsp_settings', []);
        if (isset($settings['require_email_verification']) && 'yes' !== $settings['require_email_verification']) {
            return;
        }

        $user_id  = get_current_user_id();
        $verified = (int) get_user_meta($user_id, 'tpsp_email_verified', true);

        if (1 === $verified) {
            return;
        }

        if (is_account_page()) {
            $this->add_single_unverified_notice();
            return;
        }

        // Keep storefront purchasable; only protect account/dashboard for unverified users.
        if (is_page('student-dashboard')) {
            $this->add_single_unverified_notice();
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }

    /**
     * Optional course access restriction.
     *
     * @param bool $passed     Validation state.
     * @param int  $product_id Product ID.
     * @param int  $quantity   Quantity.
     * @return bool
     */
    public function restrict_unpurchased_course_access($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return $passed;
        }

        // If a course is already in the cart, block duplicate add without notices (UI shows "Already in cart" on product page).
        if ($this->is_course_product($product) && function_exists('WC') && WC()->cart) {
            $cart_item_key = WC()->cart->find_product_in_cart(WC()->cart->generate_cart_id($product_id));
            if (!empty($cart_item_key)) {
                return false;
            }
        }

        $settings = get_option('tpsp_settings', []);
        if (!isset($settings['restrict_course_access']) || 'yes' !== $settings['restrict_course_access']) {
            return $passed;
        }

        // Guests must log in before purchasing courses.
        if ($this->is_course_product($product) && !is_user_logged_in()) {
            wc_add_notice(__('Please log in to your account to purchase this course.', 'tpsp'), 'notice');
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('tpsp_force_login_redirect', 1);
            }
            return false;
        }

        // If course already purchased, avoid duplicate enrollments.
        if ($this->is_course_product($product) && wc_customer_bought_product('', get_current_user_id(), $product_id)) {
            wc_add_notice(__('You are already enrolled in this course.', 'tpsp'), 'notice');
            return false;
        }

        return $passed;
    }

    /**
     * Treat virtual products as single quantity.
     *
     * @param bool       $sold_individually Existing value.
     * @param WC_Product $product           Product object.
     * @return bool
     */
    public function force_single_quantity_for_courses($sold_individually, $product) {
        if ($product && $product->is_virtual()) {
            return true;
        }

        return $sold_individually;
    }

    /**
     * Disable qty edits for courses.
     *
     * @param array      $args    Qty args.
     * @param WC_Product $product Product.
     * @return array
     */
    public function disable_quantity_input_for_courses($args, $product) {
        if ($product && $product->is_virtual()) {
            $args['min_value']   = 1;
            $args['max_value']   = 1;
            $args['input_value'] = 1;
        }
        return $args;
    }

    /**
     * Force add to cart redirect destination.
     *
     * @param string $url Current redirect.
     * @return string
     */
    public function safe_add_to_cart_redirect($url) {
        if (function_exists('WC') && WC()->session && WC()->session->get('tpsp_force_login_redirect')) {
            WC()->session->__unset('tpsp_force_login_redirect');
            return $this->get_login_page_url();
        }

        $settings = get_option('tpsp_settings', []);
        if (isset($settings['cart_force_ajax']) && 'yes' === $settings['cart_force_ajax']) {
            return wc_get_cart_url();
        }
        return $url;
    }

    /**
     * Clearer messaging for virtual courses.
     *
     * @param string     $availability Product availability text.
     * @param WC_Product $product      Product object.
     * @return string
     */
    public function custom_availability_text($availability, $product) {
        if ($product && $product->is_virtual()) {
            return __('Instant access after purchase', 'tpsp');
        }
        return $availability;
    }

    /**
     * Ensure WC page options remain valid.
     *
     * @return void
     */
    public function ensure_wc_pages_assigned() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $keys = [
            'woocommerce_cart_page_id'      => 'cart',
            'woocommerce_checkout_page_id'  => 'checkout',
            'woocommerce_myaccount_page_id' => 'my-account',
        ];

        foreach ($keys as $option_key => $slug) {
            $page_id = (int) get_option($option_key);
            if ($page_id > 0 && get_post_status($page_id)) {
                continue;
            }

            $page = get_page_by_path($slug);
            if ($page instanceof WP_Post) {
                update_option($option_key, (int) $page->ID);
                continue;
            }

            TPSP_Logger::log('Missing WooCommerce page assignment', 'warning', ['option' => $option_key, 'slug' => $slug]);
        }
    }

    /**
     * Keep session alive longer for course sales.
     *
     * @param int $seconds Current value.
     * @return int
     */
    public function extend_session_expiration($seconds) {
        return 10 * DAY_IN_SECONDS;
    }

    /**
     * Keep session refresh threshold longer.
     *
     * @param int $seconds Current value.
     * @return int
     */
    public function extend_session_expiring($seconds) {
        return 9 * DAY_IN_SECONDS;
    }

    /**
     * Body classes for premium theme styling.
     *
     * @param array $classes Existing classes.
     * @return array
     */
    public function add_page_body_classes($classes) {
        if (is_account_page() || is_cart() || is_checkout() || is_page('student-dashboard')) {
            $classes[] = 'tpsp-premium-theme';
        }
        return $classes;
    }

    /**
     * Render plus/minus quantity controls.
     *
     * @param string $product_quantity Existing HTML.
     * @param string $cart_item_key    Item key.
     * @param array  $cart_item        Cart item data.
     * @return string
     */
    public function lock_cart_quantity_display($product_quantity, $cart_item_key, $cart_item) {
        $product = isset($cart_item['data']) ? $cart_item['data'] : null;
        $qty     = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

        if ($product instanceof WC_Product && $product->is_virtual()) {
            return '<span class="tpsp-qty-locked">1</span>';
        }

        // For this education store we lock quantity to avoid checkout issues.
        return '<span class="tpsp-qty-locked">' . esc_html((string) max(1, $qty)) . '</span>';
    }

    /**
     * Remove unwanted checkout fields.
     *
     * @param array $fields Checkout fields.
     * @return array
     */
    public function customize_checkout_fields($fields) {
        // Education courses are virtual; hide address sections to avoid checkout friction and routing issues.
        if (isset($fields['shipping'])) {
            unset($fields['shipping']);
        }

        if (!isset($fields['billing'])) {
            return $fields;
        }

        $billing_unset = [
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
        ];

        foreach ($billing_unset as $key) {
            if (isset($fields['billing'][$key])) {
                unset($fields['billing'][$key]);
            }
        }

        return $fields;
    }

    /**
     * Cart trust/security notice.
     *
     * @return void
     */
    public function render_cart_notice() {
        echo '<div class="tpsp-secure-note"><i class="fa-solid fa-shield-halved"></i> ' . esc_html__('Secure checkout, encrypted payment, and instant enrollment delivery.', 'tpsp') . '</div>';
    }

    /**
     * Checkout trust/security notice.
     *
     * @return void
     */
    public function render_checkout_notice() {
        echo '<div class="tpsp-secure-note"><i class="fa-solid fa-lock"></i> ' . esc_html__('Your payment and personal data are protected with industry-standard encryption.', 'tpsp') . '</div>';
    }

    /**
     * Better thank-you messaging.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function render_order_confirmation_message($order_id) {
        echo '<div class="tpsp-thankyou"><h3>' . esc_html__('Enrollment Confirmed!', 'tpsp') . '</h3><p>' . esc_html__('Your course/test access has been activated. Visit your dashboard to begin learning.', 'tpsp') . '</p></div>';
        TPSP_Logger::log('Order completed with TPSP confirmation UI', 'info', ['order_id' => (int) $order_id]);
    }

    /**
     * Refresh rewrite rules once when critical pages 404.
     *
     * @return void
     */
    public function repair_rewrites_for_critical_pages() {
        if (!is_404()) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (false === strpos($request_uri, '/cart') && false === strpos($request_uri, '/checkout') && false === strpos($request_uri, 'add-to-cart')) {
            return;
        }

        if (get_transient('tpsp_rewrite_flushed_recently')) {
            return;
        }

        flush_rewrite_rules(false);
        set_transient('tpsp_rewrite_flushed_recently', 1, HOUR_IN_SECONDS);
        TPSP_Logger::log('Triggered rewrite flush due to cart/checkout 404', 'warning', ['uri' => $request_uri]);
    }

    /**
     * Redirect guests trying to checkout course products to login page.
     *
     * @return void
     */
    public function redirect_guest_checkout_to_login() {
        if (is_user_logged_in() || !function_exists('is_checkout') || !is_checkout() || is_wc_endpoint_url('order-received')) {
            return;
        }

        if (!function_exists('WC') || !WC()->cart) {
            return;
        }

        $has_virtual_course = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if ($product instanceof WC_Product && $this->is_course_product($product)) {
                $has_virtual_course = true;
                break;
            }
        }

        if (!$has_virtual_course) {
            return;
        }

        wc_add_notice(__('Please log in to continue course checkout.', 'tpsp'), 'notice');
        wp_safe_redirect($this->get_login_page_url());
        exit;
    }

    /**
     * Redirect guests when purchase is attempted via URL actions.
     *
     * @return void
     */
    public function redirect_guest_purchase_attempts_to_login() {
        if (is_user_logged_in()) {
            return;
        }

        $request_product_id = 0;
        if (isset($_REQUEST['add-to-cart'])) {
            $request_product_id = absint(wp_unslash($_REQUEST['add-to-cart']));
        } elseif (isset($_REQUEST['buy-now'])) {
            $request_product_id = absint(wp_unslash($_REQUEST['buy-now']));
        } elseif (isset($_REQUEST['product_id'])) {
            $request_product_id = absint(wp_unslash($_REQUEST['product_id']));
        }

        if ($request_product_id <= 0) {
            return;
        }

        $product = wc_get_product($request_product_id);
        if (!$product instanceof WC_Product || !$this->is_course_product($product)) {
            return;
        }

        wc_add_notice(__('Please log in to your account to purchase this course.', 'tpsp'), 'notice');
        wp_safe_redirect($this->get_login_page_url());
        exit;
    }

    /**
     * Keep account clean by hiding coupon endpoint access.
     *
     * @return void
     */
    public function redirect_coupon_endpoint_to_courses() {
        if (!is_user_logged_in() || !function_exists('is_account_page') || !is_account_page()) {
            return;
        }

        $has_coupon_query = isset($_GET['smart-coupon']) || isset($_GET['my-coupons']) || isset($_GET['my-coupon']);
        $is_coupon_endpoint = function_exists('is_wc_endpoint_url')
            && (is_wc_endpoint_url('smart-coupon') || is_wc_endpoint_url('my-coupons') || is_wc_endpoint_url('my-coupon'));

        if (!$has_coupon_query && !$is_coupon_endpoint) {
            return;
        }

        wp_safe_redirect(tpsp_get_account_endpoint_url('my-courses'));
        exit;
    }

    /**
     * Remove coupon endpoints injected by external plugins.
     *
     * @param array $items Account menu items.
     * @return array
     */
    public function remove_coupon_menu_items($items) {
        $coupon_keys = ['smart-coupon', 'smart-coupons', 'my-coupon', 'my-coupons', 'coupons', 'coupon'];
        foreach ($coupon_keys as $key) {
            if (isset($items[$key])) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Ensure checkout button always targets checkout page URL.
     *
     * @param string $url Checkout URL.
     * @return string
     */
    public function normalize_checkout_url($url) {
        $checkout_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('checkout') : '';
        if (!empty($checkout_url)) {
            return $checkout_url;
        }

        return $url;
    }

    /**
     * Redirect 404 purchase requests to login for guests.
     *
     * @return void
     */
    public function redirect_guest_purchase_404_to_login() {
        if (is_user_logged_in() || !is_404()) {
            return;
        }

        $has_purchase_params = isset($_REQUEST['add-to-cart']) || isset($_REQUEST['buy-now']) || isset($_REQUEST['product_id']);
        if (!$has_purchase_params) {
            return;
        }

        wc_add_notice(__('Please log in to your account to purchase this course.', 'tpsp'), 'notice');
        wp_safe_redirect($this->get_login_page_url());
        exit;
    }

    /**
     * Replace loop add-to-cart CTA with My Courses link when already purchased.
     *
     * @param string     $link    Existing HTML.
     * @param WC_Product $product Product object.
     * @return string
     */
    public function replace_loop_cta_for_purchased_courses($link, $product) {
        if (!($product instanceof WC_Product) || !$this->is_course_product($product) || !is_user_logged_in()) {
            return $link;
        }

        if (!wc_customer_bought_product('', get_current_user_id(), $product->get_id())) {
            return $link;
        }

        return '<a class="button tpsp-view-courses-btn" href="' . esc_url(tpsp_get_account_endpoint_url('my-courses')) . '">' . esc_html__('View Courses', 'tpsp') . '</a>';
    }

    /**
     * Replace single product CTA for already-purchased courses.
     *
     * @return void
     */
    public function override_single_product_cta_for_purchased_courses() {
        if (!is_product() || !is_user_logged_in()) {
            return;
        }

        global $product;
        if (!($product instanceof WC_Product) || !$this->is_course_product($product)) {
            return;
        }

        if (!wc_customer_bought_product('', get_current_user_id(), $product->get_id())) {
            return;
        }

        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
        add_action('woocommerce_single_product_summary', [$this, 'render_view_courses_single_cta'], 30);
    }

    /**
     * Render View Courses CTA on purchased product.
     *
     * @return void
     */
    public function render_view_courses_single_cta() {
        echo '<a class="button alt tpsp-view-courses-btn" href="' . esc_url(tpsp_get_account_endpoint_url('my-courses')) . '">' . esc_html__('View Courses', 'tpsp') . '</a>';
    }

    /**
     * Determine whether a product should be treated as a course.
     *
     * @param WC_Product $product Product object.
     * @return bool
     */
    private function is_course_product($product) {
        if (!($product instanceof WC_Product)) {
            return false;
        }

        if ($product->is_virtual() || $product->is_downloadable()) {
            return true;
        }

        $product_id = (int) $product->get_id();
        if ($product_id <= 0) {
            return false;
        }

        $course_terms = ['course', 'courses', 'test-series', 'tests', 'exam'];
        if (has_term($course_terms, 'product_cat', $product_id) || has_term($course_terms, 'product_tag', $product_id)) {
            return true;
        }

        return true;
    }

    /**
     * Resolve account login page URL.
     *
     * @return string
     */
    private function get_login_page_url() {
        return $this->get_custom_login_url();
    }

    /**
     * Resolve dedicated public login page URL.
     *
     * @return string
     */
    private function get_custom_login_url() {
        $login_page = get_page_by_path('login');
        if ($login_page instanceof WP_Post) {
            $url = get_permalink($login_page);
            if (!empty($url)) {
                return $url;
            }
        }

        return home_url('/login/');
    }

    /**
     * Add unverified notice once to avoid duplicate stacking.
     *
     * @return void
     */
    private function add_single_unverified_notice() {
        if (!function_exists('WC') || !WC()->session || !function_exists('wc_add_notice')) {
            return;
        }

        $shown_at = (int) WC()->session->get('tpsp_unverified_notice_shown_at');
        if ($shown_at > 0 && (time() - $shown_at) < 60) {
            return;
        }

        wc_clear_notices();
        wc_add_notice(__('Please verify your email to access your student dashboard and course content.', 'tpsp'), 'notice');
        WC()->session->set('tpsp_unverified_notice_shown_at', time());
    }
}
