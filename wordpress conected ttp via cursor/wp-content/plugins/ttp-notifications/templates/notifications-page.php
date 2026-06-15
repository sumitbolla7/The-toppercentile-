<?php
if (!defined('ABSPATH')) {
    exit;
}
$user_id = get_current_user_id();
$items   = TTPN_Plugin::instance()->notifications()->get_for_user($user_id, ['limit' => 50]);
?>
<div class="ttpn-page">
    <h2><?php esc_html_e('Your Notifications', 'ttp-notifications'); ?></h2>
    <p><button type="button" class="button ttpn-mark-all"><?php esc_html_e('Mark all as read', 'ttp-notifications'); ?></button>
    <button type="button" class="button ttpn-enable-push"><?php esc_html_e('Enable browser push alerts', 'ttp-notifications'); ?></button></p>
    <ul class="ttpn-list">
        <?php if (empty($items)) : ?>
            <li class="ttpn-empty"><?php esc_html_e('No notifications yet.', 'ttp-notifications'); ?></li>
        <?php else : ?>
            <?php foreach ($items as $item) : ?>
                <li class="ttpn-item <?php echo $item['is_read'] ? 'is-read' : 'is-unread'; ?>" data-id="<?php echo esc_attr($item['id']); ?>">
                    <strong><?php echo esc_html($item['title']); ?></strong>
                    <p><?php echo esc_html(wp_strip_all_tags($item['message'])); ?></p>
                    <small><?php echo esc_html($item['created_at']); ?></small>
                    <?php if (!empty($item['link'])) : ?>
                        <a href="<?php echo esc_url($item['link']); ?>"><?php esc_html_e('Open', 'ttp-notifications'); ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
