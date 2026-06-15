<?php
/**
 * Plugin Name: Top Percentile Student Portal
 * Description: Premium student portal for WooCommerce education websites with dashboard, email verification, and cart/checkout stability improvements.
 * Version: 1.2.20
 * Author: Top Percentile
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Text Domain: tpsp
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TPSP_VERSION', '1.2.38');
define('TPSP_PLUGIN_FILE', __FILE__);
define('TPSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TPSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TPSP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Public branded login page URL (page slug `login`), not wp-login.php.
 *
 * @return string
 */
function tpsp_get_login_page_url() {
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
 * Login page URL with redirect_to after successful authentication.
 *
 * @param string $redirect_target Absolute URL (e.g. checkout with add-to-cart).
 * @return string
 */
function tpsp_get_login_redirect_url($redirect_target) {
    $base = tpsp_get_login_page_url();
    if (empty($redirect_target)) {
        return $base;
    }

    return add_query_arg('redirect_to', $redirect_target, $base);
}

/**
 * Edit profile on /login/ using User Registration form.
 *
 * @return string
 */
function tpsp_get_edit_profile_url() {
    return add_query_arg('tpsp_edit_profile', '1', tpsp_get_login_page_url());
}

/**
 * Primary User Registration form ID (login page shortcode, else newest published form).
 *
 * @return int
 */
function tpsp_get_default_ur_form_id() {
    static $cached = null;

    if ($cached !== null) {
        return (int) $cached;
    }

    $login_page = get_page_by_path('login');
    if ($login_page instanceof WP_Post && is_string($login_page->post_content) && $login_page->post_content !== '') {
        if (preg_match('/\[user_registration_form[^\]]*id=["\']?(\d+)/i', $login_page->post_content, $matches)) {
            $cached = (int) $matches[1];
            if ($cached > 0) {
                return $cached;
            }
        }
    }

    $forms = get_posts([
        'post_type'   => 'user_registration',
        'post_status' => 'publish',
        'numberposts' => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'fields'      => 'ids',
    ]);

    $cached = !empty($forms[0]) ? (int) $forms[0] : 0;

    return (int) $cached;
}

/**
 * Ensure logged-in students have ur_form_id so UR edit profile renders.
 *
 * @param int $user_id User ID (0 = current).
 * @return int Form ID or 0.
 */
function tpsp_ensure_user_registration_form_id($user_id = 0) {
    $user_id = $user_id > 0 ? (int) $user_id : (int) get_current_user_id();
    if ($user_id < 1) {
        return 0;
    }

    $form_id = (int) get_user_meta($user_id, 'ur_form_id', true);
    if ($form_id < 1 && function_exists('ur_get_form_id_by_userid')) {
        $form_id = (int) ur_get_form_id_by_userid($user_id);
    }
    if ($form_id < 1) {
        $form_id = tpsp_get_default_ur_form_id();
        if ($form_id > 0) {
            update_user_meta($user_id, 'ur_form_id', $form_id);
        }
    }

    return $form_id;
}

/**
 * Whether User Registration edit-profile shortcode returned a real form.
 *
 * @param string $html Shortcode HTML.
 * @return bool
 */
function tpsp_ur_edit_profile_html_is_valid($html) {
    if (!is_string($html) || '' === $html) {
        return false;
    }

    return false !== strpos($html, 'ur-frontend-form')
        || false !== strpos($html, 'user-registration-EditProfileForm');
}

/**
 * Whether a URL points at the bare WooCommerce My Account dashboard (not orders/courses/etc.).
 *
 * @param string $url URL to inspect.
 * @return bool
 */
function tpsp_is_my_account_dashboard_url($url) {
    if (!is_string($url) || '' === $url) {
        return false;
    }

    $parsed = wp_parse_url($url);
    if (!is_array($parsed)) {
        return false;
    }

    $path = isset($parsed['path']) ? untrailingslashit((string) $parsed['path']) : '';
    if ('' === $path) {
        return false;
    }

    $myaccount = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
    if (!$myaccount) {
        $myaccount = home_url('/my-account/');
    }

    $base_path = wp_parse_url($myaccount, PHP_URL_PATH);
    if (!is_string($base_path)) {
        return false;
    }

    if ($path !== untrailingslashit($base_path)) {
        return false;
    }

    if (!empty($parsed['query'])) {
        parse_str((string) $parsed['query'], $query);
        $endpoint_keys = apply_filters('tpsp_my_account_allowed_endpoints', [
            'orders',
            'my-courses',
            'view-order',
            'edit-account',
            'downloads',
            'payment-methods',
        ]);

        foreach ($endpoint_keys as $key) {
            if (isset($query[$key])) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Send students to /login/ instead of the WooCommerce account dashboard after auth.
 *
 * @param string $redirect Candidate redirect URL.
 * @param string $fallback Fallback when redirect is empty or blocked.
 * @return string
 */
function tpsp_normalize_student_login_redirect($redirect, $fallback = null) {
    if (null === $fallback) {
        $fallback = tpsp_get_login_page_url();
    }

    $validated = wp_validate_redirect((string) $redirect, '');
    if ('' === $validated) {
        return $fallback;
    }

    if (tpsp_is_my_account_dashboard_url($validated)) {
        return $fallback;
    }

    return $validated;
}

/**
 * When Ultimate Member or User Registration & Membership is active, do not stack TPSP email verification on top.
 *
 * @return bool
 */
function tpsp_membership_plugin_handles_email_verification() {
    if (defined('UM_VERSION')) {
        return true;
    }
    if (defined('UR_VERSION')) {
        return true;
    }
    if (class_exists('User_Registration', false)) {
        return true;
    }

    return false;
}

require_once TPSP_PLUGIN_DIR . 'includes/class-tpsp-activator.php';
require_once TPSP_PLUGIN_DIR . 'includes/class-tpsp-deactivator.php';
require_once TPSP_PLUGIN_DIR . 'includes/class-tpsp.php';

register_activation_hook(__FILE__, ['TPSP_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['TPSP_Deactivator', 'deactivate']);

/**
 * Boot plugin.
 *
 * @return TPSP
 */
function tpsp_bootstrap() {
    static $plugin = null;

    if (null === $plugin) {
        $plugin = new TPSP();
        $plugin->run();
    }

    return $plugin;
}

tpsp_bootstrap();

/**
 * After plugin updates, flush rewrites once so WooCommerce account endpoints (orders, my-courses) resolve.
 *
 * @return void
 */
function tpsp_maybe_flush_rewrites_for_endpoints() {
    if (!function_exists('wc_get_page_permalink')) {
        return;
    }
    $stored = get_option('tpsp_rewrite_flush_version', '');
    if ($stored === TPSP_VERSION) {
        return;
    }
    flush_rewrite_rules(false);
    update_option('tpsp_rewrite_flush_version', TPSP_VERSION);
}
add_action('init', 'tpsp_maybe_flush_rewrites_for_endpoints', 999);
