<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap ttpa-wrap">
    <h1><?php esc_html_e('TTP Affiliate Dashboard', 'ttp-affiliate'); ?></h1>
    <div class="ttpa-stats-grid">
        <div class="ttpa-stat-card"><h2><?php esc_html_e('Referrals', 'ttp-affiliate'); ?></h2><p><?php echo esc_html($stats['referrals']); ?></p></div>
        <div class="ttpa-stat-card"><h2><?php esc_html_e('Converted', 'ttp-affiliate'); ?></h2><p><?php echo esc_html($stats['converted']); ?></p></div>
        <div class="ttpa-stat-card"><h2><?php esc_html_e('Link Clicks', 'ttp-affiliate'); ?></h2><p><?php echo esc_html($stats['clicks']); ?></p></div>
        <div class="ttpa-stat-card"><h2><?php esc_html_e('Total Commissions', 'ttp-affiliate'); ?></h2><p><?php echo wp_kses_post(ttpa_format_money($stats['commissions_total'])); ?></p></div>
        <div class="ttpa-stat-card"><h2><?php esc_html_e('Pending', 'ttp-affiliate'); ?></h2><p><?php echo wp_kses_post(ttpa_format_money($stats['commissions_pending'])); ?></p></div>
        <div class="ttpa-stat-card"><h2><?php esc_html_e('Paid Out', 'ttp-affiliate'); ?></h2><p><?php echo wp_kses_post(ttpa_format_money($stats['commissions_paid'])); ?></p></div>
    </div>

    <h2><?php esc_html_e('Top Referrers', 'ttp-affiliate'); ?></h2>
    <table class="widefat striped">
        <thead><tr><th><?php esc_html_e('User', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Referrals', 'ttp-affiliate'); ?></th><th><?php esc_html_e('Earned', 'ttp-affiliate'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($leaderboard as $row) : $user = get_userdata((int) $row['user_id']); ?>
            <tr>
                <td><?php echo esc_html($user ? $user->display_name : '#' . $row['user_id']); ?></td>
                <td><?php echo esc_html($row['referral_count']); ?></td>
                <td><?php echo wp_kses_post(ttpa_format_money($row['total_earned'])); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
