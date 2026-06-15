<?php
if (!defined('ABSPATH')) {
    exit;
}

$show_stats = !empty($atts['show_stats']) && 'yes' === $atts['show_stats'];
$show_code  = !isset($atts['show_code']) || 'yes' === $atts['show_code'];

if ($show_stats && class_exists('TTPA_Plugin')) {
    $commissions  = TTPA_Plugin::instance()->commissions();
    $clicks       = $referrals->count_clicks($user_id);
    $ref_list     = $referrals->get_referrals(['referrer_id' => $user_id, 'limit' => 5]);
    $total_sales  = $commissions->get_total_sales($user_id);
    $total_earned = $commissions->get_total_earned($user_id);
}
?>
<div class="anh-referral-link ttpa-link-box">
    <label for="anh-ref-link"><?php esc_html_e('Your referral link', 'anh-hub'); ?></label>
    <div class="ttpa-link-row">
        <input id="anh-ref-link" type="text" readonly value="<?php echo esc_attr($link); ?>" />
        <button type="button" class="button ttpa-copy-link" data-copy="<?php echo esc_attr($link); ?>">
            <?php esc_html_e('Copy', 'anh-hub'); ?>
        </button>
    </div>
    <?php if ($show_code) : ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: referral code */
                esc_html__('Share this link. Referral code: %s', 'anh-hub'),
                '<strong>' . esc_html($code) . '</strong>'
            );
            ?>
        </p>
    <?php endif; ?>

    <?php if ($show_stats && isset($clicks)) : ?>
        <div class="ttpa-cards" style="margin-top:16px">
            <div class="ttpa-card"><span><?php esc_html_e('Clicks', 'anh-hub'); ?></span><strong><?php echo esc_html($clicks); ?></strong></div>
            <div class="ttpa-card"><span><?php esc_html_e('Referrals', 'anh-hub'); ?></span><strong><?php echo esc_html(count($ref_list)); ?></strong></div>
            <div class="ttpa-card"><span><?php esc_html_e('Total Sales', 'anh-hub'); ?></span><strong><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($total_sales)) : esc_html($total_sales); ?></strong></div>
            <div class="ttpa-card"><span><?php esc_html_e('Earned', 'anh-hub'); ?></span><strong><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($total_earned)) : esc_html($total_earned); ?></strong></div>
        </div>
    <?php endif; ?>
</div>
