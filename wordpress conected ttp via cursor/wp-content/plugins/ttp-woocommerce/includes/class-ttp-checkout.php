<?php
if (!defined('ABSPATH')) exit;

class TTP_Checkout {

    public function __construct() {
        add_action('woocommerce_payment_complete', [$this, 'trigger_tcy_registration']);
        add_action('woocommerce_order_status_completed', [$this, 'trigger_tcy_registration']);
        add_action('woocommerce_order_status_processing', [$this, 'trigger_tcy_registration']);
        add_action('woocommerce_order_status_on-hold', [$this, 'trigger_tcy_registration']);
        add_action('woocommerce_order_status_failed', [$this, 'revoke_unpaid_order_access']);
        add_action('woocommerce_order_status_cancelled', [$this, 'revoke_unpaid_order_access']);
        add_action('woocommerce_order_status_pending', [$this, 'revoke_unpaid_order_access']);
        add_action('woocommerce_order_status_refunded', [$this, 'revoke_unpaid_order_access']);
        add_action('woocommerce_thankyou', [$this, 'maybe_trigger_tcy_on_thankyou'], 5);
        /*
         * Block themes / some checkout layouts render the order-received page without firing
         * woocommerce_thankyou; this hook always runs after the order table on the thank-you view.
         */
        add_action('woocommerce_order_details_after_order_table', [$this, 'maybe_trigger_tcy_on_thankyou'], 5, 1);
        // Per-course "Access — …" block removed; keep single main course access CTA only.
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);
        add_action('template_redirect', [$this, 'nocache_order_received_page'], 0);
        add_action('template_redirect', [$this, 'maybe_redirect_thankyou_to_study_portal'], 3);
        add_action('wp_head', [$this, 'hide_legacy_thankyou_ttp_markup'], 0);
        add_action('wp_footer', [$this, 'remove_legacy_thankyou_ttp_markup_from_dom'], 1);

        // Allow other classes (e.g. AJAX TCY login) to reuse the same checkout instance without re-instantiating hooks.
        $GLOBALS['ttp_checkout'] = $this;
    }

    /**
     * Ensure TCY registration runs before thank-you UI when payment hooks already ran.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function maybe_trigger_tcy_on_thankyou($order_id_or_order) {
        if ($order_id_or_order instanceof WC_Order) {
            $order_id = (int) $order_id_or_order->get_id();
        } else {
            $order_id = absint($order_id_or_order);
        }
        if ($order_id <= 0) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }
        if (!function_exists('ttp_order_qualifies_for_tcy_actions') || !ttp_order_qualifies_for_tcy_actions($order)) {
            return;
        }
        if (!$this->order_contains_tcy_course($order)) {
            return;
        }
        $this->sync_tcy_if_missing_mapping($order_id);
    }

    /**
     * If order meta says "enrolled" but there is no usable TCY mapping row, clear stale meta and re-run registration.
     * Also used from AJAX magic-login so the first click can succeed even when thank-you hooks did not persist mapping.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function sync_tcy_if_missing_mapping($order_id) {
        $order_id = (int) $order_id;
        if ($order_id <= 0) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }
        if (!function_exists('ttp_order_qualifies_for_tcy_actions') || !ttp_order_qualifies_for_tcy_actions($order)) {
            return;
        }
        if (!$this->order_contains_tcy_course($order)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ttp_order_mapping';
        $mapping = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE order_id = %d AND tcy_user_id IS NOT NULL AND tcy_user_id <> '' ORDER BY id DESC LIMIT 1",
                $order_id
            )
        );
        if ($mapping && (!function_exists('ttp_order_tcy_mapping_mismatch') || !ttp_order_tcy_mapping_mismatch($order, $mapping))) {
            return;
        }

        $order->delete_meta_data('_ttp_tcy_registered');
        $order->delete_meta_data('_tcy_enrolled');
        $order->delete_meta_data('_tcy_enrolled_at');
        $order->save();

        $this->trigger_tcy_registration($order_id);
    }

    public function trigger_tcy_registration($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        if (!function_exists('ttp_order_qualifies_for_tcy_actions') || !ttp_order_qualifies_for_tcy_actions($order)) {
            return;
        }
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND status = %s",
                (int) $order_id,
                'failed'
            )
        );

        $user_id = (int) $order->get_user_id();
        $student = null;
        if ($user_id > 0) {
            $student = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ttp_students WHERE wp_user_id = %d", $user_id
            ));
        }

        if ( class_exists( 'TTP_Catalog_Seed' ) ) {
            foreach ( $order->get_items() as $item ) {
                $product_id = (int) $item->get_product_id();
                if ( $product_id < 1 ) {
                    continue;
                }
                $line_name = (string) $item->get_name();
                if ( function_exists( 'ttp_bulk_order_item_is_jbims' ) && ttp_bulk_order_item_is_jbims( $item ) ) {
                    TTP_Catalog_Seed::repair_tcy_meta_for_product( $product_id );
                } elseif ( preg_match( '/jbims|mfin|mhrd|bootcamp/i', $line_name ) ) {
                    TTP_Catalog_Seed::repair_tcy_meta_for_product( $product_id );
                }
            }
        }

        $line_items = [];
        foreach ($order->get_items() as $item) {
            $product_id = (int) $item->get_product_id();
            if ($product_id < 1) {
                continue;
            }
            $tcy_ids = function_exists( 'ttp_get_tcy_ids_for_line_item' )
                ? ttp_get_tcy_ids_for_line_item( $item, true )
                : ( function_exists( 'ttp_get_tcy_ids_for_product' )
                    ? ttp_get_tcy_ids_for_product( $product_id, true )
                    : [
                        'course_id'   => (string) get_post_meta( $product_id, '_ttp_tcy_course_id', true ),
                        'category_id' => (string) get_post_meta( $product_id, '_ttp_tcy_category_id', true ),
                    ] );
            $tcy_course_id   = isset($tcy_ids['course_id']) ? (string) $tcy_ids['course_id'] : '';
            $tcy_category_id = isset($tcy_ids['category_id']) ? (string) $tcy_ids['category_id'] : '';
            if ($tcy_course_id === '' || $tcy_course_id === '0') {
                continue;
            }
            $line_items[] = [
                'product_id'  => $product_id,
                'course_id'   => $tcy_course_id,
                'category_id' => $tcy_category_id,
            ];
        }

        if (empty($line_items)) {
            return;
        }

        $billing_email = (string) $order->get_billing_email();

        $api = new TTP_TCY_API();

        $full_name = function_exists('ttp_resolve_customer_full_name')
            ? ttp_resolve_customer_full_name($user_id, $order)
            : ($student ? $student->full_name : trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()));

        // One TCY ERP user per customer (same user_id as Postman register e.g. ODEzMjQ0MA==).
        $tcy_account_id = function_exists('ttp_get_canonical_tcy_user_id')
            ? ttp_get_canonical_tcy_user_id($user_id, $billing_email)
            : '';
        if ($tcy_account_id === '' && $student && !empty($student->tcy_user_id)) {
            $tcy_account_id = function_exists('ttp_sanitize_tcy_user_id')
                ? ttp_sanitize_tcy_user_id((string) $student->tcy_user_id)
                : (string) $student->tcy_user_id;
        }
        if ($tcy_account_id === '' && function_exists('ttp_lookup_tcy_user_id_by_email')) {
            $tcy_account_id = ttp_lookup_tcy_user_id_by_email($billing_email);
        }

        $did_fresh_register = false;
        $register_course_ids = [];
        $skip_register_courses = [];
        $needs_add_course = false;

        if ($tcy_account_id === '') {
            // Register enrolls the first course on TCY — do not call add_course for it afterward.
            $first = function_exists('ttp_tcy_pick_register_line_item')
                ? ttp_tcy_pick_register_line_item($line_items)
                : $line_items[0];
            if (!is_array($first)) {
                $first = $line_items[0];
            }
            $register = $api->register_student([
                'full_name'   => $full_name,
                'email'       => $order->get_billing_email(),
                'mobile'      => $order->get_billing_phone(),
                'course_id'   => $first['course_id'],
                'category_id' => $first['category_id'],
                'order_id'    => $order_id,
            ]);
            $tcy_account_id = $this->extract_tcy_user_id($register);
            if ($tcy_account_id !== null && $tcy_account_id !== '') {
                $tcy_account_id = function_exists('ttp_sanitize_tcy_user_id')
                    ? ttp_sanitize_tcy_user_id((string) $tcy_account_id)
                    : (string) $tcy_account_id;
                $did_fresh_register = true;
                $register_course_ids[] = (string) $first['course_id'];
                $order->update_meta_data('_ttp_tcy_fresh_register', 'yes');
                $order->update_meta_data('_ttp_tcy_register_course_id', (string) $first['course_id']);
            } else {
                $tcy_account_id = '';
            }
        }

        if ($tcy_account_id !== '' && $user_id > 0) {
            if ($student) {
                $wpdb->update($wpdb->prefix . 'ttp_students', ['tcy_user_id' => $tcy_account_id], ['wp_user_id' => $user_id]);
            } else {
                $wpdb->insert($wpdb->prefix . 'ttp_students', [
                    'wp_user_id'  => $user_id,
                    'full_name'   => $full_name,
                    'email'       => $order->get_billing_email(),
                    'mobile'      => $order->get_billing_phone(),
                    'username'    => $order->get_billing_email(),
                    'tcy_user_id' => $tcy_account_id,
                ]);
            }
            update_user_meta($user_id, '_ttp_tcy_user_id', $tcy_account_id);
        }

        if ( $tcy_account_id !== '' ) {
            $skip_register_courses = $register_course_ids;
            foreach ( $line_items as $row ) {
                $cid = isset( $row['course_id'] ) ? (string) $row['course_id'] : '';
                if ( $cid === '' ) {
                    continue;
                }
                if ( function_exists( 'ttp_tcy_order_course_enrolled_via_register' )
                    && ttp_tcy_order_course_enrolled_via_register( (int) $order_id, $cid ) ) {
                    $skip_register_courses[] = $cid;
                }
            }
            $skip_register_courses = array_values( array_unique( array_filter( array_map( 'strval', $skip_register_courses ) ) ) );
            $needs_add_course      = count( $skip_register_courses ) < count( $line_items );
            if ( $needs_add_course && function_exists( 'ttp_tcy_add_all_courses_for_order' ) ) {
                $order_loop = ttp_tcy_add_all_courses_for_order( $order, $tcy_account_id, $skip_register_courses );
                $order->update_meta_data( '_ttp_tcy_order_add_course_loop', wp_json_encode( $order_loop ) );
            }
            $this->enroll_all_line_items_on_tcy_account( $order, $line_items, $tcy_account_id, $user_id, $api );
        } else {
            foreach ( $line_items as $row ) {
                $wpdb->insert(
                    $wpdb->prefix . 'ttp_order_mapping',
                    [
                        'order_id'        => $order_id,
                        'wp_user_id'      => $user_id,
                        'tcy_user_id'     => '',
                        'tcy_course_id'   => $row['course_id'],
                        'tcy_category_id' => $row['category_id'],
                        'status'          => 'failed',
                    ]
                );
            }
        }

        $registered_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d AND status = %s",
                (int) $order_id,
                'registered'
            )
        );
        if ($registered_count > 0) {
            $order->update_meta_data('_ttp_tcy_registered', true);
            $order->update_meta_data('_tcy_enrolled', 'yes');
            $order->update_meta_data('_tcy_enrolled_at', current_time('mysql'));
        }
        if ($tcy_account_id !== '') {
            $order->update_meta_data('_ttp_tcy_user_id', $tcy_account_id);
        }

        $sync_tcy_id = function_exists( 'ttp_get_canonical_tcy_user_id' )
            ? ttp_get_canonical_tcy_user_id( $user_id, $billing_email )
            : '';
        if ( $sync_tcy_id === '' ) {
            $sync_tcy_id = $tcy_account_id;
        }
        if ( $sync_tcy_id !== '' && ! $did_fresh_register && ! $needs_add_course && empty( $skip_register_courses ) ) {
            if ( function_exists( 'ttp_tcy_schedule_deferred_full_sync' ) ) {
                ttp_tcy_schedule_deferred_full_sync( $sync_tcy_id, $user_id, $billing_email );
            } else {
                $this->sync_all_tcy_courses_for_customer( $order, $sync_tcy_id, $user_id, $billing_email );
            }
        } elseif ( $sync_tcy_id !== '' && ! $did_fresh_register && $needs_add_course ) {
            if ( function_exists( 'ttp_tcy_schedule_deferred_full_sync' ) ) {
                ttp_tcy_schedule_deferred_full_sync( $sync_tcy_id, $user_id, $billing_email );
            }
        }

        if (function_exists('ttp_tcy_collect_purchased_course_pairs')) {
            $pairs = ttp_tcy_collect_purchased_course_pairs($user_id, $order->get_billing_email());
            if (!empty($pairs)) {
                $order->update_meta_data('_ttp_tcy_enrolled_pairs', wp_json_encode(array_values($pairs)));
            }
        }

        if ($tcy_account_id !== '' && $user_id > 0 && function_exists('ttp_sync_portal_enrollments_for_user')) {
            ttp_sync_portal_enrollments_for_user($user_id, $billing_email);
        }

        $order->save();
    }

    /**
     * Thank-you HTML is per-order and changes when plugins update; full-page cache causes a 1-frame “old UI” flash.
     *
     * @return void
     */
    public function nocache_order_received_page() {
        if (!$this->is_order_received_thankyou_request()) {
            return;
        }
        if (headers_sent()) {
            return;
        }
        nocache_headers();
    }

    /**
     * Legacy TTP thank-you blocks (green box + party emoji) may still appear from full-page cache or old templates.
     * Hide immediately in head, then strip from DOM in footer so nothing flashes.
     *
     * @return void
     */
    public function hide_legacy_thankyou_ttp_markup() {
        if (!$this->is_order_received_thankyou_request()) {
            return;
        }
        ?>
        <style id="ttp-hide-legacy-thankyou-markup">
        .ttp-order-course-access{display:none!important;visibility:hidden!important;max-height:0!important;overflow:hidden!important;margin:0!important;padding:0!important;border:0!important;opacity:0!important;pointer-events:none!important;}
        .ttp-success-box,.ttp-purchase-thankyou{display:none!important;visibility:hidden!important;max-height:0!important;overflow:hidden!important;margin:0!important;padding:0!important;border:0!important;opacity:0!important;pointer-events:none!important;}
        </style>
        <?php
    }

    /**
     * @return void
     */
    public function remove_legacy_thankyou_ttp_markup_from_dom() {
        if (!$this->is_order_received_thankyou_request()) {
            return;
        }
        ?>
        <script id="ttp-remove-legacy-thankyou-markup">
        (function(){
            function strip(){
                document.querySelectorAll('.ttp-order-course-access,.ttp-success-box,.ttp-purchase-thankyou').forEach(function(n){n.remove();});
                document.querySelectorAll('div[style*="48px"]').forEach(function(n){
                    var t=(n.textContent||'').trim();
                    if(t.indexOf('🎉')!==-1&&t.length<16){n.remove();}
                });
            }
            strip();
            if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',strip);}
            setTimeout(strip,0);
        })();
        </script>
        <?php
    }

    /**
     * After a successful course purchase, send the customer straight to the TCY study portal (magic login).
     *
     * @return void
     */
    public function maybe_redirect_thankyou_to_study_portal() {
        if (!$this->is_order_received_thankyou_request()) {
            return;
        }
        if (isset($_GET['ttp_stay'])) {
            return;
        }
        if ('yes' !== get_option('ttp_redirect_thankyou_to_study', 'no')) {
            return;
        }
        $order_id = $this->get_order_id_from_order_received_request();
        if (!$order_id) {
            return;
        }
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }
        if (!$this->customer_can_access_thankyou_order($order)) {
            return;
        }
        if (!apply_filters('ttp_redirect_thankyou_to_study_portal', true, $order)) {
            return;
        }
        if (!$this->order_contains_tcy_course($order)) {
            return;
        }
        if (!function_exists('ttp_order_qualifies_for_tcy_actions') || !ttp_order_qualifies_for_tcy_actions($order)) {
            return;
        }
        if (!$order->get_meta('_ttp_tcy_registered')) {
            $this->trigger_tcy_registration($order_id);
        }
        $target = $this->get_study_magic_login_redirect_url((int) $order_id);
        if (!is_string($target) || $target === '') {
            return;
        }
        if (!$this->is_permitted_magic_login_redirect_host($target)) {
            return;
        }
        if (function_exists('ttp_log_study_portal_redirect')) {
            global $wpdb;
            $tcy_id = '';
            if (isset($wpdb)) {
                $row = $wpdb->get_var($wpdb->prepare(
                    "SELECT tcy_user_id FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d ORDER BY id DESC LIMIT 1",
                    $order_id
                ));
                $tcy_id = is_string($row) ? $row : '';
            }
            ttp_log_study_portal_redirect('thankyou_redirect', $order_id, $target, $target, $tcy_id, 'Auto redirect after payment');
        }
        if (function_exists('ttp_redirect_to_study_portal')) {
            ttp_redirect_to_study_portal($target);
        }
        nocache_headers();
        wp_redirect($target, 302);
        exit;
    }

    /**
     * Only redirect browsers to known TCY / study hosts (magic-login URLs from our API).
     *
     * @param string $url Full URL.
     * @return bool
     */
    private function is_permitted_magic_login_redirect_host($url) {
        if (!is_string($url) || $url === '') {
            return false;
        }
        $parts = wp_parse_url($url);
        if (empty($parts['host']) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }
        $host = strtolower($parts['host']);
        $allowed = [
            'study.thetoppercentile.co.in',
            'thetoppercentile.tcyonline.co.in',
            'www.thetoppercentile.tcyonline.co.in',
            'www.tcyonline.com',
            'tcyonline.com',
            'www.tcyonline.co.in',
            'tcyonline.co.in',
        ];
        $study = wp_parse_url((string) get_option('ttp_study_portal_base_url', 'https://study.thetoppercentile.co.in'));
        if (!empty($study['host'])) {
            $allowed[] = strtolower($study['host']);
        }
        $allowed = array_unique(array_map('strtolower', apply_filters('ttp_allowed_magic_login_redirect_hosts', $allowed)));
        if (in_array($host, $allowed, true)) {
            return true;
        }
        // Any TCY-managed host under tcyonline.com / tcyonline.co.in (white-label magic links).
        if (preg_match('/(?:^|\.)tcyonline\.(?:co\.in|com)$/i', $host)) {
            return (bool) apply_filters('ttp_allow_tcyonline_subdomain_redirects', true);
        }
        return false;
    }

    /**
     * True on classic or block checkout “order received” route (is_checkout() can be false with blocks).
     *
     * @return bool
     */
    private function is_order_received_thankyou_request() {
        if (function_exists('is_checkout') && is_checkout() && function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
            return true;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        return (bool) preg_match('#/checkout/order-received/\d+#i', $uri);
    }

    /**
     * Resolve order ID from query var or request URI (fallback when query vars are not set yet).
     *
     * @return int
     */
    private function get_order_id_from_order_received_request() {
        $order_id = absint(get_query_var('order-received'));
        if ($order_id) {
            return $order_id;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (preg_match('#/checkout/order-received/(\d+)#i', $uri, $m)) {
            return absint($m[1]);
        }
        return 0;
    }

    /**
     * Valid order key in URL, or logged-in customer who owns the order (many stores omit ?key= for logged-in buyers).
     *
     * @param WC_Order $order Order.
     * @return bool
     */
    private function customer_can_access_thankyou_order($order) {
        if (!$order instanceof WC_Order) {
            return false;
        }
        $key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
        if ($key !== '' && $order->key_is_valid($key)) {
            return true;
        }
        if (is_user_logged_in()) {
            $customer_id = (int) $order->get_user_id();
            if ($customer_id > 0 && $customer_id === (int) get_current_user_id()) {
                return true;
            }
        }
        return (bool) apply_filters('ttp_thankyou_redirect_allow_without_key', false, $order);
    }

    /**
     * @param int $order_id Order ID.
     * @return string|false Absolute URL or false.
     */
    private function get_study_magic_login_redirect_url($order_id) {
        $order = wc_get_order((int) $order_id);
        if (!$order instanceof WC_Order) {
            return false;
        }
        $tcy_user_id = function_exists('ttp_get_tcy_user_id_for_order') ? ttp_get_tcy_user_id_for_order($order) : '';
        if ($tcy_user_id === '' || !function_exists('ttp_build_study_portal_access_url')) {
            return false;
        }
        $final = ttp_build_study_portal_access_url($tcy_user_id, (int) $order_id, 0, 'Thank-you redirect');
        return $final !== '' ? $final : false;
    }

    /**
     * One "Access" button per purchased TCY course on order-received (multi-course carts).
     *
     * @param int|WC_Order $order_id_or_order Order.
     * @return void
     */
    public function render_per_course_access_buttons($order_id_or_order) {
        $order = $order_id_or_order instanceof WC_Order ? $order_id_or_order : wc_get_order((int) $order_id_or_order);
        if (!$order instanceof WC_Order) {
            return;
        }
        if (!function_exists('ttp_order_qualifies_for_tcy_actions') || !ttp_order_qualifies_for_tcy_actions($order)) {
            return;
        }
        if (!$this->order_contains_tcy_course($order)) {
            return;
        }
        if (!$this->customer_can_access_thankyou_order($order)) {
            return;
        }

        $this->sync_tcy_if_missing_mapping((int) $order->get_id());

        $user_id   = (int) $order->get_user_id();
        $order_id  = (int) $order->get_id();
        $order_key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
        $rows      = [];

        foreach ($order->get_items() as $item) {
            $pid = (int) $item->get_product_id();
            if ($pid < 1) {
                continue;
            }
            $ids = function_exists('ttp_get_tcy_ids_for_product')
                ? ttp_get_tcy_ids_for_product($pid, false)
                : ['course_id' => (string) get_post_meta($pid, '_ttp_tcy_course_id', true)];
            $cid = isset($ids['course_id']) ? (string) $ids['course_id'] : '';
            if ($cid === '' || $cid === '0') {
                continue;
            }
            $name = trim((string) $item->get_name());
            if ($name === '') {
                continue;
            }
            $open_uid = $user_id > 0 ? $user_id : (is_user_logged_in() ? get_current_user_id() : 0);
            $url      = function_exists('ttp_get_open_course_url_for_user')
                ? ttp_get_open_course_url_for_user($open_uid, $pid, $order_id, $order_key)
                : home_url('/');
            $rows[]   = ['name' => $name, 'url' => $url];
        }

        if (empty($rows)) {
            return;
        }

        echo '<div class="ttp-order-course-access">';
        echo '<h2 class="ttp-order-course-access__title">' . esc_html__('Course Access', 'ttp-woocommerce') . '</h2>';
        echo '<p class="ttp-order-course-access__lead">' . esc_html__('Open each course on the study portal (all courses from this order are activated first).', 'ttp-woocommerce') . '</p>';
        echo '<ul class="ttp-order-course-access__list">';
        foreach ($rows as $row) {
            echo '<li class="ttp-order-course-access__item">';
            echo '<a class="ttp-btn ttp-btn--access ttp-access-btn" href="' . esc_url($row['url']) . '">';
            echo esc_html__('Access', 'ttp-woocommerce') . ' — ' . esc_html($row['name']);
            echo '</a></li>';
        }
        echo '</ul></div>';
    }

    /**
     * @param WC_Order $order Order.
     * @return bool
     */
    private function order_contains_tcy_course($order) {
        if (!$order instanceof WC_Order) {
            return false;
        }
        foreach ($order->get_items() as $item) {
            $pid = (int) $item->get_product_id();
            if (!$pid) {
                continue;
            }
            $tcy_ids = function_exists('ttp_get_tcy_ids_for_line_item')
                ? ttp_get_tcy_ids_for_line_item($item, false)
                : (function_exists('ttp_get_tcy_ids_for_product')
                    ? ttp_get_tcy_ids_for_product($pid, false)
                    : [
                        'course_id' => (string) get_post_meta($pid, '_ttp_tcy_course_id', true),
                    ]);
            $course_id = isset($tcy_ids['course_id']) ? (string) $tcy_ids['course_id'] : '';
            if ($course_id !== '' && $course_id !== '0') {
                return true;
            }
        }
        return false;
    }

    /**
     * On failed/cancelled/pending/refunded orders, remove order-level TCY access artifacts.
     * This does not touch user-level mapping to avoid affecting previously paid orders.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function revoke_unpaid_order_access($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Gateway captured payment but WooCommerce marked cancelled/failed — promote and enroll instead of revoking.
        if (function_exists('ttp_maybe_promote_gateway_paid_order') && ttp_maybe_promote_gateway_paid_order($order)) {
            return;
        }

        // Do not strip TCY access while the order is still a successful checkout (covers ₹0 where is_paid() is false).
        if (function_exists('ttp_order_qualifies_for_tcy_actions') && ttp_order_qualifies_for_tcy_actions($order)) {
            return;
        }

        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}ttp_order_mapping WHERE order_id = %d",
                (int) $order_id
            )
        );

        $order->delete_meta_data('_ttp_tcy_registered');
        $order->delete_meta_data('_tcy_enrolled');
        $order->delete_meta_data('_tcy_enrolled_at');
        $order->save();
    }

    public function customize_checkout_fields($fields) {
        unset($fields['billing']['billing_company']);
        $fields['billing']['billing_address_2']['required'] = false;
        $fields['billing']['billing_phone']['required']     = true;
        return $fields;
    }

    /**
     * register once → add_course in a loop for every line item (same TCY user_id).
     *
     * @param WC_Order $order          Order.
     * @param array    $line_items     Rows with course_id, category_id, product_id.
     * @param string   $tcy_account_id TCY user id (e.g. ODEzMjQ0MA==).
     * @param int      $user_id        WP user id.
     * @param TTP_TCY_API|null $api    API client.
     * @return void
     */
    private function enroll_all_line_items_on_tcy_account( $order, array $line_items, $tcy_account_id, $user_id, $api = null ) {
        if ( ! $order instanceof WC_Order || $tcy_account_id === '' || empty( $line_items ) ) {
            return;
        }
        $order_id = (int) $order->get_id();
        global $wpdb;
        $table = $wpdb->prefix . 'ttp_order_mapping';

        foreach ( $line_items as $row ) {
            $course_id   = isset( $row['course_id'] ) ? (string) $row['course_id'] : '';
            $category_id = isset( $row['category_id'] ) ? (string) $row['category_id'] : '';
            if ( $course_id === '' || $course_id === '0' ) {
                continue;
            }

            $existing = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE order_id = %d AND tcy_course_id = %s AND status = %s LIMIT 1",
                    $order_id,
                    $course_id,
                    'registered'
                )
            );
            if ( $existing > 0 ) {
                $wpdb->update(
                    $table,
                    [
                        'tcy_user_id'     => $tcy_account_id,
                        'tcy_category_id' => $category_id,
                        'wp_user_id'      => (int) $user_id,
                    ],
                    [ 'id' => $existing ],
                    [ '%s', '%s', '%d' ],
                    [ '%d' ]
                );
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'order_id'        => $order_id,
                        'wp_user_id'      => (int) $user_id,
                        'tcy_user_id'     => $tcy_account_id,
                        'tcy_course_id'   => $course_id,
                        'tcy_category_id' => $category_id,
                        'status'          => 'registered',
                    ]
                );
            }
        }
    }

    /**
     * Sync this order + every paid course on the customer's TCY account.
     *
     * @param WC_Order $order          Order.
     * @param string   $tcy_user_id    TCY user id.
     * @param int      $user_id        WP user id.
     * @param string   $billing_email  Billing email.
     * @return void
     */
    private function sync_all_tcy_courses_for_customer( $order, $tcy_user_id, $user_id, $billing_email ) {
        if ( ! $order instanceof WC_Order || $tcy_user_id === '' ) {
            return;
        }
        $order_id = (int) $order->get_id();
        if ( function_exists( 'ttp_sync_tcy_courses_for_order' ) ) {
            delete_transient( 'ttp_sync_order_' . $order_id );
            ttp_sync_tcy_courses_for_order( $order );
        }
        if ( function_exists( 'ttp_tcy_schedule_deferred_full_sync' ) ) {
            ttp_tcy_schedule_deferred_full_sync( $tcy_user_id, (int) $user_id, (string) $billing_email );
            return;
        }
        if ( function_exists( 'ttp_tcy_loop_add_all_courses_for_user_id' ) ) {
            ttp_tcy_loop_add_all_courses_for_user_id( $tcy_user_id, (int) $user_id, (string) $billing_email );
        } elseif ( function_exists( 'ttp_tcy_sync_all_purchased_courses_for_user' ) ) {
            ttp_tcy_sync_all_purchased_courses_for_user( $tcy_user_id, (int) $user_id, (string) $billing_email );
        }
        if ( function_exists( 'ttp_tcy_collect_purchased_course_pairs' ) ) {
            $pairs = ttp_tcy_collect_purchased_course_pairs( (int) $user_id, (string) $billing_email );
            if ( ! empty( $pairs ) ) {
                $order->update_meta_data( '_ttp_tcy_enrolled_pairs', wp_json_encode( array_values( $pairs ) ) );
            }
        }
    }

    private function extract_tcy_user_id($response) {
        if (function_exists('ttp_tcy_extract_register_user_id')) {
            $s = ttp_tcy_extract_register_user_id($response);
            return $s !== '' ? $s : null;
        }
        if (!is_array($response)) {
            return null;
        }

        $possible_keys = apply_filters(
            'ttp_tcy_register_user_id_keys',
            array('user_id', 'tcy_user_id', 'userid', 'student_id', 'erp_user_id', 'erp_userid', 'id')
        );
        foreach ($possible_keys as $key) {
            if (!empty($response[$key])) {
                return sanitize_text_field((string) $response[$key]);
            }
            if (isset($response['data']) && is_array($response['data']) && !empty($response['data'][$key])) {
                return sanitize_text_field((string) $response['data'][$key]);
            }
        }

        return null;
    }
}
