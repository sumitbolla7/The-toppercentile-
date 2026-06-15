<?php
/**
 * Main plugin orchestrator.
 */

if (!defined('ABSPATH')) {
    exit;
}

foreach (
	array(
		'class-tpsp-logger.php',
		'class-tpsp-assets.php',
		'class-tpsp-admin.php',
		'class-tpsp-user-registration-auto-approve.php',
		'class-tpsp-email-verification.php',
		'class-tpsp-dashboard.php',
		'class-tpsp-woocommerce.php',
		'class-tpsp-ajax.php',
	) as $tpsp_include
) {
	$tpsp_path = TPSP_PLUGIN_DIR . 'includes/' . $tpsp_include;
	if ( is_readable( $tpsp_path ) ) {
		require_once $tpsp_path;
	}
}

class TPSP {
    /**
     * Plugin modules.
     *
     * @var array
     */
    private $modules = [];

    /**
     * Register plugin hooks.
     *
     * @return void
     */
    public function run() {
        $this->modules['assets']            = new TPSP_Assets();
        $this->modules['admin']             = new TPSP_Admin();
        $this->modules['ur_auto_approve'] = new TPSP_User_Registration_Auto_Approve();
        $this->modules['email_verification'] = new TPSP_Email_Verification();
        $this->modules['dashboard']         = new TPSP_Dashboard();
        $this->modules['woocommerce']       = new TPSP_WooCommerce();
        $this->modules['ajax']              = new TPSP_Ajax();

        foreach ($this->modules as $module) {
            if (method_exists($module, 'hooks')) {
                $module->hooks();
            }
        }

        add_action('tpsp_cleanup_expired_tokens', [$this, 'cleanup_expired_verification_tokens']);
    }

    /**
     * Cleanup expired verification tokens.
     *
     * @return void
     */
    public function cleanup_expired_verification_tokens() {
        global $wpdb;

        $meta_key_expiry = 'tpsp_email_verification_expiry';
        $now             = time();

        $user_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value <> '' AND CAST(meta_value AS UNSIGNED) < %d",
                $meta_key_expiry,
                $now
            )
        );

        if (empty($user_ids)) {
            return;
        }

        foreach ($user_ids as $user_id) {
            delete_user_meta((int) $user_id, 'tpsp_email_verification_token');
            delete_user_meta((int) $user_id, 'tpsp_email_verification_expiry');
        }
    }
}
