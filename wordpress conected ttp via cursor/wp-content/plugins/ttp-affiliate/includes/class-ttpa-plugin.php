<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once TTPA_PATH . 'includes/class-ttpa-database.php';
require_once TTPA_PATH . 'includes/class-ttpa-referral-service.php';
require_once TTPA_PATH . 'includes/class-ttpa-commission-service.php';
require_once TTPA_PATH . 'includes/class-ttpa-payout-service.php';
require_once TTPA_PATH . 'includes/class-ttpa-tracker.php';
require_once TTPA_PATH . 'includes/class-ttpa-woocommerce.php';
require_once TTPA_PATH . 'includes/class-ttpa-admin.php';
require_once TTPA_PATH . 'includes/class-ttpa-frontend.php';

class TTPA_Plugin {

    /** @var self|null */
    private static $instance = null;

    /** @var TTPA_Referral_Service */
    private $referrals;

    /** @var TTPA_Commission_Service */
    private $commissions;

    /** @var TTPA_Payout_Service */
    private $payouts;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->referrals   = new TTPA_Referral_Service();
        $this->commissions = new TTPA_Commission_Service();
        $this->payouts     = new TTPA_Payout_Service($this->commissions);
    }

    public function boot() {
        TTPA_Referral_Service::ensure_influencer_role_exists();

        add_action('user_register', [$this, 'maybe_enable_influencer_referrals'], 20);
        add_action('set_user_role', [$this, 'maybe_enable_influencer_on_role_change'], 10, 3);

        (new TTPA_Tracker($this->referrals))->hooks();
        (new TTPA_WooCommerce($this->referrals, $this->commissions))->hooks();
        (new TTPA_Admin($this->referrals, $this->commissions, $this->payouts))->hooks();
        (new TTPA_Frontend($this->referrals, $this->commissions, $this->payouts))->hooks();

        add_action('ttpa_process_auto_payouts', [$this->payouts, 'process_auto_payouts']);
        add_action('user_register', [$this, 'link_referral_on_register'], 30, 1);
    }

    public function maybe_enable_influencer_referrals($user_id) {
        if ($this->referrals->is_influencer($user_id)) {
            $this->referrals->set_affiliate_enabled($user_id, true);
        } else {
            update_user_meta((int) $user_id, 'ttpa_affiliate_enabled', 0);
        }
    }

    public function maybe_enable_influencer_on_role_change($user_id, $role, $old_roles) {
        unset($old_roles, $role);
        if ($this->referrals->is_influencer($user_id)) {
            $this->referrals->set_affiliate_enabled($user_id, true);
        }
    }

    public function link_referral_on_register($user_id) {
        $code = TTPA_Tracker::get_cookie_code();
        if (!$code) {
            return;
        }

        $referrer_id = $this->referrals->get_user_id_by_code($code);
        if (!$referrer_id || (int) $referrer_id === (int) $user_id) {
            return;
        }

        $referral_id = $this->referrals->create_referral($referrer_id, $user_id, $code);
        if ($referral_id) {
            do_action('ttp_affiliate_referral_registered', $referrer_id, $user_id, $code);
        }
    }

    /** @return TTPA_Referral_Service */
    public function referrals() {
        return $this->referrals;
    }

    /** @return TTPA_Commission_Service */
    public function commissions() {
        return $this->commissions;
    }

    /** @return TTPA_Payout_Service */
    public function payouts() {
        return $this->payouts;
    }
}
