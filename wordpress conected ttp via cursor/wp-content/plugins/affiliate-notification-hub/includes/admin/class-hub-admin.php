<?php

namespace ANH\Admin;

use ANH\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Hub_Admin {

    public function hooks() {
        add_action('admin_menu', [$this, 'register_menu'], 9);
        add_action('admin_menu', [$this, 'hide_legacy_menus'], 999);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_post_anh_create_notification', [$this, 'handle_create_notification']);
        add_action('admin_post_anh_generate_affiliate_link', [$this, 'handle_generate_affiliate_link']);
        add_action('admin_post_anh_save_affiliate_access', [$this, 'handle_save_affiliate_access']);
        add_action('admin_post_anh_update_referral_member', [$this, 'handle_update_referral_member']);
        add_action('admin_post_anh_revoke_referral_access', [$this, 'handle_revoke_referral_access']);
        add_action('admin_post_anh_revoke_all_referral_access', [$this, 'handle_revoke_all_referral_access']);
        add_action('admin_post_anh_regenerate_referral_code', [$this, 'handle_regenerate_referral_code']);
        add_action('wp_ajax_anh_search_users', [$this, 'ajax_search_users']);
    }

    public function register_menu() {
        $cap = 'manage_options';

        add_menu_page(
            __('Affiliate Hub', 'anh-hub'),
            __('Affiliate Hub', 'anh-hub'),
            $cap,
            'anh-hub',
            [$this, 'render_dashboard'],
            'dashicons-groups',
            56
        );

        add_submenu_page('anh-hub', __('Dashboard', 'anh-hub'), __('Dashboard', 'anh-hub'), $cap, 'anh-hub', [$this, 'render_dashboard']);
        add_submenu_page('anh-hub', __('Affiliate Links', 'anh-hub'), __('Affiliate Links', 'anh-hub'), $cap, 'anh-affiliate-links', [$this, 'render_affiliate_links']);
        add_submenu_page('anh-hub', __('Referral Program', 'anh-hub'), __('Referral Program', 'anh-hub'), $cap, 'anh-referrals', [$this, 'render_referral_program']);
        add_submenu_page('anh-hub', __('Referral Members', 'anh-hub'), __('Referral Members', 'anh-hub'), $cap, 'anh-referral-members', [$this, 'render_referral_members']);
        add_submenu_page('anh-hub', __('Create Notification', 'anh-hub'), __('Create Notification', 'anh-hub'), $cap, 'anh-create-notification', [$this, 'render_create_notification']);
        add_submenu_page('anh-hub', __('Commissions', 'anh-hub'), __('Commissions', 'anh-hub'), $cap, 'anh-commissions', [$this, 'redirect_ttpa_commissions']);
        add_submenu_page('anh-hub', __('Payouts', 'anh-hub'), __('Payouts', 'anh-hub'), $cap, 'anh-payouts', [$this, 'redirect_ttpa_payouts']);
        add_submenu_page('anh-hub', __('Notifications Log', 'anh-hub'), __('Notifications', 'anh-hub'), $cap, 'anh-notifications-log', [$this, 'redirect_ttpn_log']);
        add_submenu_page('anh-hub', __('Site Popup', 'anh-hub'), __('Site Popup', 'anh-hub'), $cap, 'anh-promo-popup', [$this, 'render_promo_popup']);
        add_submenu_page('anh-hub', __('Settings', 'anh-hub'), __('Settings', 'anh-hub'), $cap, 'anh-settings', [$this, 'render_settings']);
    }

    public function render_promo_popup() {
        $admin = new Promo_Popup_Admin();
        $admin->render_page();
    }

    public function hide_legacy_menus() {
        remove_menu_page('ttp-affiliate');
        remove_menu_page('ttp-notifications');
    }

    public function render_referral_program() {
        if (class_exists('TTPA_Referral_Service')) {
            \TTPA_Referral_Service::ensure_influencer_role_exists();
        }

        $referrals = Plugin::instance()->referrals();
        $members   = $referrals ? $referrals->get_enabled_affiliates(['limit' => 500]) : [];
        $signups   = $referrals ? $referrals->get_referrals(['limit' => 50]) : [];

        include ANH_PATH . 'templates/admin-referral-program.php';
    }

    public function redirect_ttpa_commissions() {
        wp_safe_redirect(admin_url('admin.php?page=ttp-affiliate-commissions'));
        exit;
    }

    public function redirect_ttpa_payouts() {
        wp_safe_redirect(admin_url('admin.php?page=ttp-affiliate-payouts'));
        exit;
    }

    public function redirect_ttpn_log() {
        wp_safe_redirect(admin_url('admin.php?page=ttp-notifications-log'));
        exit;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'anh-') === false && $hook !== 'toplevel_page_anh-hub') {
            return;
        }

        wp_enqueue_style('anh-admin', ANH_URL . 'assets/css/admin.css', [], ANH_VERSION);

        if (false !== strpos($hook, 'anh-affiliate-links') || false !== strpos($hook, 'anh-create-notification')) {
            $this->enqueue_user_search_assets();
        }

        if (false !== strpos($hook, 'anh-referrals') || false !== strpos($hook, 'anh-referral-members')) {
            wp_enqueue_script('anh-admin', ANH_URL . 'assets/js/admin.js', ['jquery'], ANH_VERSION, true);
        }

        if ($hook === 'affiliate-hub_page_anh-promo-popup') {
            $promo_admin = new Promo_Popup_Admin();
            $promo_admin->enqueue_assets($hook);
        }
    }

    private function enqueue_user_search_assets() {
        $deps = ['jquery'];
        $has_select = false;

        if (class_exists('WooCommerce')) {
            if (!wp_script_is('selectWoo', 'registered')) {
                wp_register_script(
                    'selectWoo',
                    plugins_url('assets/js/selectWoo/selectWoo.full.min.js', WC_PLUGIN_FILE),
                    ['jquery'],
                    defined('WC_VERSION') ? WC_VERSION : '1.0',
                    true
                );
                wp_register_style(
                    'select2',
                    plugins_url('assets/css/select2.css', WC_PLUGIN_FILE),
                    [],
                    defined('WC_VERSION') ? WC_VERSION : '1.0'
                );
            }

            wp_enqueue_style('select2');
            wp_enqueue_script('selectWoo');
            $deps[] = 'selectWoo';
            $has_select = true;
        }

        if (!$has_select) {
            wp_enqueue_style(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css',
                [],
                '4.0.13'
            );
            wp_enqueue_script(
                'select2',
                'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js',
                ['jquery'],
                '4.0.13',
                true
            );
            $deps[] = 'select2';
        }

        wp_enqueue_script('anh-admin', ANH_URL . 'assets/js/admin.js', $deps, ANH_VERSION, true);
        wp_localize_script('anh-admin', 'anhAdmin', [
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('anh_admin'),
            'affiliatePage' => admin_url('admin.php?page=anh-affiliate-links'),
        ]);
    }

    /**
     * AJAX user search for SelectWoo (name, login, or email).
     *
     * @return void
     */
    public function ajax_search_users() {
        check_ajax_referer('anh_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Forbidden', 'anh-hub')], 403);
        }

        $query = isset($_REQUEST['q']) ? sanitize_text_field(wp_unslash($_REQUEST['q'])) : '';
        if (strlen($query) < 2) {
            wp_send_json_success(['results' => []]);
        }

        $user_query = new \WP_User_Query([
            'number'         => 30,
            'search'         => '*' . $query . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'orderby'        => 'display_name',
            'order'          => 'ASC',
        ]);

        $referrals = Plugin::instance()->referrals();
        $results   = [];
        foreach ($user_query->get_results() as $user) {
            if (!$user instanceof \WP_User) {
                continue;
            }

            $label = $this->format_user_search_label($user, $referrals);
            $results[] = [
                'id'   => (int) $user->ID,
                'text' => $label,
            ];
        }

        if (empty($results) && is_email($query)) {
            $user = get_user_by('email', $query);
            if ($user instanceof \WP_User) {
                $results[] = [
                    'id'   => (int) $user->ID,
                    'text' => $this->format_user_search_label($user, $referrals),
                ];
            }
        }

        wp_send_json_success(['results' => $results]);
    }

    /**
     * @param \WP_User                    $user      User.
     * @param \TTPA_Referral_Service|null $referrals Referral service.
     * @return string
     */
    private function format_user_search_label($user, $referrals) {
        $name = trim((string) $user->display_name);
        if ($name === '' || is_email($name)) {
            $local = strstr((string) $user->user_email, '@', true);
            $name  = is_string($local) && $local !== '' ? $local : (string) $user->user_login;
        }

        $label = $name . ' (' . $user->user_email . ')';
        if ($referrals && $referrals->is_influencer($user->ID)) {
            $label .= ' — ' . __('Influencer', 'anh-hub');
        } elseif ($referrals && $referrals->is_affiliate_enabled($user->ID)) {
            $label .= ' — ' . __('Referral enabled', 'anh-hub');
        }

        return $label;
    }

    public function render_dashboard() {
        $referrals = Plugin::instance()->referrals();
        $notifs    = Plugin::instance()->notifications();

        $affiliate_stats = $referrals ? $referrals->get_stats() : [];
        $notif_stats     = $notifs ? $notifs->get_stats() : [];
        $leaderboard     = $referrals ? $referrals->get_leaderboard(5) : [];
        $enabled_count   = $referrals ? $referrals->count_enabled_affiliates() : 0;
        $enabled_members = $referrals ? $referrals->get_enabled_affiliates(['limit' => 50]) : [];

        include ANH_PATH . 'templates/admin-hub-dashboard.php';
    }

    public function render_referral_members() {
        $referrals = Plugin::instance()->referrals();
        $members   = $referrals ? $referrals->get_enabled_affiliates(['limit' => 500]) : [];

        include ANH_PATH . 'templates/admin-referral-members.php';
    }

    public function render_affiliate_links() {
        if (class_exists('TTPA_Referral_Service')) {
            \TTPA_Referral_Service::ensure_influencer_role_exists();
        }

        $referrals = Plugin::instance()->referrals();
        $selected  = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $link      = '';
        $code      = '';
        $affiliate_enabled = false;
        $is_influencer     = false;
        $selected_user     = null;
        $enabled_members   = $referrals ? $referrals->get_enabled_affiliates(['limit' => 100]) : [];
        $commission_rate   = (float) get_option('ttpa_commission_rate', 10);
        $default_commission_rate = $commission_rate;

        if ($selected && $referrals) {
            $selected_user     = get_userdata($selected);
            $affiliate_enabled = $referrals->is_affiliate_enabled($selected);
            $is_influencer     = $referrals->is_influencer($selected);
            $commission_rate   = $referrals->get_commission_rate($selected);

            if ($affiliate_enabled) {
                $code = get_user_meta($selected, 'ttpa_referral_code', true);
                if (!$code) {
                    $code = $referrals->get_or_create_code($selected);
                }
                $link = $referrals->get_referral_link($selected);
            }
        }

        include ANH_PATH . 'templates/admin-affiliate-links.php';
    }

    public function render_create_notification() {
        include ANH_PATH . 'templates/admin-create-notification.php';
    }

    public function render_settings() {
        $referral_param = get_option('ttpa_referral_param', 'ref');
        $commission     = (float) get_option('ttpa_commission_rate', 10);
        $enable_push    = (bool) get_option('ttpn_enable_push', true);
        $enable_in_app  = (bool) get_option('ttpn_enable_in_app', true);

        include ANH_PATH . 'templates/admin-settings.php';
    }

    public function handle_create_notification() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        check_admin_referer('anh_create_notification');

        $title   = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $message = wp_kses_post(wp_unslash($_POST['message'] ?? ''));
        $type    = sanitize_key($_POST['type'] ?? 'general');
        $link    = esc_url_raw(wp_unslash($_POST['link'] ?? ''));
        $target  = sanitize_key($_POST['target'] ?? 'single');
        $push    = !empty($_POST['send_push']);
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $duration = isset($_POST['duration_seconds']) ? absint($_POST['duration_seconds']) : 5;
        $duration = max(3, min(120, $duration));

        if ($link === 'https://' || $link === 'http://') {
            $link = '';
        }

        $service = Plugin::instance()->notifications();
        if (!$service || $title === '' || $message === '') {
            wp_safe_redirect(admin_url('admin.php?page=anh-create-notification&error=1'));
            exit;
        }

        $args = [
            'type'            => $type,
            'link'            => $link,
            'push'            => $push,
            'push_immediate'  => 'single' === $target,
            'meta'            => [
                'toast_duration_seconds' => $duration,
            ],
        ];
        $sent = 0;

        if (function_exists('wp_raise_memory_limit')) {
            wp_raise_memory_limit('admin');
        }

        @set_time_limit(in_array($target, ['all', 'affiliates', 'csv'], true) ? 300 : 60);

        if ('all' === $target) {
            $sent = $service->create_for_all_users($title, $message, $args);
        } elseif ('affiliates' === $target) {
            $sent = $service->create_for_affiliates($title, $message, $args);
        } elseif ('csv' === $target) {
            $user_ids = $this->parse_csv_user_ids();
            if (empty($user_ids)) {
                wp_safe_redirect(admin_url('admin.php?page=anh-create-notification&csv_error=1'));
                exit;
            }
            $args['push_immediate'] = false;
            $sent = $service->create_for_users($user_ids, $title, $message, $args);
        } elseif ($user_id > 0) {
            $created = $service->create($user_id, $title, $message, $args);
            $sent    = $created ? 1 : 0;
        }

        wp_safe_redirect(admin_url('admin.php?page=anh-create-notification&sent=' . (int) $sent));
        exit;
    }

    /**
     * Parse uploaded CSV and return matching user IDs.
     *
     * @return int[]
     */
    private function parse_csv_user_ids() {
        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            return [];
        }

        $file_type = wp_check_filetype($_FILES['csv_file']['name'] ?? '');
        if (!in_array($file_type['ext'], ['csv', 'txt'], true)) {
            return [];
        }

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            return [];
        }

        $user_ids      = [];
        $email_index   = null;
        $user_id_index = null;
        $row_number    = 0;

        while (($row = fgetcsv($handle)) !== false) {
            ++$row_number;
            if (!is_array($row) || empty($row)) {
                continue;
            }

            $row = array_map(static function ($cell) {
                return trim((string) $cell);
            }, $row);

            if ($row_number === 1) {
                foreach ($row as $index => $cell) {
                    $key = strtolower($cell);
                    if ('email' === $key) {
                        $email_index = (int) $index;
                    } elseif (in_array($key, ['user_id', 'userid', 'id'], true)) {
                        $user_id_index = (int) $index;
                    }
                }

                if (null !== $email_index || null !== $user_id_index) {
                    continue;
                }
            }

            if (null !== $user_id_index && isset($row[$user_id_index]) && is_numeric($row[$user_id_index])) {
                $user_ids[] = (int) $row[$user_id_index];
                continue;
            }

            $email = '';
            if (null !== $email_index && isset($row[$email_index])) {
                $email = sanitize_email($row[$email_index]);
            } elseif (isset($row[0]) && is_email($row[0])) {
                $email = sanitize_email($row[0]);
            } elseif (isset($row[1]) && is_email($row[1])) {
                $email = sanitize_email($row[1]);
            }

            if ($email !== '') {
                $user = get_user_by('email', $email);
                if ($user instanceof \WP_User) {
                    $user_ids[] = (int) $user->ID;
                }
            }
        }

        fclose($handle);

        return array_values(array_unique(array_filter(array_map('intval', $user_ids))));
    }

    public function handle_save_affiliate_access() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        check_admin_referer('anh_save_affiliate_access');

        $user_id   = (int) ($_POST['user_id'] ?? 0);
        $referrals = Plugin::instance()->referrals();
        $enabled   = !empty($_POST['affiliate_enabled']);
        $generate  = isset($_POST['generate_link']);
        $influencer = !empty($_POST['grant_influencer']);
        $commission = isset($_POST['commission_rate']) ? (float) $_POST['commission_rate'] : null;

        if ($user_id && $referrals) {
            if ($influencer) {
                $referrals->set_influencer_role($user_id, true);
            } elseif ($referrals->is_influencer($user_id)) {
                $referrals->set_influencer_role($user_id, false);
            }

            if (!$referrals->is_influencer($user_id)) {
                $referrals->set_affiliate_enabled($user_id, $enabled, [
                    'source'      => 'manual',
                    'granted_by'  => get_current_user_id(),
                ]);
            } elseif ($enabled) {
                $referrals->set_affiliate_enabled($user_id, true, [
                    'source'      => 'influencer_role',
                    'granted_by'  => get_current_user_id(),
                ]);
            }

            if ($commission !== null && ($enabled || $referrals->is_influencer($user_id))) {
                $referrals->set_commission_rate($user_id, $commission);
            }

            if ($generate && $referrals->is_affiliate_enabled($user_id)) {
                $referrals->get_or_create_code($user_id);
                wp_safe_redirect(admin_url('admin.php?page=anh-affiliate-links&user_id=' . $user_id . '&generated=1'));
                exit;
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=anh-affiliate-links&user_id=' . $user_id . '&access_updated=1'));
        exit;
    }

    public function handle_generate_affiliate_link() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        check_admin_referer('anh_generate_affiliate_link');

        $user_id = (int) ($_POST['user_id'] ?? 0);
        $referrals = Plugin::instance()->referrals();

        if ($user_id && $referrals) {
            $referrals->set_affiliate_enabled($user_id, true, [
                'source'     => 'manual',
                'granted_by' => get_current_user_id(),
            ]);
            $referrals->get_or_create_code($user_id);
        }

        wp_safe_redirect(admin_url('admin.php?page=anh-affiliate-links&user_id=' . $user_id . '&generated=1'));
        exit;
    }

    public function handle_update_referral_member() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        check_admin_referer('anh_update_referral_member');

        $user_id   = (int) ($_POST['user_id'] ?? 0);
        $referrals = Plugin::instance()->referrals();

        if ($user_id && $referrals) {
            $granted = isset($_POST['access_granted_at']) ? sanitize_text_field(wp_unslash($_POST['access_granted_at'])) : '';
            $expires = isset($_POST['access_expires_at']) ? sanitize_text_field(wp_unslash($_POST['access_expires_at'])) : '';
            $days    = isset($_POST['tracking_days']) ? absint($_POST['tracking_days']) : 0;
            $extend  = isset($_POST['extend_days']) ? absint($_POST['extend_days']) : 0;
            $commission = isset($_POST['commission_rate']) ? (float) $_POST['commission_rate'] : null;

            if ($granted !== '') {
                $granted = $granted . ' 00:00:00';
            }
            if ($expires !== '') {
                $expires = $expires . ' 23:59:59';
            }

            $referrals->update_access_schedule($user_id, $granted, $expires);

            if ($days > 0) {
                $referrals->set_tracking_days($user_id, $days);
            }

            if ($extend > 0) {
                $referrals->extend_access_days($user_id, $extend);
            }

            if ($commission !== null) {
                $referrals->set_commission_rate($user_id, $commission);
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=anh-referrals&updated=1'));
        exit;
    }

    public function handle_revoke_referral_access() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        $user_id = (int) ($_GET['user_id'] ?? 0);
        check_admin_referer('anh_revoke_referral_access_' . $user_id);

        $referrals = Plugin::instance()->referrals();
        if ($user_id && $referrals) {
            $referrals->revoke_all_access($user_id);
        }

        $redirect = isset($_GET['redirect']) ? sanitize_key(wp_unslash($_GET['redirect'])) : '';
        $redirect_pages = [
            'members' => 'anh-referral-members',
            'links'   => 'anh-affiliate-links',
        ];
        $redirect_page = $redirect_pages[$redirect] ?? 'anh-referrals';
        $args = ['revoked' => 1];
        if ($redirect_page === 'anh-affiliate-links' && $user_id > 0) {
            $args['user_id'] = $user_id;
        }
        wp_safe_redirect(add_query_arg($args, admin_url('admin.php?page=' . $redirect_page)));
        exit;
    }

    public function handle_revoke_all_referral_access() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        check_admin_referer('anh_revoke_all_referral_access');

        $referrals = Plugin::instance()->referrals();
        $count     = 0;
        if ($referrals && method_exists($referrals, 'revoke_everyone')) {
            $result = $referrals->revoke_everyone();
            $count  = (int) ($result['revoked'] ?? 0);
        }

        wp_safe_redirect(add_query_arg(
            [
                'revoked_all' => 1,
                'count'       => $count,
            ],
            admin_url('admin.php?page=anh-referral-members')
        ));
        exit;
    }

    public function handle_regenerate_referral_code() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'anh-hub'));
        }

        $user_id = (int) ($_GET['user_id'] ?? 0);
        check_admin_referer('anh_regenerate_referral_code_' . $user_id);

        $referrals = Plugin::instance()->referrals();
        if ($user_id && $referrals) {
            $referrals->regenerate_referral_code($user_id);
        }

        wp_safe_redirect(admin_url('admin.php?page=anh-referrals&regenerated=1'));
        exit;
    }
}
