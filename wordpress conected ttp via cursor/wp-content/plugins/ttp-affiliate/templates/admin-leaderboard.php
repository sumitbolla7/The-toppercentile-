<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap ttpa-wrap">
    <h1><?php esc_html_e('Affiliate Leaderboard', 'ttp-affiliate'); ?></h1>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>#</th>
                <th><?php esc_html_e('Referrer', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Referrals', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Commissions', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Total Earned', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Approved', 'ttp-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php $rank = 1; foreach ($items as $row) :
            $user = get_userdata((int) $row['user_id']);
        ?>
            <tr>
                <td><?php echo esc_html($rank++); ?></td>
                <td><?php echo esc_html($user ? $user->display_name : '#' . $row['user_id']); ?></td>
                <td><?php echo esc_html($row['referral_count']); ?></td>
                <td><?php echo esc_html($row['commission_count']); ?></td>
                <td><?php echo wp_kses_post(ttpa_format_money($row['total_earned'])); ?></td>
                <td><?php echo wp_kses_post(ttpa_format_money($row['approved_earned'])); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
