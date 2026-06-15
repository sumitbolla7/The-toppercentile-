<?php

namespace TTP_CRM\Core;

use TTP_CRM\Database\ContactRepository;

defined('ABSPATH') || exit;

class PurchaseIntegration
{
    /**
     * @var ContactRepository
     */
    private $contacts;

    /**
     * Stage precedence used to prevent downgrades.
     * Higher number means later stage in pipeline.
     */
    private const STAGE_RANK = array(
        'inactive'   => 0,
        'new'        => 1,
        'contacted'  => 2,
        'interested' => 3,
        'enrolled'   => 4,
    );

    public function __construct(ContactRepository $contacts)
    {
        $this->contacts = $contacts;
    }

    public function register_hooks()
    {
        // WooCommerce only.
        if (!class_exists('\WooCommerce')) {
            return;
        }

        // When checkout creates the order, mark as Interested (pre-payment).
        add_action('woocommerce_checkout_order_processed', array($this, 'handle_order_created'), 10, 3);

        // When payment completes / order becomes paid, mark as Enrolled.
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'handle_order_paid_status'), 10, 2);
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_paid_status'), 10, 2);
    }

    public function handle_order_created($order_id, $posted_data, $order)
    {
        unset($posted_data);

        if (!is_object($order) && function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }

        $this->upsert_contact_from_order($order, 'interested', 'Checkout Started');
    }

    public function handle_payment_complete($order_id)
    {
        if (!function_exists('wc_get_order')) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->upsert_contact_from_order($order, 'enrolled', 'Payment Completed');
    }

    public function handle_order_paid_status($order_id, $order)
    {
        if (!$order && function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return;
        }

        $this->upsert_contact_from_order($order, 'enrolled', 'Order Paid');
    }

    private function upsert_contact_from_order($order, $target_stage, $event_label)
    {
        $email = method_exists($order, 'get_billing_email') ? (string) $order->get_billing_email() : '';
        $email = sanitize_email($email);
        if (empty($email)) {
            return;
        }

        $first_name = method_exists($order, 'get_billing_first_name') ? (string) $order->get_billing_first_name() : '';
        $last_name  = method_exists($order, 'get_billing_last_name') ? (string) $order->get_billing_last_name() : '';
        $phone      = method_exists($order, 'get_billing_phone') ? (string) $order->get_billing_phone() : '';

        $order_id     = method_exists($order, 'get_id') ? (int) $order->get_id() : 0;
        $order_total  = method_exists($order, 'get_total') ? (float) $order->get_total() : 0.0;
        $order_status = method_exists($order, 'get_status') ? (string) $order->get_status() : '';

        $item_names = array();
        $product_ids = array();
        if (method_exists($order, 'get_items')) {
            foreach ($order->get_items() as $item) {
                if (is_object($item) && method_exists($item, 'get_name')) {
                    $name = (string) $item->get_name();
                    if ($name !== '') {
                        $item_names[] = $name;
                    }
                }
                if (is_object($item) && method_exists($item, 'get_product_id')) {
                    $pid = (int) $item->get_product_id();
                    if ($pid > 0) {
                        $product_ids[] = (string) $pid;
                    }
                }
            }
        }

        $course_name = implode(' + ', array_slice(array_unique($item_names), 0, 3));
        $category_tag = '';

        // Optional: map WooCommerce product_id to course metadata (set in CRM → Courses).
        $course_map = get_option('ttp_crm_course_map', array());
        if (is_array($course_map) && !empty($product_ids)) {
            foreach ($product_ids as $pid) {
                if (isset($course_map[$pid]) && is_array($course_map[$pid])) {
                    $mapped_course = (string) ($course_map[$pid]['course_name'] ?? '');
                    if ($mapped_course !== '') {
                        $course_name = $mapped_course;
                    }
                    $mapped_category = (string) ($course_map[$pid]['category_name'] ?? '');
                    if ($mapped_category !== '') {
                        $category_tag = 'category-' . sanitize_title($mapped_category);
                    }
                    break;
                }
            }
        }

        $now = current_time('mysql');
        $history_line = sprintf('[%s][Purchase] %s (Order #%d, %s)', $now, $event_label, $order_id, $order_status);

        $existing = $this->contacts->find_by_email($email);
        $new_stage = $this->resolve_stage($existing ? (string) $existing['stage'] : '', (string) $target_stage);

        $tags = $this->merge_tags($existing ? (string) $existing['tags'] : '', 'website-purchase');
        if ($category_tag !== '') {
            $tags = $this->merge_tags($tags, $category_tag);
        }

        $purchase_summary = $existing ? (string) $existing['purchase_summary'] : '';
        $purchase_summary = trim($purchase_summary . PHP_EOL . sprintf('[%s] Order #%d (%s) Total: %0.2f Products: %s', $now, $order_id, $order_status, $order_total, implode(',', array_slice(array_unique($product_ids), 0, 6))));

        $communication_history = $existing ? (string) $existing['communication_history'] : '';
        $communication_history = trim($communication_history . PHP_EOL . $history_line);

        $data = array(
            'first_name'            => sanitize_text_field($first_name !== '' ? $first_name : ($existing['first_name'] ?? '')),
            'last_name'             => sanitize_text_field($last_name !== '' ? $last_name : ($existing['last_name'] ?? '')),
            'email'                 => $email,
            'phone'                 => sanitize_text_field($phone !== '' ? $phone : ($existing['phone'] ?? '')),
            'stage'                 => $new_stage !== '' ? $new_stage : 'interested',
            'tags'                  => $tags,
            'course_name'           => $course_name !== '' ? sanitize_text_field($course_name) : (string) ($existing['course_name'] ?? ''),
            'lead_source'           => 'Website Purchase',
            'revenue_amount'        => $target_stage === 'enrolled' ? max((float) ($existing['revenue_amount'] ?? 0), $order_total) : (float) ($existing['revenue_amount'] ?? 0),
            'student_profile'       => (string) ($existing['student_profile'] ?? ''),
            'purchase_summary'      => $purchase_summary,
            'progress_notes'        => (string) ($existing['progress_notes'] ?? ''),
            'follow_up_at'          => $existing['follow_up_at'] ?? null,
            'reminder_sent_at'      => $existing['reminder_sent_at'] ?? null,
            'internal_notes'        => (string) ($existing['internal_notes'] ?? ''),
            'communication_history' => $communication_history,
        );

        if ($existing && !empty($existing['id'])) {
            $this->contacts->update((int) $existing['id'], $data);
            return;
        }

        $data['internal_notes'] = trim(($data['internal_notes'] ? $data['internal_notes'] . PHP_EOL : '') . 'Auto-added from website purchase.');
        $this->contacts->insert($data);
    }

    private function resolve_stage($current_stage, $target_stage)
    {
        $current_stage = sanitize_key($current_stage);
        $target_stage  = sanitize_key($target_stage);

        if ($target_stage === '') {
            return $current_stage;
        }

        $current_rank = isset(self::STAGE_RANK[$current_stage]) ? self::STAGE_RANK[$current_stage] : 0;
        $target_rank  = isset(self::STAGE_RANK[$target_stage]) ? self::STAGE_RANK[$target_stage] : $current_rank;

        return ($target_rank >= $current_rank) ? $target_stage : $current_stage;
    }

    private function merge_tags($existing_tags, $new_tag)
    {
        $tags = $this->contacts->parse_tags($existing_tags);
        $tags[] = sanitize_text_field($new_tag);

        return implode(', ', array_unique($tags));
    }
}

