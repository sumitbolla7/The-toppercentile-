<?php

namespace ANH\Frontend;

use ANH\Promo_Popup;

if (!defined('ABSPATH')) {
    exit;
}

class Promo_Display {

    public function hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_status_probe'], 25);
        add_action('wp_ajax_anh_promo_status', [$this, 'ajax_status']);
        add_action('wp_ajax_nopriv_anh_promo_status', [$this, 'ajax_status']);
        add_action('wp_footer', [$this, 'render_popup'], 5);
        add_action('wp_footer', [$this, 'suppress_if_disabled'], 999);
    }

    public function ajax_status() {
        wp_send_json([
            'enabled' => Promo_Popup::should_display(),
        ]);
    }

    /**
     * Live check bypasses stale LiteSpeed HTML that still embeds an old popup shell.
     */
    public function enqueue_status_probe() {
        if (is_admin()) {
            return;
        }

        $status_url = admin_url('admin-ajax.php?action=anh_promo_status');
        wp_register_script('anh-promo-status', false, [], ANH_VERSION, true);
        wp_enqueue_script('anh-promo-status');
        wp_add_inline_script(
            'anh-promo-status',
            '(function(){fetch(' . wp_json_encode($status_url) . ',{credentials:"same-origin",cache:"no-store"}).then(function(r){return r.json();}).then(function(d){if(!d||!d.enabled){var p=document.getElementById("anh-promo-popup");if(p){p.remove();}document.querySelectorAll("link[href*=\\"promo-popup.css\\"],script[src*=\\"promo-popup.js\\"]").forEach(function(el){el.remove();});document.body.style.overflow="";}}).catch(function(){});})();'
        );
    }

    public function suppress_if_disabled() {
        if (Promo_Popup::should_display()) {
            return;
        }

        echo '<script id="anh-promo-disabled-cleanup">(function(){var p=document.getElementById("anh-promo-popup");if(p){p.remove();}document.querySelectorAll("link[href*=\\"promo-popup.css\\"],script[src*=\\"promo-popup.js\\"]").forEach(function(el){el.remove();});document.body.style.overflow="";})();</script>';
    }

    public function enqueue_assets() {
        if (!Promo_Popup::should_display()) {
            return;
        }

        wp_enqueue_style('anh-promo-popup', ANH_URL . 'assets/css/promo-popup.css', [], ANH_VERSION);
        wp_enqueue_script('anh-promo-popup', ANH_URL . 'assets/js/promo-popup.js', [], ANH_VERSION, true);

        $settings = Promo_Popup::get_settings();
        wp_localize_script('anh-promo-popup', 'anhPromoPopup', [
            'delay'           => (int) $settings['delay_seconds'],
            'showOnceSession' => (bool) $settings['show_once_session'],
            'countdownEnd'    => Promo_Popup::countdown_iso(),
            'showCountdown'   => (bool) $settings['show_countdown'],
            'statusUrl'       => admin_url('admin-ajax.php?action=anh_promo_status'),
        ]);
    }

    public function render_popup() {
        if (!Promo_Popup::should_display()) {
            return;
        }

        $settings = Promo_Popup::get_settings();
        include ANH_PATH . 'templates/promo-popup.php';
    }
}
