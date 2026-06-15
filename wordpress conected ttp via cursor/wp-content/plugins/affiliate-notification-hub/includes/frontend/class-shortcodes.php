<?php

namespace ANH\Frontend;

use ANH\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcodes {

    public function hooks() {
        add_shortcode('affiliate_referral_link', [$this, 'referral_link']);
        add_shortcode('affiliate_leaderboard', [$this, 'leaderboard']);
        add_shortcode('affiliate_dashboard', [$this, 'dashboard']);

        if (!shortcode_exists('notification_bell')) {
            add_shortcode('notification_bell', [$this, 'notification_bell']);
        }
    }

    public function register_referrals_endpoint() {
        add_rewrite_endpoint('affiliate-referrals', EP_ROOT | EP_PAGES);
    }

    public function add_referrals_menu($items) {
        $new = [];
        foreach ($items as $key => $label) {
            $new[$key] = $label;
            if ('dashboard' === $key) {
                $new['affiliate-referrals'] = __('Referral Link', 'anh-hub');
            }
        }

        if (!isset($new['affiliate-referrals'])) {
            $new['affiliate-referrals'] = __('Referral Link', 'anh-hub');
        }

        return $new;
    }

    public function render_account_referrals() {
        echo do_shortcode('[affiliate_referral_link show_stats="yes"]');
    }

    public function referral_link($atts) {
        $atts = shortcode_atts([
            'user_id'    => 0,
            'show_code'  => 'yes',
            'show_stats' => 'no',
        ], $atts, 'affiliate_referral_link');

        if (!is_user_logged_in() && empty($atts['user_id'])) {
            return '<p class="anh-login-prompt">' . esc_html__('Please log in to get your referral link.', 'anh-hub') . '</p>';
        }

        $check_id = (int) ($atts['user_id'] ?? 0) ?: get_current_user_id();
        if (function_exists('ttpa_user_can_refer') && !ttpa_user_can_refer($check_id)) {
            return '';
        }

        $referrals = Plugin::instance()->referrals();
        if (!$referrals) {
            return '';
        }

        $user_id = (int) $atts['user_id'] ?: get_current_user_id();
        $link    = $referrals->get_referral_link($user_id);
        $code    = $referrals->get_or_create_code($user_id);

        if (class_exists('TTPA_Frontend')) {
            wp_enqueue_style('ttpa-frontend', TTPA_URL . 'assets/css/frontend.css', [], TTPA_VERSION);
            wp_enqueue_script('ttpa-frontend', TTPA_URL . 'assets/js/frontend.js', ['jquery'], TTPA_VERSION, true);
        }

        ob_start();
        include ANH_PATH . 'templates/referral-link.php';
        return ob_get_clean();
    }

    public function leaderboard($atts) {
        if (shortcode_exists('ttp_affiliate_leaderboard')) {
            return do_shortcode('[ttp_affiliate_leaderboard' . $this->atts_to_string($atts) . ']');
        }

        return '';
    }

    public function dashboard($atts) {
        if (shortcode_exists('ttp_affiliate_dashboard')) {
            return do_shortcode('[ttp_affiliate_dashboard]');
        }

        return '';
    }

    public function notification_bell() {
        if (!is_user_logged_in() || !class_exists('TTPN_Plugin')) {
            return '';
        }

        $notifications = Plugin::instance()->notifications();
        if (!$notifications) {
            return '';
        }

        wp_enqueue_style('ttpn-frontend', TTPN_URL . 'assets/css/frontend.css', [], TTPN_VERSION);
        wp_enqueue_script('ttpn-frontend', TTPN_URL . 'assets/js/frontend.js', ['jquery'], TTPN_VERSION, true);

        wp_localize_script('ttpn-frontend', 'TTPN', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'restUrl'     => rest_url('ttpn/v1/'),
            'nonce'       => wp_create_nonce('ttpn_frontend'),
            'restNonce'   => wp_create_nonce('wp_rest'),
            'enablePush'  => (bool) get_option('ttpn_enable_push', true),
            'vapidPublic' => \TTPN_Plugin::instance()->push()->get_vapid_public_key(),
            'swUrl'       => rest_url('ttpn/v1/sw'),
            'unread'      => $notifications->count_unread(get_current_user_id()),
        ]);

        $unread = $notifications->count_unread(get_current_user_id());
        ob_start();
        include TTPN_PATH . 'templates/notification-bell.php';
        return ob_get_clean();
    }

    private function atts_to_string($atts) {
        if (empty($atts) || !is_array($atts)) {
            return '';
        }

        $parts = [];
        foreach ($atts as $key => $value) {
            $parts[] = $key . '="' . esc_attr($value) . '"';
        }

        return $parts ? ' ' . implode(' ', $parts) : '';
    }
}
