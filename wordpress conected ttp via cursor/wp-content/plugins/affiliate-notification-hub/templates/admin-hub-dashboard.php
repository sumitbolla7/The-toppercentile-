<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap anh-wrap">
    <h1><?php esc_html_e('Affiliate Hub', 'anh-hub'); ?></h1>

    <div class="anh-cards">
        <div class="anh-card">
            <h3><?php esc_html_e('Referral members', 'anh-hub'); ?></h3>
            <p class="anh-stat"><?php echo esc_html($enabled_count ?? 0); ?></p>
        </div>
        <div class="anh-card">
            <h3><?php esc_html_e('Referrals', 'anh-hub'); ?></h3>
            <p class="anh-stat"><?php echo esc_html($affiliate_stats['referrals'] ?? 0); ?></p>
        </div>
        <div class="anh-card">
            <h3><?php esc_html_e('Link clicks', 'anh-hub'); ?></h3>
            <p class="anh-stat"><?php echo esc_html($affiliate_stats['clicks'] ?? 0); ?></p>
        </div>
        <div class="anh-card">
            <h3><?php esc_html_e('Commissions paid', 'anh-hub'); ?></h3>
            <p class="anh-stat"><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($affiliate_stats['commissions_paid'] ?? 0)) : esc_html($affiliate_stats['commissions_paid'] ?? 0); ?></p>
        </div>
        <div class="anh-card">
            <h3><?php esc_html_e('Notifications', 'anh-hub'); ?></h3>
            <p class="anh-stat"><?php echo esc_html($notif_stats['total'] ?? 0); ?></p>
        </div>
    </div>

    <h2><?php esc_html_e('Quick actions', 'anh-hub'); ?></h2>
    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links')); ?>"><?php esc_html_e('Create affiliate link', 'anh-hub'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=anh-referrals')); ?>"><?php esc_html_e('View all influencers', 'anh-hub'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=anh-create-notification')); ?>"><?php esc_html_e('Send notification', 'anh-hub'); ?></a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ttp-affiliate-commissions')); ?>"><?php esc_html_e('View commissions', 'anh-hub'); ?></a>
    </p>

    <h2><?php esc_html_e('Referral program members', 'anh-hub'); ?></h2>
    <p class="description"><?php esc_html_e('Shows everyone you enabled for referrals — even if they have zero clicks or earnings.', 'anh-hub'); ?></p>
    <table class="widefat striped anh-members-table">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Email', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Referral link', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Code', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Status', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Clicks', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Referrals', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Total Sales', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Earned', 'anh-hub'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($enabled_members)) : ?>
            <tr>
                <td colspan="9">
                    <?php esc_html_e('No referral members yet.', 'anh-hub'); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links')); ?>"><?php esc_html_e('Enable referral access for a user', 'anh-hub'); ?></a>
                </td>
            </tr>
        <?php else : foreach ($enabled_members as $member) : ?>
            <tr>
                <td>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links&user_id=' . (int) ($member['user_id'] ?? 0))); ?>">
                        <?php echo esc_html($member['display_name'] ?? '—'); ?>
                    </a>
                </td>
                <td><?php echo esc_html($member['email'] ?? '—'); ?></td>
                <td>
                    <?php if (!empty($member['referral_link'])) : ?>
                        <code style="font-size:11px;word-break:break-all;"><?php echo esc_html($member['referral_link']); ?></code>
                    <?php else : ?>
                        <span class="anh-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($member['referral_code'])) : ?>
                        <code><?php echo esc_html($member['referral_code']); ?></code>
                    <?php else : ?>
                        <span class="anh-muted"><?php esc_html_e('Pending', 'anh-hub'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($member['is_influencer'])) : ?>
                        <span class="anh-badge anh-badge-influencer"><?php esc_html_e('Influencer', 'anh-hub'); ?></span>
                    <?php else : ?>
                        <span class="anh-badge anh-badge-on"><?php esc_html_e('Enabled', 'anh-hub'); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html((string) ($member['click_count'] ?? 0)); ?></td>
                <td><?php echo esc_html((string) ($member['referral_count'] ?? 0)); ?></td>
                <td><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($member['total_sales'] ?? 0)) : esc_html($member['total_sales'] ?? 0); ?></td>
                <td><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($member['total_earned'] ?? 0)) : esc_html($member['total_earned'] ?? 0); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    <?php if (!empty($enabled_members) && ($enabled_count ?? 0) > count($enabled_members)) : ?>
        <p><a href="<?php echo esc_url(admin_url('admin.php?page=anh-referrals')); ?>"><?php esc_html_e('View all influencers with full details', 'anh-hub'); ?></a></p>
    <?php endif; ?>

    <h2><?php esc_html_e('Top earners', 'anh-hub'); ?></h2>
    <p class="description"><?php esc_html_e('Affiliates ranked by commission earnings (only appears after someone earns).', 'anh-hub'); ?></p>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Affiliate', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Referrals', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Earned', 'anh-hub'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($leaderboard)) : ?>
            <tr><td colspan="3"><?php esc_html_e('No earnings yet.', 'anh-hub'); ?></td></tr>
        <?php else : foreach ($leaderboard as $row) :
            $user = get_userdata((int) $row['user_id']);
        ?>
            <tr>
                <td><?php echo esc_html($user ? $user->display_name : '—'); ?></td>
                <td><?php echo esc_html($row['referral_count'] ?? 0); ?></td>
                <td><?php echo function_exists('wc_price') ? wp_kses_post(wc_price($row['total_earned'] ?? 0)) : esc_html($row['total_earned'] ?? 0); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <h2><?php esc_html_e('Shortcodes for your site', 'anh-hub'); ?></h2>
    <table class="widefat">
        <tbody>
            <tr><td><code>[affiliate_referral_link]</code></td><td><?php esc_html_e('Shows logged-in user referral link with copy button.', 'anh-hub'); ?></td></tr>
            <tr><td><code>[affiliate_referral_link show_stats="yes"]</code></td><td><?php esc_html_e('Referral link plus clicks, referrals, and earnings.', 'anh-hub'); ?></td></tr>
            <tr><td><code>[affiliate_leaderboard limit="10"]</code></td><td><?php esc_html_e('Public top affiliates table.', 'anh-hub'); ?></td></tr>
            <tr><td><code>[notification_bell]</code></td><td><?php esc_html_e('Notification bell icon (add to header/menu).', 'anh-hub'); ?></td></tr>
            <tr><td><code>[ttp_affiliate_dashboard]</code></td><td><?php esc_html_e('Full affiliate dashboard for My Account.', 'anh-hub'); ?></td></tr>
        </tbody>
    </table>
</div>
