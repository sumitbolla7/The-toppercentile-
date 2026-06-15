<?php

namespace ANH;

if (!defined('ABSPATH')) {
    exit;
}

class Autoloader {

    public static function register() {
        spl_autoload_register([__CLASS__, 'load']);
    }

    public static function load($class) {
        $prefix = __NAMESPACE__ . '\\';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $relative   = substr($class, strlen($prefix));
        $parts      = explode('\\', $relative);
        $class_name = array_pop($parts);
        $dir        = strtolower(implode('/', $parts));
        $file_name  = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        $file       = ANH_PATH . 'includes/' . ($dir ? $dir . '/' : '') . $file_name;

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
