<?php

namespace TTP_CRM\Core;

defined('ABSPATH') || exit;

class Deactivator
{
    public static function deactivate()
    {
        $timestamp = wp_next_scheduled('ttp_crm_run_reminders');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ttp_crm_run_reminders');
        }
    }
}
