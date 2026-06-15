<?php
/**
 * Logged-in student dashboard on the /login/ page.
 *
 * @var WP_User      $user
 * @var WC_Order[]   $orders
 * @var array        $profile
 * @var string       $billing_html
 * @var string       $edit_account_url
 * @var array<int,string> $enrolled_courses product_id => name
 */

if (!defined('ABSPATH')) {
    exit;
}

$avatar_url = !empty($profile['avatar']) ? wp_get_attachment_image_url((int) $profile['avatar'], 'thumbnail') : get_avatar_url($user->ID);
$orders_count = is_array($orders) ? count($orders) : 0;
$enrolled_list = isset($enrolled_courses) && is_array($enrolled_courses) ? $enrolled_courses : [];
$billing_safe = isset($billing_html) && is_string($billing_html) ? $billing_html : '';
?>
<div class="tpsp-login-dashboard">
    <div class="tpsp-login-dashboard__hero">
        <div class="tpsp-login-dashboard__hero-text">
            <p class="tpsp-login-dashboard__eyebrow"><?php esc_html_e('Student account', 'tpsp'); ?></p>
            <h1 class="tpsp-login-dashboard__title"><?php echo esc_html(sprintf(__('Hello, %s', 'tpsp'), $user->display_name)); ?></h1>
            <p class="tpsp-login-dashboard__lead"><?php esc_html_e('Manage your profile, billing, and course access from here.', 'tpsp'); ?></p>
        </div>
        <div class="tpsp-login-dashboard__hero-avatar">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="" class="tpsp-login-dashboard__avatar" width="88" height="88"/>
        </div>
    </div>

    <div class="tpsp-login-dashboard__grid">
        <div class="tpsp-login-dashboard__card">
            <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Your details', 'tpsp'); ?></h2>
            <dl class="tpsp-login-dashboard__dl">
                <div><dt><?php esc_html_e('Name', 'tpsp'); ?></dt><dd><?php echo esc_html($user->display_name); ?></dd></div>
                <div><dt><?php esc_html_e('Email', 'tpsp'); ?></dt><dd><?php echo esc_html($user->user_email); ?></dd></div>
                <div><dt><?php esc_html_e('Phone', 'tpsp'); ?></dt><dd><?php echo esc_html($profile['phone'] ? $profile['phone'] : '—'); ?></dd></div>
            </dl>
            <a class="tpsp-btn tpsp-btn--block" href="<?php echo esc_url(tpsp_get_account_endpoint_url('my-profile')); ?>"><?php esc_html_e('Edit profile', 'tpsp'); ?></a>
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
            <?php if (!empty($enrolled_list)) : ?>
                <ul class="tpsp-login-dashboard__courses">
                    <?php foreach ($enrolled_list as $course_name) : ?>
                        <li><?php echo esc_html($course_name); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="tpsp-login-dashboard__muted"><?php esc_html_e('No enrollments found yet. Your purchased courses will appear here after payment.', 'tpsp'); ?></p>
            <?php endif; ?>
            <a class="tpsp-btn tpsp-btn--secondary tpsp-btn--block" href="<?php echo esc_url(tpsp_get_account_endpoint_url('my-courses')); ?>"><?php esc_html_e('Open my courses', 'tpsp'); ?></a>
        </div>

        <div class="tpsp-login-dashboard__card">
            <h2 class="tpsp-login-dashboard__card-title"><?php esc_html_e('Orders', 'tpsp'); ?></h2>
            <p class="tpsp-login-dashboard__stat"><?php echo esc_html((string) $orders_count); ?> <span><?php esc_html_e('recent orders shown in account', 'tpsp'); ?></span></p>
            <a class="tpsp-btn tpsp-btn--ghost tpsp-btn--block" href="<?php echo esc_url(tpsp_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('View orders', 'tpsp'); ?></a>
        </div>
    </div>

    <div class="tpsp-login-dashboard__actions">
        <a class="tpsp-btn tpsp-btn--danger" href="<?php echo esc_url(wc_logout_url(home_url('/login/'))); ?>"><?php esc_html_e('Log out', 'tpsp'); ?></a>
    </div>
</div>
