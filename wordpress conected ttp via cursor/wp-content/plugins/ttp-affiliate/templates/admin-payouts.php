<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap ttpa-wrap">
    <h1><?php esc_html_e('Payouts', 'ttp-affiliate'); ?></h1>
    <?php if (!empty($_GET['payout'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Payout processed.', 'ttp-affiliate'); ?></p></div><?php endif; ?>

    <h2><?php esc_html_e('Manual payout', 'ttp-affiliate'); ?></h2>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ttpa-payout-form">
        <?php wp_nonce_field('ttpa_process_payout'); ?>
        <input type="hidden" name="action" value="ttpa_process_payout" />
        <p>
            <label><?php esc_html_e('User ID', 'ttp-affiliate'); ?> <input type="number" name="user_id" required /></label>
            <label><?php esc_html_e('Amount', 'ttp-affiliate'); ?> <input type="number" step="0.01" name="amount" required /></label>
            <label><?php esc_html_e('Method', 'ttp-affiliate'); ?> <input type="text" name="payment_method" value="manual" /></label>
            <label><?php esc_html_e('Reference', 'ttp-affiliate'); ?> <input type="text" name="payment_reference" /></label>
            <button class="button button-primary"><?php esc_html_e('Process payout', 'ttp-affiliate'); ?></button>
        </p>
    </form>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php esc_html_e('User', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Amount', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Method', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Reference', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Status', 'ttp-affiliate'); ?></th>
                <th><?php esc_html_e('Date', 'ttp-affiliate'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($items)) : ?>
            <tr><td colspan="7"><?php esc_html_e('No payouts yet.', 'ttp-affiliate'); ?></td></tr>
        <?php else : foreach ($items as $item) :
            $user = get_userdata((int) $item['user_id']);
        ?>
            <tr>
                <td><?php echo esc_html($item['id']); ?></td>
                <td><?php echo esc_html($user ? $user->display_name : '#' . $item['user_id']); ?></td>
                <td><?php echo wp_kses_post(ttpa_format_money($item['amount'])); ?></td>
                <td><?php echo esc_html($item['payment_method']); ?></td>
                <td><?php echo esc_html($item['payment_reference']); ?></td>
                <td><?php echo esc_html($item['status']); ?></td>
                <td><?php echo esc_html($item['created_at']); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
