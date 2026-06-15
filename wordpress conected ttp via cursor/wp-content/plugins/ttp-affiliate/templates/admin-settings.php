<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap ttpa-wrap">
    <h1><?php esc_html_e('Affiliate Settings', 'ttp-affiliate'); ?></h1>
    <?php if (!empty($_GET['updated'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Settings saved.', 'ttp-affiliate'); ?></p></div><?php endif; ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('ttpa_save_settings'); ?>
        <input type="hidden" name="action" value="ttpa_save_settings" />
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Commission rate (%)', 'ttp-affiliate'); ?></th>
                <td><input type="number" step="0.01" min="0" max="100" name="commission_rate" value="<?php echo esc_attr($settings['commission_rate']); ?>" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Auto-approve commissions', 'ttp-affiliate'); ?></th>
                <td><label><input type="checkbox" name="auto_approve_commissions" value="1" <?php checked($settings['auto_approve_commissions']); ?> /> <?php esc_html_e('Approve on order completion', 'ttp-affiliate'); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Auto payout', 'ttp-affiliate'); ?></th>
                <td><label><input type="checkbox" name="auto_payout_enabled" value="1" <?php checked($settings['auto_payout_enabled']); ?> /> <?php esc_html_e('Run daily auto payouts', 'ttp-affiliate'); ?></label></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Payout threshold', 'ttp-affiliate'); ?></th>
                <td><input type="number" step="0.01" min="0" name="payout_threshold" value="<?php echo esc_attr($settings['payout_threshold']); ?>" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Referral URL parameter', 'ttp-affiliate'); ?></th>
                <td><input type="text" name="referral_param" value="<?php echo esc_attr($settings['referral_param']); ?>" /> <p class="description"><?php esc_html_e('Example: ?ref=CODE or /ref/CODE', 'ttp-affiliate'); ?></p></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
