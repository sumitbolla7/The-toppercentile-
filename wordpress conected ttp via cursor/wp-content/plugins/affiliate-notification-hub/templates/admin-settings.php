<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap anh-wrap">
    <h1><?php esc_html_e('Affiliate Hub Settings', 'anh-hub'); ?></h1>
    <p><?php esc_html_e('Quick links to detailed settings in the component plugins.', 'anh-hub'); ?></p>
    <ul>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=ttp-affiliate-settings')); ?>"><?php esc_html_e('Affiliate commission & referral parameter settings', 'anh-hub'); ?></a></li>
        <li><a href="<?php echo esc_url(admin_url('admin.php?page=ttp-notifications-settings')); ?>"><?php esc_html_e('Push notification & in-app settings', 'anh-hub'); ?></a></li>
    </ul>
    <table class="form-table">
        <tr><th><?php esc_html_e('Referral URL parameter', 'anh-hub'); ?></th><td><code>?<?php echo esc_html($referral_param); ?>=CODE</code></td></tr>
        <tr><th><?php esc_html_e('Commission rate', 'anh-hub'); ?></th><td><?php echo esc_html($commission); ?>%</td></tr>
        <tr><th><?php esc_html_e('In-app notifications', 'anh-hub'); ?></th><td><?php echo $enable_in_app ? esc_html__('Enabled', 'anh-hub') : esc_html__('Disabled', 'anh-hub'); ?></td></tr>
        <tr><th><?php esc_html_e('Browser push', 'anh-hub'); ?></th><td><?php echo $enable_push ? esc_html__('Enabled', 'anh-hub') : esc_html__('Disabled', 'anh-hub'); ?></td></tr>
    </table>
</div>
