<?php
/**
 * Activation tasks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_Activator {
    /**
     * Run activation routine.
     *
     * @return void
     */
    public static function activate() {
        self::ensure_woocommerce_pages();
        self::ensure_dashboard_page();
        self::set_default_options();

        if (!wp_next_scheduled('tpsp_cleanup_expired_tokens')) {
            wp_schedule_event(time(), 'hourly', 'tpsp_cleanup_expired_tokens');
        }

        flush_rewrite_rules();
    }

    /**
     * Ensure WC pages exist and are assigned.
     *
     * @return void
     */
    private static function ensure_woocommerce_pages() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        $required_pages = [
            'woocommerce_cart_page_id'       => ['title' => 'Cart', 'shortcode' => '[woocommerce_cart]'],
            'woocommerce_checkout_page_id'   => ['title' => 'Checkout', 'shortcode' => '[woocommerce_checkout]'],
            'woocommerce_myaccount_page_id'  => ['title' => 'My Account', 'shortcode' => '[woocommerce_my_account]'],
        ];

        foreach ($required_pages as $option_key => $page_data) {
            $page_id = (int) get_option($option_key);
            if ($page_id > 0 && get_post_status($page_id)) {
                continue;
            }

            $existing = get_page_by_title($page_data['title']);
            if ($existing instanceof WP_Post) {
                update_option($option_key, (int) $existing->ID);
                continue;
            }

            $new_page_id = wp_insert_post([
                'post_title'   => $page_data['title'],
                'post_content' => $page_data['shortcode'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ]);

            if (!is_wp_error($new_page_id)) {
                update_option($option_key, (int) $new_page_id);
            }
        }
    }

    /**
     * Ensure dedicated dashboard page exists.
     *
     * @return void
     */
    private static function ensure_dashboard_page() {
        $page_slug = 'student-dashboard';
        $existing  = get_page_by_path($page_slug);

        if ($existing instanceof WP_Post) {
            return;
        }

        wp_insert_post([
            'post_title'   => 'Student Dashboard',
            'post_name'    => $page_slug,
            'post_content' => '[tpsp_student_dashboard]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }

    /**
     * Save default plugin settings.
     *
     * @return void
     */
    private static function set_default_options() {
        $defaults = [
            'require_email_verification' => 'yes',
            'enable_debug_logging'       => 'yes',
            'cart_force_ajax'            => 'yes',
            'restrict_course_access'     => 'yes',
        ];

        $saved = get_option('tpsp_settings', []);
        update_option('tpsp_settings', wp_parse_args($saved, $defaults));
    }
}
