<?php
/**
 * Plugin Name: TTP WooCommerce - TCY Integration
 * Plugin URI: https://thetoppercentile.co.in
 * Description: WooCommerce + TCY platform integration for Top Percentile.
 * Version: 2.0.8
 * Author: Top Percentile
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TTP_VERSION',    '2.0.7' );
define( 'TTP_DIR',        plugin_dir_path( __FILE__ ) );
define( 'TTP_URL',        plugin_dir_url( __FILE__ ) );

/* ── Autoload classes ── */
require_once TTP_DIR . 'includes/class-ttp-settings.php';
require_once TTP_DIR . 'includes/class-ttp-tcy-api.php';
require_once TTP_DIR . 'includes/class-ttp-student.php';
require_once TTP_DIR . 'includes/class-ttp-checkout.php';
require_once TTP_DIR . 'includes/class-ttp-products.php';
require_once TTP_DIR . 'includes/class-ttp-enroll-page.php';
require_once TTP_DIR . 'includes/class-ttp-catalog-seed.php';
require_once TTP_DIR . 'admin/class-ttp-admin.php';

/* ── Activation: create DB tables + registration page ── */
register_activation_hook( __FILE__, 'ttp_activate' );
function ttp_activate() {
    global $wpdb;
    $c = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ttp_students (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        wp_user_id bigint(20) NOT NULL,
        full_name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        mobile varchar(20) NOT NULL,
        username varchar(100) NOT NULL,
        tcy_user_id varchar(100) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $c;" );

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ttp_api_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) DEFAULT NULL,
        action varchar(100) NOT NULL,
        request_data longtext,
        response_data longtext,
        status varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $c;" );

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ttp_order_mapping (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        wp_user_id bigint(20) NOT NULL,
        tcy_user_id varchar(100) DEFAULT NULL,
        tcy_course_id varchar(100) DEFAULT NULL,
        tcy_category_id varchar(100) DEFAULT NULL,
        login_link text DEFAULT NULL,
        status varchar(50) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $c;" );

    if ( ! get_page_by_path( 'student-registration' ) ) {
        wp_insert_post( [
            'post_title'   => 'Student Registration',
            'post_name'    => 'student-registration',
            'post_content' => '[ttp_student_registration]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
    }
}

/* ── Boot after WooCommerce loads ── */
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }
    new TTP_Settings();
    new TTP_Student();
    new TTP_Checkout();
    new TTP_Products();
    new TTP_Enroll_Page();
    new TTP_Admin();

    if ( class_exists( 'TTP_Catalog_Seed' ) ) {
        $v = get_option( 'ttp_catalog_mba_cet_2027_version', '' );
        if ( $v !== TTP_Catalog_Seed::CATALOG_VERSION ) {
            TTP_Catalog_Seed::seed( false );
        }
    }
}, 11 );

/* ── Frontend assets ── */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(  'ttp-style',  TTP_URL . 'assets/css/ttp-style.css',  [], TTP_VERSION );
    wp_enqueue_script( 'ttp-script', TTP_URL . 'assets/js/ttp-script.js', ['jquery'], TTP_VERSION, true );
    wp_localize_script( 'ttp-script', 'ttp_ajax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ttp_nonce' ),
    ] );
} );
