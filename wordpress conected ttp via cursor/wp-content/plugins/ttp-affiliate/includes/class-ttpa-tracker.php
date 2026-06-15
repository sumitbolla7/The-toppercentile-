<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Tracker {

    /** @var TTPA_Referral_Service */
    private $referrals;

    public function __construct($referrals) {
        $this->referrals = $referrals;
    }

    public function hooks() {
        add_action('init', [$this, 'capture_referral'], 1);
        add_action('init', [$this, 'register_rewrite']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_pretty_referral']);
    }

    public function register_rewrite() {
        add_rewrite_rule('^ref/([^/]+)/?', 'index.php?ttpa_ref=$matches[1]', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'ttpa_ref';
        return $vars;
    }

    public function capture_referral() {
        $param = sanitize_key(get_option('ttpa_referral_param', 'ref'));
        $code  = '';

        if (!empty($_GET[$param])) {
            $code = sanitize_text_field(wp_unslash($_GET[$param]));
        }

        if (!$code) {
            $code = get_query_var('ttpa_ref');
            $code = sanitize_text_field($code);
        }

        if (!$code) {
            return;
        }

        $referrer_id = $this->referrals->get_user_id_by_code($code);
        if (!$referrer_id || !$this->referrals->is_affiliate_enabled($referrer_id)) {
            return;
        }

        if (is_user_logged_in() && get_current_user_id() === $referrer_id) {
            return;
        }

        $this->set_cookie($code, $referrer_id);
        $landing = (is_ssl() ? 'https://' : 'http://') . (isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '') . (isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '');
        $this->referrals->log_click($referrer_id, $code, $landing);
    }

    public function handle_pretty_referral() {
        $code = get_query_var('ttpa_ref');
        if (!$code) {
            return;
        }

        wp_safe_redirect(home_url('/'));
        exit;
    }

    public static function get_cookie_code() {
        return isset($_COOKIE[TTPA_COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[TTPA_COOKIE])) : '';
    }

    private function set_cookie($code, $referrer_id = 0) {
        $days   = $referrer_id > 0 ? $this->referrals->get_tracking_days($referrer_id) : (int) TTPA_COOKIE_DAYS;
        $expire = time() + (DAY_IN_SECONDS * max(1, $days));
        setcookie(TTPA_COOKIE, $code, $expire, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE[TTPA_COOKIE] = $code;
    }
}
