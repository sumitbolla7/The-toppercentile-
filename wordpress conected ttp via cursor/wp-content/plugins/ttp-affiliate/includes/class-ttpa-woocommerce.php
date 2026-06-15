<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_WooCommerce {

    private $referrals;
    private $commissions;

    public function __construct($referrals, $commissions) {
        $this->referrals   = $referrals;
        $this->commissions = $commissions;
    }

    public function hooks() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('woocommerce_order_status_completed', [$this, 'process_order_commission'], 20, 1);
        add_action('woocommerce_checkout_order_processed', [$this, 'attach_referral_to_order'], 20, 1);
    }

    public function attach_referral_to_order($order_id) {
        $code = TTPA_Tracker::get_cookie_code();
        if (!$code) {
            return;
        }

        $referrer_id = $this->referrals->get_user_id_by_code($code);
        if (!$referrer_id || !function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $customer_id = $order->get_customer_id();
        if ($customer_id && (int) $customer_id === (int) $referrer_id) {
            return;
        }

        $order->update_meta_data('_ttpa_referrer_id', $referrer_id);
        $order->update_meta_data('_ttpa_referral_code', $code);
        $order->save();
    }

    public function process_order_commission($order_id) {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $referrer_id = (int) $order->get_meta('_ttpa_referrer_id');

        if (!$referrer_id) {
            $customer_id = $order->get_customer_id();
            if ($customer_id) {
                $referral = $this->referrals->get_referral_for_user($customer_id);
                if ($referral) {
                    $referrer_id = (int) $referral['referrer_user_id'];
                }
            }
        }

        if (!$referrer_id) {
            return;
        }

        $this->commissions->create_from_order($referrer_id, $order);
    }
}
