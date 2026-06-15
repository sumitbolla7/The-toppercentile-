<?php
if (!defined('ABSPATH')) exit;

class TTP_Student {

    public function __construct() {
        add_shortcode('ttp_student_registration', [$this, 'registration_form']);
        add_action('wp_ajax_nopriv_ttp_register_student', [$this, 'handle_registration']);
        add_action('wp_ajax_ttp_register_student', [$this, 'handle_registration']);
        add_action('wp_ajax_nopriv_ttp_buy_now', [$this, 'handle_buy_now']);
        add_action('wp_ajax_ttp_buy_now', [$this, 'handle_buy_now']);
        add_action('wp_ajax_ttp_tcy_login', [$this, 'handle_tcy_login']);
        add_action('wp_ajax_nopriv_ttp_tcy_login', [$this, 'handle_tcy_login']);
    }

    public function registration_form($atts) {
        if (is_user_logged_in()) {
            $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
            if ($product_id) {
                WC()->cart->empty_cart();
                WC()->cart->add_to_cart($product_id);
                wp_redirect(wc_get_checkout_url());
                exit;
            }
        }
        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $product    = $product_id ? wc_get_product($product_id) : null;
        ob_start();
        include TTP_DIR . 'templates/registration-form.php';
        return ob_get_clean();
    }

    public function handle_registration() {
        check_ajax_referer('ttp_nonce', 'nonce');
        $full_name  = sanitize_text_field($_POST['full_name']);
        if (function_exists('ttp_sanitize_person_name_value')) {
            $full_name = ttp_sanitize_person_name_value($full_name);
        }
        if ($full_name === '' || is_email($full_name)) {
            wp_send_json_error(['message' => 'Please enter your real name (not your email address).']);
        }
        $email      = sanitize_email($_POST['email']);
        $mobile     = sanitize_text_field($_POST['mobile']);
        $username   = sanitize_user($_POST['username']);
        $password   = $_POST['password'];
        $product_id = intval($_POST['product_id']);

        if (!$full_name || !$email || !$mobile || !$username || !$password) {
            wp_send_json_error(['message' => 'All fields are required.']);
        }
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.']);
        }
        if (email_exists($email)) {
            wp_send_json_error(['message' => 'This email is already registered. Please login.']);
        }
        if (username_exists($username)) {
            wp_send_json_error(['message' => 'This username is already taken. Please choose another.']);
        }

        $wp_user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($wp_user_id)) {
            wp_send_json_error(['message' => $wp_user_id->get_error_message()]);
        }

        wp_update_user(['ID' => $wp_user_id, 'display_name' => $full_name, 'first_name' => $full_name]);
        update_user_meta($wp_user_id, 'ttp_mobile', $mobile);
        update_user_meta($wp_user_id, 'ttp_full_name', $full_name);

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'ttp_students', [
            'wp_user_id' => $wp_user_id,
            'full_name'  => $full_name,
            'email'      => $email,
            'mobile'     => $mobile,
            'username'   => $username,
        ]);

        wp_set_current_user($wp_user_id);
        wp_set_auth_cookie($wp_user_id);

        WC()->cart->empty_cart();
        if ($product_id) WC()->cart->add_to_cart($product_id);

        wp_send_json_success([
            'message'      => 'Account created! Redirecting to checkout...',
            'redirect_url' => wc_get_checkout_url(),
        ]);
    }

    public function handle_buy_now() {
        check_ajax_referer('ttp_nonce', 'nonce');
        $product_id = absint($_POST['product_id'] ?? 0);
        $exam_page  = !empty($_POST['exam_page']) || $this->is_exam_buy_now_request();

        if ($exam_page && !is_user_logged_in()) {
            wp_send_json_success([
                'redirect_url' => function_exists('ttp_get_login_url_with_checkout_redirect')
                    ? ttp_get_login_url_with_checkout_redirect($product_id)
                    : home_url('/login/'),
            ]);
            return;
        }

        if ($product_id > 0 && is_user_logged_in() && function_exists('wc_customer_bought_product') && wc_customer_bought_product('', get_current_user_id(), $product_id)) {
            wp_send_json_error(['message' => 'You are already enrolled in this course.']);
            return;
        }

        $checkout_url = function_exists('ttp_guest_checkout_direct_url')
            ? ttp_guest_checkout_direct_url()
            : ( function_exists('wc_get_page_permalink') ? wc_get_page_permalink('checkout') : home_url('/checkout/') );
        if ($product_id) {
            WC()->cart->empty_cart();
            WC()->cart->add_to_cart($product_id);
            if ($product_id > 0) {
                $checkout_url = add_query_arg('add-to-cart', $product_id, $checkout_url);
            }
        }
        wp_send_json_success(['redirect_url' => $checkout_url]);
    }

    /**
     * @return bool
     */
    private function is_exam_buy_now_request() {
        $ref = wp_get_referer();
        if (is_string($ref) && false !== strpos($ref, '/exam')) {
            return true;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        return is_string($uri) && false !== strpos($uri, '/exam');
    }

    public function handle_tcy_login() {
        check_ajax_referer('ttp_nonce', 'nonce');
        $order_id = isset($_POST['order_id']) ? absint(wp_unslash($_POST['order_id'])) : 0;
        $order    = $order_id > 0 ? wc_get_order($order_id) : null;
        if (!$order instanceof WC_Order) {
            wp_send_json_error(['message' => 'Invalid order.']);
        }

        $guest_key = isset($_POST['order_key']) ? wc_clean(wp_unslash($_POST['order_key'])) : '';
        $order_uid = (int) $order->get_user_id();

        if (!is_user_logged_in()) {
            if ($guest_key === '' || !$order->key_is_valid($guest_key)) {
                wp_send_json_error(['message' => 'Please log in or open this page from your order email link.']);
            }
        } else {
            $current_uid = get_current_user_id();
            $key_ok      = $guest_key !== '' && $order->key_is_valid($guest_key);
            if ($order_uid > 0 && $order_uid !== $current_uid) {
                wp_send_json_error(['message' => 'Invalid order.']);
            }
            if (0 === $order_uid) {
                if (!$key_ok) {
                    $u       = wp_get_current_user();
                    $billing = strtolower(trim((string) $order->get_billing_email()));
                    $acct    = strtolower(trim((string) $u->user_email));
                    if ($billing === '' || $acct === '' || $billing !== $acct) {
                        wp_send_json_error([
                            'message' => 'This order was placed as a guest. Use the link in your order confirmation email (it includes a security key), or log in with the same email you used at checkout.',
                        ]);
                    }
                }
            }
        }

        if (!$this->is_order_paid_for_tcy_access($order)) {
            wp_send_json_error(['message' => 'TCY access is available only after successful payment.']);
        }

        $mapping_complete = function_exists('ttp_order_tcy_mapping_is_complete') && ttp_order_tcy_mapping_is_complete($order);

        if (!$mapping_complete) {
            global $ttp_checkout;
            if ($ttp_checkout instanceof TTP_Checkout) {
                $ttp_checkout->sync_tcy_if_missing_mapping($order_id);
            } elseif (function_exists('ttp_order_tcy_mapping_needs_repair') && ttp_order_tcy_mapping_needs_repair($order)) {
                $checkout = new TTP_Checkout();
                $checkout->trigger_tcy_registration($order_id);
            }
        }

        if ($mapping_complete && function_exists('ttp_get_cached_study_login_url_for_order')) {
            $cached = ttp_get_cached_study_login_url_for_order($order_id);
            if ($cached !== '') {
                wp_send_json_success([
                    'login_url'   => $cached,
                    'courses_url' => function_exists('ttp_get_study_portal_courses_list_url') ? ttp_get_study_portal_courses_list_url() : '',
                ]);
                return;
            }
        }

        global $wpdb;
        $wp_uid = (int) $order->get_user_id();
        // Guest checkout orders have customer_id 0; use the logged-in account for TCY recovery when billing email matches (see recover_order_mapping).
        if ($wp_uid < 1 && is_user_logged_in()) {
            $wp_uid = get_current_user_id();
        }
        $mapping = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND tcy_user_id IS NOT NULL AND tcy_user_id <> '' ORDER BY (status = %s) DESC, id DESC LIMIT 1",
                $order_id,
                'registered'
            )
        );
        if (!$mapping || !$mapping->tcy_user_id) {
            $mapping = $this->recover_order_mapping($order_id, (int) $wp_uid);
        }
        if ($mapping && function_exists('ttp_order_tcy_mapping_mismatch') && ttp_order_tcy_mapping_mismatch($order, $mapping)) {
            $mapping = $this->repair_order_tcy_mapping($order, $mapping, (int) $wp_uid);
        }
        if (!$mapping_complete && $mapping && !empty($mapping->tcy_user_id) && function_exists('ttp_sync_tcy_courses_for_order')) {
            ttp_sync_tcy_courses_for_order($order);
        } elseif (!$mapping_complete && $order instanceof WC_Order && function_exists('ttp_sync_tcy_courses_for_order')) {
            global $ttp_checkout;
            if ($ttp_checkout instanceof TTP_Checkout) {
                $ttp_checkout->trigger_tcy_registration((int) $order_id);
            }
            ttp_sync_tcy_courses_for_order($order);
        }
        if ($mapping && !empty($mapping->tcy_user_id) && isset($mapping->status) && 'registered' !== $mapping->status) {
            $wpdb->update(
                $wpdb->prefix . 'ttp_order_mapping',
                array('status' => 'registered'),
                array('id' => (int) $mapping->id),
                array('%s'),
                array('%d')
            );
            $mapping->status = 'registered';
        }
        if (!$mapping || empty($mapping->tcy_user_id)) {
            $fallback_tcy = '';
            if ($wp_uid > 0) {
                $fallback_tcy = (string) get_user_meta($wp_uid, '_ttp_tcy_user_id', true);
                if ($fallback_tcy === '') {
                    $fallback_tcy = (string) $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT tcy_user_id FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d AND tcy_user_id <> '' LIMIT 1",
                            $wp_uid
                        )
                    );
                }
            }
            if ($fallback_tcy !== '' && function_exists('ttp_build_study_portal_access_url')) {
                $link = ttp_build_study_portal_access_url($fallback_tcy, (int) $order_id, 0, 'Login after order course sync');
                if ($link !== '') {
                    wp_send_json_success([
                        'login_url' => function_exists('ttp_finalize_study_portal_login_url')
                            ? ttp_finalize_study_portal_login_url($link)
                            : $link,
                    ]);
                }
            }
            $msg = function_exists('ttp_tcy_customer_error_message_for_order')
                ? ttp_tcy_customer_error_message_for_order(
                    $order_id,
                    'We could not create your automatic study-portal login yet. Please try again in a minute. If it keeps failing, contact support with your order number.'
                )
                : 'We could not create your automatic study-portal login yet. Please try again in a minute. If it keeps failing, contact support with your order number.';
            if ($msg === '') {
                wp_send_json_error(['message' => '', 'silent' => true]);
            }
            wp_send_json_error(['message' => $msg]);
        }
        if (function_exists('ttp_build_study_portal_access_url')) {
            $link = ttp_build_study_portal_access_url(
                (string) $mapping->tcy_user_id,
                (int) $order_id,
                0,
                'Login & Access button'
            );
            if ($link !== '') {
                $wpdb->update($wpdb->prefix . 'ttp_order_mapping', ['login_link' => $link], ['id' => (int) $mapping->id]);
                wp_send_json_success([
                    'login_url' => $link,
                    'courses_url' => function_exists('ttp_get_study_portal_courses_list_url') ? ttp_get_study_portal_courses_list_url() : '',
                ]);
                return;
            }
        }
        if ($ttp_checkout instanceof TTP_Checkout) {
            $ttp_checkout->sync_tcy_if_missing_mapping($order_id);
        }
        $msg = function_exists('ttp_tcy_customer_error_message_for_order')
            ? ttp_tcy_customer_error_message_for_order($order_id, 'Could not generate login link. Please contact support.')
            : 'Could not generate login link. Please contact support.';
        if ($msg === '') {
            wp_send_json_error(['message' => '', 'silent' => true]);
        }
        wp_send_json_error(['message' => $msg]);
    }

    /**
     * Re-assign the correct TCY course when mapping row does not match the purchased product.
     *
     * @param WC_Order $order   Order.
     * @param object   $mapping DB row.
     * @param int      $user_id WP user id.
     * @return object|null Updated mapping row.
     */
    private function repair_order_tcy_mapping($order, $mapping, $user_id) {
        if (!function_exists('ttp_get_tcy_ids_for_order') || !function_exists('ttp_tcy_ensure_course_on_account')) {
            return $mapping;
        }
        $expected = ttp_get_tcy_ids_for_order($order);
        if ($expected['course_id'] === '' || empty($mapping->tcy_user_id)) {
            return $mapping;
        }

        ttp_tcy_ensure_course_on_account(
            (string) $mapping->tcy_user_id,
            $expected['course_id'],
            $expected['category_id'],
            (int) $order->get_id(),
            isset( $expected['product_id'] ) ? (int) $expected['product_id'] : 0
        );

        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'ttp_order_mapping',
            [
                'tcy_course_id'   => $expected['course_id'],
                'tcy_category_id' => $expected['category_id'],
                'status'          => 'registered',
                'login_link'      => '',
            ],
            ['id' => (int) $mapping->id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        $mapping->tcy_course_id   = $expected['course_id'];
        $mapping->tcy_category_id = $expected['category_id'];
        $mapping->status          = 'registered';
        $mapping->login_link      = '';

        return $mapping;
    }

    private function recover_order_mapping($order_id, $user_id) {
        global $wpdb;

        $order = wc_get_order($order_id);
        if (!$order) {
            return null;
        }
        $order_uid = (int) $order->get_user_id();
        $uid       = (int) $user_id;
        // Order is tied to a specific WP user: recovery must use that id (caller passes order owner or 0 only for pure guest/key flows).
        if ($order_uid > 0 && 0 === $uid) {
            return null;
        }
        if ($order_uid > 0 && $uid > 0 && $order_uid !== $uid) {
            return null;
        }
        // Guest order + logged-in customer: only allow when checkout email matches the WP account.
        if (0 === $order_uid && $uid > 0) {
            $u       = get_userdata($uid);
            $billing = strtolower(trim((string) $order->get_billing_email()));
            $acct    = $u ? strtolower(trim((string) $u->user_email)) : '';
            if ($billing === '' || $acct === '' || $billing !== $acct) {
                return null;
            }
        }
        // Never create/recover TCY mappings for unpaid orders.
        if (!$this->is_order_paid_for_tcy_access($order)) {
            return null;
        }

        $mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND wp_user_id = %d",
            $order_id, $user_id
        ));

        $tcy_user_id = !empty($mapping->tcy_user_id) ? $mapping->tcy_user_id : '';
        if (empty($tcy_user_id) && $user_id > 0) {
            $tcy_user_id = get_user_meta($user_id, '_ttp_tcy_user_id', true);
        }
        if (empty($tcy_user_id) && $user_id > 0) {
            $student_row = $wpdb->get_row($wpdb->prepare(
                "SELECT tcy_user_id FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d",
                $user_id
            ));
            if ($student_row && !empty($student_row->tcy_user_id)) {
                $tcy_user_id = $student_row->tcy_user_id;
            }
        }

        $api = new TTP_TCY_API();

        if (empty($tcy_user_id) && function_exists('ttp_get_canonical_tcy_user_id')) {
            $tcy_user_id = ttp_get_canonical_tcy_user_id(
                $user_id > 0 ? $user_id : (int) $order->get_user_id(),
                (string) $order->get_billing_email()
            );
        }

        if (empty($tcy_user_id)) {
            $reg_course   = '';
            $reg_category = '';
            foreach ($order->get_items() as $item) {
                $product_id = (int) $item->get_product_id();
                if ($product_id < 1) {
                    continue;
                }
                $tcy_ids = function_exists('ttp_get_tcy_ids_for_product')
                    ? ttp_get_tcy_ids_for_product($product_id, true)
                    : [
                        'course_id'   => (string) get_post_meta($product_id, '_ttp_tcy_course_id', true),
                        'category_id' => (string) get_post_meta($product_id, '_ttp_tcy_category_id', true),
                    ];
                $tcy_course_id   = isset($tcy_ids['course_id']) ? (string) $tcy_ids['course_id'] : '';
                $tcy_category_id = isset($tcy_ids['category_id']) ? (string) $tcy_ids['category_id'] : '';
                if ($tcy_course_id !== '') {
                    $reg_course   = $tcy_course_id;
                    $reg_category = $tcy_category_id;
                    break;
                }
            }
            if ($reg_course !== '') {
                $register = $api->register_student([
                    'full_name'   => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                    'email'       => $order->get_billing_email(),
                    'mobile'      => $order->get_billing_phone(),
                    'course_id'   => $reg_course,
                    'category_id' => $reg_category,
                    'order_id'    => $order_id,
                ]);
                $tcy_user_id = $this->extract_tcy_user_id($register);
            }
        }

        if (empty($tcy_user_id)) {
            return null;
        }

        if ($user_id > 0) {
            update_user_meta($user_id, '_ttp_tcy_user_id', $tcy_user_id);
            $existing_student = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d",
                $user_id
            ));
            if ($existing_student) {
                $wpdb->update($wpdb->prefix . 'ttp_students', ['tcy_user_id' => $tcy_user_id], ['wp_user_id' => $user_id]);
            } else {
                $wpdb->insert($wpdb->prefix . 'ttp_students', [
                    'wp_user_id'  => $user_id,
                    'full_name'   => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                    'email'       => $order->get_billing_email(),
                    'mobile'      => $order->get_billing_phone(),
                    'username'    => $order->get_billing_email(),
                    'tcy_user_id' => $tcy_user_id,
                ]);
            }
        }

        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();
            if ($product_id < 1) {
                continue;
            }
            $tcy_ids = function_exists('ttp_get_tcy_ids_for_product')
                ? ttp_get_tcy_ids_for_product($product_id, true)
                : [
                    'course_id'   => (string) get_post_meta($product_id, '_ttp_tcy_course_id', true),
                    'category_id' => (string) get_post_meta($product_id, '_ttp_tcy_category_id', true),
                ];
            $tcy_course_id   = isset($tcy_ids['course_id']) ? (string) $tcy_ids['course_id'] : '';
            $tcy_category_id = isset($tcy_ids['category_id']) ? (string) $tcy_ids['category_id'] : '';
            if ($tcy_course_id !== '') {
                if (function_exists('ttp_tcy_ensure_course_on_account')) {
                    ttp_tcy_ensure_course_on_account($tcy_user_id, $tcy_course_id, $tcy_category_id, (int) $order_id, $product_id);
                } else {
                    $api->add_course($tcy_user_id, $tcy_course_id, $tcy_category_id, $order_id);
                }
            }
        }

        $wpdb->delete($wpdb->prefix . 'ttp_order_mapping', ['order_id' => $order_id], ['%d']);
        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();
            if ($product_id < 1) {
                continue;
            }
            $tcy_ids = function_exists('ttp_get_tcy_ids_for_product')
                ? ttp_get_tcy_ids_for_product($product_id, false)
                : [
                    'course_id'   => (string) get_post_meta($product_id, '_ttp_tcy_course_id', true),
                    'category_id' => (string) get_post_meta($product_id, '_ttp_tcy_category_id', true),
                ];
            $line_course   = isset($tcy_ids['course_id']) ? (string) $tcy_ids['course_id'] : '';
            $line_category = isset($tcy_ids['category_id']) ? (string) $tcy_ids['category_id'] : '';
            if ($line_course === '') {
                continue;
            }
            $wpdb->insert($wpdb->prefix . 'ttp_order_mapping', [
                'order_id'        => $order_id,
                'wp_user_id'      => $user_id,
                'tcy_user_id'     => $tcy_user_id,
                'tcy_course_id'   => $line_course,
                'tcy_category_id' => $line_category,
                'status'          => 'registered',
            ]);
        }

        $order->update_meta_data('_ttp_tcy_user_id', $tcy_user_id);
        $order->save();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND wp_user_id = %d ORDER BY id DESC LIMIT 1",
            $order_id, $user_id
        ));
    }

    /**
     * Access gate for TCY: aligns with ttp_order_qualifies_for_tcy_actions (paid / no balance due, allowed status).
     *
     * @param WC_Order|mixed $order Order object.
     * @return bool
     */
    private function is_order_paid_for_tcy_access($order) {
        if (!$order instanceof WC_Order) {
            return false;
        }
        return function_exists('ttp_order_qualifies_for_tcy_actions') && ttp_order_qualifies_for_tcy_actions($order);
    }

    private function extract_tcy_user_id($response) {
        if (function_exists('ttp_tcy_extract_register_user_id')) {
            return ttp_tcy_extract_register_user_id($response);
        }
        if (!is_array($response)) {
            return '';
        }

        $possible_keys = apply_filters(
            'ttp_tcy_register_user_id_keys',
            array('user_id', 'tcy_user_id', 'userid', 'student_id', 'erp_user_id', 'erp_userid', 'id')
        );
        foreach ($possible_keys as $key) {
            if (!empty($response[$key])) {
                return sanitize_text_field((string) $response[$key]);
            }
            if (isset($response['data']) && is_array($response['data']) && !empty($response['data'][$key])) {
                return sanitize_text_field((string) $response['data'][$key]);
            }
        }

        return '';
    }
}
