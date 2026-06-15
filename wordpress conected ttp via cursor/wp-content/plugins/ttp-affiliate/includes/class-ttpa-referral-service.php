<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Referral_Service {

    public function get_or_create_code($user_id = 0) {
        $user_id = $user_id ?: get_current_user_id();
        if ($user_id <= 0) {
            return '';
        }

        $code = get_user_meta($user_id, 'ttpa_referral_code', true);
        if ($code) {
            return $code;
        }

        if (!$this->is_affiliate_enabled($user_id)) {
            return '';
        }

        $code = $this->generate_unique_code($user_id);
        update_user_meta($user_id, 'ttpa_referral_code', $code);

        return $code;
    }

    public function influencer_roles() {
        return apply_filters('ttpa_influencer_roles', ['influencer']);
    }

    public function is_influencer($user_id) {
        $user = get_userdata((int) $user_id);
        if (!$user) {
            return false;
        }

        return (bool) array_intersect((array) $user->roles, $this->influencer_roles());
    }

    /**
     * Referral access requires an explicit admin audit trail (granted_by user id).
     *
     * @param int $user_id User ID.
     * @return bool
     */
    public function is_admin_granted_access($user_id) {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }

        $granted_by = (int) get_user_meta($user_id, 'ttpa_access_granted_by', true);

        return $granted_by > 0;
    }

    public function is_affiliate_enabled($user_id) {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }

        if ($this->is_access_expired($user_id)) {
            return false;
        }

        if (!$this->is_admin_granted_access($user_id)) {
            return false;
        }

        if ($this->is_influencer($user_id)) {
            return true;
        }

        $enabled = get_user_meta($user_id, 'ttpa_affiliate_enabled', true);

        return $enabled === '1' || $enabled === 1 || $enabled === true;
    }

    public function is_access_expired($user_id) {
        $expires = (string) get_user_meta((int) $user_id, 'ttpa_access_expires_at', true);
        if ($expires === '') {
            return false;
        }

        $timestamp = strtotime($expires);
        if (!$timestamp) {
            return false;
        }

        return $timestamp < current_time('timestamp');
    }

    /**
     * @param int $user_id User ID.
     * @return array<string,mixed>
     */
    public function get_access_details($user_id) {
        $user_id = (int) $user_id;
        $granted = (string) get_user_meta($user_id, 'ttpa_access_granted_at', true);
        $expires = (string) get_user_meta($user_id, 'ttpa_access_expires_at', true);
        $days    = (int) get_user_meta($user_id, 'ttpa_tracking_days', true);

        if ($days <= 0) {
            $days = (int) TTPA_COOKIE_DAYS;
        }

        return [
            'granted_at'    => $granted,
            'expires_at'    => $expires,
            'tracking_days' => $days,
            'is_expired'    => $this->is_access_expired($user_id),
            'is_influencer' => $this->is_influencer($user_id),
            'is_enabled'    => $this->is_affiliate_enabled($user_id),
        ];
    }

    public function get_tracking_days($user_id) {
        $days = (int) get_user_meta((int) $user_id, 'ttpa_tracking_days', true);

        return $days > 0 ? $days : (int) TTPA_COOKIE_DAYS;
    }

    public function set_tracking_days($user_id, $days) {
        $days = max(1, min(365, (int) $days));
        update_user_meta((int) $user_id, 'ttpa_tracking_days', $days);

        return $days;
    }

    public function update_access_schedule($user_id, $granted_at = '', $expires_at = '') {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }

        if ($granted_at !== '') {
            update_user_meta($user_id, 'ttpa_access_granted_at', sanitize_text_field($granted_at));
        }

        if ($expires_at === '') {
            delete_user_meta($user_id, 'ttpa_access_expires_at');
        } else {
            update_user_meta($user_id, 'ttpa_access_expires_at', sanitize_text_field($expires_at));
        }

        return true;
    }

    public function extend_access_days($user_id, $extra_days) {
        $user_id    = (int) $user_id;
        $extra_days = max(1, (int) $extra_days);
        $expires    = (string) get_user_meta($user_id, 'ttpa_access_expires_at', true);
        $base       = $expires !== '' ? strtotime($expires) : current_time('timestamp');

        if (!$base || $base < current_time('timestamp')) {
            $base = current_time('timestamp');
        }

        $new_expires = wp_date('Y-m-d H:i:s', $base + ($extra_days * DAY_IN_SECONDS));
        update_user_meta($user_id, 'ttpa_access_expires_at', $new_expires);

        return $new_expires;
    }

    public function regenerate_referral_code($user_id) {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$this->is_affiliate_enabled($user_id)) {
            return '';
        }

        delete_user_meta($user_id, 'ttpa_referral_code');

        return $this->get_or_create_code($user_id);
    }

    /**
     * Commission rate for a referrer (%). Uses per-user override or site default.
     *
     * @param int $user_id User ID.
     * @return float
     */
    public function get_commission_rate($user_id) {
        $stored = get_user_meta((int) $user_id, 'ttpa_commission_rate', true);
        if ($stored !== '' && $stored !== false && is_numeric($stored)) {
            return max(0.0, min(100.0, (float) $stored));
        }

        return max(0.0, min(100.0, (float) get_option('ttpa_commission_rate', 10)));
    }

    /**
     * @param int   $user_id User ID.
     * @param float $rate    Percentage 0–100.
     * @return float
     */
    public function set_commission_rate($user_id, $rate) {
        $rate = max(0.0, min(100.0, (float) $rate));
        update_user_meta((int) $user_id, 'ttpa_commission_rate', $rate);

        return $rate;
    }

    public function revoke_all_access($user_id) {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }

        $this->set_influencer_role($user_id, false);
        update_user_meta($user_id, 'ttpa_affiliate_enabled', 0);
        delete_user_meta($user_id, 'ttpa_referral_code');
        delete_user_meta($user_id, 'ttpa_access_expires_at');
        delete_user_meta($user_id, 'ttpa_access_granted_at');
        delete_user_meta($user_id, 'ttpa_access_source');
        delete_user_meta($user_id, 'ttpa_access_granted_by');

        return true;
    }

    /**
     * Revoke referral access for every user who has any affiliate flags/roles.
     *
     * @return array{revoked: int, users: array<int, array<string, mixed>>}
     */
    public function revoke_everyone() {
        global $wpdb;

        $candidate_ids = [];

        $meta_ids = $wpdb->get_col(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key IN ('ttpa_affiliate_enabled', 'ttpa_referral_code', 'ttpa_access_granted_by')
               AND meta_value <> '' AND meta_value <> '0'"
        );
        foreach ((array) $meta_ids as $uid) {
            $candidate_ids[] = (int) $uid;
        }

        foreach (['influencer', 'affiliate'] as $role) {
            $users = get_users([
                'role'   => $role,
                'fields' => 'ID',
                'number' => 1000,
            ]);
            foreach ((array) $users as $uid) {
                $candidate_ids[] = (int) $uid;
            }
        }

        $candidate_ids = array_values(array_unique(array_filter(array_map('intval', $candidate_ids))));
        $revoked       = [];
        foreach ($candidate_ids as $user_id) {
            if ($user_id < 1) {
                continue;
            }
            $user = get_userdata($user_id);
            if (!$user instanceof WP_User) {
                continue;
            }
            $this->revoke_all_access($user_id);
            if (in_array('affiliate', (array) $user->roles, true)) {
                $user->remove_role('affiliate');
            }
            $revoked[] = [
                'user_id' => $user_id,
                'email'   => $user->user_email,
                'name'    => $user->display_name,
            ];
        }

        $this->purge_stale_affiliate_flags();

        return [
            'revoked' => count($revoked),
            'users'   => $revoked,
        ];
    }

    /**
     * Clear leftover affiliate meta that does not have a valid admin grant.
     *
     * @return int Meta rows cleaned.
     */
    public function purge_stale_affiliate_flags() {
        global $wpdb;

        $user_ids = $wpdb->get_col(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key IN ('ttpa_affiliate_enabled', 'ttpa_referral_code', 'ttpa_access_source', 'ttpa_access_granted_at', 'ttpa_access_expires_at')
               AND meta_value <> ''"
        );

        $cleaned = 0;
        foreach ((array) $user_ids as $user_id) {
            $user_id = (int) $user_id;
            if ($user_id < 1 || $this->is_affiliate_enabled($user_id)) {
                continue;
            }
            $this->revoke_all_access($user_id);
            $user = get_userdata($user_id);
            if ($user instanceof WP_User && in_array('affiliate', (array) $user->roles, true)) {
                $user->remove_role('affiliate');
            }
            ++$cleaned;
        }

        return $cleaned;
    }

    /**
     * Human-readable reason this user has (or had) referral access.
     *
     * @param int $user_id User ID.
     * @return string manual|influencer_role|legacy_role|none
     */
    public function get_access_source($user_id) {
        $user_id = (int) $user_id;
        $user    = get_userdata($user_id);
        if (!$user instanceof WP_User) {
            return 'none';
        }

        $stored = sanitize_key((string) get_user_meta($user_id, 'ttpa_access_source', true));
        if ($stored !== '') {
            return $stored;
        }

        if (in_array('influencer', (array) $user->roles, true)) {
            return 'influencer_role';
        }

        $enabled = get_user_meta($user_id, 'ttpa_affiliate_enabled', true);
        if ($enabled === '1' || $enabled === 1 || $enabled === true) {
            return $this->is_admin_granted_access($user_id) ? 'manual' : 'stale_flag';
        }

        if (in_array('affiliate', (array) $user->roles, true)) {
            return 'legacy_role';
        }

        return 'none';
    }

    /**
     * @param int $user_id User ID.
     * @return string
     */
    public function get_access_source_label($user_id) {
        $labels = [
            'manual'          => __('Manual (admin enabled)', 'ttp-affiliate'),
            'influencer_role' => __('Influencer role (admin enabled)', 'ttp-affiliate'),
            'legacy_role'     => __('Legacy WP role only (not admin-enabled)', 'ttp-affiliate'),
            'stale_flag'      => __('Stale flag — not admin-approved', 'ttp-affiliate'),
            'none'            => __('No access', 'ttp-affiliate'),
        ];

        $source = $this->get_access_source($user_id);

        return $labels[$source] ?? $source;
    }

    /**
     * @param int $user_id User ID.
     * @return string
     */
    public function get_access_granted_by_label($user_id) {
        $granted_by = (int) get_user_meta((int) $user_id, 'ttpa_access_granted_by', true);
        if ($granted_by < 1) {
            return '';
        }

        $admin = get_userdata($granted_by);
        if (!$admin instanceof WP_User) {
            return '#' . $granted_by;
        }

        return $admin->display_name . ' (' . $admin->user_email . ')';
    }

    public function set_affiliate_enabled($user_id, $enabled, $audit = []) {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return false;
        }

        update_user_meta($user_id, 'ttpa_affiliate_enabled', $enabled ? 1 : 0);

        if ($enabled) {
            if (!get_user_meta($user_id, 'ttpa_access_granted_at', true)) {
                update_user_meta($user_id, 'ttpa_access_granted_at', current_time('mysql'));
            }

            $source = isset($audit['source']) ? sanitize_key($audit['source']) : '';
            if ($source === '') {
                $source = $this->is_influencer($user_id) ? 'influencer_role' : 'manual';
            }
            update_user_meta($user_id, 'ttpa_access_source', $source);

            $granted_by = isset($audit['granted_by']) ? (int) $audit['granted_by'] : 0;
            if ($granted_by > 0) {
                update_user_meta($user_id, 'ttpa_access_granted_by', $granted_by);
                $this->get_or_create_code($user_id);
            }
        } else {
            delete_user_meta($user_id, 'ttpa_access_source');
            delete_user_meta($user_id, 'ttpa_access_granted_by');
            if (!$this->is_influencer($user_id)) {
                delete_user_meta($user_id, 'ttpa_referral_code');
            }
        }

        return true;
    }

    /**
     * Grant or remove the influencer role (auto referral access).
     *
     * @param int  $user_id User ID.
     * @param bool $grant   True to grant, false to remove.
     * @return bool
     */
    public function set_influencer_role($user_id, $grant) {
        $user_id = (int) $user_id;
        $user    = get_userdata($user_id);
        if (!$user instanceof WP_User) {
            return false;
        }

        self::ensure_influencer_role_exists();

        if ($grant) {
            $user->add_role('influencer');
            $audit = ['source' => 'influencer_role'];
            if (is_user_logged_in() && current_user_can('manage_options')) {
                $audit['granted_by'] = get_current_user_id();
            }
            $this->set_affiliate_enabled($user_id, true, $audit);
            return true;
        }

        if (in_array('influencer', (array) $user->roles, true)) {
            $user->remove_role('influencer');
        }

        if (!$this->is_affiliate_enabled($user_id)) {
            delete_user_meta($user_id, 'ttpa_referral_code');
        }

        return true;
    }

    /**
     * Register influencer role if missing.
     *
     * @return void
     */
    public static function ensure_influencer_role_exists() {
        if (!get_role('influencer')) {
            add_role(
                'influencer',
                __('Influencer', 'ttp-affiliate'),
                [
                    'read' => true,
                ]
            );
        }
    }

    public function get_referral_link($user_id = 0) {
        $user_id = $user_id ?: get_current_user_id();
        if (!$this->is_affiliate_enabled($user_id)) {
            return '';
        }

        $code  = $this->get_or_create_code($user_id);
        $param = sanitize_key(get_option('ttpa_referral_param', 'ref'));

        return add_query_arg($param, $code, home_url('/'));
    }

    public function get_user_id_by_code($code) {
        global $wpdb;

        $code = sanitize_text_field($code);
        if ($code === '') {
            return 0;
        }

        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'ttpa_referral_code' AND meta_value = %s LIMIT 1",
                $code
            )
        );

        return (int) $user_id;
    }

    public function create_referral($referrer_id, $referred_id, $code) {
        global $wpdb;

        if ($this->get_referral_by_users($referrer_id, $referred_id)) {
            return false;
        }

        $inserted = $wpdb->insert(
            TTPA_Database::referrals_table(),
            [
                'referrer_user_id' => (int) $referrer_id,
                'referred_user_id' => (int) $referred_id,
                'referral_code'    => sanitize_text_field($code),
                'status'           => 'registered',
                'ip_address'       => $this->get_ip(),
                'created_at'       => TTPA_Database::now(),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : false;
    }

    public function mark_converted($referral_id, $order_id) {
        global $wpdb;

        return (bool) $wpdb->update(
            TTPA_Database::referrals_table(),
            [
                'status'       => 'converted',
                'order_id'     => (int) $order_id,
                'converted_at' => TTPA_Database::now(),
            ],
            ['id' => (int) $referral_id],
            ['%s', '%d', '%s'],
            ['%d']
        );
    }

    public function get_referral_by_users($referrer_id, $referred_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . TTPA_Database::referrals_table() . ' WHERE referrer_user_id = %d AND referred_user_id = %d LIMIT 1',
                (int) $referrer_id,
                (int) $referred_id
            ),
            ARRAY_A
        );
    }

    public function get_referral_for_user($referred_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . TTPA_Database::referrals_table() . ' WHERE referred_user_id = %d ORDER BY id DESC LIMIT 1',
                (int) $referred_id
            ),
            ARRAY_A
        );
    }

    public function log_click($referrer_id, $code, $landing_url) {
        global $wpdb;

        return (bool) $wpdb->insert(
            TTPA_Database::clicks_table(),
            [
                'referrer_user_id' => (int) $referrer_id,
                'referral_code'    => sanitize_text_field($code),
                'landing_url'      => esc_url_raw($landing_url),
                'ip_address'       => $this->get_ip(),
                'user_agent'       => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
                'created_at'       => TTPA_Database::now(),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
    }

    public function get_referrals($args = []) {
        global $wpdb;

        $defaults = [
            'referrer_id' => 0,
            'status'      => '',
            'limit'       => 50,
            'offset'      => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $table  = TTPA_Database::referrals_table();
        $where  = ['1=1'];
        $params = [];

        if ($args['referrer_id'] > 0) {
            $where[]  = 'referrer_user_id = %d';
            $params[] = (int) $args['referrer_id'];
        }

        if ($args['status'] !== '') {
            $where[]  = 'status = %s';
            $params[] = sanitize_key($args['status']);
        }

        $where_sql = implode(' AND ', $where);
        $params[]  = (int) $args['limit'];
        $params[]  = (int) $args['offset'];

        $sql = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $params
        );

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    public function count_referrals($referrer_id = 0) {
        global $wpdb;

        $table = TTPA_Database::referrals_table();
        if ($referrer_id > 0) {
            return (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE referrer_user_id = %d", (int) $referrer_id)
            );
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    public function count_clicks($referrer_id = 0) {
        global $wpdb;

        $table = TTPA_Database::clicks_table();
        if ($referrer_id > 0) {
            return (int) $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE referrer_user_id = %d", (int) $referrer_id)
            );
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    public function get_leaderboard($limit = 10) {
        global $wpdb;

        $referrals   = TTPA_Database::referrals_table();
        $commissions = TTPA_Database::commissions_table();

        $sql = $wpdb->prepare(
            "SELECT c.referrer_user_id AS user_id,
                    COUNT(DISTINCT r.id) AS referral_count,
                    COUNT(DISTINCT c.id) AS commission_count,
                    COALESCE(SUM(c.commission_amount), 0) AS total_earned,
                    COALESCE(SUM(CASE WHEN c.status IN ('approved','paid') THEN c.commission_amount ELSE 0 END), 0) AS approved_earned
             FROM {$commissions} c
             LEFT JOIN {$referrals} r ON r.referrer_user_id = c.referrer_user_id
             GROUP BY c.referrer_user_id
             ORDER BY total_earned DESC
             LIMIT %d",
            (int) $limit
        );

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Count users with referral program access (enabled, code, or influencer role).
     *
     * @return int
     */
    public function count_enabled_affiliates() {
        return count($this->get_enabled_affiliate_user_ids());
    }

    /**
     * All users who have been given referral program access, with stats.
     *
     * @param array $args limit, offset.
     * @return array<int,array<string,mixed>>
     */
    public function get_enabled_affiliates($args = []) {
        $defaults = [
            'limit'  => 100,
            'offset' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $user_ids = $this->get_enabled_affiliate_user_ids();
        if (empty($user_ids)) {
            return [];
        }

        $rows = [];
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user instanceof WP_User) {
                continue;
            }

            $code    = (string) get_user_meta($user_id, 'ttpa_referral_code', true);
            $access  = $this->get_access_details($user_id);
            $balance = 0.0;
            $earned  = $this->get_total_earned_for_user($user_id);
            $sales   = 0.0;

            if (class_exists('TTPA_Plugin')) {
                $commissions = TTPA_Plugin::instance()->commissions();
                $balance     = $commissions->get_balance($user_id, 'approved');
                $earned      = $commissions->get_total_earned($user_id);
                $sales       = $commissions->get_total_sales($user_id);
            }

            $rows[] = [
                'user_id'                => $user_id,
                'display_name'           => $user->display_name,
                'email'                  => $user->user_email,
                'user_roles'             => implode(', ', (array) $user->roles),
                'referral_code'          => $code,
                'referral_link'          => $this->get_referral_link($user_id),
                'is_influencer'          => $this->is_influencer($user_id),
                'affiliate_enabled'      => $this->is_affiliate_enabled($user_id),
                'access_source'          => $this->get_access_source($user_id),
                'access_source_label'    => $this->get_access_source_label($user_id),
                'access_granted_by'      => $this->get_access_granted_by_label($user_id),
                'access_granted_at'      => $access['granted_at'],
                'access_expires_at'      => $access['expires_at'],
                'tracking_days'          => $access['tracking_days'],
                'is_expired'             => $access['is_expired'],
                'commission_rate'        => $this->get_commission_rate($user_id),
                'referral_count'         => $this->count_referrals($user_id),
                'click_count'            => $this->count_clicks($user_id),
                'balance'                => $balance,
                'total_sales'            => $sales,
                'total_earned'           => $earned,
            ];
        }

        usort(
            $rows,
            static function ($a, $b) {
                return strcasecmp((string) $a['display_name'], (string) $b['display_name']);
            }
        );

        return array_slice($rows, (int) $args['offset'], (int) $args['limit']);
    }

    /**
     * @return int[]
     */
    private function get_enabled_affiliate_user_ids() {
        global $wpdb;

        $enabled_ids = $wpdb->get_col(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta}
             WHERE meta_key = 'ttpa_affiliate_enabled' AND meta_value = '1'"
        );

        $role_ids = [];
        $role_query = new WP_User_Query([
            'role__in' => $this->influencer_roles(),
            'fields'   => 'ID',
            'number'   => 500,
        ]);
        foreach ($role_query->get_results() as $user_id) {
            $role_ids[] = (int) $user_id;
        }

        $all = array_unique(array_map('intval', array_merge($enabled_ids ?: [], $role_ids)));

        return array_values(array_filter($all, function ($user_id) {
            return $user_id > 0 && $this->is_affiliate_enabled((int) $user_id);
        }));
    }

    /**
     * Total commission amount earned by a referrer.
     *
     * @param int $user_id User ID.
     * @return float
     */
    public function get_total_earned_for_user($user_id) {
        global $wpdb;

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COALESCE(SUM(commission_amount), 0) FROM ' . TTPA_Database::commissions_table() . ' WHERE referrer_user_id = %d',
                (int) $user_id
            )
        );
    }

    public function get_stats() {
        global $wpdb;

        $referrals   = TTPA_Database::referrals_table();
        $commissions = TTPA_Database::commissions_table();
        $clicks      = TTPA_Database::clicks_table();

        return [
            'referrals'           => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$referrals}"),
            'converted'           => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$referrals} WHERE status = 'converted'"),
            'clicks'              => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$clicks}"),
            'commissions_total'   => (float) $wpdb->get_var("SELECT COALESCE(SUM(commission_amount),0) FROM {$commissions}"),
            'commissions_pending' => (float) $wpdb->get_var("SELECT COALESCE(SUM(commission_amount),0) FROM {$commissions} WHERE status = 'pending'"),
            'commissions_paid'    => (float) $wpdb->get_var("SELECT COALESCE(SUM(commission_amount),0) FROM {$commissions} WHERE status = 'paid'"),
        ];
    }

    private function generate_unique_code($user_id) {
        $base = strtoupper(substr(md5($user_id . wp_salt('auth')), 0, 8));
        $code = 'TTP' . $base;

        if ($this->get_user_id_by_code($code)) {
            $code = 'TTP' . strtoupper(wp_generate_password(8, false, false));
        }

        return $code;
    }

    private function get_ip() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        return substr($ip, 0, 45);
    }
}
