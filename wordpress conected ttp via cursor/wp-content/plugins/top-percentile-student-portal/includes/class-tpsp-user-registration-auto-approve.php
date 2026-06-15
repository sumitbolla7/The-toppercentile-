<?php
/**
 * User Registration: auto-approve users stuck on "Admin approval" (login + new signups + optional bulk).
 *
 * Skips entirely if the standalone "TTP UR Auto Approve" plugin is active (same hooks).
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_User_Registration_Auto_Approve {
    /**
     * @return bool
     */
    public static function is_feature_enabled() {
        $opts = get_option('tpsp_settings', []);
        if (!is_array($opts)) {
            return true;
        }
        if (!array_key_exists('ur_auto_approve', $opts)) {
            return true;
        }

        return isset($opts['ur_auto_approve']) && 'yes' === $opts['ur_auto_approve'];
    }

    /**
     * @return bool
     */
    public static function is_user_registration_active() {
        return defined('UR_VERSION') || class_exists('UserRegistration', false);
    }

    /**
     * @return void
     */
    public function hooks() {
        if (!self::is_user_registration_active() || !self::is_feature_enabled()) {
            return;
        }
        if (function_exists('ttp_ur_max_unlock_ur_user_for_login')) {
            return;
        }

        add_action('user_registration_after_register_user_action', [$this, 'on_register'], 20, 3);
        add_filter('authenticate', [$this, 'on_authenticate'], 30, 3);
        add_action('user_registration_check_token_complete', [$this, 'on_email_token'], 999, 2);
        add_action('user_registration_login_process_before_username_validation', [$this, 'on_ur_login_start'], 1, 4);
        add_filter('wp_authenticate_user', [$this, 'on_wp_authenticate_user'], 0, 2);
        add_filter('login_errors', [$this, 'fix_resend_href'], 999);
        add_action('admin_post_tpsp_ur_bulk_approve', [$this, 'handle_bulk_approve']);
    }

    /**
     * @param int $user_id User ID.
     * @return string
     */
    private function resolve_login_option($user_id) {
        $user_id = (int) $user_id;
        if ($user_id < 1 || !function_exists('ur_get_user_login_option')) {
            return '';
        }
        $login_option = ur_get_user_login_option($user_id);
        if ('' !== (string) $login_option) {
            return (string) $login_option;
        }

        return $this->assigned_form_login_option($user_id);
    }

    /**
     * UR form post login option for this user (ur_form_id meta first).
     *
     * @param int $user_id User ID.
     * @return string
     */
    private function assigned_form_login_option($user_id) {
        $user_id = (int) $user_id;
        if ($user_id < 1 || !function_exists('ur_get_single_post_meta')) {
            return '';
        }
        $form_id = (int) get_user_meta($user_id, 'ur_form_id', true);
        if ($form_id < 1 && function_exists('ur_get_form_id_by_userid')) {
            $form_id = (int) ur_get_form_id_by_userid($user_id);
        }
        if ($form_id < 1) {
            return '';
        }

        return (string) ur_get_single_post_meta(
            $form_id,
            'user_registration_form_setting_login_options',
            get_option('user_registration_general_setting_login_options', 'default')
        );
    }

    /**
     * @param int  $user_id    User ID.
     * @param bool $alert_user Notify user via UR emails.
     * @return void
     */
    private function ur_approve($user_id, $alert_user = false) {
        $user_id = (int) $user_id;
        if ($user_id < 1 || !class_exists('UR_Admin_User_Manager', false)) {
            return;
        }
        try {
            $mgr = new UR_Admin_User_Manager($user_id);
            $mgr->save_status(UR_Admin_User_Manager::APPROVED, $alert_user);
        } catch (\Throwable $e) {
            unset($e);
            update_user_meta($user_id, 'ur_user_status', 1);
        }
    }

    /**
     * Approve without UR class (bulk fallback if save_status fatals).
     *
     * @param int $user_id User ID.
     * @return void
     */
    private function ur_approve_meta_only($user_id) {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return;
        }
        update_user_meta($user_id, 'ur_user_status', 1);
    }

    /**
     * @param int $user_id User ID.
     * @return void
     */
    private function unlock_for_login($user_id) {
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return;
        }
        $form_uid = (int) get_user_meta($user_id, 'ur_form_id', true);
        if ($form_uid < 1 && function_exists('ur_get_form_id_by_userid')) {
            $form_uid = (int) ur_get_form_id_by_userid($user_id);
        }
        if ($form_uid < 1) {
            return;
        }
        if (!function_exists('ur_string_to_bool') || !function_exists('ur_get_user_login_option')) {
            return;
        }
        if ('denied' === get_user_meta($user_id, 'ur_admin_approval_after_email_confirmation', true)) {
            return;
        }

        $login_option         = $this->resolve_login_option($user_id);
        $form_login_option    = $this->assigned_form_login_option($user_id);
        $status_login_option  = $login_option;
        $user_status          = null;

        if (class_exists('UR_Admin_User_Manager', false)) {
            try {
                $mgr          = new UR_Admin_User_Manager($user_id);
                $status_array = $mgr->get_user_status();
                if (!empty($status_array['login_option'])) {
                    $status_login_option = (string) $status_array['login_option'];
                }
                if (isset($status_array['user_status'])) {
                    $user_status = (int) $status_array['user_status'];
                }
            } catch (\Throwable $e) {
                unset($e);
            }
        }

        $raw_meta_status = get_user_meta($user_id, 'ur_user_status', true);
        if (null === $user_status) {
            $user_status = (int) $raw_meta_status;
        }

        $admin_modes        = ['admin_approval', 'admin_approval_after_email_confirmation'];
        $uses_admin         = in_array($login_option, $admin_modes, true) || in_array($status_login_option, $admin_modes, true) || in_array($form_login_option, $admin_modes, true);
        $is_admin_after     = in_array('admin_approval_after_email_confirmation', [$login_option, $status_login_option, $form_login_option], true);
        $login_option_gate  = $login_option;
        if ('' === $login_option_gate && '' !== $form_login_option) {
            $login_option_gate = $form_login_option;
        }

        if ($uses_admin) {
            if (class_exists('UR_Admin_User_Manager', false)) {
                try {
                    $mgr    = new UR_Admin_User_Manager($user_id);
                    $status = $mgr->get_user_status();
                    if (isset($status['user_status']) && UR_Admin_User_Manager::DENIED === (int) $status['user_status']) {
                        return;
                    }
                    if (isset($status['user_status']) && UR_Admin_User_Manager::APPROVED === (int) $status['user_status']) {
                        if ($is_admin_after) {
                            update_user_meta($user_id, 'ur_confirm_email', 1);
                            update_user_meta($user_id, 'ur_admin_approval_after_email_confirmation', 'true');
                            update_user_meta($user_id, 'ur_user_status', UR_Admin_User_Manager::APPROVED);
                        }
                        update_user_meta($user_id, 'tpsp_email_verified', 1);
                        delete_user_meta($user_id, 'tpsp_email_verification_token');
                        delete_user_meta($user_id, 'tpsp_email_verification_expiry');
                        return;
                    }
                } catch (\Throwable $e) {
                    unset($e);
                }
            } elseif (-1 === (int) $user_status) {
                return;
            }

            if (!class_exists('UR_Admin_User_Manager', false) || UR_Admin_User_Manager::APPROVED !== (int) $user_status) {
                $this->ur_approve($user_id, false);
            }

            if ($is_admin_after) {
                update_user_meta($user_id, 'ur_confirm_email', 1);
                update_user_meta($user_id, 'ur_admin_approval_after_email_confirmation', 'true');
                update_user_meta($user_id, 'ur_user_status', UR_Admin_User_Manager::APPROVED);
            }

            update_user_meta($user_id, 'tpsp_email_verified', 1);
            delete_user_meta($user_id, 'tpsp_email_verification_token');
            delete_user_meta($user_id, 'tpsp_email_verification_expiry');

            return;
        }

        if (!ur_string_to_bool(get_user_meta($user_id, 'ur_confirm_email', true))) {
            return;
        }

        if ('email_confirmation' === $login_option_gate) {
            update_user_meta($user_id, 'ur_user_status', 1);
        }

        update_user_meta($user_id, 'tpsp_email_verified', 1);
        delete_user_meta($user_id, 'tpsp_email_verification_token');
        delete_user_meta($user_id, 'tpsp_email_verification_expiry');
    }

    /**
     * @param mixed $form_data Unused.
     * @param int   $form_id   Form ID.
     * @param int   $user_id   User ID.
     * @return void
     */
    public function on_register($form_data, $form_id, $user_id) {
        unset($form_data, $form_id);
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return;
        }
        $fid = (int) get_user_meta($user_id, 'ur_form_id', true);
        if ($fid < 1 && function_exists('ur_get_form_id_by_userid')) {
            $fid = (int) ur_get_form_id_by_userid($user_id);
        }
        if ($fid < 1) {
            return;
        }
        $login_opt = $this->resolve_login_option($user_id);
        $form_opt  = $this->assigned_form_login_option($user_id);
        $admin_ok  = in_array($login_opt, ['admin_approval', 'admin_approval_after_email_confirmation'], true)
            || in_array($form_opt, ['admin_approval', 'admin_approval_after_email_confirmation'], true);
        if (!$admin_ok) {
            return;
        }
        $this->ur_approve($user_id, false);
        if ('admin_approval_after_email_confirmation' === $login_opt || 'admin_approval_after_email_confirmation' === $form_opt) {
            update_user_meta($user_id, 'ur_confirm_email', 1);
            update_user_meta($user_id, 'ur_admin_approval_after_email_confirmation', 'true');
        }
    }

    /**
     * @param WP_User|WP_Error|null $user     User.
     * @param string                $username Username.
     * @param string                $password Password.
     * @return WP_User|WP_Error|null
     */
    public function on_authenticate($user, $username, $password) {
        unset($username, $password);
        if (!$user instanceof WP_User) {
            return $user;
        }
        $this->unlock_for_login($user->ID);

        return $user;
    }

    /**
     * @param int  $user_id             User ID.
     * @param bool $user_reg_successful Success.
     * @return void
     */
    public function on_email_token($user_id, $user_reg_successful) {
        if (!$user_reg_successful) {
            return;
        }
        $this->unlock_for_login((int) $user_id);
        $user = get_user_by('id', $user_id);
        if ($user instanceof WP_User) {
            wp_set_current_user((int) $user_id);
            wp_set_auth_cookie((int) $user_id);
        }
    }

    /**
     * @param array  $post        POST.
     * @param string $username    Username.
     * @param string $nonce_value Nonce.
     * @param array  $messages    Messages.
     * @return void
     */
    public function on_ur_login_start($post, $username, $nonce_value, $messages) {
        unset($post, $nonce_value, $messages);
        $username = trim((string) $username);
        if ('' === $username) {
            return;
        }
        $user = is_email($username) ? get_user_by('email', $username) : get_user_by('login', $username);
        if (!$user instanceof WP_User) {
            return;
        }
        $this->unlock_for_login($user->ID);
    }

    /**
     * @param WP_User|WP_Error $user     User.
     * @param string           $password Password.
     * @return WP_User|WP_Error
     */
    public function on_wp_authenticate_user($user, $password) {
        unset($password);
        if (!$user instanceof WP_User) {
            return $user;
        }
        $this->unlock_for_login($user->ID);

        return $user;
    }

    /**
     * @param string $message HTML.
     * @return string
     */
    public function fix_resend_href($message) {
        if (!is_string($message) || '' === $message) {
            return $message;
        }
        if (false === strpos($message, 'ur_resend_id')) {
            return $message;
        }
        $fixed = preg_replace('#https?://([a-z0-9][a-z0-9.-]*\.[a-z]{2,})(?=https?://)#i', '', $message);
        if (!is_string($fixed)) {
            return $message;
        }
        $fixed = preg_replace('#([a-z0-9][a-z0-9.-]*\.[a-z]{2,})https//#i', '$1/https://', $fixed);

        return $fixed;
    }

    /**
     * @return int
     */
    public static function count_pending_ur_users() {
        global $wpdb;
        if (!isset($wpdb->usermeta)) {
            return 0;
        }
        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT u.user_id) FROM {$wpdb->usermeta} u
            INNER JOIN {$wpdb->usermeta} s ON s.user_id = u.user_id AND s.meta_key = %s AND s.meta_value = %s
            WHERE u.meta_key = %s AND u.meta_value <> ''",
            'ur_user_status',
            '0',
            'ur_form_id'
        );

        return (int) $wpdb->get_var($sql);
    }

    /**
     * @return int
     */
    public static function bulk_approve_pending_admin_approval() {
        if (!function_exists('ur_get_user_login_option')) {
            return 0;
        }
        $q = new WP_User_Query([
            'number'     => 500,
            'fields'     => 'ID',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => 'ur_form_id',
                    'compare' => 'EXISTS',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'ur_user_status',
                        'value'   => '0',
                        'compare' => '=',
                    ],
                    [
                        'key'     => 'ur_user_status',
                        'value'   => 0,
                        'compare' => '=',
                    ],
                ],
            ],
        ]);
        $ids   = $q->get_results();
        $count = 0;
        $self  = new self();
        foreach ((array) $ids as $uid) {
            $uid = (int) $uid;
            if ($uid < 1 || !get_userdata($uid)) {
                continue;
            }
            try {
                if (!in_array($self->resolve_login_option($uid), ['admin_approval', 'admin_approval_after_email_confirmation'], true)) {
                    continue;
                }
                $self->ur_approve($uid, false);
            } catch (\Throwable $e) {
                unset($e);
                $self->ur_approve_meta_only($uid);
            }
            ++$count;
        }

        return $count;
    }

    /**
     * @return void
     */
    public function handle_bulk_approve() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied.', 'tpsp'));
        }
        check_admin_referer('tpsp_ur_bulk_approve', 'tpsp_ur_bulk_nonce');

        if (!function_exists('ur_get_user_login_option')) {
            $url = add_query_arg(
                [
                    'page'               => 'tpsp-settings',
                    'tpsp_ur_bulk_error' => '1',
                ],
                admin_url('admin.php')
            );
            wp_safe_redirect($url);
            exit;
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        try {
            $n = self::bulk_approve_pending_admin_approval();
        } catch (\Throwable $e) {
            unset($e);
            $n = 0;
        }

        $url = add_query_arg(
            [
                'page'               => 'tpsp-settings',
                'tpsp_ur_bulk_done'  => '1',
                'tpsp_ur_bulk_count' => (string) $n,
            ],
            admin_url('admin.php')
        );
        wp_safe_redirect($url);
        exit;
    }
}
