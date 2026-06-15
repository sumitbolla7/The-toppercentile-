<?php
/**
 * Fallback profile editor when User Registration shortcode is empty.
 *
 * @var WP_User $user
 * @var array   $profile
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = isset($user) && $user instanceof WP_User ? $user : wp_get_current_user();
$phone = '';
foreach (['billing_phone', 'user_registration_phone_number', 'ttp_mobile'] as $phone_key) {
    $val = trim((string) get_user_meta($user->ID, $phone_key, true));
    if ($val !== '') {
        $phone = $val;
        break;
    }
}
if ($phone === '' && !empty($profile['phone'])) {
    $phone = (string) $profile['phone'];
}

$display_name = (string) $user->display_name;
if (function_exists('ttp_sanitize_person_name_value')) {
    $display_name = ttp_sanitize_person_name_value($display_name);
}
if ($display_name === '' || is_email($display_name)) {
    if (function_exists('ttp_user_get_name_parts')) {
        $parts = ttp_user_get_name_parts($user->ID);
        $display_name = trim($parts['first_name'] . ' ' . $parts['last_name']);
    }
}
if ($display_name === '' || is_email($display_name)) {
    $display_name = '';
}

$avatar_url = !empty($profile['avatar']) ? wp_get_attachment_image_url((int) $profile['avatar'], 'thumbnail') : '';
$from_edit_profile = !empty($_GET['tpsp_edit_profile']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="tpsp-card tpsp-card--ur-profile">
    <form method="post" enctype="multipart/form-data" class="tpsp-form">
        <?php wp_nonce_field('tpsp_profile_update', 'tpsp_profile_nonce'); ?>
        <?php if ($from_edit_profile) : ?>
            <input type="hidden" name="tpsp_from_edit_profile" value="1" />
        <?php endif; ?>
        <p class="tpsp-login-dashboard__muted"><?php esc_html_e('Enter your full name (not your email). This is used for course access on the study portal.', 'tpsp'); ?></p>
        <?php if ($avatar_url || true) : ?>
            <div class="tpsp-avatar-row">
                <img src="<?php echo esc_url($avatar_url ? $avatar_url : get_avatar_url($user->ID)); ?>" alt="<?php esc_attr_e('Avatar', 'tpsp'); ?>" class="tpsp-avatar" />
                <input type="file" name="avatar" accept="image/*" />
            </div>
        <?php endif; ?>
        <div class="tpsp-form-grid">
            <input type="text" name="display_name" value="<?php echo esc_attr($display_name); ?>" placeholder="<?php esc_attr_e('Full name', 'tpsp'); ?>" required />
            <input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" placeholder="<?php esc_attr_e('Email', 'tpsp'); ?>" required />
            <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" placeholder="<?php esc_attr_e('Phone number', 'tpsp'); ?>" />
        </div>
        <button type="submit" name="tpsp_profile_submit" class="tpsp-btn"><?php esc_html_e('Update Profile', 'tpsp'); ?></button>
    </form>
</div>
