<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Commission_Service {

    public function create_from_order($referrer_id, $order) {
        global $wpdb;

        if (!$order instanceof WC_Order) {
            return false;
        }

        $order_id = $order->get_id();
        if ($this->get_by_order($order_id)) {
            return false;
        }

        $rate   = TTPA_Plugin::instance()->referrals()->get_commission_rate($referrer_id);
        $total  = (float) $order->get_total();
        $amount = round($total * ($rate / 100), 2);

        if ($amount <= 0) {
            return false;
        }

        $referral = TTPA_Plugin::instance()->referrals()->get_referral_by_users($referrer_id, $order->get_customer_id());
        $referral_id = $referral ? (int) $referral['id'] : 0;

        $status = get_option('ttpa_auto_approve_commissions', 1) ? 'approved' : 'pending';

        $inserted = $wpdb->insert(
            TTPA_Database::commissions_table(),
            [
                'referrer_user_id'   => (int) $referrer_id,
                'referral_id'        => $referral_id,
                'order_id'           => (int) $order_id,
                'order_total'        => $total,
                'commission_rate'    => $rate,
                'commission_amount'  => $amount,
                'status'             => $status,
                'created_at'         => TTPA_Database::now(),
                'approved_at'        => 'approved' === $status ? TTPA_Database::now() : null,
            ],
            ['%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return false;
        }

        $commission_id = (int) $wpdb->insert_id;

        if ($referral_id) {
            TTPA_Plugin::instance()->referrals()->mark_converted($referral_id, $order_id);
        }

        do_action('ttp_affiliate_commission_created', $referrer_id, $amount, $order_id, $commission_id);

        return $commission_id;
    }

    public function get_by_order($order_id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . TTPA_Database::commissions_table() . ' WHERE order_id = %d LIMIT 1',
                (int) $order_id
            ),
            ARRAY_A
        );
    }

    public function get_list($args = []) {
        global $wpdb;

        $defaults = [
            'referrer_id' => 0,
            'status'      => '',
            'limit'       => 50,
            'offset'      => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        $table  = TTPA_Database::commissions_table();
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

    public function update_status($commission_id, $status) {
        global $wpdb;

        $data = ['status' => sanitize_key($status)];
        $format = ['%s'];

        if ('approved' === $status) {
            $data['approved_at'] = TTPA_Database::now();
            $format[] = '%s';
        }

        if ('paid' === $status) {
            $data['paid_at'] = TTPA_Database::now();
            $format[] = '%s';
        }

        return (bool) $wpdb->update(
            TTPA_Database::commissions_table(),
            $data,
            ['id' => (int) $commission_id],
            $format,
            ['%d']
        );
    }

    public function get_balance($user_id, $status = 'approved') {
        global $wpdb;

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COALESCE(SUM(commission_amount), 0) FROM ' . TTPA_Database::commissions_table() . ' WHERE referrer_user_id = %d AND status = %s',
                (int) $user_id,
                sanitize_key($status)
            )
        );
    }

    public function get_total_earned($user_id) {
        global $wpdb;

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COALESCE(SUM(commission_amount), 0) FROM ' . TTPA_Database::commissions_table() . ' WHERE referrer_user_id = %d',
                (int) $user_id
            )
        );
    }

    /**
     * Sum of referred order totals (sales generated via referral link).
     *
     * @param int $user_id Referrer user ID.
     * @return float
     */
    public function get_total_sales($user_id) {
        global $wpdb;

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COALESCE(SUM(order_total), 0) FROM ' . TTPA_Database::commissions_table() . ' WHERE referrer_user_id = %d',
                (int) $user_id
            )
        );
    }

    public function attach_to_payout($commission_ids, $payout_id) {
        global $wpdb;

        if (empty($commission_ids)) {
            return false;
        }

        $now = TTPA_Database::now();
        foreach ($commission_ids as $commission_id) {
            $wpdb->update(
                TTPA_Database::commissions_table(),
                [
                    'status'    => 'paid',
                    'paid_at'   => $now,
                    'payout_id' => (int) $payout_id,
                ],
                [
                    'id'     => (int) $commission_id,
                    'status' => 'approved',
                ],
                ['%s', '%s', '%d'],
                ['%d', '%s']
            );
        }

        return true;
    }

    public function get_approved_for_user($user_id) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . TTPA_Database::commissions_table() . ' WHERE referrer_user_id = %d AND status = %s ORDER BY created_at ASC',
                (int) $user_id,
                'approved'
            ),
            ARRAY_A
        ) ?: [];
    }
}
