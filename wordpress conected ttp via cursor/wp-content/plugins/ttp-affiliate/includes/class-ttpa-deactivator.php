<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPA_Deactivator {

    public static function deactivate() {
        wp_clear_scheduled_hook('ttpa_process_auto_payouts');
    }
}
