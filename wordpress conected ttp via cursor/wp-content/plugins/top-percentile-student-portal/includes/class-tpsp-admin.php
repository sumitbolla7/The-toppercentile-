<?php
/**
 * Admin settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_Admin {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add settings page.
     *
     * @return void
     */
    public function register_settings_page() {
        add_menu_page(
            __('Top Percentile Portal', 'tpsp'),
            __('Top Percentile Portal', 'tpsp'),
            'manage_options',
            'tpsp-settings',
            [$this, 'render_settings_page'],
            'dashicons-welcome-learn-more',
            56
        );
    }

    /**
     * Register settings fields.
     *
     * @return void
     */
    public function register_settings() {
        register_setting('tpsp_settings_group', 'tpsp_settings', [$this, 'sanitize_settings']);

        add_settings_section('tpsp_general', __('General Settings', 'tpsp'), '__return_false', 'tpsp-settings');

        $fields = [
            'require_email_verification' => __('Require email verification before login', 'tpsp'),
            'cart_force_ajax'            => __('Force AJAX add to cart', 'tpsp'),
            'restrict_course_access'     => __('Restrict course products to purchased users', 'tpsp'),
            'enable_debug_logging'       => __('Enable debug logging', 'tpsp'),
            'ur_auto_approve'            => __('User Registration: auto-approve “admin approval” members (login + new signups)', 'tpsp'),
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                $key,
                $label,
                [$this, 'render_checkbox_field'],
                'tpsp-settings',
                'tpsp_general',
                ['key' => $key]
            );
        }
    }

    /**
     * Sanitize settings values.
     *
     * @param array $input Raw input.
     * @return array
     */
    public function sanitize_settings($input) {
        $existing = get_option('tpsp_settings', []);
        if (!is_array($existing)) {
            $existing = [];
        }
        $clean = $existing;
        $keys  = ['require_email_verification', 'cart_force_ajax', 'restrict_course_access', 'enable_debug_logging', 'ur_auto_approve'];

        foreach ($keys as $key) {
            $clean[$key] = isset($input[$key]) && 'yes' === $input[$key] ? 'yes' : 'no';
        }

        return $clean;
    }

    /**
     * Render a checkbox field.
     *
     * @param array $args Field args.
     * @return void
     */
    public function render_checkbox_field($args) {
        $options = get_option('tpsp_settings', []);
        $key     = $args['key'];
        if ('ur_auto_approve' === $key && !array_key_exists('ur_auto_approve', $options)) {
            $value = 'yes';
        } else {
            $value = isset($options[$key]) ? $options[$key] : 'no';
        }
        ?>
        <label>
            <input type="checkbox" name="tpsp_settings[<?php echo esc_attr($key); ?>]" value="yes" <?php checked($value, 'yes'); ?> />
        </label>
        <?php
    }

    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings_page() {
        if (isset($_GET['tpsp_ur_bulk_error']) && current_user_can('manage_options')) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            esc_html_e('Bulk approve could not run: User Registration functions were not loaded. Try again from this screen, or check wp-content/debug.log for a PHP error.', 'tpsp');
            echo '</p></div>';
        }
        if (isset($_GET['tpsp_ur_bulk_done']) && isset($_GET['tpsp_ur_bulk_count']) && current_user_can('manage_options')) {
            $n = absint(wp_unslash($_GET['tpsp_ur_bulk_count']));
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo esc_html(sprintf(
                /* translators: %d: number of users approved */
                __('User Registration: approved %d pending member(s) (admin approval only).', 'tpsp'),
                $n
            ));
            echo '</p></div>';
        }

        $ur_active = defined('UR_VERSION') || class_exists('UserRegistration', false);
        $ttp_standalone = function_exists('ttp_ur_max_unlock_ur_user_for_login');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Top Percentile Student Portal', 'tpsp'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('tpsp_settings_group');
                do_settings_sections('tpsp-settings');
                submit_button(__('Save Settings', 'tpsp'));
                ?>
            </form>

            <?php if ($ur_active) : ?>
                <hr />
                <h2><?php esc_html_e('User Registration — quick actions', 'tpsp'); ?></h2>
                <?php if ($ttp_standalone) : ?>
                    <p class="description"><?php esc_html_e('The separate plugin “TTP UR Auto Approve” is active; it already handles auto-approval. You can leave the checkbox above on or off — the standalone plugin takes precedence.', 'tpsp'); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e('Approximate Members still pending (UR form + status 0):', 'tpsp'); ?>
                        <strong><?php echo esc_html((string) TPSP_User_Registration_Auto_Approve::count_pending_ur_users()); ?></strong>
                    </p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('tpsp_ur_bulk_approve', 'tpsp_ur_bulk_nonce'); ?>
                        <input type="hidden" name="action" value="tpsp_ur_bulk_approve" />
                        <?php
                        submit_button(
                            __('Approve up to 500 pending members now (admin approval only)', 'tpsp'),
                            'secondary',
                            'submit',
                            false
                        );
                        ?>
                    </form>
                    <p class="description"><?php esc_html_e('Safe to run more than once. Denied accounts are not changed. Ongoing auto-approval on login uses the checkbox above.', 'tpsp'); ?></p>
                <?php endif; ?>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=user-registration-users')); ?>"><?php esc_html_e('Open User Registration → Members', 'tpsp'); ?></a>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}
