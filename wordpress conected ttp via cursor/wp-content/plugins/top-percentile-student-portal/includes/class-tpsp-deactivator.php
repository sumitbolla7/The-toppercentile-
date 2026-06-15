<?php
/**
 * Deactivation tasks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_Deactivator {
    /**
     * Run deactivation routine.
     *
     * @return void
     */
    public static function deactivate() {
        wp_clear_scheduled_hook('tpsp_cleanup_expired_tokens');
        flush_rewrite_rules();
    }
}
