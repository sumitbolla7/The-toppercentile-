<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap anh-wrap">
    <h1><?php esc_html_e('Affiliate Links & Access', 'anh-hub'); ?></h1>

    <?php if (!empty($_GET['generated'])) : ?>
        <div class="notice notice-success"><p><?php esc_html_e('Referral link generated successfully.', 'anh-hub'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['access_updated'])) : ?>
        <div class="notice notice-success"><p><?php esc_html_e('Referral program access updated.', 'anh-hub'); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_GET['revoked'])) : ?>
        <div class="notice notice-success"><p><?php esc_html_e('Referral access revoked for this user.', 'anh-hub'); ?></p></div>
    <?php endif; ?>

    <p><?php esc_html_e('Referral access is off by default for new users. Only influencers or users you manually enable below can get a referral link.', 'anh-hub'); ?></p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="anh-form-inline anh-access-form" id="anh-affiliate-access-form">
        <?php wp_nonce_field('anh_save_affiliate_access'); ?>
        <input type="hidden" name="action" value="anh_save_affiliate_access" />
        <div class="anh-user-select-field">
            <label for="anh-affiliate-user" class="screen-reader-text"><?php esc_html_e('Select user', 'anh-hub'); ?></label>
            <input type="search" class="anh-user-filter-input anh-user-search-input" placeholder="<?php esc_attr_e('Type name or email to filter…', 'anh-hub'); ?>" autocomplete="off" />
            <select id="anh-affiliate-user" name="user_id" class="anh-user-search" data-search-placeholder="<?php esc_attr_e('Search by name or email…', 'anh-hub'); ?>" data-auto-load="1">
                <option value=""><?php esc_html_e('Select user…', 'anh-hub'); ?></option>
                <?php if ($selected_user instanceof WP_User) : ?>
                    <option value="<?php echo esc_attr($selected_user->ID); ?>" selected>
                        <?php echo esc_html($selected_user->display_name . ' (' . $selected_user->user_email . ')'); ?>
                    </option>
                <?php endif; ?>
            </select>
            <p class="description"><?php esc_html_e('Type at least 2 characters (name or email) and pick a user from the list.', 'anh-hub'); ?></p>
        </div>
        <label class="anh-inline-check">
            <input type="checkbox" name="grant_influencer" value="1" <?php checked($is_influencer); ?> />
            <?php esc_html_e('Mark as influencer (always has referral access)', 'anh-hub'); ?>
        </label>
        <label class="anh-inline-check">
            <input type="checkbox" name="affiliate_enabled" value="1" <?php checked($affiliate_enabled); ?> <?php disabled($is_influencer); ?> />
            <?php esc_html_e('Enable referral program for this user', 'anh-hub'); ?>
        </label>
        <label class="anh-inline-check anh-commission-field">
            <?php esc_html_e('Commission %', 'anh-hub'); ?>
            <input type="number" name="commission_rate" min="0" max="100" step="0.1" value="<?php echo esc_attr($commission_rate); ?>" class="small-text" />
            <span class="description"><?php printf(esc_html__('Default site rate: %s%%', 'anh-hub'), esc_html($default_commission_rate)); ?></span>
        </label>
        <?php submit_button(__('Save access', 'anh-hub'), 'secondary', 'submit', false); ?>
        <?php submit_button(__('Generate link', 'anh-hub'), 'primary', 'generate_link', false); ?>
    </form>

    <?php if ($selected_user) :
        $selected_access_source = '';
        if ($referrals) {
            $selected_access_source = $referrals->get_access_source_label($selected_user->ID);
        }
        $revoke_selected_url = wp_nonce_url(
            add_query_arg(
                [
                    'action'   => 'anh_revoke_referral_access',
                    'user_id'  => (int) $selected_user->ID,
                    'redirect' => 'links',
                ],
                admin_url('admin-post.php')
            ),
            'anh_revoke_referral_access_' . (int) $selected_user->ID
        );
        ?>
        <div class="anh-result-box anh-user-status">
            <p>
                <strong><?php echo esc_html($selected_user->display_name ?: $selected_user->user_email); ?></strong>
                <?php if ($is_influencer) : ?>
                    <span class="anh-badge anh-badge-influencer"><?php esc_html_e('Influencer — always enabled', 'anh-hub'); ?></span>
                <?php elseif ($affiliate_enabled) : ?>
                    <span class="anh-badge anh-badge-on"><?php esc_html_e('Referral program enabled', 'anh-hub'); ?></span>
                <?php else : ?>
                    <span class="anh-badge anh-badge-off"><?php esc_html_e('Referral program disabled', 'anh-hub'); ?></span>
                <?php endif; ?>
                <?php if ($affiliate_enabled || $is_influencer) : ?>
                    <br><span class="description"><?php printf(esc_html__('Commission: %s%%', 'anh-hub'), esc_html($commission_rate)); ?></span>
                <?php endif; ?>
                <?php if ($selected_access_source !== '') : ?>
                    <br><span class="description"><?php printf(esc_html__('Access source: %s', 'anh-hub'), esc_html($selected_access_source)); ?></span>
                <?php endif; ?>
            </p>
            <?php if ($affiliate_enabled || $is_influencer) : ?>
                <p class="anh-quick-actions">
                    <a class="button button-link-delete" href="<?php echo esc_url($revoke_selected_url); ?>" onclick="return confirm('<?php echo esc_js(__('Revoke all referral access for this user? Their referral link will stop working.', 'anh-hub')); ?>');">
                        <?php esc_html_e('Revoke referral access', 'anh-hub'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($link) : ?>
        <div class="anh-result-box">
            <h2><?php esc_html_e('Referral link', 'anh-hub'); ?></h2>
            <p><strong><?php esc_html_e('Code:', 'anh-hub'); ?></strong> <code><?php echo esc_html($code); ?></code></p>
            <input type="text" readonly class="large-text" value="<?php echo esc_attr($link); ?>" onclick="this.select();" />
            <p class="description">
                <?php
                printf(
                    esc_html__('Visitors who use this link are tracked for 30 days. Format: %s', 'anh-hub'),
                    esc_html(home_url('/?ref=' . $code))
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (!empty($enabled_members)) : ?>
        <div class="anh-result-box">
            <h2><?php esc_html_e('Users with referral access', 'anh-hub'); ?></h2>
            <p class="description"><?php esc_html_e('Click a name to manage their referral settings.', 'anh-hub'); ?></p>
            <table class="widefat striped anh-members-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User', 'anh-hub'); ?></th>
                        <th><?php esc_html_e('Email', 'anh-hub'); ?></th>
                        <th><?php esc_html_e('Access source', 'anh-hub'); ?></th>
                        <th><?php esc_html_e('Granted by', 'anh-hub'); ?></th>
                        <th><?php esc_html_e('Code', 'anh-hub'); ?></th>
                        <th><?php esc_html_e('Actions', 'anh-hub'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enabled_members as $member) :
                        $member_revoke_url = wp_nonce_url(
                            add_query_arg(
                                [
                                    'action'   => 'anh_revoke_referral_access',
                                    'user_id'  => (int) ($member['user_id'] ?? 0),
                                    'redirect' => 'links',
                                ],
                                admin_url('admin-post.php')
                            ),
                            'anh_revoke_referral_access_' . (int) ($member['user_id'] ?? 0)
                        );
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=anh-affiliate-links&user_id=' . (int) ($member['user_id'] ?? 0))); ?>">
                                    <?php echo esc_html($member['display_name'] ?: $member['email']); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($member['email']); ?></td>
                            <td><?php echo esc_html($member['access_source_label'] ?? '—'); ?></td>
                            <td><?php echo esc_html($member['access_granted_by'] ?: '—'); ?></td>
                            <td>
                                <?php if (!empty($member['referral_code'])) : ?>
                                    <code><?php echo esc_html($member['referral_code']); ?></code>
                                <?php else : ?>
                                    <span class="anh-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($member_revoke_url); ?>" class="button-link-delete" onclick="return confirm('<?php echo esc_js(__('Revoke all referral access for this user?', 'anh-hub')); ?>');">
                                    <?php esc_html_e('Revoke', 'anh-hub'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
