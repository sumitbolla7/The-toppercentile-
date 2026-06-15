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
        add_action('template_redirect', [$this, 'force_guest_account_path_to_login'], 3);
        add_action('template_redirect', [$this, 'redirect_unpaid_order_received_to_checkout'], 2);
        add_action('template_redirect', [$this, 'restrict_unverified_access'], 5);
        add_action('template_redirect', [$this, 'redirect_logged_out_my_account_to_login'], 6);
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
        add_filter('woocommerce_get_checkout_url', [$this, 'guest_cart_use_login_with_checkout_redirect'], 30);
        add_filter('woocommerce_get_availability_text', [$this, 'custom_availability_text'], 10, 2);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, 'replace_loop_cta_for_purchased_courses'], 20, 3);
        add_action('wp', [$this, 'override_single_product_cta_for_purchased_courses']);
        add_action('init', [$this, 'ensure_wc_pages_assigned'], 20);
        add_filter('woocommerce_session_expiration', [$this, 'extend_session_expiration']);
        add_filter('woocommerce_session_expiring', [$this, 'extend_session_expiring']);
        add_filter('woocommerce_cart_item_quantity', [$this, 'lock_cart_quantity_display'], 10, 3);
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields'], 20);
        add_filter('body_class', [$this, 'add_page_body_classes']);
        add_filter('woocommerce_logout_default_redirect_url', [$this, 'force_logout_redirect_to_login'], 20, 1);
        add_action('woocommerce_before_cart', [$this, 'render_cart_notice']);
        add_action('woocommerce_before_checkout_form', [$this, 'render_checkout_notice']);
        add_action('woocommerce_thankyou', [$this, 'render_order_confirmation_message']);
        add_action('woocommerce_thankyou', [$this, 'clear_cart_after_successful_order'], 1);
        add_action('woocommerce_payment_complete', [$this, 'clear_cart_after_successful_order'], 1);
        add_action('woocommerce_cart_loaded_from_session', [$this, 'remove_already_purchased_courses_from_cart'], 20);
        add_action('template_redirect', [$this, 'repair_rewrites_for_critical_pages'], 1);
        add_action('wp', [$this, 'maybe_replace_add_to_cart_with_cart_link'], 99);
        add_action('wp_loaded', [$this, 'replace_proceed_to_checkout_button'], 99);
        add_action('wp_loaded', [$this, 'simplify_wt_smart_coupon_checkout_order_review_cart_actions'], 100);
        add_filter('woocommerce_checkout_cart_item_quantity', [$this, 'add_checkout_line_item_remove_link'], 20, 3);
    }

    /**
     * WT Smart Coupon checkout cart-actions row: show per-item remove links + Go to cart.
     *
     * @return void
     */
    public function simplify_wt_smart_coupon_checkout_order_review_cart_actions() {
        if (!apply_filters('tpsp_simplify_wt_checkout_cart_actions_row', true)) {
            return;
        }
        if (!class_exists('Wt_Smart_Coupon_Public', false)) {
            return;
        }
        $ref = new ReflectionClass('Wt_Smart_Coupon_Public');
        if (!$ref->hasProperty('instance')) {
            return;
        }
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $public = $prop->getValue(null);
        if (!$public instanceof Wt_Smart_Coupon_Public) {
            return;
        }
        remove_action('woocommerce_review_order_after_cart_contents', [$public, 'render_checkout_remove_item_links'], 20);
        add_action('woocommerce_review_order_after_cart_contents', [$this, 'render_checkout_go_to_cart_row_only'], 20);
    }

    /**
     * Checkout order review: remove one product per line + Go to cart.
     *
     * @return void
     */
    public function render_checkout_go_to_cart_row_only() {
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }
        if (!function_exists('wc_get_cart_url') || !function_exists('wc_get_cart_remove_url')) {
            return;
        }
        ?>
        <tr class="wt_sc_checkout_cart_actions tpsp-checkout-cart-actions">
            <th scope="row"><?php esc_html_e('Cart actions', 'tpsp'); ?></th>
            <td>
                <ul class="wt_sc_checkout_remove_list tpsp-checkout-remove-list">
                    <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) : ?>
                        <?php
                        $product = isset($cart_item['data']) ? $cart_item['data'] : null;
                        $label   = ($product instanceof WC_Product) ? $product->get_name() : __('Item', 'tpsp');
                        ?>
                        <li>
                            <a class="wt_sc_remove_checkout_item tpsp-checkout-remove-item" href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>">
                                <?php
                                echo esc_html(
                                    sprintf(
                                        /* translators: %s: product name */
                                        __('Remove %s', 'tpsp'),
                                        $label
                                    )
                                );
                                ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a class="button wt_sc_go_to_cart_btn" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                    <?php esc_html_e('Go to cart', 'woocommerce'); ?>
                </a>
            </td>
        </tr>
        <?php
    }

    /**
     * Inline remove link beside each checkout line (when order-review table shows quantity).
     *
     * @param string $quantity_html Quantity HTML.
     * @param array  $cart_item     Cart item.
     * @param string $cart_item_key Cart item key.
     * @return string
     */
    public function add_checkout_line_item_remove_link($quantity_html, $cart_item, $cart_item_key) {
        if (!function_exists('is_checkout') || !is_checkout() || (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received'))) {
            return $quantity_html;
        }
        if (!function_exists('wc_get_cart_remove_url')) {
            return $quantity_html;
        }
        $remove = '<a class="tpsp-checkout-remove-item wt_sc_inline_remove_checkout_item" href="'
            . esc_url(wc_get_cart_remove_url($cart_item_key))
            . '">'
            . esc_html__('Remove', 'tpsp')
            . '</a>';

        return $quantity_html . '<br>' . $remove;
    }

    /**
     * Replace WooCommerce default proceed output so a plain checkout URL is always used
     * (avoids disabled / hijacked markup from coupon plugins).
     *
     * @return void
     */
    public function replace_proceed_to_checkout_button() {
        if (!function_exists('WC') || !function_exists('woocommerce_button_proceed_to_checkout')) {
            return;
        }
        if (!apply_filters('tpsp_solo_proceed_to_checkout_button', true)) {
            remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
            add_action('woocommerce_proceed_to_checkout', [$this, 'render_tpsp_proceed_to_checkout_link'], 20);
            return;
        }
        // Strip every other theme/plugin callback so only one CTA prints (avoids duplicate “Continue to payment” / extra buttons).
        remove_all_actions('woocommerce_proceed_to_checkout');
        add_action('woocommerce_proceed_to_checkout', [$this, 'render_tpsp_proceed_to_checkout_link'], 20);
    }

    /**
     * Fresh "Proceed to checkout" anchor — same destination as core, new DOM node.
     *
     * @return void
     */
    public function render_tpsp_proceed_to_checkout_link() {
        if (!function_exists('wc_get_checkout_url')) {
            return;
        }
        $url = wc_get_checkout_url();
        if (empty($url)) {
            return;
        }
        $guest_course = !is_user_logged_in() && $this->cart_has_course_product();
        $label = $guest_course
            ? esc_html__('Log in to continue to checkout', 'tpsp')
            : esc_html__('Proceed to checkout', 'woocommerce');
        // Single CTA: link (themes expect .checkout-button). Secondary GET form removed — it duplicated the cart totals UI.
        echo '<a href="' . esc_url($url) . '" id="tpsp-proceed-checkout" class="checkout-button button alt wc-forward tpsp-proceed-checkout">' . $label . '</a>';
    }

    /**
     * On the cart screen only, send guests with courses to login with redirect_to=real checkout
     * so "Proceed" always leaves /cart/ in one step (avoids theme/JS blocking checkout URL).
     *
     * @param string $url Normalized checkout URL.
     * @return string
     */
    public function guest_cart_use_login_with_checkout_redirect($url) {
        if (!apply_filters('tpsp_guest_cart_use_login_redirect', true)) {
            return $url;
        }
        if (is_user_logged_in()) {
            return $url;
        }
        if (!function_exists('is_cart') || !is_cart()) {
            return $url;
        }
        if (!function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return $url;
        }
        if (!$this->cart_has_course_product()) {
            return $url;
        }
        if (!function_exists('tpsp_get_login_redirect_url')) {
            return $url;
        }

        return tpsp_get_login_redirect_url($url);
    }

    /**
     * @return bool
     */
    private function cart_has_course_product() {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }
        foreach (WC()->cart->get_cart() as $item) {
            $product = isset($item['data']) ? $item['data'] : null;
            if ($product instanceof WC_Product && $this->is_course_product($product)) {
                return true;
            }
        }

        return false;
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

        if (is_user_logged_in() && function_exists('wc_customer_bought_product') && wc_customer_bought_product('', get_current_user_id(), (int) $product->get_id())) {
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
        echo '<div class="tpsp-in-cart-wrap tpsp-course-cta-stack">';
        echo '<p class="tpsp-course-cart-status" role="status"><span aria-hidden="true">&#10003;</span> ';
        esc_html_e('Already added to cart', 'tpsp');
        echo '</p>';
        echo '<a href="' . esc_url($url) . '" class="button alt single_add_to_cart_button tpsp-go-cart-btn tpsp-already-in-cart-btn">';
        esc_html_e('Go to cart', 'tpsp');
        echo '</a></div>';
    }

    /**
     * Hard redirect for guest access to any my-account URL variant.
     *
     * @return void
     */
    public function force_guest_account_path_to_login() {
        if (is_user_logged_in() || is_admin() || wp_doing_ajax()) {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? strtolower((string) wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if ('' === $request_uri || false === strpos($request_uri, '/my-account')) {
            return;
        }

        wp_safe_redirect($this->guest_login_redirect_preserving_ur_verification());
        exit;
    }

    /**
     * Keep guest users on the dedicated login page, not raw My Account form.
     *
     * @return void
     */
    public function redirect_logged_out_my_account_to_login() {
        if (is_user_logged_in() || !function_exists('is_account_page') || !is_account_page()) {
            return;
        }

        wp_safe_redirect($this->guest_login_redirect_preserving_ur_verification());
        exit;
    }

    /**
     * Send guests to /login/ but keep User Registration email-verification query args.
     *
     * @return string
     */
    private function guest_login_redirect_preserving_ur_verification() {
        $url = $this->get_custom_login_url();
        $preserve = ['ur_token', 'ur_resend_id', 'ur_resend_token', '_wpnonce'];

        foreach ($preserve as $key) {
            if (empty($_GET[$key])) {
                continue;
            }

            $value = wp_unslash((string) $_GET[$key]);
            if ('ur_token' === $key) {
                $url = add_query_arg($key, rawurlencode($value), $url);
                continue;
            }

            $url = add_query_arg($key, sanitize_text_field($value), $url);
        }

        return $url;
    }

    /**
     * Always send users to custom login page after logout.
     *
     * @param string $redirect Existing redirect URL.
     * @return string
     */
    public function force_logout_redirect_to_login($redirect) {
        unset($redirect);
        return $this->get_custom_login_url();
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

        if (function_exists('tpsp_membership_plugin_handles_email_verification') && tpsp_membership_plugin_handles_email_verification()) {
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

        // Always block repurchasing a course the customer already owns.
        if ($this->is_course_product($product) && $this->user_owns_course_product(get_current_user_id(), $product_id)) {
            wc_add_notice(__('You are already enrolled in this course.', 'tpsp'), 'notice');
            return false;
        }

        $settings = get_option('tpsp_settings', []);
        if (!isset($settings['restrict_course_access']) || 'yes' !== $settings['restrict_course_access']) {
            return $passed;
        }

        // Guests must log in before purchasing courses (unless guest checkout is enabled).
        if ($this->is_course_product($product) && !is_user_logged_in() && !apply_filters('tpsp_allow_guest_course_checkout', false)) {
            wc_add_notice(__('Please log in to your account to purchase this course.', 'tpsp'), 'notice');
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('tpsp_force_login_redirect', 1);
            }
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
        if (!is_user_logged_in() && function_exists('is_account_page') && is_account_page()) {
            $classes[] = 'tpsp-guest-my-account';
        }
        if (is_page('login')) {
            $classes[] = 'tpsp-login-page';
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
        $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
        if (!$order instanceof WC_Order) {
            return;
        }
        $qualifies = function_exists('ttp_order_qualifies_for_tcy_actions') && ttp_order_qualifies_for_tcy_actions($order);
        $legacy_ok = $order->is_paid() && in_array($order->get_status(), ['processing', 'completed'], true);
        if (!$qualifies && !$legacy_ok) {
            return;
        }
        $show_enrollment = apply_filters('ttp_show_enrollment_confirmed_on_thankyou', true, $order);
        if (!$show_enrollment) {
            return;
        }
        echo '<div class="tpsp-thankyou"><h3>' . esc_html__('Enrollment Confirmed!', 'tpsp') . '</h3><p>' . esc_html__('Your course/test access has been activated. Visit your dashboard to begin learning.', 'tpsp') . '</p></div>';
        TPSP_Logger::log('Order completed with TPSP confirmation UI', 'info', ['order_id' => (int) $order_id]);
    }

    /**
     * Hard-stop failed/cancelled/pending/refunded order-received pages from showing
     * any success/access widgets by redirecting to checkout.
     *
     * @return void
     */
    public function redirect_unpaid_order_received_to_checkout() {
        if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('order-received')) {
            return;
        }
        if (!function_exists('wc_get_order')) {
            return;
        }
        $order = null;

        $key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
        if ('' !== $key && function_exists('wc_get_order_id_by_order_key')) {
            $order_id = wc_get_order_id_by_order_key($key);
            if ($order_id) {
                $order = wc_get_order($order_id);
            }
        }

        if (!$order && function_exists('get_query_var')) {
            $endpoint_id = absint(get_query_var('order-received'));
            if ($endpoint_id > 0) {
                $order = wc_get_order($endpoint_id);
            }
        }

        if (!$order) {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
            if (preg_match('~\/checkout\/order-received\/([0-9]+)\/?~i', $request_uri, $m)) {
                $order = wc_get_order(absint($m[1]));
            }
        }

        if (!$order) {
            return;
        }

        $qualifies = function_exists('ttp_order_qualifies_for_tcy_actions') && ttp_order_qualifies_for_tcy_actions($order);
        $legacy_ok = $order->is_paid() && in_array($order->get_status(), ['processing', 'completed'], true);
        if ($qualifies || $legacy_ok) {
            return;
        }

        wc_add_notice(__('Your payment was not successful. Please try checkout again.', 'tpsp'), 'notice');
        wp_safe_redirect(wc_get_checkout_url());
        exit;
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

        if (apply_filters('tpsp_allow_guest_course_checkout', false)) {
            return;
        }

        /**
         * Set to true (e.g. in a mu-plugin) to test checkout while logged out.
         * Default: guests with courses in cart are sent to login first.
         */
        if (apply_filters('tpsp_disable_guest_checkout_login_redirect', false)) {
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

        $target = $this->build_safe_absolute_request_url(wc_get_page_permalink('checkout'));
        wp_safe_redirect(tpsp_get_login_redirect_url($target));
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

        if (apply_filters('tpsp_allow_guest_course_checkout', false)) {
            return;
        }

        // WooCommerce parses checkout URLs with ?add-to-cart= — don't hijack those here.
        if (function_exists('is_checkout') && is_checkout()) {
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

        $target = $this->build_safe_absolute_request_url(wc_get_cart_url());
        wp_safe_redirect(tpsp_get_login_redirect_url($target));
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

        if (apply_filters('tpsp_allow_guest_course_checkout', false)) {
            return;
        }

        $has_purchase_params = isset($_REQUEST['add-to-cart']) || isset($_REQUEST['buy-now']) || isset($_REQUEST['product_id']);
        if (!$has_purchase_params) {
            return;
        }

        wc_add_notice(__('Please log in to your account to purchase this course.', 'tpsp'), 'notice');

        $target = $this->build_safe_absolute_request_url(wc_get_cart_url());
        wp_safe_redirect(tpsp_get_login_redirect_url($target));
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
     * Whether the user already owns this course product (paid order).
     *
     * @param int $user_id    WordPress user ID.
     * @param int $product_id Product ID.
     * @return bool
     */
    private function user_owns_course_product($user_id, $product_id) {
        $user_id    = (int) $user_id;
        $product_id = (int) $product_id;
        if ($user_id < 1 || $product_id < 1) {
            return false;
        }

        if (function_exists('wc_customer_bought_product') && wc_customer_bought_product('', $user_id, $product_id)) {
            return true;
        }

        return false;
    }

    /**
     * Empty cart after a successful payment (CC Avenue / redirect gateways can leave stale items).
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function clear_cart_after_successful_order($order_id) {
        $order_id = (int) $order_id;
        if ($order_id < 1 || !function_exists('WC') || !WC()->cart) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        $paid_statuses = function_exists('wc_get_is_paid_statuses') ? wc_get_is_paid_statuses() : ['processing', 'completed', 'on-hold'];
        if (!in_array($order->get_status(), $paid_statuses, true)) {
            return;
        }

        WC()->cart->empty_cart();
    }

    /**
     * Drop owned courses from the cart when the session reloads (e.g. after checkout).
     *
     * @return void
     */
    public function remove_already_purchased_courses_from_cart() {
        if (!is_user_logged_in() || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) {
            return;
        }

        $user_id = get_current_user_id();
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if (!$product instanceof WC_Product || !$this->is_course_product($product)) {
                continue;
            }

            if (!$this->user_owns_course_product($user_id, (int) $product->get_id())) {
                continue;
            }

            WC()->cart->remove_cart_item($cart_item_key);
            wc_add_notice(
                sprintf(
                    /* translators: %s: product name */
                    __('"%s" was removed from your cart because you are already enrolled.', 'tpsp'),
                    $product->get_name()
                ),
                'notice'
            );
        }
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
    /**
     * Build an absolute redirect target from REQUEST_URI (?add-to-cart= preserved).
     *
     * @param string $fallback Allowed fallback URL when request cannot be reconstructed.
     * @return string
     */
    private function build_safe_absolute_request_url($fallback) {
        if (!is_string($fallback) || $fallback === '') {
            $fallback = wc_get_cart_url();
        }

        if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
            return $fallback;
        }

        $raw = strtok(wp_unslash((string) $_SERVER['REQUEST_URI']), '#');
        if (!is_string($raw) || $raw === '') {
            return $fallback;
        }

        $scheme = (function_exists('is_ssl') && is_ssl()) ? 'https://' : 'http://';
        $https_direct = strtolower((string) (isset($_SERVER['HTTPS']) ? wp_unslash($_SERVER['HTTPS']) : ''));
        if ($https_direct === 'on' || $https_direct === '1') {
            $scheme = 'https://';
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $fwd = strtolower(sanitize_text_field((string) wp_unslash($_SERVER['HTTP_X_FORWARDED_PROTO'])));
            if ($fwd !== '') {
                $scheme = (strpos($fwd, 'https') !== false) ? 'https://' : 'http://';
            }
        }

        $host = strtolower(sanitize_text_field((string) wp_unslash($_SERVER['HTTP_HOST'])));
        if ($host === '') {
            return $fallback;
        }

        $candidate = $scheme . $host . $raw;

        return wp_validate_redirect(wp_sanitize_redirect($candidate), $fallback);
    }

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
