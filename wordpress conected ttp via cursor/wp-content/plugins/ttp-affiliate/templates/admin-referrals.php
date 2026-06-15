<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap ttpa-wrap">
    <h1><?php esc_html_e('Referrals', 'ttp-affiliate'); ?></h1>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php esc_html_e('Referrer', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Referred User', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Code', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Status', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Order', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Date', 'ttp-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($items)) : ?>
            <tr><td colspan="7"><?php esc_html_e('No referrals yet.', 'ttp-affiliate'); ?></td></tr>
        <?php else : foreach ($items as $item) :
            $referrer = get_userdata((int) $item['referrer_user_id']);
            $referred = get_userdata((int) $item['referred_user_id']);
        ?>
            <tr>
                <td><?php echo esc_html($item['id']); ?></td>
                <td><?php echo esc_html($referrer ? $referrer->display_name : '#' . $item['referrer_user_id']); ?></td>
                <td><?php echo esc_html($referred ? $referred->display_name : '#' . $item['referred_user_id']); ?></td>
                <td><code><?php echo esc_html($item['referral_code']); ?></code></td>
                <td><?php echo esc_html($item['status']); ?></td>
                <td><?php echo $item['order_id'] ? esc_html('#' . $item['order_id']) : '—'; ?></td>
                <td><?php echo esc_html($item['created_at']); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
