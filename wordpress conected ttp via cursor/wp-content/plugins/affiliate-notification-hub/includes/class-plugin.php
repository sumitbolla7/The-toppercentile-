<?php

namespace ANH;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {

    /** @var self|null */
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot() {
        add_action('admin_notices', [$this, 'dependency_notice']);

        (new Frontend\Promo_Display())->hooks();
        (new Admin\Promo_Popup_Admin())->hooks();

        if (!$this->dependencies_ready()) {
            return;
        }

        (new Admin\Hub_Admin())->hooks();
        (new Frontend\Shortcodes())->hooks();

        do_action('anh_hub_booted');
    }

    public function dependencies_ready() {
        return class_exists('TTPA_Plugin') && class_exists('TTPN_Plugin');
    }

    public function dependency_notice() {
        if ($this->dependencies_ready()) {
            return;
        }

        $missing = [];
        if (!class_exists('TTPA_Plugin')) {
            $missing[] = 'TTP Affiliate & Referral';
        }
        if (!class_exists('TTPN_Plugin')) {
            $missing[] = 'TTP Notifications';
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Affiliate & Notification Hub requires the following plugins to be installed and activated:',
            'anh-hub'
        );
        echo ' <strong>' . esc_html(implode(', ', $missing)) . '</strong>';
        echo '</p></div>';
    }

    /** @return \TTPA_Referral_Service|null */
    public function referrals() {
        return $this->dependencies_ready() ? \TTPA_Plugin::instance()->referrals() : null;
    }

    /** @return \TTPN_Notification_Service|null */
    public function notifications() {
        return $this->dependencies_ready() ? \TTPN_Plugin::instance()->notifications() : null;
    }
}
