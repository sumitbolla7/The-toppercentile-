<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Integration {

    private $notifications;
    private $admin_alerts;

    public function __construct($notifications, $admin_alerts) {
        $this->notifications = $notifications;
        $this->admin_alerts  = $admin_alerts;
    }

    public function hooks() {
        add_action('user_register', [$this, 'on_user_register'], 20, 1);
        add_action('woocommerce_new_order', [$this, 'on_new_order'], 20, 1);
        add_action('woocommerce_order_status_completed', [$this, 'on_order_completed'], 20, 1);

        add_action('ttp_affiliate_referral_registered', [$this, 'on_referral_registered'], 10, 3);
        add_action('ttp_affiliate_commission_created', [$this, 'on_commission_created'], 10, 4);
        add_action('ttp_affiliate_payout_processed', [$this, 'on_payout_processed'], 10, 3);

        TTPN_Plugin::instance()->push()->hooks();
    }

    public function on_user_register($user_id) {
        if (!get_option('ttpn_enable_admin_alerts', true)) {
            return;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $this->admin_alerts->create(
            __('New user registered', 'ttp-notifications'),
            sprintf(
                /* translators: 1: display name, 2: email */
                __('User %1$s (%2$s) just registered.', 'ttp-notifications'),
                $user->display_name,
                $user->user_email
            ),
            [
                'alert_type'    => 'user_register',
                'object_type'   => 'user',
                'object_id'     => $user_id,
                'link'          => admin_url('user-edit.php?user_id=' . $user_id),
            ]
        );
    }

    public function on_new_order($order_id) {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->admin_alerts->create(
            __('New order placed', 'ttp-notifications'),
            sprintf(
                /* translators: 1: order number, 2: total */
                __('Order #%1$s for %2$s was placed.', 'ttp-notifications'),
                $order->get_order_number(),
                wp_strip_all_tags(wc_price($order->get_total()))
            ),
            [
                'alert_type'  => 'new_order',
                'object_type' => 'order',
                'object_id'   => $order_id,
                'link'        => admin_url('post.php?post=' . $order_id . '&action=edit'),
            ]
        );
    }

    public function on_order_completed($order_id) {
        if (!function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $customer_id = $order->get_customer_id();
        if ($customer_id > 0) {
            $this->notifications->create(
                $customer_id,
                __('Order completed', 'ttp-notifications'),
                sprintf(
                    __('Your order #%s is complete. Thank you for learning with us!', 'ttp-notifications'),
                    $order->get_order_number()
                ),
                [
                    'type' => 'order',
                    'link' => $order->get_view_order_url(),
                ]
            );
        }
    }

    public function on_referral_registered($referrer_id, $referred_id, $code) {
        $referred = get_userdata($referred_id);
        $name     = $referred ? $referred->display_name : __('Someone', 'ttp-notifications');

        $this->notifications->create(
            $referrer_id,
            __('New referral signup', 'ttp-notifications'),
            sprintf(__('Your referral link was used — %s just signed up!', 'ttp-notifications'), $name),
            ['type' => 'referral', 'link' => home_url('/my-account/referrals/')]
        );

        $this->admin_alerts->create(
            __('Referral signup tracked', 'ttp-notifications'),
            sprintf(__('Referrer #%1$d acquired referral from code %2$s.', 'ttp-notifications'), $referrer_id, $code),
            [
                'alert_type'  => 'referral',
                'object_type' => 'user',
                'object_id'   => $referred_id,
            ]
        );
    }

    public function on_commission_created($referrer_id, $amount, $order_id, $commission_id) {
        $price = function_exists('wc_price') ? wp_strip_all_tags(wc_price($amount)) : (function_exists('ttpa_format_money') ? wp_strip_all_tags(ttpa_format_money($amount)) : '₹' . number_format((float) $amount, 2));

        $this->notifications->create(
            $referrer_id,
            __('Commission earned', 'ttp-notifications'),
            sprintf(
                __('You earned %s commission on order #%d.', 'ttp-notifications'),
                wp_strip_all_tags($price),
                $order_id
            ),
            ['type' => 'commission', 'link' => home_url('/my-account/referrals/')]
        );

        $price = function_exists('wc_price') ? wc_price($amount) : (function_exists('ttpa_format_money') ? ttpa_format_money($amount) : '₹' . number_format((float) $amount, 2));

        $this->admin_alerts->create(
            __('Affiliate commission created', 'ttp-notifications'),
            sprintf(__('Commission #%1$d of %2$s recorded for user #%3$d.', 'ttp-notifications'), $commission_id, $price, $referrer_id),
            [
                'alert_type'  => 'commission',
                'object_type' => 'commission',
                'object_id'   => $commission_id,
                'link'        => admin_url('admin.php?page=ttp-affiliate-commissions'),
            ]
        );
    }

    public function on_payout_processed($user_id, $amount, $payout_id) {
        $price = function_exists('wc_price') ? wp_strip_all_tags(wc_price($amount)) : (function_exists('ttpa_format_money') ? wp_strip_all_tags(ttpa_format_money($amount)) : '₹' . number_format((float) $amount, 2));

        $this->notifications->create(
            $user_id,
            __('Payout processed', 'ttp-notifications'),
            sprintf(__('Your affiliate payout of %s has been processed.', 'ttp-notifications'), $price),
            ['type' => 'payout', 'link' => home_url('/my-account/referrals/')]
        );

        $price = function_exists('wc_price') ? wc_price($amount) : (function_exists('ttpa_format_money') ? ttpa_format_money($amount) : '₹' . number_format((float) $amount, 2));

        $this->admin_alerts->create(
            __('Affiliate payout processed', 'ttp-notifications'),
            sprintf(__('Payout #%1$d of %2$s sent to user #%3$d.', 'ttp-notifications'), $payout_id, $price, $user_id),
            [
                'alert_type'  => 'payout',
                'object_type' => 'payout',
                'object_id'   => $payout_id,
                'link'        => admin_url('admin.php?page=ttp-affiliate-payouts'),
            ]
        );
    }
}
