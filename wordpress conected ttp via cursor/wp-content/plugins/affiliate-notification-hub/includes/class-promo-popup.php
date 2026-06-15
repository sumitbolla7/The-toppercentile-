<?php

namespace ANH;

if (!defined('ABSPATH')) {
    exit;
}

class Promo_Popup {

    const OPTION_KEY = 'anh_promo_popup_settings';

    /**
     * @return array<string,mixed>
     */
    public static function defaults() {
        return [
            'enabled'           => 0,
            'title'             => 'Flash Sale',
            'headline'          => '50% OFF',
            'subtitle'          => 'ON ENTIRE ORDER',
            'description'       => 'LIMITED-TIME OFFER! SALE ENDS IN',
            'countdown_end'     => '',
            'show_countdown'    => 1,
            'image_url'         => '',
            'button_text'       => 'Shop The Flash Sale Now',
            'button_url'        => '',
            'dismiss_text'      => 'NO, THANKS!',
            'accent_color'      => '#e91e63',
            'panel_color'       => '#fce4ec',
            'delay_seconds'     => 2,
            'show_once_session' => 1,
            'show_logged_in'    => 1,
            'show_guests'       => 1,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function get_settings() {
        $saved = get_option(self::OPTION_KEY, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        $merged = array_merge(self::defaults(), $saved);
        if (empty($merged['button_url'])) {
            $merged['button_url'] = home_url('/');
        }

        return $merged;
    }

    /**
     * @param array<string,mixed> $input Raw settings.
     * @return array<string,mixed>
     */
    public static function sanitize_settings($input) {
        $defaults = self::defaults();
        $out      = [];

        $out['enabled']           = !empty($input['enabled']) ? 1 : 0;
        $out['title']             = sanitize_text_field($input['title'] ?? $defaults['title']);
        $out['headline']          = sanitize_text_field($input['headline'] ?? $defaults['headline']);
        $out['subtitle']          = sanitize_text_field($input['subtitle'] ?? $defaults['subtitle']);
        $out['description']       = sanitize_text_field($input['description'] ?? $defaults['description']);
        $out['countdown_end']     = sanitize_text_field($input['countdown_end'] ?? '');
        $out['show_countdown']    = !empty($input['show_countdown']) ? 1 : 0;
        $out['image_url']         = esc_url_raw($input['image_url'] ?? '');
        $out['button_text']       = sanitize_text_field($input['button_text'] ?? $defaults['button_text']);
        $out['button_url']        = esc_url_raw($input['button_url'] ?? $defaults['button_url']);
        $out['dismiss_text']      = sanitize_text_field($input['dismiss_text'] ?? $defaults['dismiss_text']);
        $out['accent_color']      = sanitize_hex_color($input['accent_color'] ?? $defaults['accent_color']) ?: $defaults['accent_color'];
        $out['panel_color']       = sanitize_hex_color($input['panel_color'] ?? $defaults['panel_color']) ?: $defaults['panel_color'];
        $out['delay_seconds']     = max(0, min(60, absint($input['delay_seconds'] ?? $defaults['delay_seconds'])));
        $out['show_once_session'] = !empty($input['show_once_session']) ? 1 : 0;
        $out['show_logged_in']   = !empty($input['show_logged_in']) ? 1 : 0;
        $out['show_guests']        = !empty($input['show_guests']) ? 1 : 0;

        return $out;
    }

    public static function should_display() {
        $s = self::get_settings();
        if (empty($s['enabled'])) {
            return false;
        }

        if (is_admin()) {
            return false;
        }

        if (is_user_logged_in() && empty($s['show_logged_in'])) {
            return false;
        }

        if (!is_user_logged_in() && empty($s['show_guests'])) {
            return false;
        }

        if (!empty($s['countdown_end']) && strtotime($s['countdown_end']) < time()) {
            return false;
        }

        return true;
    }

    /**
     * Countdown end as ISO string for JS.
     *
     * @return string
     */
    public static function countdown_iso() {
        $end = (string) self::get_settings()['countdown_end'];
        if ($end === '') {
            return '';
        }

        $ts = strtotime($end);
        if (!$ts) {
            return '';
        }

        return wp_date('c', $ts);
    }
}
