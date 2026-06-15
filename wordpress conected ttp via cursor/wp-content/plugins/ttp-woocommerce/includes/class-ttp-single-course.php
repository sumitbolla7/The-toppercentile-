<?php
/**
 * Premium single product layout for course (virtual / TCY) products.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TTP_Single_Course {

    public function __construct() {
        add_action('wp', [$this, 'maybe_setup_summary_layout'], 8);
        add_action('woocommerce_before_single_product', [$this, 'defer_global_notices'], 4);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles'], 25);
        add_filter('body_class', [$this, 'body_class']);
    }

    /**
     * @return bool
     */
    private function is_supported_product(?WC_Product $product) {
        if (!$product instanceof WC_Product || !$product->is_type('simple')) {
            return false;
        }
        // TCY-backed integration.
        if (get_post_meta($product->get_id(), '_ttp_tcy_course_id', true)) {
            return true;
        }

        if (get_post_meta($product->get_id(), '_ttp_tcy_category_id', true) || get_post_meta($product->get_id(), '_ttp_validity', true)) {
            return true;
        }

        if ($product->is_virtual() || $product->is_downloadable()) {
            return true;
        }

        $sku = (string) $product->get_sku();
        if ($sku !== '' && strpos(strtoupper($sku), 'TTP') !== false) {
            return true;
        }

        $course_slugs = ['mba-cet-2027', 'courses', 'course', 'test-series', 'exam', 'omets'];
        foreach ($course_slugs as $slug) {
            if (has_term($slug, 'product_cat', (int) $product->get_id())) {
                return true;
            }
        }

        return (bool) apply_filters('ttp_use_course_product_summary_card', false, $product);
    }

    /**
     * Resolve product on single product templates (global may be unset during body_class).
     *
     * @return WC_Product|null
     */
    private function get_context_product() {
        if (!function_exists('is_product') || !is_product()) {
            return null;
        }
        global $product;
        if ($product instanceof WC_Product) {
            return $product;
        }
        $qid = get_queried_object_id();

        return $qid ? wc_get_product($qid) : null;
    }

    /**
     * @return bool
     */
    private function layout_active_here() {
        return $this->is_supported_product($this->get_context_product());
    }

    /**
     * @return void
     */
    public function defer_global_notices() {
        if (!$this->layout_active_here()) {
            return;
        }
        foreach ([5, 10, 15, 20, 22, 47, 96] as $priority) {
            remove_action('woocommerce_before_single_product', 'woocommerce_output_all_notices', $priority);
            remove_action('woocommerce_before_single_product_summary', 'woocommerce_output_all_notices', $priority);
            remove_action('woocommerce_before_main_content', 'woocommerce_output_all_notices', $priority);
        }
    }

    /**
     * Remove default WooCommerce summary pieces and inject card shell hooks.
     *
     * @return void
     */
    public function maybe_setup_summary_layout() {
        if (!$this->layout_active_here()) {
            return;
        }

        foreach ([5, 10, 15, 20, 21, 25, 29] as $p) {
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', $p);
        }

        foreach ([10, 15, 21] as $p) {
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', $p);
        }

        foreach ([10, 15] as $p) {
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', $p);
        }

        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);

        add_action('woocommerce_single_product_summary', [$this, 'open_summary_card'], 3);
        add_action('woocommerce_single_product_summary', [$this, 'render_card_header_blocks'], 5);
        add_action('woocommerce_single_product_summary', [$this, 'render_card_notices_and_inline_slot'], 31);
        add_action('woocommerce_single_product_summary', [$this, 'close_summary_card'], 120);
    }

    /**
     * @param array<string> $classes Existing body classes.
     * @return array<string>
     */
    public function body_class($classes) {
        if ($this->layout_active_here()) {
            $classes[] = 'ttp-course-product-card';
        }
        return $classes;
    }

    /**
     * @return void
     */
    public function enqueue_styles() {
        if (!$this->layout_active_here()) {
            return;
        }

        $deps = ['woocommerce-general'];
        if (!wp_style_is('woocommerce-general', 'registered')) {
            $deps = [];
        }

        wp_enqueue_style(
            'ttp-single-course',
            TTP_URL . 'assets/css/ttp-single-course.css',
            $deps,
            TTP_VERSION
        );
    }

    /**
     * @return void
     */
    public function open_summary_card() {
        echo '<div class="ttp-course-summary-card">';
        echo '<div class="ttp-course-summary-card__surface">';
    }

    /**
     * @return void
     */
    public function close_summary_card() {
        echo '</div></div>';
    }

    /**
     * @return void
     */
    public function render_card_notices_and_inline_slot() {
        echo '<div class="ttp-course-card-alerts">';
        echo '<div id="ttp-course-card-messages" class="ttp-course-card-messages ttp-course-card-messages-js" aria-live="polite"></div>';
        if (function_exists('woocommerce_output_all_notices')) {
            woocommerce_output_all_notices();
        }
        echo '</div>';
    }

    /**
     * @return void
     */
    public function render_card_header_blocks() {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $badge = trim((string) get_post_meta($product->get_id(), '_ttp_course_badge_label', true));
        if ($badge === '') {
            $badge = $this->infer_category_badge($product);
        }

        echo '<header class="ttp-course-card__masthead">';
        echo '<h1 class="ttp-course-card__title product_title entry-title">' . esc_html(get_the_title()) . '</h1>';
        if ($badge !== '') {
            echo '<span class="ttp-course-card__badge">' . esc_html($badge) . '</span>';
        }
        echo '</header>';

        echo '<hr class="ttp-course-card__rule" />';

        echo '<div class="ttp-course-card__price-strip">';
        $this->render_price_strip($product);
        echo '</div>';

        echo '<hr class="ttp-course-card__rule" />';

        $grid_raw = '';
        if (class_exists('TTP_Catalog_Seed')) {
            $grid_raw = TTP_Catalog_Seed::get_feature_grid_for_product($product);
        }
        if ($grid_raw === '') {
            $grid_raw = (string) get_post_meta($product->get_id(), '_ttp_course_feature_grid', true);
        }
        $grid = self::parse_feature_grid($grid_raw);
        echo '<div class="ttp-course-features" role="presentation">';
        foreach ($grid as $row) {
            $empty = trim(implode('', $row)) === '';
            echo '<div class="ttp-course-features__row ' . ($empty ? 'ttp-course-features__row--empty' : '') . '">';
            echo '<div class="ttp-course-features__cell"><strong class="ttp-course-features__label">' . esc_html($row[0]) . '</strong>';
            echo '<span class="ttp-course-features__desc">' . esc_html($row[1]) . '</span></div>';
            echo '<div class="ttp-course-features__cell"><strong class="ttp-course-features__label">' . esc_html($row[2]) . '</strong>';
            echo '<span class="ttp-course-features__desc">' . esc_html($row[3]) . '</span></div>';
            echo '</div>';
            echo '<hr class="ttp-course-card__rule ttp-course-card__rule--subtle ttp-course-features__sep" />';
        }
        echo '</div>';
    }

    /**
     * @param WC_Product $product Current product.
     * @return void
     */
    private function render_price_strip(WC_Product $product) {
        $regular_raw = $product->get_regular_price('edit');
        $sale_raw    = $product->get_sale_price('edit');
        $current     = wc_get_price_to_display($product);
        $regular_f   = $regular_raw !== '' ? wc_get_price_to_display($product, ['price' => (float) $regular_raw]) : $current;

        $pct = 0;
        if ($product->is_on_sale() && is_numeric($regular_raw) && (float) $regular_raw > 0 && $sale_raw !== '') {
            $pct = (int) round((1 - ((float) $sale_raw / (float) $regular_raw)) * 100);
            $pct = max(0, min(100, $pct));
        }

        echo '<div class="ttp-price-strip">';
        echo '<span class="ttp-price-strip__discount">';
        if ($pct > 0) {
            /* translators: %d: percentage off */
            echo '<span class="ttp-price-strip__discount-badge">' . esc_html(sprintf(__('Save %d%%', 'ttp-woocommerce'), $pct)) . '</span>';
        } else {
            echo '<span class="ttp-price-strip__discount-note">' . esc_html__('Best price', 'ttp-woocommerce') . '</span>';
        }
        echo '</span>';

        echo '<span class="ttp-price-strip__current">' . wp_kses_post(wc_price($current)) . '</span>';

        echo '<span class="ttp-price-strip__was">';
        if ($product->is_on_sale()) {
            echo '<del>' . wp_kses_post(wc_price($regular_f)) . '</del>';
        }
        echo '</span>';
        echo '</div>';
    }

    /**
     * @param WC_Product $product Product.
     * @return string
     */
    private function infer_category_badge(WC_Product $product) {
        $cats = wc_get_product_category_list($product->get_id(), ', ', '', '');
        if (!is_string($cats) || trim(wp_strip_all_tags($cats)) === '') {
            return '';
        }

        $parts = array_map('trim', explode(',', wp_strip_all_tags($cats)));

        return isset($parts[0]) ? (string) $parts[0] : '';
    }

    /**
     * @param string $raw Admin textarea contents.
     * @return array<int, array<int, string>>
     */
    public static function parse_feature_grid($raw) {
        $lines = preg_split('/\r\n|\r|\n/', $raw !== '' ? $raw : '');
        if (!is_array($lines)) {
            $lines = [];
        }

        $rows = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $pieces = array_map(
                static function ($p) {
                    return trim((string) $p);
                },
                explode('|', $line)
            );
            while (count($pieces) < 4) {
                $pieces[] = '';
            }

            $rows[] = [$pieces[0], $pieces[1], $pieces[2], $pieces[3]];
        }

        // Pad exactly four rows like the referenced layout template.
        while (count($rows) < 4) {
            $rows[] = ['', '', '', ''];
        }

        return array_slice($rows, 0, 4);
    }
}
