<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap anh-wrap">
    <h1><?php esc_html_e('Referral Members', 'anh-hub'); ?></h1>
    <?php if (!empty($_GET['revoked'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Referral access revoked.', 'anh-hub'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['revoked_all'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php printf(esc_html__('Revoked referral access for %d user(s). Only users you explicitly enable from Affiliate Links will appear here again.', 'anh-hub'), (int) ($_GET['count'] ?? 0)); ?></p></div>
    <?php endif; ?>
    <p><?php esc_html_e('Only users you explicitly enable under Affiliate Links appear here. Access requires your admin account in “Granted by”. Stale auto-flags no longer grant referral links.', 'anh-hub'); ?></p>

    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links')); ?>"><?php esc_html_e('Add or manage member', 'anh-hub'); ?></a>
        <?php if (!empty($members)) :
            $revoke_all_url = wp_nonce_url(
                add_query_arg(['action' => 'anh_revoke_all_referral_access'], admin_url('admin-post.php')),
                'anh_revoke_all_referral_access'
            );
            ?>
            <a class="button button-secondary" href="<?php echo esc_url($revoke_all_url); ?>" style="margin-left:8px;color:#b91c1c;border-color:#b91c1c;" onclick="return confirm('<?php echo esc_js(__('Remove referral access from EVERYONE in this list? You can re-enable individuals later from Affiliate Links.', 'anh-hub')); ?>');"><?php esc_html_e('Revoke ALL members', 'anh-hub'); ?></a>
        <?php endif; ?>
    </p>

    <table class="widefat striped anh-members-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Email', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Access source', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Granted', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Granted by', 'anh-hub'); ?></th>
                <th><?php esc_html_e('WP roles', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Referral code', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Status', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Clicks', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Referrals', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Earned', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Actions', 'anh-hub'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($members)) : ?>
            <tr>
                <td colspan="12">
                    <?php esc_html_e('No referral members yet. Use Affiliate Links to enable the program for a user.', 'anh-hub'); ?>
                </td>
            </tr>
        <?php else : foreach ($members as $member) :
            $granted_at = !empty($member['access_granted_at']) ? wp_date('Y-m-d', strtotime($member['access_granted_at'])) : '—';
            $revoke_url = wp_nonce_url(
                add_query_arg(
                    [
                        'action'   => 'anh_revoke_referral_access',
                        'user_id'  => (int) ($member['user_id'] ?? 0),
                        'redirect' => 'members',
                    ],
                    admin_url('admin-post.php')
                ),
                'anh_revoke_referral_access_' . (int) ($member['user_id'] ?? 0)
            );
            ?>
            <tr>
                <td><?php echo esc_html($member['display_name'] ?? '—'); ?></td>
                <td><?php echo esc_html($member['email'] ?? '—'); ?></td>
                <td>
                    <?php echo esc_html($member['access_source_label'] ?? '—'); ?>
                    <?php if (($member['access_source'] ?? '') === 'stale_flag' || (($member['access_source'] ?? '') === 'manual' && empty($member['access_granted_by']))) : ?>
                        <br><span class="anh-badge anh-badge-off"><?php esc_html_e('Not admin-approved', 'anh-hub'); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html($granted_at); ?></td>
                <td><?php echo esc_html($member['access_granted_by'] ?: '—'); ?></td>
                <td><code><?php echo esc_html($member['user_roles'] ?? '—'); ?></code></td>
                <td>
                    <?php if (!empty($member['referral_code'])) : ?>
                        <code><?php echo esc_html($member['referral_code']); ?></code>
                    <?php else : ?>
                        <span class="anh-muted"><?php esc_html_e('Not generated', 'anh-hub'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($member['is_influencer'])) : ?>
                        <span class="anh-badge anh-badge-influencer"><?php esc_html_e('Influencer', 'anh-hub'); ?></span>
                    <?php elseif (!empty($member['affiliate_enabled'])) : ?>
                        <span class="anh-badge anh-badge-on"><?php esc_html_e('Enabled', 'anh-hub'); ?></span>
                    <?php else : ?>
                        <span class="anh-badge anh-badge-off"><?php esc_html_e('Inactive', 'anh-hub'); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html((string) ($member['click_count'] ?? 0)); ?></td>
                <td><?php echo esc_html((string) ($member['referral_count'] ?? 0)); ?></td>
                <td><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($member['total_earned'] ?? 0)) : esc_html($member['total_earned'] ?? 0); ?></td>
                <td>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links&user_id=' . (int) ($member['user_id'] ?? 0))); ?>">
                        <?php esc_html_e('Manage', 'anh-hub'); ?>
                    </a>
                    |
                    <a href="<?php echo esc_url($revoke_url); ?>" onclick="return confirm('<?php echo esc_js(__('Revoke all referral access for this user?', 'anh-hub')); ?>');">
                        <?php esc_html_e('Revoke', 'anh-hub'); ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
