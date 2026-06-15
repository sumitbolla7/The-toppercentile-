<?php
/**
 * User Registration edit profile on /login/ with TPSP fallback form.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('tpsp_ensure_user_registration_form_id')) {
    tpsp_ensure_user_registration_form_id();
}

$ur_form_html = '';
if (shortcode_exists('user_registration_edit_profile')) {
    $ur_form_html = (string) do_shortcode('[user_registration_edit_profile]');
}
$ur_has_form = function_exists('tpsp_ur_edit_profile_html_is_valid')
    ? tpsp_ur_edit_profile_html_is_valid($ur_form_html)
    : ($ur_form_html !== '' && (false !== strpos($ur_form_html, 'ur-frontend-form') || false !== strpos($ur_form_html, 'user-registration-EditProfileForm')));
?>
<div class="tpsp-login-dashboard tpsp-login-dashboard--edit-profile">
    <?php if (function_exists('wc_print_notices')) : ?>
        <div class="tpsp-login-dashboard__notices"><?php wc_print_notices(); ?></div>
    <?php endif; ?>
    <p class="tpsp-login-dashboard__back">
        <a class="tpsp-btn tpsp-btn--ghost tpsp-btn--small" href="<?php echo esc_url(function_exists('tpsp_get_login_page_url') ? tpsp_get_login_page_url() : home_url('/login/')); ?>">
            <?php esc_html_e('← Back to account', 'tpsp'); ?>
        </a>
    </p>
    <h1 class="tpsp-login-dashboard__title"><?php esc_html_e('Edit profile', 'tpsp'); ?></h1>
    <p class="tpsp-login-dashboard__lead"><?php esc_html_e('Update your registration details below. Changes are saved to your student account.', 'tpsp'); ?></p>
    <div class="tpsp-login-dashboard__ur-form">
        <?php if ($ur_has_form) : ?>
            <div class="tpsp-ur-edit-profile-wrap">
                <?php echo $ur_form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php else : ?>
            <?php
            $user    = wp_get_current_user();
            $profile = isset($profile) && is_array($profile) ? $profile : [];
            $fallback = TPSP_PLUGIN_DIR . 'templates/partial-edit-profile-fallback-form.php';
            if (is_readable($fallback)) {
                include $fallback;
            }
            ?>
        <?php endif; ?>
    </div>
</div>
