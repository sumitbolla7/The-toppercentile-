<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ttpn-wrap">
    <h1><?php esc_html_e('Push Subscriptions', 'ttp-notifications'); ?></h1>
    <table class="widefat striped ttpn-table">
        <thead>
            <tr>
                <th><?php esc_html_e('User', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('Endpoint', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('User Agent', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('Updated', 'ttp-notifications'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)) : ?>
                <tr><td colspan="4"><?php esc_html_e('No push subscriptions yet.', 'ttp-notifications'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($items as $item) : ?>
                    <?php $user = get_userdata((int) $item['user_id']); ?>
                    <tr>
                        <td><?php echo esc_html($user ? $user->display_name : '#' . $item['user_id']); ?></td>
                        <td><code><?php echo esc_html(wp_trim_words($item['endpoint'], 8, '…')); ?></code></td>
                        <td><?php echo esc_html($item['user_agent']); ?></td>
                        <td><?php echo esc_html($item['updated_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
