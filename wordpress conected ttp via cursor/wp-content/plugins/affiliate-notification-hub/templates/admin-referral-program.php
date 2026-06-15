<?php
if (!defined('ABSPATH')) {
    exit;
}

$format_money = static function ($amount) {
    if (function_exists('ttpa_format_money')) {
        return ttpa_format_money($amount);
    }
    if (function_exists('wc_price')) {
        return wc_price($amount);
    }

    return '₹' . number_format((float) $amount, 2);
};

$total_clicks    = 0;
$total_signups   = 0;
$total_sales     = 0.0;
$total_earned_sum = 0.0;

foreach ($members as $member) {
    $total_clicks  += (int) ($member['click_count'] ?? 0);
    $total_signups += (int) ($member['referral_count'] ?? 0);
    $total_sales   += (float) ($member['total_sales'] ?? 0);
    $total_earned_sum += (float) ($member['total_earned'] ?? 0);
}
?>
<div class="wrap anh-wrap">
    <h1><?php esc_html_e('Referral Program', 'anh-hub'); ?></h1>

    <?php if (!empty($_GET['updated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Member settings saved.', 'anh-hub'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['revoked'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Referral access revoked.', 'anh-hub'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['regenerated'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('New referral code generated.', 'anh-hub'); ?></p></div>
    <?php endif; ?>

    <p><?php esc_html_e('Same referral details your influencers see on their account — link, code, clicks, signups, total sales, and earnings — all in one place.', 'anh-hub'); ?></p>

    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links')); ?>">
            <?php esc_html_e('+ Add member / influencer', 'anh-hub'); ?>
        </a>
    </p>

    <?php if (!empty($members)) : ?>
        <div class="anh-cards anh-program-summary">
            <div class="anh-card">
                <h3><?php esc_html_e('Active members', 'anh-hub'); ?></h3>
                <p class="anh-stat"><?php echo esc_html(count($members)); ?></p>
            </div>
            <div class="anh-card">
                <h3><?php esc_html_e('Total clicks', 'anh-hub'); ?></h3>
                <p class="anh-stat"><?php echo esc_html($total_clicks); ?></p>
            </div>
            <div class="anh-card">
                <h3><?php esc_html_e('Total signups', 'anh-hub'); ?></h3>
                <p class="anh-stat"><?php echo esc_html($total_signups); ?></p>
            </div>
            <div class="anh-card">
                <h3><?php esc_html_e('Total sales', 'anh-hub'); ?></h3>
                <p class="anh-stat"><?php echo wp_kses_post($format_money($total_sales)); ?></p>
            </div>
            <div class="anh-card">
                <h3><?php esc_html_e('Total earned', 'anh-hub'); ?></h3>
                <p class="anh-stat"><?php echo wp_kses_post($format_money($total_earned_sum)); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <h2><?php esc_html_e('Influencers & referral members', 'anh-hub'); ?></h2>

    <?php if (empty($members)) : ?>
        <div class="anh-result-box">
            <p><?php esc_html_e('No one has referral access yet.', 'anh-hub'); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links')); ?>">
                    <?php esc_html_e('Add your first influencer', 'anh-hub'); ?>
                </a>
            </p>
        </div>
    <?php else : ?>
        <div class="anh-influencer-grid">
            <?php foreach ($members as $member) :
                $user_id     = (int) ($member['user_id'] ?? 0);
                $granted_val = !empty($member['access_granted_at']) ? wp_date('Y-m-d', strtotime($member['access_granted_at'])) : '';
                $expires_val = !empty($member['access_expires_at']) ? wp_date('Y-m-d', strtotime($member['access_expires_at'])) : '';
                $revoke_url  = wp_nonce_url(
                    add_query_arg(
                        [
                            'action'  => 'anh_revoke_referral_access',
                            'user_id' => $user_id,
                        ],
                        admin_url('admin-post.php')
                    ),
                    'anh_revoke_referral_access_' . $user_id
                );
                $regen_url   = wp_nonce_url(
                    add_query_arg(
                        [
                            'action'  => 'anh_regenerate_referral_code',
                            'user_id' => $user_id,
                        ],
                        admin_url('admin-post.php')
                    ),
                    'anh_regenerate_referral_code_' . $user_id
                );
                ?>
                <div class="anh-influencer-card">
                    <div class="anh-influencer-card__head">
                        <div>
                            <h3><?php echo esc_html($member['display_name'] ?: $member['email']); ?></h3>
                            <p class="description"><?php echo esc_html($member['email']); ?></p>
                        </div>
                        <div class="anh-influencer-card__badges">
                            <?php if (!empty($member['is_influencer'])) : ?>
                                <span class="anh-badge anh-badge-influencer"><?php esc_html_e('Influencer', 'anh-hub'); ?></span>
                            <?php else : ?>
                                <span class="anh-badge anh-badge-on"><?php esc_html_e('Manual access', 'anh-hub'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($member['is_expired'])) : ?>
                                <span class="anh-badge anh-badge-off"><?php esc_html_e('Expired', 'anh-hub'); ?></span>
                            <?php elseif (!empty($member['affiliate_enabled'])) : ?>
                                <span class="anh-badge anh-badge-on"><?php esc_html_e('Active', 'anh-hub'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="anh-influencer-stats">
                        <div class="anh-influencer-stat">
                            <span><?php esc_html_e('Clicks', 'anh-hub'); ?></span>
                            <strong><?php echo esc_html((string) ($member['click_count'] ?? 0)); ?></strong>
                        </div>
                        <div class="anh-influencer-stat">
                            <span><?php esc_html_e('Referrals', 'anh-hub'); ?></span>
                            <strong><?php echo esc_html((string) ($member['referral_count'] ?? 0)); ?></strong>
                        </div>
                        <div class="anh-influencer-stat">
                            <span><?php esc_html_e('Total Sales', 'anh-hub'); ?></span>
                            <strong><?php echo wp_kses_post($format_money($member['total_sales'] ?? 0)); ?></strong>
                        </div>
                        <div class="anh-influencer-stat">
                            <span><?php esc_html_e('Earned', 'anh-hub'); ?></span>
                            <strong><?php echo wp_kses_post($format_money($member['total_earned'] ?? 0)); ?></strong>
                        </div>
                    </div>

                    <div class="anh-influencer-link-box">
                        <label><?php esc_html_e('Referral link', 'anh-hub'); ?></label>
                        <?php if (!empty($member['referral_link'])) : ?>
                            <div class="anh-link-row">
                                <input type="text" readonly value="<?php echo esc_attr($member['referral_link']); ?>" onclick="this.select();" />
                                <button type="button" class="button anh-copy-link" data-copy="<?php echo esc_attr($member['referral_link']); ?>">
                                    <?php esc_html_e('Copy', 'anh-hub'); ?>
                                </button>
                            </div>
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__('Referral code: %s', 'anh-hub'),
                                    '<code>' . esc_html($member['referral_code']) . '</code>'
                                );
                                ?>
                            </p>
                        <?php else : ?>
                            <p class="anh-muted"><?php esc_html_e('No referral code generated yet.', 'anh-hub'); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="anh-influencer-meta">
                        <span>
                            <?php esc_html_e('Access source:', 'anh-hub'); ?>
                            <?php echo esc_html($member['access_source_label'] ?? '—'); ?>
                        </span>
                        <span>
                            <?php esc_html_e('Granted by:', 'anh-hub'); ?>
                            <?php echo esc_html($member['access_granted_by'] ?: '—'); ?>
                        </span>
                        <span>
                            <?php esc_html_e('Granted:', 'anh-hub'); ?>
                            <?php echo $granted_val ? esc_html($granted_val) : esc_html__('—', 'anh-hub'); ?>
                        </span>
                        <span>
                            <?php esc_html_e('Expires:', 'anh-hub'); ?>
                            <?php echo $expires_val ? esc_html($expires_val) : esc_html__('No expiry', 'anh-hub'); ?>
                        </span>
                        <span>
                            <?php esc_html_e('Commission:', 'anh-hub'); ?>
                            <?php echo esc_html((string) ($member['commission_rate'] ?? 10)); ?>%
                        </span>
                        <span>
                            <?php esc_html_e('Tracking:', 'anh-hub'); ?>
                            <?php echo esc_html((string) ($member['tracking_days'] ?? 30)); ?>
                            <?php esc_html_e('days', 'anh-hub'); ?>
                        </span>
                    </div>

                    <details class="anh-member-details">
                        <summary><?php esc_html_e('Manage access & settings', 'anh-hub'); ?></summary>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="anh-member-form">
                            <?php wp_nonce_field('anh_update_referral_member'); ?>
                            <input type="hidden" name="action" value="anh_update_referral_member" />
                            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>" />
                            <p>
                                <label><?php esc_html_e('Access granted', 'anh-hub'); ?></label><br>
                                <input type="date" name="access_granted_at" value="<?php echo esc_attr($granted_val); ?>" />
                            </p>
                            <p>
                                <label><?php esc_html_e('Access expires', 'anh-hub'); ?></label><br>
                                <input type="date" name="access_expires_at" value="<?php echo esc_attr($expires_val); ?>" />
                            </p>
                            <p>
                                <label><?php esc_html_e('Extend access by (days)', 'anh-hub'); ?></label><br>
                                <input type="number" name="extend_days" min="0" max="365" value="0" class="small-text" />
                            </p>
                            <p>
                                <label><?php esc_html_e('Commission rate (%)', 'anh-hub'); ?></label><br>
                                <input type="number" name="commission_rate" min="0" max="100" step="0.1" value="<?php echo esc_attr((string) ($member['commission_rate'] ?? 10)); ?>" class="small-text" />
                            </p>
                            <p>
                                <label><?php esc_html_e('Link tracking duration (days)', 'anh-hub'); ?></label><br>
                                <input type="number" name="tracking_days" min="1" max="365" value="<?php echo esc_attr((string) ($member['tracking_days'] ?? 30)); ?>" class="small-text" />
                            </p>
                            <?php submit_button(__('Save settings', 'anh-hub'), 'secondary small', 'submit', false); ?>
                        </form>
                        <p class="anh-quick-actions">
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links&user_id=' . $user_id)); ?>">
                                <?php esc_html_e('Full edit', 'anh-hub'); ?>
                            </a>
                            <a class="button button-small" href="<?php echo esc_url($regen_url); ?>" onclick="return confirm('<?php echo esc_js(__('Generate a new referral code? Old links will stop working.', 'anh-hub')); ?>');">
                                <?php esc_html_e('New code', 'anh-hub'); ?>
                            </a>
                            <a class="button button-small button-link-delete" href="<?php echo esc_url($revoke_url); ?>" onclick="return confirm('<?php echo esc_js(__('Revoke all referral access for this user?', 'anh-hub')); ?>');">
                                <?php esc_html_e('Revoke access', 'anh-hub'); ?>
                            </a>
                        </p>
                    </details>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 style="margin-top:32px;"><?php esc_html_e('Recent signups via referral links', 'anh-hub'); ?></h2>
    <p class="description"><?php esc_html_e('People who registered after clicking an influencer\'s referral link.', 'anh-hub'); ?></p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Referrer', 'anh-hub'); ?></th>
                <th><?php esc_html_e('New user', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Code', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Status', 'anh-hub'); ?></th>
                <th><?php esc_html_e('Date', 'anh-hub'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($signups)) : ?>
            <tr><td colspan="5"><?php esc_html_e('No signups via referral links yet.', 'anh-hub'); ?></td></tr>
        <?php else : foreach ($signups as $item) :
            $referrer = get_userdata((int) $item['referrer_user_id']);
            $referred = get_userdata((int) $item['referred_user_id']);
            ?>
            <tr>
                <td><?php echo esc_html($referrer ? $referrer->display_name : '#' . $item['referrer_user_id']); ?></td>
                <td><?php echo esc_html($referred ? $referred->display_name : '#' . $item['referred_user_id']); ?></td>
                <td><code><?php echo esc_html($item['referral_code']); ?></code></td>
                <td><?php echo esc_html($item['status']); ?></td>
                <td><?php echo esc_html($item['created_at']); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
