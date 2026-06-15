<?php

if (!defined('ABSPATH')) {
    exit;
}

class TTPN_Deactivator {

    public static function deactivate() {
        wp_clear_scheduled_hook('ttpn_process_push_queue');
    }
}
