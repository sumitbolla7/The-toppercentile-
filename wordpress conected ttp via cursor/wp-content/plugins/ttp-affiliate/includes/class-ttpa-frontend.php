<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Frontend {

    private $referrals;
    private $commissions;
    private $payouts;

    public function __construct($referrals, $commissions, $payouts) {
        $this->referrals   = $referrals;
        $this->commissions = $commissions;
        $this->payouts     = $payouts;
    }

    public function hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('ttp_affiliate_dashboard', [$this, 'render_dashboard_shortcode']);
        add_shortcode('affiliate_dashboard', [$this, 'render_dashboard_shortcode']);
        add_shortcode('ttp_affiliate_leaderboard', [$this, 'render_leaderboard_shortcode']);
        add_shortcode('affiliate_leaderboard', [$this, 'render_leaderboard_shortcode']);
        add_shortcode('ttp_affiliate_referral_link', [$this, 'render_referral_link_shortcode']);
        add_action('init', [$this, 'register_public_shortcode_aliases'], 20);
    }

    public function register_public_shortcode_aliases() {
        if (!shortcode_exists('affiliate_referral_link')) {
            add_shortcode('affiliate_referral_link', [$this, 'render_referral_link_shortcode']);
        }
    }

    public function register_endpoint() {
        add_rewrite_endpoint('referrals', EP_ROOT | EP_PAGES);
    }

    public function add_account_menu_item($items) {
        $new = [];
        foreach ($items as $key => $label) {
            $new[$key] = $label;
            if ('notifications' === $key || 'dashboard' === $key) {
                if ('dashboard' === $key && !isset($items['notifications'])) {
                    // keep going
                }
            }
        }

        if (!isset($new['referrals'])) {
            $inserted = [];
            foreach ($new as $key => $label) {
                $inserted[$key] = $label;
                if ('notifications' === $key || ('dashboard' === $key && !isset($new['notifications']))) {
                    $inserted['referrals'] = __('Referrals', 'ttp-affiliate');
                }
            }
            if (!isset($inserted['referrals'])) {
                $inserted['referrals'] = __('Referrals', 'ttp-affiliate');
            }
            $new = $inserted;
        }

        return $new;
    }

    public function enqueue_assets() {
        if (!is_user_logged_in() && !$this->page_has_affiliate_shortcode()) {
            return;
        }

        wp_enqueue_style('ttpa-frontend', TTPA_URL . 'assets/css/frontend.css', [], TTPA_VERSION);
        wp_enqueue_script('ttpa-frontend', TTPA_URL . 'assets/js/frontend.js', ['jquery'], TTPA_VERSION, true);
    }

    private function page_has_affiliate_shortcode() {
        global $post;

        if (!$post instanceof WP_Post) {
            return false;
        }

        $shortcodes = [
            'affiliate_referral_link',
            'ttp_affiliate_referral_link',
            'ttp_affiliate_dashboard',
            'affiliate_dashboard',
            'ttp_affiliate_leaderboard',
            'affiliate_leaderboard',
        ];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return function_exists('is_account_page') && is_account_page();
    }

    public function render_dashboard_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to access your referral dashboard.', 'ttp-affiliate') . '</p>';
        }

        if (function_exists('ttpa_user_can_refer') && !ttpa_user_can_refer()) {
            return '';
        }

        ob_start();
        $this->render_dashboard(get_current_user_id());
        return ob_get_clean();
    }

    public function render_leaderboard_shortcode($atts) {
        $atts = shortcode_atts(['limit' => 10], $atts, 'ttp_affiliate_leaderboard');
        $items = $this->referrals->get_leaderboard((int) $atts['limit']);

        ob_start();
        include TTPA_PATH . 'templates/leaderboard-public.php';
        return ob_get_clean();
    }

    public function render_referral_link_shortcode($atts) {
        $atts = shortcode_atts([
            'user_id'    => 0,
            'show_code'  => 'yes',
            'show_stats' => 'no',
        ], $atts, 'affiliate_referral_link');

        if (!is_user_logged_in() && empty($atts['user_id'])) {
            return '<p>' . esc_html__('Please log in to get your referral link.', 'ttp-affiliate') . '</p>';
        }

        $user_id = (int) $atts['user_id'] ?: get_current_user_id();

        if (function_exists('ttpa_user_can_refer') && !ttpa_user_can_refer($user_id)) {
            return '';
        }

        $link    = $this->referrals->get_referral_link($user_id);
        $code    = $this->referrals->get_or_create_code($user_id);

        wp_enqueue_style('ttpa-frontend', TTPA_URL . 'assets/css/frontend.css', [], TTPA_VERSION);
        wp_enqueue_script('ttpa-frontend', TTPA_URL . 'assets/js/frontend.js', ['jquery'], TTPA_VERSION, true);

        ob_start();
        ?>
        <div class="ttpa-link-box">
            <label for="ttpa-ref-link"><?php esc_html_e('Your referral link', 'ttp-affiliate'); ?></label>
            <div class="ttpa-link-row">
                <input id="ttpa-ref-link" type="text" readonly value="<?php echo esc_attr($link); ?>" />
                <button type="button" class="button ttpa-copy-link" data-copy="<?php echo esc_attr($link); ?>"><?php esc_html_e('Copy', 'ttp-affiliate'); ?></button>
            </div>
            <?php if ('yes' === $atts['show_code']) : ?>
                <p class="description"><?php printf(esc_html__('Referral code: %s', 'ttp-affiliate'), esc_html($code)); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_account_endpoint() {
        echo do_shortcode('[ttp_affiliate_dashboard]');
    }

    private function render_dashboard($user_id) {
        $link         = $this->referrals->get_referral_link($user_id);
        $code         = $this->referrals->get_or_create_code($user_id);
        $referrals    = $this->referrals->get_referrals(['referrer_id' => $user_id, 'limit' => 20]);
        $commissions  = $this->commissions->get_list(['referrer_id' => $user_id, 'limit' => 20]);
        $payouts      = $this->payouts->get_list(['user_id' => $user_id, 'limit' => 10]);
        $clicks       = $this->referrals->count_clicks($user_id);
        $balance      = $this->commissions->get_balance($user_id, 'approved');
        $total_earned = $this->commissions->get_total_earned($user_id);

        include TTPA_PATH . 'templates/affiliate-dashboard.php';
    }
}
