<?php

namespace ANH\Admin;

use ANH\Promo_Popup;

if (!defined('ABSPATH')) {
    exit;
}

class Promo_Popup_Admin {

    public function hooks() {
        add_action('admin_post_anh_save_promo_popup', [$this, 'handle_save']);
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'affiliate-hub_page_anh-promo-popup') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script(
            'anh-promo-admin',
            ANH_URL . 'assets/js/promo-admin.js',
            ['jquery', 'wp-color-picker'],
            ANH_VERSION,
            true
        );
    }

    public function render_page() {
        $settings = Promo_Popup::get_settings();
        include ANH_PATH . 'templates/admin-promo-popup.php';
    }

    public function handle_save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        check_admin_referer('anh_save_promo_popup');

        $raw = wp_unslash($_POST);
        if (!empty($raw['countdown_end'])) {
            $raw['countdown_end'] = str_replace('T', ' ', $raw['countdown_end']);
        }
        $settings = Promo_Popup::sanitize_settings($raw);
        update_option(Promo_Popup::OPTION_KEY, $settings, false);
        wp_cache_delete(Promo_Popup::OPTION_KEY, 'options');
        self::purge_site_cache();

        wp_safe_redirect(admin_url('admin.php?page=anh-promo-popup&updated=1'));
        exit;
    }

    /**
     * Clear page caches so disabled popup HTML is not served from LiteSpeed / Hostinger.
     */
    public static function purge_site_cache() {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        if (class_exists('LiteSpeed_Cache_API')) {
            \LiteSpeed_Cache_API::purge_all();
        }

        do_action('litespeed_purge_all');

        if (function_exists('litespeed_purge_all')) {
            litespeed_purge_all();
        }

        if (function_exists('hostinger_purge_cache')) {
            hostinger_purge_cache();
        }

        do_action('hostinger_cache_purge');
    }
}
