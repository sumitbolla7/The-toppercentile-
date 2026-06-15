<?php
/**
 * Debug logger wrapper.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_Logger {
    /**
     * Write plugin logs to WooCommerce logger.
     *
     * @param string $message Log message.
     * @param string $level   Log level.
     * @param array  $context Extra context.
     * @return void
     */
    public static function log($message, $level = 'info', array $context = []) {
        $settings = get_option('tpsp_settings', []);
        $enabled  = isset($settings['enable_debug_logging']) ? $settings['enable_debug_logging'] : 'yes';

        if ('yes' !== $enabled || !function_exists('wc_get_logger')) {
            return;
        }

        $logger = wc_get_logger();
        $source = ['source' => 'tpsp'];
        $body   = $message;

        if (!empty($context)) {
            $body .= ' | Context: ' . wp_json_encode($context);
        }

        if (method_exists($logger, $level)) {
            $logger->{$level}($body, $source);
            return;
        }

        $logger->info($body, $source);
    }
}
