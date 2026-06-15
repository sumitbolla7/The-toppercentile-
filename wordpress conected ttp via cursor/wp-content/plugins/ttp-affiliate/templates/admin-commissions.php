<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap ttpa-wrap">
    <h1><?php esc_html_e('Commissions', 'ttp-affiliate'); ?></h1>
    <?php if (!empty($_GET['updated'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Commission updated.', 'ttp-affiliate'); ?></p></div><?php endif; ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php esc_html_e('Referrer', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Order', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Rate', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Amount', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Status', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Date', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Action', 'ttp-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($items)) : ?>
            <tr><td colspan="8"><?php esc_html_e('No commissions yet.', 'ttp-affiliate'); ?></td></tr>
        <?php else : foreach ($items as $item) :
            $user = get_userdata((int) $item['referrer_user_id']);
        ?>
            <tr>
                <td><?php echo esc_html($item['id']); ?></td>
                <td><?php echo esc_html($user ? $user->display_name : '#' . $item['referrer_user_id']); ?></td>
                <td><a href="<?php echo esc_url(admin_url('post.php?post=' . $item['order_id'] . '&action=edit')); ?>">#<?php echo esc_html($item['order_id']); ?></a></td>
                <td><?php echo esc_html($item['commission_rate']); ?>%</td>
                <td><?php echo wp_kses_post(ttpa_format_money($item['commission_amount'])); ?></td>
                <td><?php echo esc_html($item['status']); ?></td>
                <td><?php echo esc_html($item['created_at']); ?></td>
                <td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('ttpa_update_commission'); ?>
                        <input type="hidden" name="action" value="ttpa_update_commission" />
                        <input type="hidden" name="commission_id" value="<?php echo esc_attr($item['id']); ?>" />
                        <select name="status">
                            <option value="pending" <?php selected($item['status'], 'pending'); ?>>pending</option>
                            <option value="approved" <?php selected($item['status'], 'approved'); ?>>approved</option>
                            <option value="paid" <?php selected($item['status'], 'paid'); ?>>paid</option>
                        </select>
                        <button class="button button-small"><?php esc_html_e('Update', 'ttp-affiliate'); ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
