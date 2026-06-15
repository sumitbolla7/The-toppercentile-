<?php if (!defined('ABSPATH')) exit; ?>
<div class="ttpa-dashboard">
    <h2><?php esc_html_e('Your Referral Program', 'ttp-affiliate'); ?></h2>

    <div class="ttpa-cards">
        <div class="ttpa-card">
            <span><?php esc_html_e('Available balance', 'ttp-affiliate'); ?></span>
            <strong><?php echo wp_kses_post(wc_price($balance)); ?></strong>
        </div>
        <div class="ttpa-card">
            <span><?php esc_html_e('Total earned', 'ttp-affiliate'); ?></span>
            <strong><?php echo wp_kses_post(wc_price($total_earned)); ?></strong>
        </div>
        <div class="ttpa-card">
            <span><?php esc_html_e('Link clicks', 'ttp-affiliate'); ?></span>
            <strong><?php echo esc_html($clicks); ?></strong>
        </div>
        <div class="ttpa-card">
            <span><?php esc_html_e('Referrals', 'ttp-affiliate'); ?></span>
            <strong><?php echo esc_html(count($referrals)); ?></strong>
        </div>
    </div>

    <div class="ttpa-link-box">
        <label for="ttpa-ref-link"><?php esc_html_e('Your unique referral link', 'ttp-affiliate'); ?></label>
        <div class="ttpa-link-row">
            <input id="ttpa-ref-link" type="text" readonly value="<?php echo esc_attr($link); ?>" />
            <button type="button" class="button ttpa-copy-link" data-copy="<?php echo esc_attr($link); ?>"><?php esc_html_e('Copy', 'ttp-affiliate'); ?></button>
        </div>
        <p class="description"><?php printf(esc_html__('Referral code: %s', 'ttp-affiliate'), esc_html($code)); ?></p>
    </div>

    <h3><?php esc_html_e('Recent referrals', 'ttp-affiliate'); ?></h3>
    <table class="shop_table shop_table_responsive">
        <thead><tr><th><?php esc_html_e('User', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Status', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Date', 'ttp-affiliate'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($referrals)) : ?>
            <tr><td colspan="3"><?php esc_html_e('No referrals yet. Share your link!', 'ttp-affiliate'); ?></td></tr>
        <?php else : foreach ($referrals as $row) :
            $u = get_userdata((int) $row['referred_user_id']);
        ?>
            <tr>
                <td><?php echo esc_html($u ? $u->display_name : '—'); ?></td>
                <td><?php echo esc_html($row['status']); ?></td>
                <td><?php echo esc_html($row['created_at']); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <h3><?php esc_html_e('Commissions', 'ttp-affiliate'); ?></h3>
    <table class="shop_table shop_table_responsive">
        <thead><tr><th><?php esc_html_e('Order', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Amount', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Status', 'ttp-affiliate'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($commissions)) : ?>
            <tr><td colspan="3"><?php esc_html_e('No commissions yet.', 'ttp-affiliate'); ?></td></tr>
        <?php else : foreach ($commissions as $row) : ?>
            <tr>
                <td>#<?php echo esc_html($row['order_id']); ?></td>
                <td><?php echo wp_kses_post(wc_price($row['commission_amount'])); ?></td>
                <td><?php echo esc_html($row['status']); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <h3><?php esc_html_e('Payout history', 'ttp-affiliate'); ?></h3>
    <table class="shop_table shop_table_responsive">
        <thead><tr><th><?php esc_html_e('Amount', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Method', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Date', 'ttp-affiliate'); ?></th></tr></thead>
        <tbody>
        <?php if (empty($payouts)) : ?>
            <tr><td colspan="3"><?php esc_html_e('No payouts yet.', 'ttp-affiliate'); ?></td></tr>
        <?php else : foreach ($payouts as $row) : ?>
            <tr>
                <td><?php echo wp_kses_post(wc_price($row['amount'])); ?></td>
                <td><?php echo esc_html($row['payment_method']); ?></td>
                <td><?php echo esc_html($row['created_at']); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
