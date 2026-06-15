<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ttpn-wrap">
    <h1><?php esc_html_e('Admin Alerts', 'ttp-notifications'); ?></h1>

    <?php if (!empty($_GET['marked'])) : ?>
        <div class="notice notice-success"><p><?php esc_html_e('All alerts marked as read.', 'ttp-notifications'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom:1rem;">
        <?php wp_nonce_field('ttpn_mark_all_alerts'); ?>
        <input type="hidden" name="action" value="ttpn_mark_all_admin_alerts_read" />
        <button class="button"><?php esc_html_e('Mark all as read', 'ttp-notifications'); ?></button>
    </form>

    <table class="widefat striped ttpn-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Type', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('Title', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('Actor', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('Read', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('Date', 'ttp-notifications'); ?></th>
                <th><?php esc_html_e('Action', 'ttp-notifications'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)) : ?>
                <tr><td colspan="6"><?php esc_html_e('No admin alerts yet.', 'ttp-notifications'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($items as $item) : ?>
                    <?php $actor = $item['actor_user_id'] ? get_userdata($item['actor_user_id']) : null; ?>
                    <tr class="<?php echo $item['is_read'] ? '' : 'ttpn-unread-row'; ?>">
                        <td><?php echo esc_html($item['alert_type']); ?></td>
                        <td>
                            <strong><?php echo esc_html($item['title']); ?></strong><br />
                            <small><?php echo esc_html(wp_strip_all_tags($item['message'])); ?></small>
                        </td>
                        <td><?php echo esc_html($actor ? $actor->display_name : '—'); ?></td>
                        <td><?php echo $item['is_read'] ? esc_html__('Yes', 'ttp-notifications') : esc_html__('No', 'ttp-notifications'); ?></td>
                        <td><?php echo esc_html($item['created_at']); ?></td>
                        <td>
                            <?php if (!empty($item['link'])) : ?>
                                <a href="<?php echo esc_url($item['link']); ?>"><?php esc_html_e('View', 'ttp-notifications'); ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
