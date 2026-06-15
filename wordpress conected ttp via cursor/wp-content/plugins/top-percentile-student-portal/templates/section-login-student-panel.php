<?php
/**
 * Logged-in student dashboard on the /login/ page.
 *
 * @var WP_User      $user
 * @var WC_Order[]   $orders
 * @var array        $profile
 * @var string       $billing_html
 * @var string       $edit_account_url
 * @var array<int, array{name: string, open_url: string}|string> $enrolled_courses product_id => row
 */

if (!defined('ABSPATH')) {
    exit;
}

$avatar_raw = !empty($profile['avatar']) ? wp_get_attachment_image_url((int) $profile['avatar'], 'thumbnail') : '';
$avatar_url = is_string($avatar_raw) && '' !== $avatar_raw ? $avatar_raw : get_avatar_url($user->ID);
$orders_count = is_array($orders) ? count($orders) : 0;
$enrolled_list = isset($enrolled_courses) && is_array($enrolled_courses) ? $enrolled_courses : [];
$billing_safe = isset($billing_html) && is_string($billing_html) ? $billing_html : '';
$tpsp_notifications = [];
$tpsp_unread_count  = 0;
if (class_exists('TTPN_Plugin')) {
    $tpsp_notifications = TTPN_Plugin::instance()->notifications()->get_for_user($user->ID, ['limit' => 15]);
    $tpsp_unread_count  = TTPN_Plugin::instance()->notifications()->count_unread($user->ID);
}
?>
<div class="tpsp-login-dashboard">
    <?php if (function_exists('wc_print_notices')) : ?>
        <div class="tpsp-login-dashboard__notices"><?php wc_print_notices(); ?></div>
    <?php endif; ?>
    <div class="tpsp-login-dashboard__hero">
        <div class="tpsp-login-dashboard__hero-text">
            <p class="tpsp-login-dashboard__eyebrow"><?php esc_html_e('Student account', 'tpsp'); ?></p>
            <?php
            $greeting_name = (string) $user->display_name;
            if (is_email($greeting_name) && function_exists('ttp_resolve_customer_full_name')) {
                $resolved_name = ttp_resolve_customer_full_name((int) $user->ID);
                if ($resolved_name !== '' && !is_email($resolved_name)) {
                    $greeting_name = $resolved_name;
                }
            }
            ?>
            <h1 class="tpsp-login-dashboard__title"><?php echo esc_html(sprintf(__('Hello, %s', 'tpsp'), $greeting_name)); ?></h1>
            <p class="tpsp-login-dashboard__lead"><?php esc_html_e('Manage your profile, billing, and course access from here.', 'tpsp'); ?></p>
        </div>
        <div class="tpsp-login-dashboard__hero-avatar">
            <form method="post" enctype="multipart/form-data" class="tpsp-login-dashboard__avatar-form">
                <?php wp_nonce_field('tpsp_profile_update', 'tpsp_profile_nonce'); ?>
                <input type="hidden" name="tpsp_from_login" value="1" />
                <label class="tpsp-login-dashboard__avatar-label" for="tpsp-login-avatar">
                    <span class="tpsp-login-dashboard__avatar-wrap">
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="tpsp-login-dashboard__avatar" width="96" height="96"/>
                        <span class="tpsp-login-dashboard__avatar-badge"><?php esc_html_e('Change photo', 'tpsp'); ?></span>
                    </span>
                </label>
                <input id="tpsp-login-avatar" type="file" name="avatar" accept="image/*" class="tpsp-login-dashboard__avatar-input" />
                <p class="tpsp-login-dashboard__avatar-help"><?php esc_html_e('Choose an image, then tap Upload photo.', 'tpsp'); ?></p>
                <button type="submit" name="tpsp_avatar_submit" class="tpsp-btn tpsp-btn--block tpsp-btn--small"><?php esc_html_e('Upload photo', 'tpsp'); ?></button>
            </form>
        </div>
    </div>

    <div class="tpsp-login-dashboard__grid">
        <div class="tpsp-login-dashboard__card">
            <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Your details', 'tpsp'); ?></h2>
            <dl class="tpsp-login-dashboard__dl">
                <div><dt><?php esc_html_e('Name', 'tpsp'); ?></dt><dd><?php echo esc_html($greeting_name); ?></dd></div>
                <div><dt><?php esc_html_e('Email', 'tpsp'); ?></dt><dd><?php echo esc_html($user->user_email); ?></dd></div>
                <div><dt><?php esc_html_e('Phone', 'tpsp'); ?></dt><dd><?php echo esc_html($profile['phone'] ? $profile['phone'] : '—'); ?></dd></div>
            </dl>
            <a class="tpsp-btn tpsp-btn--block" href="<?php echo esc_url(function_exists('tpsp_get_edit_profile_url') ? tpsp_get_edit_profile_url() : tpsp_get_account_endpoint_url('my-profile')); ?>"><?php esc_html_e('Edit profile', 'tpsp'); ?></a>
        </div>

        <div class="tpsp-login-dashboard__card">
            <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Billing address', 'tpsp'); ?></h2>
            <div class="tpsp-login-dashboard__billing">
                <?php if ($billing_safe !== '') : ?>
                    <div class="tpsp-login-dashboard__billing-html"><?php echo wp_kses_post($billing_safe); ?></div>
                <?php else : ?>
                    <p class="tpsp-login-dashboard__muted"><?php esc_html_e('No billing address saved yet. Add it at checkout or in your account.', 'tpsp'); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($edit_account_url)) : ?>
                <a class="tpsp-btn tpsp-btn--ghost tpsp-btn--block" href="<?php echo esc_url($edit_account_url); ?>"><?php esc_html_e('Account details', 'tpsp'); ?></a>
            <?php endif; ?>
        </div>

        <div class="tpsp-login-dashboard__card tpsp-login-dashboard__card--wide">
            <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Enrolled courses', 'tpsp'); ?></h2>
            <?php
            $ttp_open_err = isset($_GET['ttp_course_open']) ? sanitize_key(wp_unslash($_GET['ttp_course_open'])) : '';
            if ($ttp_open_err !== '') :
                if ('no_tcy' === $ttp_open_err) {
                    $ttp_open_msg = __('Your course is still being linked to the study portal. Please wait a few minutes and try again, or contact support with your order number.', 'tpsp');
                } elseif ('login_failed' === $ttp_open_err) {
                    $ttp_open_msg = __('Study portal sign-in failed. Use Open course next to your plan, or contact support with your email and order number.', 'tpsp');
                } elseif ('no_order' === $ttp_open_err) {
                    $ttp_open_msg = __('We could not find a paid order for that course on this account. Check you are logged in with the same email used at checkout.', 'tpsp');
                } else {
                    $ttp_open_msg = __('Could not open that course right now. Please try again in a minute or contact support with your order number.', 'tpsp');
                }
                ?>
                <p class="tpsp-login-dashboard__muted" role="alert"><?php echo esc_html($ttp_open_msg); ?></p>
            <?php endif; ?>
            <?php if (!empty($enrolled_list)) : ?>
                <ul class="tpsp-login-dashboard__courses">
                    <?php foreach ($enrolled_list as $course_row) :
                        $course_name = is_array($course_row) ? (string) ($course_row['name'] ?? '') : (string) $course_row;
                        $open_url    = is_array($course_row) ? (string) ($course_row['open_url'] ?? home_url('/login/')) : home_url('/login/');
                        if ($course_name === '') {
                            continue;
                        }
                        ?>
                        <li class="tpsp-login-dashboard__course-row">
                            <a class="tpsp-login-dashboard__course-open" href="<?php echo esc_url($open_url); ?>"><?php esc_html_e('Open course', 'tpsp'); ?></a>
                            <span class="tpsp-login-dashboard__course-name"><?php echo esc_html($course_name); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="tpsp-login-dashboard__muted"><?php esc_html_e('No enrollments found yet. Your purchased courses will appear here after payment.', 'tpsp'); ?></p>
            <?php endif; ?>
        </div>

        <div class="tpsp-login-dashboard__card">
            <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Orders', 'tpsp'); ?></h2>
            <p class="tpsp-login-dashboard__stat"><?php echo esc_html((string) $orders_count); ?> <span><?php esc_html_e('recent orders shown in account', 'tpsp'); ?></span></p>
            <a class="tpsp-btn tpsp-btn--ghost tpsp-btn--block" href="<?php echo esc_url(tpsp_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('View orders', 'tpsp'); ?></a>
        </div>

        <?php if (class_exists('TTPN_Plugin')) : ?>
        <div class="tpsp-login-dashboard__card tpsp-login-dashboard__card--wide tpsp-login-dashboard__notifications-card" id="tpsp-notifications">
            <div class="tpsp-login-dashboard__notifications-head">
                <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Notifications', 'tpsp'); ?></h2>
                <?php if ($tpsp_unread_count > 0) : ?>
                    <span class="tpsp-login-dashboard__notif-badge"><?php echo esc_html((string) $tpsp_unread_count); ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($tpsp_notifications)) : ?>
                <ul class="tpsp-login-dashboard__notifications">
                    <?php foreach ($tpsp_notifications as $notice) : ?>
                        <li class="tpsp-login-dashboard__notification<?php echo empty($notice['is_read']) ? ' is-unread' : ''; ?>">
                            <strong><?php echo esc_html($notice['title']); ?></strong>
                            <p><?php echo esc_html(wp_strip_all_tags($notice['message'])); ?></p>
                            <small><?php echo esc_html($notice['created_at']); ?></small>
                            <?php if (!empty($notice['link'])) : ?>
                                <a href="<?php echo esc_url($notice['link']); ?>"><?php esc_html_e('Open', 'tpsp'); ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="tpsp-login-dashboard__muted"><?php esc_html_e('No notifications yet. New alerts will pop up at the top of the site when you are logged in.', 'tpsp'); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (shortcode_exists('affiliate_referral_link') && function_exists('ttpa_user_can_refer') && ttpa_user_can_refer($user->ID)) : ?>
        <div class="tpsp-login-dashboard__card tpsp-login-dashboard__card--wide tpsp-login-dashboard__referral-card">
            <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Your referral program', 'tpsp'); ?></h2>
            <p class="tpsp-login-dashboard__muted"><?php esc_html_e('Share your unique link and earn commission when friends enroll.', 'tpsp'); ?></p>
            <div class="tpsp-login-dashboard__referral-box">
                <?php echo do_shortcode('[affiliate_referral_link show_stats="yes"]'); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="tpsp-login-dashboard__actions">
        <a class="tpsp-btn tpsp-btn--danger" href="<?php echo esc_url(function_exists('wc_logout_url') ? wc_logout_url(home_url('/login/')) : wp_logout_url(home_url('/login/'))); ?>"><?php esc_html_e('Log out', 'tpsp'); ?></a>
    </div>
</div>
