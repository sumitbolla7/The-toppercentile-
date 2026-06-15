<?php if (!defined('ABSPATH')) exit; ?>
<div class="ttpa-leaderboard-public">
    <h3><?php esc_html_e('Top Referrers', 'ttp-affiliate'); ?></h3>
    <ol>
        <?php foreach ($items as $row) :
            $user = get_userdata((int) $row['user_id']);
            $name = $user ? $user->display_name : __('Referrer', 'ttp-affiliate') . ' #' . $row['user_id'];
        ?>
            <li>
                <strong><?php echo esc_html($name); ?></strong>
                — <?php echo wp_kses_post(wc_price($row['total_earned'])); ?>
                (<?php echo esc_html($row['referral_count']); ?> <?php esc_html_e('referrals', 'ttp-affiliate'); ?>)
            </li>
        <?php endforeach; ?>
    </ol>
</div>
