<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Admin {

    private $referrals;
    private $commissions;
    private $payouts;

    public function __construct($referrals, $commissions, $payouts) {
        $this->referrals   = $referrals;
        $this->commissions = $commissions;
        $this->payouts     = $payouts;
    }

    public function hooks() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_ttpa_save_settings', [$this, 'handle_save_settings']);
        add_action('admin_post_ttpa_process_payout', [$this, 'handle_process_payout']);
        add_action('admin_post_ttpa_update_commission', [$this, 'handle_update_commission']);
    }

    public function register_menu() {
        $cap = 'manage_options';

        add_menu_page(
            __('TTP Affiliate', 'ttp-affiliate'),
            __('TTP Affiliate', 'ttp-affiliate'),
            $cap,
            'ttp-affiliate',
            [$this, 'render_dashboard'],
            'dashicons-share-alt2',
            58
        );

        add_submenu_page('ttp-affiliate', __('Dashboard', 'ttp-affiliate'), __('Dashboard', 'ttp-affiliate'), $cap, 'ttp-affiliate', [$this, 'render_dashboard']);
        add_submenu_page('ttp-affiliate', __('Referrals', 'ttp-affiliate'), __('Referrals', 'ttp-affiliate'), $cap, 'ttp-affiliate-referrals', [$this, 'render_referrals']);
        add_submenu_page('ttp-affiliate', __('Commissions', 'ttp-affiliate'), __('Commissions', 'ttp-affiliate'), $cap, 'ttp-affiliate-commissions', [$this, 'render_commissions']);
        add_submenu_page('ttp-affiliate', __('Payouts', 'ttp-affiliate'), __('Payouts', 'ttp-affiliate'), $cap, 'ttp-affiliate-payouts', [$this, 'render_payouts']);
        add_submenu_page('ttp-affiliate', __('Leaderboard', 'ttp-affiliate'), __('Leaderboard', 'ttp-affiliate'), $cap, 'ttp-affiliate-leaderboard', [$this, 'render_leaderboard']);
        add_submenu_page('ttp-affiliate', __('Settings', 'ttp-affiliate'), __('Settings', 'ttp-affiliate'), $cap, 'ttp-affiliate-settings', [$this, 'render_settings']);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'ttp-affiliate') === false) {
            return;
        }

        wp_enqueue_style('ttpa-admin', TTPA_URL . 'assets/css/admin.css', [], TTPA_VERSION);
    }

    public function render_dashboard() {
        $stats       = $this->referrals->get_stats();
        $leaderboard = $this->referrals->get_leaderboard(5);
        include TTPA_PATH . 'templates/admin-dashboard.php';
    }

    public function render_referrals() {
        $items = $this->referrals->get_referrals(['limit' => 100]);
        include TTPA_PATH . 'templates/admin-referrals.php';
    }

    public function render_commissions() {
        $items = $this->commissions->get_list(['limit' => 100]);
        include TTPA_PATH . 'templates/admin-commissions.php';
    }

    public function render_payouts() {
        $items = $this->payouts->get_list(['limit' => 100]);
        include TTPA_PATH . 'templates/admin-payouts.php';
    }

    public function render_leaderboard() {
        $items = $this->referrals->get_leaderboard(50);
        include TTPA_PATH . 'templates/admin-leaderboard.php';
    }

    public function render_settings() {
        $settings = [
            'commission_rate'          => (float) get_option('ttpa_commission_rate', 10),
            'auto_approve_commissions' => (bool) get_option('ttpa_auto_approve_commissions', 1),
            'auto_payout_enabled'      => (bool) get_option('ttpa_auto_payout_enabled', 0),
            'payout_threshold'         => (float) get_option('ttpa_payout_threshold', 500),
            'referral_param'           => get_option('ttpa_referral_param', 'ref'),
        ];
        include TTPA_PATH . 'templates/admin-settings.php';
    }

    public function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-affiliate'));
        }
        check_admin_referer('ttpa_save_settings');

        update_option('ttpa_commission_rate', max(0, min(100, (float) ($_POST['commission_rate'] ?? 10))));
        update_option('ttpa_auto_approve_commissions', !empty($_POST['auto_approve_commissions']));
        update_option('ttpa_auto_payout_enabled', !empty($_POST['auto_payout_enabled']));
        update_option('ttpa_payout_threshold', max(0, (float) ($_POST['payout_threshold'] ?? 500)));
        update_option('ttpa_referral_param', sanitize_key($_POST['referral_param'] ?? 'ref'));

        wp_safe_redirect(admin_url('admin.php?page=ttp-affiliate-settings&updated=1'));
        exit;
    }

    public function handle_process_payout() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-affiliate'));
        }
        check_admin_referer('ttpa_process_payout');

        $user_id = (int) ($_POST['user_id'] ?? 0);
        $amount  = (float) ($_POST['amount'] ?? 0);
        $method  = sanitize_text_field(wp_unslash($_POST['payment_method'] ?? 'manual'));
        $ref     = sanitize_text_field(wp_unslash($_POST['payment_reference'] ?? ''));

        if ($user_id && $amount > 0) {
            $this->payouts->create_payout($user_id, $amount, [
                'payment_method'    => $method,
                'payment_reference' => $ref,
            ]);
        }

        wp_safe_redirect(admin_url('admin.php?page=ttp-affiliate-payouts&payout=1'));
        exit;
    }

    public function handle_update_commission() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'ttp-affiliate'));
        }
        check_admin_referer('ttpa_update_commission');

        $id     = (int) ($_POST['commission_id'] ?? 0);
        $status = sanitize_key($_POST['status'] ?? '');

        if ($id && in_array($status, ['pending', 'approved', 'paid'], true)) {
            $this->commissions->update_status($id, $status);
        }

        wp_safe_redirect(admin_url('admin.php?page=ttp-affiliate-commissions&updated=1'));
        exit;
    }
}
