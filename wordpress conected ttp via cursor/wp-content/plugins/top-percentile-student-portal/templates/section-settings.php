<?php
/**
 * Settings section.
 */
?>
<div class="tpsp-card">
    <h3><i class="fa-solid fa-gear"></i> <?php esc_html_e('Account Settings', 'tpsp'); ?></h3>
    <div class="tpsp-list">
        <div class="tpsp-list-item">
            <div>
                <strong><?php esc_html_e('Change Password', 'tpsp'); ?></strong>
                <small><?php esc_html_e('Use WooCommerce account password form for secure password updates.', 'tpsp'); ?></small>
            </div>
            <a class="tpsp-btn" href="<?php echo esc_url(tpsp_get_account_endpoint_url('edit-account')); ?>"><?php esc_html_e('Change Password', 'tpsp'); ?></a>
        </div>
        <div class="tpsp-list-item">
            <div>
                <strong><?php esc_html_e('Change Email', 'tpsp'); ?></strong>
                <small><?php esc_html_e('Update account email and personal details from account editor.', 'tpsp'); ?></small>
            </div>
            <a class="tpsp-btn tpsp-btn-secondary" href="<?php echo esc_url(tpsp_get_account_endpoint_url('edit-account')); ?>"><?php esc_html_e('Change Email', 'tpsp'); ?></a>
        </div>
        <div class="tpsp-list-item">
            <div>
                <strong><?php esc_html_e('Profile, Address & Phone', 'tpsp'); ?></strong>
                <small><?php esc_html_e('Upload profile image and edit address/phone from My Profile.', 'tpsp'); ?></small>
            </div>
            <a class="tpsp-btn" href="<?php echo esc_url(function_exists('tpsp_get_edit_profile_url') ? tpsp_get_edit_profile_url() : tpsp_get_account_endpoint_url('my-profile')); ?>"><?php esc_html_e('Edit Profile Details', 'tpsp'); ?></a>
        </div>
    </div>

    <h4><?php esc_html_e('Resend Verification Email', 'tpsp'); ?></h4>
    <div class="tpsp-resend-wrap">
        <input id="tpsp-resend-email" type="email" value="<?php echo esc_attr($user->user_email); ?>" />
        <button type="button" class="tpsp-btn" id="tpsp-resend-verification"><?php esc_html_e('Resend Verification', 'tpsp'); ?></button>
    </div>
    <div id="tpsp-resend-message" aria-live="polite"></div>

    <a class="tpsp-btn tpsp-btn-danger" href="<?php echo esc_url(wc_logout_url()); ?>"><?php esc_html_e('Logout', 'tpsp'); ?></a>
</div>
