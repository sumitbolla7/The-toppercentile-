<?php

namespace TTP_CRM\Database;

defined('ABSPATH') || exit;

class ContactRepository
{
    const DEFAULT_STAGES = array('new', 'contacted', 'interested', 'enrolled', 'inactive');

    public function get_table_name()
    {
        global $wpdb;

        return $wpdb->prefix . 'ttp_crm_contacts';
    }

    public function all($filters = array(), $pagination = array())
    {
        global $wpdb;

        $table           = $this->get_table_name();
        $where_statement = $this->build_where_statement($filters);
        $sql             = "SELECT * FROM {$table} {$where_statement['sql']} ORDER BY id DESC";
        $args            = $where_statement['args'];

        if (!empty($pagination['per_page'])) {
            $page     = empty($pagination['page']) ? 1 : max(1, absint($pagination['page']));
            $per_page = max(1, absint($pagination['per_page']));
            $offset   = ($page - 1) * $per_page;

            $sql .= ' LIMIT %d OFFSET %d';
            $args[] = $per_page;
            $args[] = $offset;
        }

        if (!empty($args)) {
            $sql = $wpdb->prepare($sql, $args); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function find($id)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id);

        return $wpdb->get_row($sql, ARRAY_A);
    }

    public function insert($data)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $now   = current_time('mysql');

        if ($this->email_exists($data['email'])) {
            return new \WP_Error('duplicate_email', __('A contact with this email already exists.', 'ttp-crm'));
        }

        $result = $wpdb->insert(
            $table,
            array(
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'stage'      => $data['stage'],
                'tags'       => $data['tags'],
                'course_name' => $data['course_name'],
                'lead_source' => $data['lead_source'],
                'revenue_amount' => $data['revenue_amount'],
                'student_profile'       => $data['student_profile'],
                'purchase_summary'      => $data['purchase_summary'],
                'progress_notes'        => $data['progress_notes'],
                'follow_up_at'          => $data['follow_up_at'],
                'reminder_sent_at'      => null,
                'internal_notes'        => $data['internal_notes'],
                'communication_history' => $data['communication_history'],
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        return false !== $result ? true : new \WP_Error('insert_failed', __('Contact could not be saved.', 'ttp-crm'));
    }

    public function update($id, $data)
    {
        global $wpdb;

        if ($this->email_exists($data['email'], $id)) {
            return new \WP_Error('duplicate_email', __('A contact with this email already exists.', 'ttp-crm'));
        }

        $table  = $this->get_table_name();
        $result = $wpdb->update(
            $table,
            array(
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'],
                'stage'      => $data['stage'],
                'tags'       => $data['tags'],
                'course_name' => $data['course_name'],
                'lead_source' => $data['lead_source'],
                'revenue_amount' => $data['revenue_amount'],
                'student_profile'       => $data['student_profile'],
                'purchase_summary'      => $data['purchase_summary'],
                'progress_notes'        => $data['progress_notes'],
                'follow_up_at'          => $data['follow_up_at'],
                'reminder_sent_at'      => !empty($data['follow_up_at']) ? null : ($data['reminder_sent_at'] ?? null),
                'internal_notes'        => $data['internal_notes'],
                'communication_history' => $data['communication_history'],
                'updated_at' => current_time('mysql'),
            ),
            array('id' => absint($id)),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        return false !== $result ? true : new \WP_Error('update_failed', __('Contact could not be updated.', 'ttp-crm'));
    }

    public function delete($id)
    {
        global $wpdb;

        $table  = $this->get_table_name();
        $result = $wpdb->delete($table, array('id' => absint($id)), array('%d'));

        return false !== $result;
    }

    public function delete_dummy_contacts()
    {
        global $wpdb;

        $table = $this->get_table_name();

        // Matches seeded demo contacts created by this plugin.
        $email_like = 'dummy.%@example.com';
        $notes_like = '%Seeded dummy contact%';

        $sql = $wpdb->prepare(
            "DELETE FROM {$table} WHERE email LIKE %s OR internal_notes LIKE %s",
            $email_like,
            $notes_like
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $deleted = $wpdb->query($sql);

        return (int) $deleted;
    }

    public function count_contacts()
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = "SELECT COUNT(*) FROM {$table}";

        return (int) $wpdb->get_var($sql);
    }

    public function count_by_stage($stage)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE stage = %s", $stage);

        return (int) $wpdb->get_var($sql);
    }

    public function count_filtered($filters = array())
    {
        global $wpdb;

        $table           = $this->get_table_name();
        $where_statement = $this->build_where_statement($filters);
        $sql             = "SELECT COUNT(*) FROM {$table} {$where_statement['sql']}";
        $args            = $where_statement['args'];

        if (!empty($args)) {
            $sql = $wpdb->prepare($sql, $args); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        }

        return (int) $wpdb->get_var($sql);
    }

    public function count_unique_tags()
    {
        $contacts = $this->all();
        $tag_map  = array();

        foreach ($contacts as $contact) {
            $tags = $this->parse_tags($contact['tags']);
            foreach ($tags as $tag) {
                $tag_map[$tag] = true;
            }
        }

        return count($tag_map);
    }

    public function count_recent_contacts($days = 7)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $days  = max(1, absint($days));
        $from  = gmdate('Y-m-d H:i:s', current_time('timestamp', true) - ($days * DAY_IN_SECONDS));
        $sql   = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $from);

        return (int) $wpdb->get_var($sql);
    }

    public function contacts_created_last_days($days = 7)
    {
        global $wpdb;

        $days  = max(1, absint($days));
        $table = $this->get_table_name();
        $from  = gmdate('Y-m-d H:i:s', current_time('timestamp', true) - ($days * DAY_IN_SECONDS));
        $sql   = $wpdb->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS total
            FROM {$table}
            WHERE created_at >= %s
            GROUP BY DATE(created_at)
            ORDER BY day ASC",
            $from
        );
        $rows = $wpdb->get_results($sql, ARRAY_A);

        $map = array();
        foreach ($rows as $row) {
            $map[$row['day']] = (int) $row['total'];
        }

        $result = array();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = gmdate('Y-m-d', current_time('timestamp', true) - ($i * DAY_IN_SECONDS));
            $result[$date] = isset($map[$date]) ? $map[$date] : 0;
        }

        return $result;
    }

    public function followup_status_summary()
    {
        global $wpdb;

        $table = $this->get_table_name();
        $now   = current_time('mysql');

        $due_sql      = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE follow_up_at IS NOT NULL AND follow_up_at <= %s", $now);
        $upcoming_sql = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE follow_up_at IS NOT NULL AND follow_up_at > %s", $now);
        $none_sql     = "SELECT COUNT(*) FROM {$table} WHERE follow_up_at IS NULL OR follow_up_at = '0000-00-00 00:00:00'";

        return array(
            'due'      => (int) $wpdb->get_var($due_sql),
            'upcoming' => (int) $wpdb->get_var($upcoming_sql),
            'none'     => (int) $wpdb->get_var($none_sql),
        );
    }

    public function get_stage_conversion_rate($from_stage = 'new', $to_stage = 'enrolled')
    {
        $from_count = $this->count_by_stage($from_stage);
        $to_count   = $this->count_by_stage($to_stage);

        if ($from_count < 1) {
            return 0.0;
        }

        return min(100, round(($to_count / $from_count) * 100, 2));
    }

    public function revenue_by_course($limit = 10)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = $wpdb->prepare(
            "SELECT course_name, SUM(revenue_amount) AS revenue, COUNT(*) AS contacts
            FROM {$table}
            WHERE course_name <> ''
            GROUP BY course_name
            ORDER BY revenue DESC
            LIMIT %d",
            absint($limit)
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function lead_source_performance($limit = 10)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = $wpdb->prepare(
            "SELECT lead_source, COUNT(*) AS leads, SUM(revenue_amount) AS revenue
            FROM {$table}
            WHERE lead_source <> ''
            GROUP BY lead_source
            ORDER BY leads DESC
            LIMIT %d",
            absint($limit)
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function contacts_created_last_weeks($weeks = 8)
    {
        global $wpdb;

        $weeks = max(1, absint($weeks));
        $table = $this->get_table_name();
        $from  = gmdate('Y-m-d H:i:s', current_time('timestamp', true) - ($weeks * WEEK_IN_SECONDS));
        $sql   = $wpdb->prepare(
            "SELECT YEARWEEK(created_at, 1) AS yw, COUNT(*) AS total
            FROM {$table}
            WHERE created_at >= %s
            GROUP BY YEARWEEK(created_at, 1)
            ORDER BY yw ASC",
            $from
        );
        $rows = $wpdb->get_results($sql, ARRAY_A);

        $result = array();
        foreach ($rows as $row) {
            $result['W' . $row['yw']] = (int) $row['total'];
        }

        return $result;
    }

    public function parse_tags($tags_csv)
    {
        if (empty($tags_csv)) {
            return array();
        }

        $raw_tags = explode(',', (string) $tags_csv);
        $tags     = array();

        foreach ($raw_tags as $raw_tag) {
            $tag = sanitize_text_field(trim($raw_tag));
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return array_values(array_unique($tags));
    }

    public function get_stages()
    {
        return self::DEFAULT_STAGES;
    }

    public function upcoming_followups($limit = 10)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $sql   = $wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE follow_up_at IS NOT NULL
            AND follow_up_at <> '0000-00-00 00:00:00'
            ORDER BY follow_up_at ASC
            LIMIT %d",
            absint($limit)
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function due_followups_for_cron($limit = 50)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $now   = current_time('mysql');
        $sql   = $wpdb->prepare(
            "SELECT * FROM {$table}
            WHERE follow_up_at IS NOT NULL
            AND follow_up_at <> '0000-00-00 00:00:00'
            AND follow_up_at <= %s
            AND (reminder_sent_at IS NULL OR reminder_sent_at = '0000-00-00 00:00:00')
            ORDER BY follow_up_at ASC
            LIMIT %d",
            $now,
            absint($limit)
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public function mark_reminder_sent($contact_id)
    {
        global $wpdb;

        $table = $this->get_table_name();

        return false !== $wpdb->update(
            $table,
            array(
                'reminder_sent_at' => current_time('mysql'),
                'updated_at'       => current_time('mysql'),
            ),
            array('id' => absint($contact_id)),
            array('%s', '%s'),
            array('%d')
        );
    }

    public function update_stage($contact_id, $stage)
    {
        global $wpdb;

        if (!in_array($stage, $this->get_stages(), true)) {
            return new \WP_Error('invalid_stage', __('Invalid stage provided.', 'ttp-crm'));
        }

        $table = $this->get_table_name();
        $done  = $wpdb->update(
            $table,
            array(
                'stage'      => $stage,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => absint($contact_id)),
            array('%s', '%s'),
            array('%d')
        );

        return false !== $done ? true : new \WP_Error('update_stage_failed', __('Could not update stage.', 'ttp-crm'));
    }

    public function email_exists($email, $exclude_id = 0)
    {
        global $wpdb;

        $email = sanitize_email($email);
        if (empty($email)) {
            return false;
        }

        $table = $this->get_table_name();
        if ($exclude_id > 0) {
            $sql = $wpdb->prepare("SELECT id FROM {$table} WHERE email = %s AND id != %d LIMIT 1", $email, absint($exclude_id));
        } else {
            $sql = $wpdb->prepare("SELECT id FROM {$table} WHERE email = %s LIMIT 1", $email);
        }

        return (bool) $wpdb->get_var($sql);
    }

    public function find_by_email($email)
    {
        global $wpdb;

        $email = sanitize_email($email);
        if (empty($email)) {
            return null;
        }

        $table = $this->get_table_name();
        $sql   = $wpdb->prepare("SELECT * FROM {$table} WHERE email = %s LIMIT 1", $email);

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Phone from User Registration / WooCommerce / membership signup meta.
     *
     * @param \WP_User $user User object.
     * @return string
     */
    /**
     * First / last name from UR, billing, or WP user profile.
     *
     * @param \WP_User $user User object.
     * @return array{first_name: string, last_name: string}
     */
    public function get_name_parts_for_wp_user($user)
    {
        if (!$user instanceof \WP_User) {
            return array('first_name' => '', 'last_name' => '');
        }

        if (function_exists('ttp_user_get_name_parts')) {
            $parts = ttp_user_get_name_parts((int) $user->ID);
            if (!empty($parts['first_name']) || !empty($parts['last_name'])) {
                return array(
                    'first_name' => sanitize_text_field((string) $parts['first_name']),
                    'last_name'  => sanitize_text_field((string) $parts['last_name']),
                );
            }
        }

        $first = trim((string) $user->first_name);
        $last  = trim((string) $user->last_name);
        if ($first === '' && $last === '' && !is_email((string) $user->display_name)) {
            $split = preg_split('/\s+/', trim((string) $user->display_name), 2);
            $first = isset($split[0]) ? trim((string) $split[0]) : '';
            $last  = isset($split[1]) ? trim((string) $split[1]) : '';
        }
        if ($first === '' && !is_email((string) $user->user_login)) {
            $first = sanitize_text_field((string) $user->user_login);
        }
        if ($first === '' && $last === '') {
            $meta_parts = $this->extract_name_parts_from_user_meta((int) $user->ID);
            if ($meta_parts['first_name'] !== '' || $meta_parts['last_name'] !== '') {
                return $meta_parts;
            }
        }
        if ($first === '' && $last === '') {
            $fallback = $this->get_fallback_name_from_email((string) $user->user_email);
            if ($fallback !== '') {
                $first = $fallback;
            }
        }

        return array(
            'first_name' => sanitize_text_field($first),
            'last_name'  => sanitize_text_field($last),
        );
    }

    /**
     * Pull name fields from common User Registration / WooCommerce meta keys.
     *
     * @param int $user_id User ID.
     * @return array{first_name: string, last_name: string}
     */
    private function extract_name_parts_from_user_meta($user_id)
    {
        $first = '';
        $last  = '';
        $full  = '';

        $first_keys = array(
            'first_name',
            'user_registration_first_name',
            'billing_first_name',
            'nickname',
        );
        $last_keys = array(
            'last_name',
            'user_registration_last_name',
            'billing_last_name',
        );
        $full_keys = array(
            'user_registration_full_name',
            'full_name',
            'name',
            'your_name',
        );

        foreach ($first_keys as $key) {
            if ($first !== '') {
                break;
            }
            $val = trim((string) get_user_meta($user_id, $key, true));
            if ($val !== '' && !is_email($val)) {
                $first = $val;
            }
        }

        foreach ($last_keys as $key) {
            if ($last !== '') {
                break;
            }
            $val = trim((string) get_user_meta($user_id, $key, true));
            if ($val !== '' && !is_email($val)) {
                $last = $val;
            }
        }

        foreach ($full_keys as $key) {
            if ($full !== '') {
                break;
            }
            $val = trim((string) get_user_meta($user_id, $key, true));
            if ($val !== '' && !is_email($val)) {
                $full = $val;
            }
        }

        if ($first === '' && $last === '' && $full !== '') {
            $split = preg_split('/\s+/', $full, 2);
            $first = isset($split[0]) ? trim((string) $split[0]) : '';
            $last  = isset($split[1]) ? trim((string) $split[1]) : '';
        }

        return array(
            'first_name' => sanitize_text_field($first),
            'last_name'  => sanitize_text_field($last),
        );
    }

    /**
     * Derive a readable label from an email address when no name is stored.
     *
     * @param string $email Email address.
     * @return string
     */
    public function get_fallback_name_from_email($email)
    {
        $email = sanitize_email($email);
        if ($email === '' || !is_email($email)) {
            return '';
        }

        $local = strstr($email, '@', true);
        if (!is_string($local) || $local === '') {
            return '';
        }

        $local = str_replace(array('.', '_', '-'), ' ', $local);
        $local = preg_replace('/\s+/', ' ', trim($local));

        return sanitize_text_field(ucwords($local));
    }

    /**
     * Best display name for a CRM contact row.
     *
     * @param array<string,mixed> $contact Contact row.
     * @return string
     */
    public function get_contact_display_name($contact)
    {
        $full_name = trim(((string) ($contact['first_name'] ?? '')) . ' ' . ((string) ($contact['last_name'] ?? '')));
        if ($full_name !== '') {
            return $full_name;
        }

        $email = sanitize_email((string) ($contact['email'] ?? ''));
        if ($email !== '' && is_email($email)) {
            $user = get_user_by('email', $email);
            if ($user instanceof \WP_User) {
                $parts     = $this->get_name_parts_for_wp_user($user);
                $from_user = trim($parts['first_name'] . ' ' . $parts['last_name']);
                if ($from_user !== '') {
                    return $from_user;
                }
                if ($parts['first_name'] !== '') {
                    return $parts['first_name'];
                }
            }
        }

        $fallback = $this->get_fallback_name_from_email($email);
        if ($fallback !== '') {
            return $fallback;
        }

        $phone = preg_replace('/\s+/', '', (string) ($contact['phone'] ?? ''));
        if ($phone !== '') {
            return $phone;
        }

        return '';
    }

    public function get_phone_for_wp_user($user)
    {
        if (!$user instanceof \WP_User) {
            return '';
        }

        if (function_exists('ttp_phone_get_user_phone')) {
            $phone = ttp_phone_get_user_phone((int) $user->ID);
            if (is_string($phone) && '' !== trim($phone)) {
                return preg_replace('/\s+/', '', sanitize_text_field($phone));
            }
        }

        $keys = array(
            'billing_phone',
            'ttp_mobile',
            'user_registration_phone_number',
            'user_registration_phone',
            'user_registration_billing_phone',
            'user_registration_mobile',
            'user_registration_mobile_number',
            'phone_number',
            'mobile_number',
        );

        foreach ($keys as $key) {
            $value = get_user_meta((int) $user->ID, $key, true);
            if (is_string($value) && '' !== trim($value)) {
                return preg_replace('/\s+/', '', sanitize_text_field(trim($value)));
            }
        }

        return '';
    }

    /**
     * Upsert CRM contact from a WordPress user (login or registration).
     *
     * @param \WP_User $user    User object.
     * @param string   $context 'login' or 'register'.
     * @return void
     */
    public function sync_wp_user_login($user, $context = 'login')
    {
        if (!$user instanceof \WP_User || empty($user->user_email)) {
            return;
        }

        $context = ('register' === $context) ? 'register' : 'login';
        $phone   = $this->get_phone_for_wp_user($user);
        $names   = $this->get_name_parts_for_wp_user($user);
        $now     = current_time('mysql');
        $ip      = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';

        if ('register' === $context) {
            $history_line = sprintf('[%s][Website Registration] Account created from %s', $now, $ip);
            $tag          = 'website-register';
            $lead_default = 'Website Registration';
            $note_new     = 'Auto-added from website registration.';
        } else {
            $history_line = sprintf('[%s][Website Login] Logged in from %s', $now, $ip);
            $tag          = 'website-login';
            $lead_default = 'Website Login';
            $note_new     = 'Auto-added from website login.';
        }

        $existing = $this->find_by_email($user->user_email);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[TTP CRM] sync_wp_user_login context=' . $context . ' email=' . $user->user_email . ' existing=' . ($existing ? 'yes' : 'no')); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        if ($existing) {
            $history      = trim($existing['communication_history'] . PHP_EOL . $history_line);
            $merged_phone = $phone !== '' ? $phone : (string) $existing['phone'];
            $merged_first = $names['first_name'] !== '' ? $names['first_name'] : (string) $existing['first_name'];
            $merged_last  = $names['last_name'] !== '' ? $names['last_name'] : (string) $existing['last_name'];
            if ($merged_first === '' && $merged_last === '') {
                $meta_parts = $this->extract_name_parts_from_user_meta((int) $user->ID);
                if ($meta_parts['first_name'] !== '') {
                    $merged_first = $meta_parts['first_name'];
                }
                if ($meta_parts['last_name'] !== '') {
                    $merged_last = $meta_parts['last_name'];
                }
            }
            if ($merged_first === '' && $merged_last === '') {
                $email_fallback = $this->get_fallback_name_from_email((string) $user->user_email);
                if ($email_fallback !== '') {
                    $merged_first = $email_fallback;
                }
            }
            $this->update(
                (int) $existing['id'],
                array(
                    'first_name'            => $merged_first,
                    'last_name'             => $merged_last,
                    'email'                 => (string) $existing['email'],
                    'phone'                 => $merged_phone,
                    'stage'                 => $existing['stage'],
                    'tags'                  => $this->merge_tags($existing['tags'], $tag),
                    'course_name'           => $existing['course_name'],
                    'lead_source'           => !empty($existing['lead_source']) ? $existing['lead_source'] : $lead_default,
                    'revenue_amount'        => (float) $existing['revenue_amount'],
                    'student_profile'       => $existing['student_profile'],
                    'purchase_summary'      => $existing['purchase_summary'],
                    'progress_notes'        => $existing['progress_notes'],
                    'follow_up_at'          => $existing['follow_up_at'],
                    'reminder_sent_at'      => $existing['reminder_sent_at'],
                    'internal_notes'        => $existing['internal_notes'],
                    'communication_history' => $history,
                )
            );
            return;
        }

        $insert_first = $names['first_name'];
        $insert_last  = $names['last_name'];
        if ($insert_first === '' && $insert_last === '') {
            $email_fallback = $this->get_fallback_name_from_email((string) $user->user_email);
            if ($email_fallback !== '') {
                $insert_first = $email_fallback;
            }
        }

        $insert = array(
            'first_name'              => $insert_first,
            'last_name'               => $insert_last,
            'email'                   => sanitize_email((string) $user->user_email),
            'phone'                   => $phone,
            'stage'                   => 'new',
            'tags'                    => $tag,
            'course_name'             => '',
            'lead_source'             => $lead_default,
            'revenue_amount'          => 0,
            'student_profile'         => '',
            'purchase_summary'        => '',
            'progress_notes'          => '',
            'follow_up_at'            => null,
            'reminder_sent_at'        => null,
            'internal_notes'          => $note_new,
            'communication_history'   => $history_line,
        );

        $insert = apply_filters('ttp_crm_sync_wp_user_new_contact_data', $insert, $user, $context);

        $result = $this->insert($insert);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (is_wp_error($result)) {
                error_log('[TTP CRM] insert failed: ' . $result->get_error_code() . ' ' . $result->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            } else {
                error_log('[TTP CRM] insert success for ' . $user->user_email); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
    }

    /**
     * Fill empty first_name values for contacts that only have email/phone.
     *
     * @param int $limit Max rows to update per run.
     * @return int Number of contacts updated.
     */
    public function backfill_missing_contact_names($limit = 200)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, email, phone, first_name, last_name FROM {$table}
                 WHERE (first_name = '' OR first_name IS NULL)
                 AND (last_name = '' OR last_name IS NULL)
                 AND email <> ''
                 ORDER BY id DESC
                 LIMIT %d",
                max(1, absint($limit))
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return 0;
        }

        $updated = 0;
        foreach ($rows as $row) {
            $display = $this->get_contact_display_name($row);
            if ($display === '') {
                continue;
            }

            $wpdb->update(
                $table,
                array('first_name' => $display),
                array('id' => (int) $row['id']),
                array('%s'),
                array('%d')
            );
            ++$updated;
        }

        return $updated;
    }

    /**
     * Fill missing CRM names from matching WordPress user profiles.
     *
     * @param int $limit Max rows per run.
     * @return int
     */
    public function backfill_names_from_wp_users($limit = 500)
    {
        global $wpdb;

        $table = $this->get_table_name();
        $rows  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, email, first_name, last_name FROM {$table}
                 WHERE email <> ''
                 AND (first_name = '' OR first_name IS NULL)
                 AND (last_name = '' OR last_name IS NULL)
                 ORDER BY id DESC
                 LIMIT %d",
                max(1, absint($limit))
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return 0;
        }

        $updated = 0;
        foreach ($rows as $row) {
            $email = sanitize_email((string) ($row['email'] ?? ''));
            if ($email === '' || !is_email($email)) {
                continue;
            }

            $user = get_user_by('email', $email);
            if (!$user instanceof \WP_User) {
                $first = $this->get_fallback_name_from_email($email);
                $last  = '';
            } else {
                $names = $this->get_name_parts_for_wp_user($user);
                $first = $names['first_name'];
                $last  = $names['last_name'];
                if ($first === '' && $last === '') {
                    $first = $this->get_fallback_name_from_email($email);
                }
            }

            if ($first === '' && $last === '') {
                continue;
            }

            $wpdb->update(
                $table,
                array(
                    'first_name' => $first,
                    'last_name'  => $last,
                ),
                array('id' => (int) $row['id']),
                array('%s', '%s'),
                array('%d')
            );
            ++$updated;
        }

        return $updated;
    }

    private function merge_tags($existing_tags, $new_tag)
    {
        $tags   = $this->parse_tags($existing_tags);
        $tags[] = sanitize_text_field($new_tag);

        return implode(', ', array_unique($tags));
    }

    private function build_where_statement($filters = array())
    {
        global $wpdb;

        $sql  = 'WHERE 1=1';
        $args = array();

        if (!empty($filters['search'])) {
            $like = '%' . $wpdb->esc_like($filters['search']) . '%';
            $sql .= ' AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
            $args[] = $like;
        }

        if (!empty($filters['stage'])) {
            $sql .= ' AND stage = %s';
            $args[] = $filters['stage'];
        }

        if (!empty($filters['tag'])) {
            $like = '%' . $wpdb->esc_like($filters['tag']) . '%';
            $sql .= ' AND tags LIKE %s';
            $args[] = $like;
        }

        return array(
            'sql'  => $sql,
            'args' => $args,
        );
    }
}
