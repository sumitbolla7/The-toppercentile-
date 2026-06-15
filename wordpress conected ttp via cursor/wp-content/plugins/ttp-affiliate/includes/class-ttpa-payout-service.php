<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Payout_Service {

    /** @var TTPA_Commission_Service */
    private $commissions;

    public function __construct($commissions) {
        $this->commissions = $commissions;
    }

    public function create_payout($user_id, $amount, $args = []) {
        global $wpdb;

        $defaults = [
            'payment_method'    => get_user_meta($user_id, 'ttpa_payout_method', true) ?: 'manual',
            'payment_reference' => '',
            'notes'             => '',
            'status'            => 'processed',
        ];
        $args = wp_parse_args($args, $defaults);

        $inserted = $wpdb->insert(
            TTPA_Database::payouts_table(),
            [
                'user_id'            => (int) $user_id,
                'amount'             => (float) $amount,
                'status'             => sanitize_key($args['status']),
                'payment_method'     => sanitize_text_field($args['payment_method']),
                'payment_reference'  => sanitize_text_field($args['payment_reference']),
                'notes'              => sanitize_textarea_field($args['notes']),
                'created_at'         => TTPA_Database::now(),
                'processed_at'       => 'processed' === $args['status'] ? TTPA_Database::now() : null,
            ],
            ['%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return false;
        }

        $payout_id = (int) $wpdb->insert_id;

        $approved = $this->commissions->get_approved_for_user($user_id);
        $remaining = (float) $amount;
        $commission_ids = [];

        foreach ($approved as $row) {
            if ($remaining <= 0) {
                break;
            }
            $commission_ids[] = (int) $row['id'];
            $remaining -= (float) $row['commission_amount'];
        }

        $this->commissions->attach_to_payout($commission_ids, $payout_id);

        do_action('ttp_affiliate_payout_processed', $user_id, $amount, $payout_id);

        return $payout_id;
    }

    public function process_auto_payouts() {
        if (!get_option('ttpa_auto_payout_enabled', 0)) {
            return;
        }

        $threshold = (float) get_option('ttpa_payout_threshold', 500);
        $user_ids  = $this->get_users_above_threshold($threshold);

        foreach ($user_ids as $user_id) {
            $balance = $this->commissions->get_balance($user_id, 'approved');
            if ($balance >= $threshold) {
                $this->create_payout($user_id, $balance, [
                    'payment_method' => get_user_meta($user_id, 'ttpa_payout_method', true) ?: 'auto',
                    'notes'          => __('Automatic payout', 'ttp-affiliate'),
                ]);
            }
        }
    }

    public function get_list($args = []) {
        global $wpdb;

        $defaults = [
            'user_id' => 0,
            'limit'   => 50,
            'offset'  => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $table  = TTPA_Database::payouts_table();
        $where  = ['1=1'];
        $params = [];

        if ($args['user_id'] > 0) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $args['user_id'];
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

    private function get_users_above_threshold($threshold) {
        global $wpdb;

        $table = TTPA_Database::commissions_table();

        return array_map('intval', $wpdb->get_col(
            $wpdb->prepare(
                "SELECT referrer_user_id FROM {$table} WHERE status = 'approved' GROUP BY referrer_user_id HAVING SUM(commission_amount) >= %f",
                (float) $threshold
            )
        ) ?: []);
    }
}
