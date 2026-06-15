<?php

namespace TTP_CRM\Core;

use TTP_CRM\Database\ContactRepository;

defined('ABSPATH') || exit;

class LoginIntegration
{
    /**
     * @var ContactRepository
     */
    private $contacts;

    public function __construct(ContactRepository $contacts)
    {
        $this->contacts = $contacts;
    }

    public function register_hooks()
    {
        add_action('wp_login', array($this, 'handle_login'), 10, 2);
        add_action('user_register', array($this, 'handle_register'), 10, 1);
        // After UR saves usermeta (ttp-phone-checkout-sync at 20).
        add_action('user_registration_after_user_meta_update', array($this, 'handle_ur_register_complete'), 30, 3);
        add_action('user_registration_after_register_user_action', array($this, 'handle_ur_register_complete'), 100, 3);
        add_filter('user_registration_membership_after_register_member', array($this, 'handle_membership_register_complete'), 10, 1);
        add_action('init', array($this, 'maybe_sync_current_user'), 20);
    }

    public function handle_login($user_login, $user)
    {
        unset($user_login);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[TTP CRM] wp_login hook fired for ' . ($user instanceof \WP_User ? $user->user_email : 'unknown')); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        $this->contacts->sync_wp_user_login($user, 'login');
    }

    public function handle_register($user_id)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[TTP CRM] user_register hook fired for user_id=' . (int) $user_id); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        $user = get_user_by('id', (int) $user_id);
        if ($user instanceof \WP_User) {
            $this->contacts->sync_wp_user_login($user, 'register');
        }
    }

    /**
     * Re-sync after User Registration form (and phone meta) is fully saved.
     *
     * @param array $valid_form_data Form data.
     * @param int   $form_id         Form ID.
     * @param int   $user_id         User ID.
     * @return void
     */
    public function handle_ur_register_complete($valid_form_data, $form_id, $user_id)
    {
        unset($form_id);
        $user_id = (int) $user_id;
        if ($user_id < 1) {
            return;
        }

        if (is_array($valid_form_data)) {
            if (function_exists('ttp_phone_extract_from_ur_form') && function_exists('ttp_phone_save_for_user')) {
                $phone = ttp_phone_extract_from_ur_form($valid_form_data);
                if ($phone !== '') {
                    ttp_phone_save_for_user($user_id, $phone);
                }
            }
            if (function_exists('ttp_ur_save_name_for_user')) {
                ttp_ur_save_name_for_user($user_id, $valid_form_data);
            }
        }

        $user = get_user_by('id', $user_id);
        if ($user instanceof \WP_User) {
            $this->contacts->sync_wp_user_login($user, 'register');
        }
    }

    /**
     * Re-sync after membership checkout completes (phone may be saved by then).
     *
     * @param array $response Membership AJAX response.
     * @return array
     */
    public function handle_membership_register_complete($response)
    {
        if (!empty($response['member_id'])) {
            $user = get_user_by('id', (int) $response['member_id']);
            if ($user instanceof \WP_User) {
                $this->contacts->sync_wp_user_login($user, 'register');
            }
        }

        return $response;
    }

    public function maybe_sync_current_user()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        if (!$user instanceof \WP_User || empty($user->user_email)) {
            return;
        }

        $last_synced = (int) get_user_meta($user->ID, '_ttp_crm_last_sync', true);
        $now         = time();

        // Throttle to avoid extra DB writes on every page load.
        if ($last_synced > 0 && ($now - $last_synced) < (10 * MINUTE_IN_SECONDS)) {
            return;
        }

        $this->contacts->sync_wp_user_login($user, 'login');
        update_user_meta($user->ID, '_ttp_crm_last_sync', $now);
    }
}
