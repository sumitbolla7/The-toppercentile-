<?php
/**
 * AJAX handlers.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_Ajax {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        add_action('wp_ajax_tpsp_resend_verification', [$this, 'resend_verification_ajax']);
        add_action('wp_ajax_nopriv_tpsp_resend_verification', [$this, 'resend_verification_ajax']);
        add_action('wp_ajax_tpsp_ajax_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_nopriv_tpsp_ajax_add_to_cart', [$this, 'ajax_add_to_cart']);
        add_action('wp_ajax_tpsp_session_status', [$this, 'session_status_ajax']);
        add_action('wp_ajax_nopriv_tpsp_session_status', [$this, 'session_status_ajax']);
        add_action('wp_ajax_tpsp_login_panel_html', [$this, 'login_panel_html_ajax']);
    }

    /**
     * Student profile HTML for /login/ when the page was served from cache as a guest.
     *
     * @return void
     */
    public function login_panel_html_ajax() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Not logged in.', 'tpsp')], 403);
        }

        $html = do_shortcode('[tpsp_login_student_panel]');
        if (!is_string($html) || '' === trim($html)) {
            wp_send_json_error(['message' => __('Panel unavailable.', 'tpsp')], 500);
        }

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Real login state for cached pages (LiteSpeed may serve a guest HTML shell).
     *
     * @return void
     */
    public function session_status_ajax() {
        $logged_in = is_user_logged_in();
        $login_url = function_exists('tpsp_get_login_page_url') ? tpsp_get_login_page_url() : home_url('/login/');
        if ($logged_in) {
            if (function_exists('ttp_student_profile_url')) {
                $profile_url = ttp_student_profile_url();
            } else {
                $profile_url = $login_url;
            }
        } else {
            $profile_url = $login_url;
        }

        wp_send_json_success(
            [
                'logged_in'   => $logged_in,
                'login_url'   => $login_url,
                'profile_url' => $profile_url,
            ]
        );
    }

    /**
     * Resend verification through AJAX.
     *
     * @return void
     */
    public function resend_verification_ajax() {
        check_ajax_referer('tpsp_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Invalid email address.', 'tpsp')], 400);
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error(['message' => __('No account found for this email.', 'tpsp')], 404);
        }

        $token_plain = wp_generate_password(48, false, false);
        update_user_meta($user->ID, 'tpsp_email_verification_token', wp_hash_password($token_plain));
        update_user_meta($user->ID, 'tpsp_email_verification_expiry', time() + DAY_IN_SECONDS);
        update_user_meta($user->ID, 'tpsp_email_verified', 0);

        $verification = new TPSP_Email_Verification();
        $verification->send_verification_email($user->ID, $token_plain);

        wp_send_json_success(['message' => __('Verification email sent. Please check inbox.', 'tpsp')]);
    }

    /**
     * Robust AJAX add to cart for single product forms.
     *
     * @return void
     */
    public function ajax_add_to_cart() {
        check_ajax_referer('tpsp_nonce', 'nonce');

        if (!function_exists('WC') || !WC()->cart) {
            wp_send_json_error(['message' => __('WooCommerce cart is unavailable.', 'tpsp')], 500);
        }

        $product_id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
        $quantity   = isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : 1;

        if (!$product_id) {
            wp_send_json_error(['message' => __('Missing product.', 'tpsp')], 400);
        }

        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            wp_send_json_error(['message' => __('Product not found.', 'tpsp')], 404);
        }

        if ($product->is_virtual() && !is_user_logged_in()) {
            $login_url = home_url('/login/');

            wp_send_json_error([
                'message'  => __('Please log in to your account to purchase this course.', 'tpsp'),
                'redirect' => true,
                'loginUrl' => $login_url,
            ], 403);
        }

        $user_id = get_current_user_id();
        if ($user_id && function_exists('wc_customer_bought_product') && wc_customer_bought_product('', $user_id, $product_id)) {
            if (function_exists('WC') && WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    if ((int) ($cart_item['product_id'] ?? 0) === $product_id) {
                        WC()->cart->remove_cart_item($cart_item_key);
                    }
                }
            }
            wp_send_json_error([
                'message' => __('You are already enrolled in this course.', 'tpsp'),
                'code'    => 'already_enrolled',
            ], 400);
        }

        // Duplicate add: treat as success so the UI can route to cart without WooCommerce error notices.
        $cart_id  = WC()->cart->generate_cart_id($product_id, 0, [], []);
        $existing = WC()->cart->find_product_in_cart($cart_id);
        if (!empty($existing)) {
            if (function_exists('wc_clear_notices')) {
                wc_clear_notices();
            }
            wp_send_json_success([
                'alreadyInCart' => true,
                'message'       => __('This course is already in your cart.', 'tpsp'),
                'cartUrl'       => wc_get_cart_url(),
            ]);
        }

        $passed = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity);
        if (!$passed) {
            $msg   = __('Unable to add this product to cart.', 'tpsp');
            $errs  = function_exists('wc_get_notices') ? wc_get_notices('error') : [];
            if (!empty($errs)) {
                $parts = [];
                foreach ($errs as $row) {
                    if (!empty($row['notice'])) {
                        $clean = function_exists('wc_kses_notice') ? wc_kses_notice($row['notice']) : wp_kses_post($row['notice']);
                        $parts[] = wp_strip_all_tags($clean);
                    }
                }
                $msg = trim(implode(' ', array_filter($parts))) ?: $msg;
            }
            if (function_exists('wc_clear_notices')) {
                wc_clear_notices();
            }

            wp_send_json_error(['message' => $msg], 400);
        }

        $added = WC()->cart->add_to_cart($product_id, $quantity);
        if (!$added) {
            TPSP_Logger::log('AJAX add to cart failed', 'error', ['product_id' => $product_id]);
            wp_send_json_error(['message' => __('Could not add product to cart.', 'tpsp')], 500);
        }

        do_action('woocommerce_ajax_added_to_cart', $product_id);
        if (class_exists('WC_AJAX')) {
            if (function_exists('wc_clear_notices')) {
                wc_clear_notices();
            }
            WC_AJAX::get_refreshed_fragments();
            return;
        }

        wp_send_json_success([
            'message' => __('Product added to cart.', 'tpsp'),
            'cartUrl' => wc_get_cart_url(),
        ]);
    }
}
