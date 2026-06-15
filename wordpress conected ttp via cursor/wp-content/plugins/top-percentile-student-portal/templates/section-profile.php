<?php
/**
 * Profile section — User Registration form when available.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('tpsp_ensure_user_registration_form_id')) {
    tpsp_ensure_user_registration_form_id();
}

$back_url = function_exists('tpsp_get_login_page_url') ? tpsp_get_login_page_url() : home_url('/login/');

$ur_form_html = '';
if (shortcode_exists('user_registration_edit_profile')) {
    $ur_form_html = (string) do_shortcode('[user_registration_edit_profile]');
}
$ur_has_form = function_exists('tpsp_ur_edit_profile_html_is_valid')
    ? tpsp_ur_edit_profile_html_is_valid($ur_form_html)
    : ($ur_form_html !== '' && (false !== strpos($ur_form_html, 'ur-frontend-form') || false !== strpos($ur_form_html, 'user-registration-EditProfileForm')));
?>
<div class="tpsp-card tpsp-card--ur-profile">
    <h3><i class="fa-solid fa-id-card"></i> <?php esc_html_e('Student Profile', 'tpsp'); ?></h3>
    <p><a class="tpsp-btn tpsp-btn--ghost tpsp-btn--small" href="<?php echo esc_url($back_url); ?>"><?php esc_html_e('← Back to account', 'tpsp'); ?></a></p>
    <?php if ($ur_has_form) : ?>
        <div class="tpsp-ur-edit-profile-wrap">
            <?php echo $ur_form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    <?php else : ?>
        <?php
        $fallback = TPSP_PLUGIN_DIR . 'templates/partial-edit-profile-fallback-form.php';
        if (is_readable($fallback)) {
            include $fallback;
        }
        ?>
    <?php endif; ?>
</div>
