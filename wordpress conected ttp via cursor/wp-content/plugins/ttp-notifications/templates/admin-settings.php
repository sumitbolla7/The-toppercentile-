<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ttpn-wrap">
    <h1><?php esc_html_e('Notification Settings', 'ttp-notifications'); ?></h1>

    <?php if (!empty($_GET['updated'])) : ?>
        <div class="notice notice-success"><p><?php esc_html_e('Settings saved.', 'ttp-notifications'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['test_sent'])) : ?>
        <div class="notice notice-success"><p><?php esc_html_e('Test notification sent to your account.', 'ttp-notifications'); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('ttpn_save_settings'); ?>
        <input type="hidden" name="action" value="ttpn_save_settings" />
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Enable in-app notifications', 'ttp-notifications'); ?></th>
                <td><label><input type="checkbox" name="enable_in_app" value="1" <?php checked($settings['enable_in_app']); ?> /> <?php esc_html_e('Show dashboard bell and My Account page', 'ttp-notifications'); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Enable browser push', 'ttp-notifications'); ?></th>
                <td><label><input type="checkbox" name="enable_push" value="1" <?php checked($settings['enable_push']); ?> /> <?php esc_html_e('Deliver offline push notifications', 'ttp-notifications'); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Enable admin alerts', 'ttp-notifications'); ?></th>
                <td><label><input type="checkbox" name="enable_admin_alerts" value="1" <?php checked($settings['enable_admin_alerts']); ?> /> <?php esc_html_e('Log admin activity alerts', 'ttp-notifications'); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e('VAPID public key', 'ttp-notifications'); ?></th>
                <td><code><?php echo esc_html($settings['vapid_public'] ?: __('Not generated — reactivate plugin', 'ttp-notifications')); ?></code></td>
            </tr>
        </table>
        <?php submit_button(__('Save settings', 'ttp-notifications')); ?>
    </form>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('ttpn_send_test'); ?>
        <input type="hidden" name="action" value="ttpn_send_test_notification" />
        <?php submit_button(__('Send test notification to me', 'ttp-notifications'), 'secondary'); ?>
    </form>
</div>
