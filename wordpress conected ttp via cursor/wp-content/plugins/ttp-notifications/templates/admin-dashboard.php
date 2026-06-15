<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ttpn-wrap">
    <h1><?php esc_html_e('TTP Notifications Dashboard', 'ttp-notifications'); ?></h1>
    <p><?php esc_html_e('Track in-app alerts, browser push subscriptions, and admin activity.', 'ttp-notifications'); ?></p>

    <div class="ttpn-stats-grid">
        <div class="ttpn-stat-card">
            <h2><?php esc_html_e('Total Notifications', 'ttp-notifications'); ?></h2>
            <p><?php echo esc_html($stats['total']); ?></p>
        </div>
        <div class="ttpn-stat-card">
            <h2><?php esc_html_e('Unread (Users)', 'ttp-notifications'); ?></h2>
            <p><?php echo esc_html($stats['unread']); ?></p>
        </div>
        <div class="ttpn-stat-card">
            <h2><?php esc_html_e('Sent Today', 'ttp-notifications'); ?></h2>
            <p><?php echo esc_html($stats['today']); ?></p>
        </div>
        <div class="ttpn-stat-card">
            <h2><?php esc_html_e('Push Subscribers', 'ttp-notifications'); ?></h2>
            <p><?php echo esc_html($push_count); ?></p>
        </div>
        <div class="ttpn-stat-card">
            <h2><?php esc_html_e('Admin Alerts', 'ttp-notifications'); ?></h2>
            <p><?php echo esc_html($alert_stats['total']); ?> (<?php echo esc_html($alert_stats['unread']); ?> <?php esc_html_e('unread', 'ttp-notifications'); ?>)</p>
        </div>
    </div>

    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=ttp-notifications-log')); ?>"><?php esc_html_e('View all notifications', 'ttp-notifications'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ttp-notifications-alerts')); ?>"><?php esc_html_e('View admin alerts', 'ttp-notifications'); ?></a>
    </p>
</div>
