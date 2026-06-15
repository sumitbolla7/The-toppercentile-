<?php
/**
 * Email verification flow.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_Email_Verification {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        add_action('user_register', [$this, 'create_verification_token'], 20);
        add_action('woocommerce_created_customer', [$this, 'create_verification_token'], 20);
        add_filter('authenticate', [$this, 'block_unverified_login'], 99, 3);
        add_action('init', [$this, 'maybe_handle_verification_link']);
        add_action('wp', [$this, 'handle_resend_request']);
        add_shortcode('tpsp_verification_status', [$this, 'render_verification_status_shortcode']);
        add_action('woocommerce_before_customer_login_form', [$this, 'render_verification_notice_on_my_account']);
    }

    /**
     * Check plugin setting.
     *
     * @return bool
     */
    private function is_required() {
        if (function_exists('tpsp_membership_plugin_handles_email_verification') && tpsp_membership_plugin_handles_email_verification()) {
            return false;
        }

        $settings = get_option('tpsp_settings', []);
        return isset($settings['require_email_verification']) ? 'yes' === $settings['require_email_verification'] : true;
    }

    /**
     * Generate token and send email.
     *
     * @param int $user_id User ID.
     * @return void
     */
    public function create_verification_token($user_id, ...$extra_args) {
        if (!$this->is_required()) {
            update_user_meta($user_id, 'tpsp_email_verified', 1);
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $token_plain = wp_generate_password(48, false, false);
        $token_hash  = wp_hash_password($token_plain);
        $expiry      = time() + DAY_IN_SECONDS;

        update_user_meta($user_id, 'tpsp_email_verified', 0);
        update_user_meta($user_id, 'tpsp_email_verification_token', $token_hash);
        update_user_meta($user_id, 'tpsp_email_verification_expiry', $expiry);

        $this->send_verification_email($user_id, $token_plain);
    }

    /**
     * Send verification email.
     *
     * @param int    $user_id     User ID.
     * @param string $token_plain Plain token.
     * @return bool
     */
    public function send_verification_email($user_id, $token_plain) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $verification_url = add_query_arg(
            [
                'tpsp_verify' => '1',
                'uid'         => $user_id,
                'token'       => rawurlencode($token_plain),
            ],
            home_url('/')
        );

        $subject = sprintf(__('Verify your email for %s', 'tpsp'), get_bloginfo('name'));
        $message = sprintf(
            __("Hi %s,\n\nPlease verify your email by clicking the link below:\n%s\n\nThis link expires in 24 hours.", 'tpsp'),
            $user->display_name ? $user->display_name : $user->user_login,
            esc_url_raw($verification_url)
        );

        $sent = wp_mail($user->user_email, $subject, $message);
        TPSP_Logger::log('Verification email dispatched', 'info', ['user_id' => $user_id, 'sent' => $sent]);

        return $sent;
    }

    /**
     * Block login for unverified users.
     *
     * @param WP_User|WP_Error|null $user     Authenticated user object.
     * @param string                $username Username.
     * @param string                $password Password.
     * @return WP_User|WP_Error|null
     */
    public function block_unverified_login($user, $username, $password) {
        if (!$this->is_required()) {
            return $user;
        }

        if ($user instanceof WP_Error || !$user instanceof WP_User) {
            return $user;
        }

        if (user_can($user, 'manage_options')) {
            return $user;
        }

        $verified = (int) get_user_meta($user->ID, 'tpsp_email_verified', true);
        if (1 === $verified) {
            return $user;
        }

        return new WP_Error(
            'tpsp_email_unverified',
            __('Please verify your email first. Check your inbox, or request a new email below.', 'tpsp')
        );
    }

    /**
     * Validate incoming verification link.
     *
     * @return void
     */
    public function maybe_handle_verification_link() {
        if (!isset($_GET['tpsp_verify'], $_GET['uid'], $_GET['token'])) {
            return;
        }

        $user_id = absint(wp_unslash($_GET['uid']));
        $token   = sanitize_text_field(wp_unslash($_GET['token']));

        $stored_hash = get_user_meta($user_id, 'tpsp_email_verification_token', true);
        $expiry      = (int) get_user_meta($user_id, 'tpsp_email_verification_expiry', true);

        if (!$stored_hash || !$expiry || time() > $expiry) {
            wp_safe_redirect(add_query_arg('tpsp_verification', 'expired', wc_get_page_permalink('myaccount')));
            exit;
        }

        if (!wp_check_password($token, $stored_hash)) {
            wp_safe_redirect(add_query_arg('tpsp_verification', 'invalid', wc_get_page_permalink('myaccount')));
            exit;
        }

        update_user_meta($user_id, 'tpsp_email_verified', 1);
        delete_user_meta($user_id, 'tpsp_email_verification_token');
        delete_user_meta($user_id, 'tpsp_email_verification_expiry');

        wp_safe_redirect(add_query_arg('tpsp_verification', 'success', wc_get_page_permalink('myaccount')));
        exit;
    }

    /**
     * Resend verification from normal request.
     *
     * @return void
     */
    public function handle_resend_request() {
        if (!isset($_POST['tpsp_resend_verification'])) {
            return;
        }

        if (!isset($_POST['tpsp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tpsp_nonce'])), 'tpsp_resend_verification')) {
            return;
        }

        $email = isset($_POST['tpsp_email']) ? sanitize_email(wp_unslash($_POST['tpsp_email'])) : '';
        if (!is_email($email)) {
            wc_add_notice(__('Please enter a valid email.', 'tpsp'), 'error');
            return;
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wc_add_notice(__('No user found with this email.', 'tpsp'), 'error');
            return;
        }

        $token_plain = wp_generate_password(48, false, false);
        update_user_meta($user->ID, 'tpsp_email_verification_token', wp_hash_password($token_plain));
        update_user_meta($user->ID, 'tpsp_email_verification_expiry', time() + DAY_IN_SECONDS);
        update_user_meta($user->ID, 'tpsp_email_verified', 0);

        $this->send_verification_email($user->ID, $token_plain);
            wc_add_notice(__('Done. We have sent a new verification email.', 'tpsp'), 'success');
    }

    /**
     * Render verification status shortcode.
     *
     * @return string
     */
    public function render_verification_status_shortcode() {
        if (!is_user_logged_in()) {
            return '';
        }

        $verified = (int) get_user_meta(get_current_user_id(), 'tpsp_email_verified', true);
        if (1 === $verified) {
            return '<div class="tpsp-notice success"><i class="fa-solid fa-circle-check"></i> ' . esc_html__('Your email is verified.', 'tpsp') . '</div>';
        }

        ob_start();
        ?>
        <div class="tpsp-notice warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?php esc_html_e('Your email is not verified yet. Verify it to open your dashboard and courses.', 'tpsp'); ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Render pending/failed/success notices.
     *
     * @return void
     */
    public function render_verification_notice_on_my_account() {
        if (isset($_GET['tpsp_verification'])) {
            $status = sanitize_text_field(wp_unslash($_GET['tpsp_verification']));
            if ('success' === $status) {
                wc_print_notice(__('Email verified successfully. You can now access your dashboard and courses.', 'tpsp'), 'success');
            } elseif ('expired' === $status) {
                wc_print_notice(__('This verification link has expired. Please request a new one.', 'tpsp'), 'error');
            } elseif ('invalid' === $status) {
                wc_print_notice(__('This verification link is invalid. Please request a new one.', 'tpsp'), 'error');
            }
        }

        if (is_user_logged_in()) {
            $verified = (int) get_user_meta(get_current_user_id(), 'tpsp_email_verified', true);
            if (1 !== $verified) {
                wc_print_notice(__('Your email is not verified yet.', 'tpsp'), 'notice');
            }
            return;
        }

        ?>
        <div class="tpsp-card tpsp-verification-block">
            <h4><?php esc_html_e('Verify Your Email', 'tpsp'); ?></h4>
            <p><?php esc_html_e('Did not get the email? Enter your registered email and click below.', 'tpsp'); ?></p>
            <form method="post" class="tpsp-form">
                <?php wp_nonce_field('tpsp_resend_verification', 'tpsp_nonce'); ?>
                <input type="email" name="tpsp_email" placeholder="<?php esc_attr_e('Enter your registered email', 'tpsp'); ?>" required />
                <button type="submit" name="tpsp_resend_verification" class="tpsp-btn"><?php esc_html_e('Send Verification Email Again', 'tpsp'); ?></button>
            </form>
        </div>
        <?php
    }
}
