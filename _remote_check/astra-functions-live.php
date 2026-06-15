<?php
/**
 * Astra functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Define Constants
 */
define( 'ASTRA_THEME_VERSION', '4.12.5' );
define( 'ASTRA_THEME_SETTINGS', 'astra-settings' );
define( 'ASTRA_THEME_DIR', trailingslashit( get_template_directory() ) );
define( 'ASTRA_THEME_URI', trailingslashit( esc_url( get_template_directory_uri() ) ) );
define( 'ASTRA_THEME_ORG_VERSION', file_exists( ASTRA_THEME_DIR . 'inc/w-org-version.php' ) );

/**
 * Minimum Version requirement of the Astra Pro addon.
 * This constant will be used to display the notice asking user to update the Astra addon to the version defined below.
 */
define( 'ASTRA_EXT_MIN_VER', '4.12.0' );

/**
 * Load in-house compatibility.
 */
if ( ASTRA_THEME_ORG_VERSION ) {
	require_once ASTRA_THEME_DIR . 'inc/w-org-version.php';
}

/**
 * Setup helper functions of Astra.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-theme-options.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-theme-strings.php';
require_once ASTRA_THEME_DIR . 'inc/core/common-functions.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-icons.php';

define( 'ASTRA_WEBSITE_BASE_URL', 'https://wpastra.com' );

/**
 * Update theme
 */
require_once ASTRA_THEME_DIR . 'inc/theme-update/astra-update-functions.php';
require_once ASTRA_THEME_DIR . 'inc/theme-update/class-astra-theme-background-updater.php';

/**
 * Fonts Files
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-font-families.php';
if ( is_admin() ) {
	require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts-data.php';
}

require_once ASTRA_THEME_DIR . 'inc/lib/webfont/class-astra-webfont-loader.php';
require_once ASTRA_THEME_DIR . 'inc/lib/docs/class-astra-docs-loader.php';
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-fonts.php';

require_once ASTRA_THEME_DIR . 'inc/dynamic-css/custom-menu-old-header.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/container-layouts.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/astra-icons.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-walker-page.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-enqueue-scripts.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-gutenberg-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-wp-editor-css.php';
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-command-palette.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/block-editor-compatibility.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/inline-on-mobile.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/content-background.php';
require_once ASTRA_THEME_DIR . 'inc/dynamic-css/dark-mode.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-dynamic-css.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-global-palette.php';

// Enable NPS Survey only if the starter templates version is < 4.3.7 or > 4.4.4 to prevent fatal error.
if ( ! defined( 'ASTRA_SITES_VER' ) || version_compare( ASTRA_SITES_VER, '4.3.7', '<' ) || version_compare( ASTRA_SITES_VER, '4.4.4', '>' ) ) {
	// NPS Survey Integration
	require_once ASTRA_THEME_DIR . 'inc/lib/class-astra-nps-notice.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/class-astra-nps-survey.php';
}

/**
 * Custom template tags for this theme.
 */
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-attr.php';
require_once ASTRA_THEME_DIR . 'inc/template-tags.php';

require_once ASTRA_THEME_DIR . 'inc/widgets.php';
require_once ASTRA_THEME_DIR . 'inc/core/theme-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/admin-functions.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-memory-limit-notice.php';
require_once ASTRA_THEME_DIR . 'inc/core/sidebar-manager.php';

/**
 * Markup Functions
 */
require_once ASTRA_THEME_DIR . 'inc/markup-extras.php';
require_once ASTRA_THEME_DIR . 'inc/extras.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog-config.php';
require_once ASTRA_THEME_DIR . 'inc/blog/blog.php';
require_once ASTRA_THEME_DIR . 'inc/blog/single-blog.php';

/**
 * Markup Files
 */
require_once ASTRA_THEME_DIR . 'inc/template-parts.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-loop.php';
require_once ASTRA_THEME_DIR . 'inc/class-astra-mobile-header.php';

/**
 * Functions and definitions.
 */
require_once ASTRA_THEME_DIR . 'inc/class-astra-after-setup-theme.php';

// Required files.
require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-helper.php';

require_once ASTRA_THEME_DIR . 'inc/schema/class-astra-schema.php';

/* Setup API */
require_once ASTRA_THEME_DIR . 'admin/includes/class-astra-learn.php';
require_once ASTRA_THEME_DIR . 'admin/includes/class-astra-api-init.php';

if ( is_admin() ) {
	/**
	 * Admin Menu Settings
	 */
	require_once ASTRA_THEME_DIR . 'inc/core/class-astra-admin-settings.php';
	require_once ASTRA_THEME_DIR . 'admin/class-astra-admin-loader.php';
	require_once ASTRA_THEME_DIR . 'inc/lib/astra-notices/class-astra-notices.php';
}

/**
 * Metabox additions.
 */
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-boxes.php';
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-meta-box-operations.php';
require_once ASTRA_THEME_DIR . 'inc/metabox/class-astra-elementor-editor-settings.php';

/**
 * Customizer additions.
 */
require_once ASTRA_THEME_DIR . 'inc/customizer/class-astra-customizer.php';

/**
 * Astra Modules.
 */
require_once ASTRA_THEME_DIR . 'inc/modules/posts-structures/class-astra-post-structures.php';
require_once ASTRA_THEME_DIR . 'inc/modules/related-posts/class-astra-related-posts.php';

/**
 * Compatibility
 */
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gutenberg.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-jetpack.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/woocommerce/class-astra-woocommerce.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/edd/class-astra-edd.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/lifterlms/class-astra-lifterlms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/learndash/class-astra-learndash.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bb-ultimate-addon.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-contact-form-7.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-visual-composer.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-site-origin.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-gravity-forms.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-bne-flyout.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-ubermeu.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-divi-builder.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-amp.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-yoast-seo.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/surecart/class-astra-surecart.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-starter-content.php';
require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-buddypress.php';
require_once ASTRA_THEME_DIR . 'inc/addons/transparent-header/class-astra-ext-transparent-header.php';
require_once ASTRA_THEME_DIR . 'inc/addons/breadcrumbs/class-astra-breadcrumbs.php';
require_once ASTRA_THEME_DIR . 'inc/addons/scroll-to-top/class-astra-scroll-to-top.php';
require_once ASTRA_THEME_DIR . 'inc/addons/heading-colors/class-astra-heading-colors.php';
require_once ASTRA_THEME_DIR . 'inc/builder/class-astra-builder-loader.php';

// Elementor Compatibility requires PHP 5.4 for namespaces.
if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-elementor-pro.php';
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-web-stories.php';
}

// Beaver Themer compatibility requires PHP 5.3 for anonymous functions.
if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	require_once ASTRA_THEME_DIR . 'inc/compatibility/class-astra-beaver-themer.php';
}

require_once ASTRA_THEME_DIR . 'inc/core/markup/class-astra-markup.php';

/**
 * Load deprecated functions
 */
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-filters.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-hooks.php';
require_once ASTRA_THEME_DIR . 'inc/core/deprecated/deprecated-functions.php';
add_action('template_redirect', 'ttp_force_registration_before_checkout');
function ttp_force_registration_before_checkout() {
    if ( ! function_exists( 'is_checkout' ) || ! function_exists( 'WC' ) || ! WC() ) {
        return;
    }
    if (is_checkout() && !is_user_logged_in()) {
        $product_id = '';
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            break;
        }
        wp_redirect(home_url('/student-registration/?product_id=' . $product_id));
        exit;
    }
}

// ─────────────────────────────────────────
// 2. SKIP CART — DIRECT TO CHECKOUT
// ─────────────────────────────────────────
add_filter('woocommerce_add_to_cart_redirect', 'ttp_skip_cart');
function ttp_skip_cart($url) {
    return wc_get_checkout_url();
}

// ─────────────────────────────────────────
// 3. CUSTOM CHECKOUT FIELDS
// ─────────────────────────────────────────
add_filter('woocommerce_checkout_fields', 'ttp_custom_checkout_fields');
function ttp_custom_checkout_fields($fields) {
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_last_name']);
    $fields['billing']['billing_first_name']['label']       = 'Full Name';
    $fields['billing']['billing_first_name']['placeholder'] = 'Enter your full name';
    $fields['billing']['billing_phone']['label']            = 'Mobile Number';
    $fields['billing']['billing_phone']['required']         = true;
    return $fields;
}

// ─────────────────────────────────────────
// 4. AUTO-FILL CHECKOUT FROM USER PROFILE
// ─────────────────────────────────────────
add_filter('woocommerce_checkout_get_value', 'ttp_autofill_checkout', 10, 2);
function ttp_autofill_checkout($value, $input) {
    if (!is_user_logged_in()) return $value;
    $user = wp_get_current_user();
    $map = [
        'billing_first_name' => get_user_meta($user->ID, 'ttp_full_name', true) ?: $user->display_name,
        'billing_email'      => $user->user_email,
        'billing_phone'      => get_user_meta($user->ID, 'ttp_mobile', true),
    ];
    return isset($map[$input]) ? $map[$input] : $value;
}

// ─────────────────────────────────────────
// 5. BUY NOW BUTTON ON PRODUCT PAGE
// ─────────────────────────────────────────
add_action('woocommerce_after_add_to_cart_button', 'ttp_buy_now_button');
function ttp_buy_now_button() {
    global $product;
    echo '<button type="button" class="button alt ttp-buy-now-btn" data-product-id="' . esc_attr($product->get_id()) . '">
        ⚡ Buy Now
    </button>';
}

// ─────────────────────────────────────────
// 6. SHOW COUNTDOWN TIMER ON PRODUCT PAGE
// ─────────────────────────────────────────
add_action('woocommerce_before_add_to_cart_form', 'ttp_show_timer');
function ttp_show_timer() {
    global $product;
    $timer    = get_post_meta($product->get_id(), '_ttp_coupon_timer', true);
    $validity = get_post_meta($product->get_id(), '_ttp_validity', true);
    if ($timer) {
        echo '<div class="ttp-timer-wrap">⏰ <strong>Limited Time Offer!</strong> Expires in: <span class="ttp-countdown" data-minutes="' . esc_attr($timer) . '">--:--</span></div>';
    }
    if ($validity) {
        echo '<div class="ttp-validity-wrap">📅 <strong>Course Validity:</strong> ' . esc_html($validity) . '</div>';
    }
}

// ─────────────────────────────────────────
// 7. ORDER SUCCESS — SHOW COURSE ACCESS BUTTON
// ─────────────────────────────────────────
add_action('woocommerce_thankyou', 'ttp_course_access_button', 5);
function ttp_course_access_button($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    ?>
    <div class="ttp-success-box">
        <div style="font-size:52px;margin-bottom:14px">🎉</div>
        <h2>Payment Successful!</h2>
        <p>Your course is now active. Click below to access your course on the TCY portal.</p>
        <button class="ttp-access-btn button alt"
            data-order-id="<?php echo esc_attr($order_id); ?>"
            data-nonce="<?php echo wp_create_nonce('ttp_nonce'); ?>">
            🚀 Login &amp; Access Your Course
        </button>
        <p class="ttp-note">You will be automatically logged in — no password needed.</p>
    </div>
    <?php
}

// ─────────────────────────────────────────
// 8. MY ACCOUNT — SHOW ACCESS BUTTON
// ─────────────────────────────────────────
add_action('woocommerce_order_details_after_order_table', 'ttp_myaccount_access');
function ttp_myaccount_access($order) {
    if (!$order->is_paid()) return;
    ?>
    <div class="ttp-myaccount-access">
        <h3>Course Access</h3>
        <button class="ttp-access-btn button"
            data-order-id="<?php echo esc_attr($order->get_id()); ?>"
            data-nonce="<?php echo wp_create_nonce('ttp_nonce'); ?>">
            🚀 Access Your Course on TCY Portal
        </button>
    </div>
    <?php
}

// ─────────────────────────────────────────
// 9. LOGIN REDIRECT — GO TO MY ORDERS
// ─────────────────────────────────────────
add_filter('woocommerce_login_redirect', 'ttp_login_redirect', 10, 2);
function ttp_login_redirect($redirect, $user) {
    return wc_get_account_endpoint_url('orders');
}

// ─────────────────────────────────────────
// 10. ENQUEUE SCRIPTS
// ─────────────────────────────────────────
add_action('wp_enqueue_scripts', 'ttp_enqueue');
function ttp_enqueue() {
    wp_enqueue_script('ttp-main', get_template_directory_uri() . '/ttp-main.js', ['jquery'], '1.0', true);
    wp_localize_script('ttp-main', 'ttp_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ttp_nonce'),
    ]);
}
add_filter('jpeg_quality', function() { return 100; });
add_filter('wp_editor_set_quality', function() { return 100; });
/**
 * TTP — Enroll Now Page + Signup Popup + Buy Now + Courses Redirect
 *
 * HOW TO USE:
 * 1. In WordPress admin → Pages → Add New → paste this as a Custom Page Template
 *    OR use it as a shortcode [ttp_enroll_page] on any page titled "Enroll Now"
 * 2. Add to functions.php (or a plugin file)
 * 3. Set your "Enroll Now" page slug to /enroll-now/ in WordPress
 * 4. Redirect /courses/ → /enroll-now/ (see bottom of this file)
 */

// ─────────────────────────────────────────
// A. REDIRECT /shop/ (Courses page) → /enroll-now/
// ─────────────────────────────────────────
add_action('template_redirect', 'ttp_redirect_courses_to_enroll');
function ttp_redirect_courses_to_enroll() {
    // Redirect WooCommerce shop page to enroll-now
    if (is_shop()) {
        wp_redirect(home_url('/enroll-now/'), 301);
        exit;
    }
}

// ─────────────────────────────────────────
// B. ENROLL NOW PAGE SHORTCODE [ttp_enroll_page]
// Updated: Added 4-tab filter (MBA CET 2027 | NMAT/SNAP | MHRD | MSc Finance)
// ─────────────────────────────────────────
add_shortcode('ttp_enroll_page', 'ttp_enroll_page_shortcode');
function ttp_enroll_page_shortcode() {
    ob_start();

    // Fetch WooCommerce products
    $products_query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ]);

    // ── TAB DEFINITIONS ──────────────────────────────────────────────────────
    // Each tab has a label, a slug (used as data-tab value), and keywords.
    // A product is assigned to the FIRST tab whose keywords match its title.
    // If no match, it falls into 'cet' (MBA CET 2027) by default.
    $tabs = [
        [
            'slug'     => 'cet',
            'label'    => 'MBA CET 2027',
            'keywords' => ['CET', 'MBA CET', 'Complete Course', 'Crash Course', 'Test Series', 'Elite'],
        ],
        [
            'slug'     => 'nmat',
            'label'    => 'NMAT / SNAP',
            'keywords' => ['NMAT', 'SNAP', 'NMAT SNAP'],
        ],
        [
            'slug'     => 'mhrd',
            'label'    => 'MHRD',
            'keywords' => ['MHRD', 'MHRd', 'JBIMS MHRD'],
        ],
        [
            'slug'     => 'msc',
            'label'    => 'MSc Finance',
            'keywords' => ['MSc', 'MFin', 'MSc Finance', 'JBIMS MFin'],
        ],
    ];

    // ── PLAN FEATURES (keyed by product name substring) ──────────────────────
    $plan_features = [
        'CET-MH Test Series' => [
            'badge'    => 'TEST SERIES',
            'popular'  => false,
            'features' => [
                '67 topic-wise concept builders',
                '196 practice tests',
                '16 TTP Turbo Mocks + 40 sectional tests',
                'MBA Topic-wise tests included',
                'Detailed performance analytics',
                'Doubt-solving WhatsApp group',
            ],
        ],
        'Elite Crash Course' => [
            'badge'    => 'COMBO',
            'popular'  => true,
            'features' => [
                '75 hrs Live Crash Course by JBIMS Alumni',
                '300+ hrs complete recorded class library',
                '1:1 Mentorship — 3 sessions with JBIMS students',
                'Live weekly group mentorship with JBIMS Alumni',
                '67 topic-wise concept builders + 196 practice tests',
                '16 TTP Turbo Mocks + 40 sectional tests',
                'Doubt-solving WhatsApp group',
            ],
        ],
        'Complete Course' => [
            'badge'    => 'COMPLETE',
            'popular'  => false,
            'features' => [
                'Full MBA CET 2026 preparation',
                '300+ hrs recorded class library',
                '67 topic-wise concept builders',
                '196 practice tests',
                '16 TTP Turbo Mocks + 40 sectional tests',
                'Live doubt-solving sessions',
                'Doubt-solving WhatsApp group',
            ],
        ],
    ];

    // ── Helper: assign a product title to a tab slug ──────────────────────────
    function ttp_get_tab_for_product($title, $tabs) {
        // Try each tab (skip the first/default) first for specificity
        foreach (array_slice($tabs, 1) as $tab) {
            foreach ($tab['keywords'] as $kw) {
                if (stripos($title, $kw) !== false) {
                    return $tab['slug'];
                }
            }
        }
        // Check first tab (cet) keywords explicitly
        foreach ($tabs[0]['keywords'] as $kw) {
            if (stripos($title, $kw) !== false) {
                return $tabs[0]['slug'];
            }
        }
        // Default fallback
        return $tabs[0]['slug'];
    }
    ?>

    <style>
    /* ── TTP ENROLL PAGE STYLES ─────────────────────────────── */
    :root {
        --ttp-yellow: #f5c518;
        --ttp-dark:   #1a1a2e;
        --ttp-mid:    #16213e;
        --ttp-gray:   #f8f8f8;
        --ttp-border: #e8e8e8;
        --ttp-text:   #222;
        --ttp-muted:  #666;
    }
    .ttp-enroll-page {
        font-family: 'Segoe UI', system-ui, sans-serif;
        color: var(--ttp-text);
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px 80px;
    }

    /* ── Hero Banner ── */
    .ttp-enroll-hero {
        background: var(--ttp-dark);
        border-radius: 16px;
        padding: 56px 40px 48px;
        text-align: center;
        margin-bottom: 48px;
        position: relative;
        overflow: hidden;
    }
    .ttp-enroll-hero::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(245,197,24,0.18) 0%, transparent 70%);
        border-radius: 50%;
    }
    .ttp-enroll-hero .ttp-hero-eyebrow {
        display: inline-block;
        background: var(--ttp-yellow);
        color: #111;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        padding: 5px 16px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    .ttp-enroll-hero h1 {
        font-size: clamp(28px, 4vw, 44px);
        font-weight: 800;
        color: #fff;
        margin: 0 0 14px;
        line-height: 1.18;
    }
    .ttp-enroll-hero h1 span { color: var(--ttp-yellow); }
    .ttp-enroll-hero p {
        color: rgba(255,255,255,0.72);
        font-size: 17px;
        max-width: 580px;
        margin: 0 auto 28px;
        line-height: 1.65;
    }
    .ttp-trust-badges {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 8px;
    }
    .ttp-trust-badge {
        display: flex;
        align-items: center;
        gap: 7px;
        color: rgba(255,255,255,0.85);
        font-size: 14px;
        font-weight: 500;
    }
    .ttp-trust-badge .ttp-dot {
        width: 8px; height: 8px;
        background: var(--ttp-yellow);
        border-radius: 50%;
        display: inline-block;
    }

    /* ── TAB BAR ── */
    .ttp-tab-bar {
        display: flex;
        gap: 0;
        border-bottom: 2px solid var(--ttp-border);
        margin-bottom: 40px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .ttp-tab-bar::-webkit-scrollbar { display: none; }

    .ttp-tab-btn {
        flex-shrink: 0;
        padding: 14px 28px;
        font-size: 15px;
        font-weight: 600;
        color: var(--ttp-muted);
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        cursor: pointer;
        transition: color 0.18s, border-color 0.18s;
        white-space: nowrap;
        font-family: 'Segoe UI', system-ui, sans-serif;
    }
    .ttp-tab-btn:hover {
        color: var(--ttp-dark);
    }
    .ttp-tab-btn.active {
        color: var(--ttp-dark);
        border-bottom-color: var(--ttp-yellow);
        font-weight: 700;
    }
    .ttp-tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f0f0f0;
        color: #888;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 20px;
        margin-left: 7px;
        min-width: 20px;
        transition: background 0.18s, color 0.18s;
    }
    .ttp-tab-btn.active .ttp-tab-count {
        background: var(--ttp-yellow);
        color: #111;
    }

    /* ── Tab Panels ── */
    .ttp-tab-panel {
        display: none;
    }
    .ttp-tab-panel.active {
        display: block;
    }

    /* ── Empty state ── */
    .ttp-tab-empty {
        text-align: center;
        padding: 60px 20px;
        color: var(--ttp-muted);
        font-size: 15px;
    }
    .ttp-tab-empty strong {
        display: block;
        font-size: 18px;
        color: var(--ttp-dark);
        margin-bottom: 8px;
    }

    /* ── Plan Cards Grid ── */
    .ttp-plans-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        align-items: start;
    }
    .ttp-plan-card {
        background: #fff;
        border: 1.5px solid var(--ttp-border);
        border-radius: 16px;
        padding: 32px 28px 28px;
        position: relative;
        transition: box-shadow 0.22s, transform 0.22s;
    }
    .ttp-plan-card:hover {
        box-shadow: 0 12px 40px rgba(26,26,46,0.13);
        transform: translateY(-3px);
    }
    .ttp-plan-card.popular {
        border: 2.5px solid var(--ttp-dark);
        box-shadow: 0 8px 32px rgba(26,26,46,0.10);
    }
    .ttp-popular-ribbon {
        position: absolute;
        top: -1px;
        right: 24px;
        background: var(--ttp-yellow);
        color: #111;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        padding: 5px 14px 6px;
        border-radius: 0 0 8px 8px;
    }
    .ttp-plan-badge {
        display: inline-block;
        background: var(--ttp-dark);
        color: var(--ttp-yellow);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        padding: 4px 12px;
        border-radius: 4px;
        margin-bottom: 14px;
    }
    .ttp-plan-card h2 {
        font-size: 20px;
        font-weight: 800;
        color: var(--ttp-dark);
        margin: 0 0 6px;
        line-height: 1.25;
    }
    .ttp-plan-discount {
        font-size: 12px;
        color: #e53e3e;
        font-weight: 700;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .ttp-plan-discount::before { content: '↓'; }
    .ttp-price-row {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 24px;
    }
    .ttp-price-current {
        font-size: 38px;
        font-weight: 900;
        color: var(--ttp-dark);
        letter-spacing: -1px;
    }
    .ttp-price-original {
        font-size: 16px;
        color: var(--ttp-muted);
        text-decoration: line-through;
    }
    .ttp-features-list {
        list-style: none;
        padding: 0;
        margin: 0 0 28px;
        border-top: 1px solid var(--ttp-border);
        padding-top: 20px;
    }
    .ttp-features-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 14px;
        color: #444;
        padding: 7px 0;
        line-height: 1.5;
    }
    .ttp-features-list li .ttp-check {
        width: 18px; height: 18px;
        background: #ebf8f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .ttp-features-list li .ttp-check::after {
        content: '';
        width: 6px; height: 10px;
        border: 2px solid #22c55e;
        border-top: none;
        border-left: none;
        transform: rotate(45deg) translateY(-1px);
        display: block;
    }

    /* ── Buttons ── */
    .ttp-btn-enroll {
        display: block;
        width: 100%;
        padding: 14px;
        background: var(--ttp-yellow);
        color: #111;
        font-size: 15px;
        font-weight: 800;
        text-align: center;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.18s, transform 0.15s;
        letter-spacing: 0.03em;
        margin-bottom: 10px;
    }
    .ttp-btn-enroll:hover {
        background: #e0b000;
        transform: scale(1.01);
        color: #111;
        text-decoration: none;
    }
    .ttp-btn-cart {
        display: block;
        width: 100%;
        padding: 12px;
        background: transparent;
        color: var(--ttp-dark);
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        border-radius: 8px;
        border: 1.5px solid var(--ttp-dark);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.18s;
    }
    .ttp-btn-cart:hover {
        background: var(--ttp-dark);
        color: #fff;
        text-decoration: none;
    }

    /* ── Signup Popup ── */
    .ttp-signup-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(26,26,46,0.72);
        z-index: 99999;
        align-items: center;
        justify-content: center;
        padding: 20px;
        backdrop-filter: blur(4px);
    }
    .ttp-signup-overlay.open { display: flex; }
    .ttp-signup-modal {
        background: #fff;
        border-radius: 20px;
        width: 100%;
        max-width: 460px;
        padding: 40px 36px 32px;
        position: relative;
        animation: ttpSlideUp 0.28s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes ttpSlideUp {
        from { transform: translateY(32px); opacity: 0; }
        to   { transform: translateY(0);    opacity: 1; }
    }
    .ttp-signup-modal .ttp-modal-close {
        position: absolute;
        top: 16px; right: 20px;
        background: none;
        border: none;
        font-size: 22px;
        cursor: pointer;
        color: #999;
        line-height: 1;
        padding: 4px;
    }
    .ttp-signup-modal .ttp-modal-logo {
        font-size: 13px;
        font-weight: 800;
        color: var(--ttp-dark);
        letter-spacing: 0.05em;
        margin-bottom: 20px;
    }
    .ttp-signup-modal h2 {
        font-size: 22px;
        font-weight: 800;
        color: var(--ttp-dark);
        margin: 0 0 6px;
    }
    .ttp-signup-modal .ttp-modal-sub {
        font-size: 14px;
        color: var(--ttp-muted);
        margin-bottom: 28px;
    }
    .ttp-signup-modal .ttp-field { margin-bottom: 16px; }
    .ttp-signup-modal label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }
    .ttp-signup-modal input[type=text],
    .ttp-signup-modal input[type=email],
    .ttp-signup-modal input[type=tel] {
        width: 100%;
        padding: 11px 14px;
        border: 1.5px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        color: var(--ttp-text);
        outline: none;
        transition: border-color 0.18s;
        box-sizing: border-box;
    }
    .ttp-signup-modal input:focus { border-color: var(--ttp-yellow); }
    .ttp-signup-modal .ttp-modal-submit {
        width: 100%;
        padding: 14px;
        background: var(--ttp-yellow);
        color: #111;
        font-size: 15px;
        font-weight: 800;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        margin-top: 8px;
        letter-spacing: 0.02em;
        transition: background 0.18s;
    }
    .ttp-signup-modal .ttp-modal-submit:hover { background: #e0b000; }
    .ttp-modal-selected-product {
        background: #fffbe6;
        border: 1px solid #f5c518;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        font-weight: 600;
        color: #7a5800;
        margin-bottom: 20px;
    }
    .ttp-modal-divider {
        text-align: center;
        font-size: 13px;
        color: #aaa;
        margin: 16px 0 14px;
    }
    .ttp-modal-login-link {
        text-align: center;
        font-size: 13px;
        color: var(--ttp-muted);
    }
    .ttp-modal-login-link a {
        color: var(--ttp-dark);
        font-weight: 700;
        text-decoration: none;
    }
    .ttp-modal-login-link a:hover { text-decoration: underline; }

    @media (max-width: 700px) {
        .ttp-enroll-hero   { padding: 40px 22px 36px; }
        .ttp-signup-modal  { padding: 32px 22px 28px; }
        .ttp-plans-grid    { grid-template-columns: 1fr; }
        .ttp-tab-btn       { padding: 12px 16px; font-size: 13px; }
    }
    </style>

    <div class="ttp-enroll-page">

        <!-- Hero -->
        <div class="ttp-enroll-hero">
            <span class="ttp-hero-eyebrow">Enroll Now</span>
            <h1>Crack Your MBA Entrance<br>with <span>The Top Percentile</span></h1>
            <p>Join 1000+ aspirants who've cracked JBIMS, IIM-A, SPJIMR &amp; NMIMS with our proven courses, test series, and mentorship.</p>
            <div class="ttp-trust-badges">
                <span class="ttp-trust-badge"><span class="ttp-dot"></span> Trusted by 1000+ Aspirants</span>
                <span class="ttp-trust-badge"><span class="ttp-dot"></span> JBIMS Alumni Mentors</span>
                <span class="ttp-trust-badge"><span class="ttp-dot"></span> Live + Recorded Sessions</span>
            </div>
        </div>

        <!-- Plans Section -->
        <div class="ttp-plans-section">
            <h2 class="ttp-plans-title" style="text-align:center;font-size:28px;font-weight:800;color:var(--ttp-dark);margin-bottom:8px;">Choose Your Plan</h2>
            <p class="ttp-plans-subtitle" style="text-align:center;color:var(--ttp-muted);font-size:15px;margin-bottom:32px;">Select your exam below — all plans include access to our TCY portal</p>

            <?php
            // ── Build tab → products map ──────────────────────────────────────
            $tab_products = [];
            foreach ($tabs as $tab) {
                $tab_products[$tab['slug']] = [];
            }

            if ($products_query->have_posts()) :
                while ($products_query->have_posts()) : $products_query->the_post();
                    global $product;
                    $product_obj = wc_get_product(get_the_ID());
                    if (!$product_obj) continue;

                    $title       = get_the_title();
                    $tab_slug    = ttp_get_tab_for_product($title, $tabs);

                    $tab_products[$tab_slug][] = [
                        'id'         => get_the_ID(),
                        'title'      => $title,
                        'price'      => $product_obj->get_price(),
                        'reg_price'  => $product_obj->get_regular_price(),
                        'sale_price' => $product_obj->get_sale_price(),
                        'url'        => get_permalink(),
                    ];
                endwhile;
                wp_reset_postdata();
            endif;
            ?>

            <!-- Tab Bar -->
            <div class="ttp-tab-bar" role="tablist">
                <?php foreach ($tabs as $i => $tab) :
                    $count = count($tab_products[$tab['slug']]);
                ?>
                <button
                    class="ttp-tab-btn <?php echo $i === 0 ? 'active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"
                    aria-controls="ttp-panel-<?php echo esc_attr($tab['slug']); ?>"
                    data-tab="<?php echo esc_attr($tab['slug']); ?>"
                >
                    <?php echo esc_html($tab['label']); ?>
                    <span class="ttp-tab-count"><?php echo $count; ?></span>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Tab Panels -->
            <?php foreach ($tabs as $i => $tab) : ?>
            <div
                class="ttp-tab-panel <?php echo $i === 0 ? 'active' : ''; ?>"
                id="ttp-panel-<?php echo esc_attr($tab['slug']); ?>"
                role="tabpanel"
            >
                <?php if (empty($tab_products[$tab['slug']])) : ?>
                    <div class="ttp-tab-empty">
                        <strong>Coming Soon</strong>
                        <?php echo esc_html($tab['label']); ?> courses will be listed here soon. Check back shortly!
                    </div>
                <?php else : ?>
                <div class="ttp-plans-grid">
                    <?php foreach ($tab_products[$tab['slug']] as $p) :
                        $title        = $p['title'];
                        $price        = $p['price'];
                        $reg_price    = $p['reg_price'];
                        $sale_price   = $p['sale_price'];
                        $product_url  = $p['url'];
                        $product_id   = $p['id'];

                        // Match plan features
                        $plan = null;
                        foreach ($plan_features as $key => $data) {
                            if (stripos($title, $key) !== false) {
                                $plan = $data;
                                break;
                            }
                        }
                        if (!$plan) {
                            $plan = [
                                'badge'    => 'COURSE',
                                'popular'  => false,
                                'features' => ['Full course access', 'Practice tests', 'Doubt-solving WhatsApp group'],
                            ];
                        }

                        $popular       = $plan['popular'];
                        $badge         = $plan['badge'];
                        $features      = $plan['features'];
                        $discount_pct  = ($reg_price && $sale_price) ? round((1 - $sale_price / $reg_price) * 100) : 0;
                        $display_price = $sale_price ?: $price;
                    ?>
                    <div class="ttp-plan-card <?php echo $popular ? 'popular' : ''; ?>">
                        <?php if ($popular) : ?>
                            <div class="ttp-popular-ribbon">MOST POPULAR</div>
                        <?php endif; ?>
                        <div class="ttp-plan-badge"><?php echo esc_html($badge); ?></div>
                        <h2><?php echo esc_html($title); ?></h2>
                        <?php if ($discount_pct) : ?>
                            <div class="ttp-plan-discount"><?php echo $discount_pct; ?>% OFF</div>
                        <?php endif; ?>
                        <div class="ttp-price-row">
                            <span class="ttp-price-current">₹<?php echo number_format($display_price); ?></span>
                            <?php if ($reg_price && $reg_price != $display_price) : ?>
                                <span class="ttp-price-original">₹<?php echo number_format($reg_price); ?></span>
                            <?php endif; ?>
                        </div>
                        <ul class="ttp-features-list">
                            <?php foreach ($features as $feat) : ?>
                                <li><span class="ttp-check"></span><?php echo esc_html($feat); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button class="ttp-btn-enroll ttp-open-signup"
                            data-product-id="<?php echo esc_attr($product_id); ?>"
                            data-product-name="<?php echo esc_attr($title); ?>"
                            data-product-url="<?php echo esc_url($product_url); ?>">
                            Enroll Now →
                        </button>
                        <a href="<?php echo esc_url($product_url); ?>" class="ttp-btn-cart">
                            View Details
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- ── SIGNUP POPUP ──────────────────────────────── -->
    <div class="ttp-signup-overlay" id="ttpSignupOverlay">
        <div class="ttp-signup-modal">
            <button class="ttp-modal-close" id="ttpModalClose" aria-label="Close">&times;</button>
            <div class="ttp-modal-logo">⭐ TheTopPercentile</div>
            <h2>Create Your Account</h2>
            <p class="ttp-modal-sub">Sign up to enroll and access your course instantly</p>
            <div class="ttp-modal-selected-product" id="ttpModalProduct"></div>
            <?php if (!is_user_logged_in()) : ?>
            <form id="ttpSignupForm" method="post">
                <div class="ttp-field">
                    <label for="ttp_full_name">Full Name</label>
                    <input type="text" id="ttp_full_name" name="ttp_full_name" placeholder="Enter your full name" required>
                </div>
                <div class="ttp-field">
                    <label for="ttp_email">Email Address</label>
                    <input type="email" id="ttp_email" name="ttp_email" placeholder="you@email.com" required>
                </div>
                <div class="ttp-field">
                    <label for="ttp_mobile">Mobile Number</label>
                    <input type="tel" id="ttp_mobile" name="ttp_mobile" placeholder="10-digit mobile number" required maxlength="10">
                </div>
                <?php wp_nonce_field('ttp_register_nonce', 'ttp_reg_nonce'); ?>
                <input type="hidden" name="ttp_redirect_product_id" id="ttpRedirectProductId">
                <button type="submit" class="ttp-modal-submit">Create Account &amp; Enroll →</button>
                <div class="ttp-modal-divider">Already have an account?</div>
                <div class="ttp-modal-login-link">
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>">Log in here</a>
                </div>
            </form>
            <?php else : ?>
            <p style="text-align:center;color:#444;font-size:14px">
                Welcome back, <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong>!<br>
                You will be redirected to checkout.
            </p>
            <a href="#" class="ttp-btn-enroll" id="ttpGoToCheckout">Proceed to Checkout →</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
    (function() {
        // ── Tab switching ──────────────────────────────────────────────────────
        var tabBtns   = document.querySelectorAll('.ttp-tab-btn');
        var tabPanels = document.querySelectorAll('.ttp-tab-panel');

        tabBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var target = this.getAttribute('data-tab');

                tabBtns.forEach(function(b) {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                tabPanels.forEach(function(p) { p.classList.remove('active'); });

                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                var panel = document.getElementById('ttp-panel-' + target);
                if (panel) panel.classList.add('active');
            });
        });

        // ── Signup popup ───────────────────────────────────────────────────────
        var overlay      = document.getElementById('ttpSignupOverlay');
        var closeBtn     = document.getElementById('ttpModalClose');
        var productLabel = document.getElementById('ttpModalProduct');
        var redirectInput= document.getElementById('ttpRedirectProductId');
        var goCheckout   = document.getElementById('ttpGoToCheckout');

        document.querySelectorAll('.ttp-open-signup').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var name = this.getAttribute('data-product-name');
                var pid  = this.getAttribute('data-product-id');
                var url  = this.getAttribute('data-product-url');
                if (productLabel)  productLabel.textContent  = '🎓 ' + name;
                if (redirectInput) redirectInput.value        = pid;
                if (goCheckout)    goCheckout.href            = '<?php echo esc_js(wc_get_checkout_url()); ?>?add-to-cart=' + pid;
                overlay.classList.add('open');
            });
        });

        if (closeBtn) closeBtn.addEventListener('click', function() { overlay.classList.remove('open'); });
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) overlay.classList.remove('open');
        });

        // ── Signup form AJAX ───────────────────────────────────────────────────
        var form = document.getElementById('ttpSignupForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var data = new FormData(form);
                data.append('action', 'ttp_register_and_checkout');
                fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    body: data
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success && res.data && res.data.redirect) {
                        window.location.href = res.data.redirect;
                    } else {
                        alert(res.data && res.data.message ? res.data.message : 'Something went wrong. Please try again.');
                    }
                })
                .catch(function() { alert('Network error. Please try again.'); });
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}


// ─────────────────────────────────────────
// C. AJAX: REGISTER + ADD TO CART + REDIRECT TO CHECKOUT
// ─────────────────────────────────────────
add_action('wp_ajax_nopriv_ttp_register_and_checkout', 'ttp_ajax_register_and_checkout');
add_action('wp_ajax_ttp_register_and_checkout',        'ttp_ajax_register_and_checkout');
function ttp_ajax_register_and_checkout() {
    check_ajax_referer('ttp_register_nonce', 'ttp_reg_nonce');

    $full_name  = sanitize_text_field($_POST['ttp_full_name']  ?? '');
    $email      = sanitize_email($_POST['ttp_email']           ?? '');
    $mobile     = sanitize_text_field($_POST['ttp_mobile']     ?? '');
    $product_id = absint($_POST['ttp_redirect_product_id']     ?? 0);

    if (!$email || !is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.']);
    }

    // Check if user exists
    $user = get_user_by('email', $email);
    if (!$user) {
        // Create new user
        $username = sanitize_user(explode('@', $email)[0]) . '_' . wp_rand(100, 999);
        $password = wp_generate_password(12, false);
        $user_id  = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }
        wp_update_user(['ID' => $user_id, 'display_name' => $full_name, 'first_name' => $full_name]);
        update_user_meta($user_id, 'ttp_full_name', $full_name);
        update_user_meta($user_id, 'ttp_mobile', $mobile);
        $user = get_user_by('id', $user_id);
    }

    // Log the user in
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);

    // Add product to cart and redirect
    if ($product_id) {
        WC()->cart->add_to_cart($product_id);
    }

    wp_send_json_success([
        'redirect' => wc_get_checkout_url(),
    ]);
}

// ─────────────────────────────────────────
// D. BUY NOW BUTTON — already in your functions.php
//    (already handled by your existing ttp_buy_now_button function)
//    Below just adds styling for the buy now button
// ─────────────────────────────────────────
add_action('wp_head', 'ttp_buy_now_button_styles');
function ttp_buy_now_button_styles() {
    if (!is_product()) return;
    ?>
    <style>
    .ttp-buy-now-btn {
        background: #f5c518 !important;
        color: #111 !important;
        border: none !important;
        font-weight: 800 !important;
        font-size: 15px !important;
        padding: 14px 28px !important;
        border-radius: 8px !important;
        margin-top: 10px !important;
        width: 100%;
        display: block !important;
        cursor: pointer !important;
        transition: background 0.18s, transform 0.15s !important;
        letter-spacing: 0.02em !important;
    }
    .ttp-buy-now-btn:hover {
        background: #e0b000 !important;
        transform: scale(1.01) !important;
        color: #111 !important;
    }
    </style>
    <?php
}
/**
 * TTP Blog Filter Bar — Persistent Filter Bar
 * Add this to functions.php OR as a must-use plugin
 *
 * Keeps the blog category filter bar visible on:
 *  - /blog/ (archive)
 *  - /category/xyz/ (category pages)
 *  - Single blog posts
 */

// ─────────────────────────────────────────
// BLOG FILTER BAR: Inject above all blog/category/single post pages
add_action( 'astra_content_top', 'ttp_blog_filter_bar_html' );
function ttp_blog_filter_bar_html() {
	if ( ! is_home() && ! is_category() && ! is_archive() ) return;

	$categories = get_categories( array(
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );

	echo '<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">';

	echo '<style>
	.ttp-blog-hero-bar{background:#1a1a2e;padding:52px 40px 60px;text-align:center;position:relative;overflow:hidden;margin-bottom:-2px;}
	.ttp-blog-hero-bar::after{content:"";position:absolute;bottom:-40px;left:50%;transform:translateX(-50%);width:120%;height:80px;background:#fff;border-radius:50% 50% 0 0/100% 100% 0 0;}
	.ttp-blog-eyebrow{display:inline-block;background:#f5c518;color:#1a1a2e;font-family:"DM Sans",sans-serif;font-size:10px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;padding:5px 14px;border-radius:3px;margin-bottom:16px;}
	.ttp-blog-hero-title{font-family:"Syne",sans-serif!important;color:#fff!important;font-size:clamp(24px,3.5vw,38px)!important;font-weight:800!important;line-height:1.2!important;margin:0 0 10px!important;}
	.ttp-blog-hero-title span{color:#f5c518;}
	.ttp-blog-hero-sub{color:rgba(255,255,255,.6);font-size:15px;margin-bottom:0;font-family:"DM Sans",sans-serif;}
	.ttp-filter-section{background:#fff;border-bottom:1.5px solid #f0f0f0;padding:0 40px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.06);}
	.ttp-filter-inner{max-width:1100px;margin:0 auto;display:flex;align-items:center;overflow-x:auto;scrollbar-width:none;-ms-overflow-style:none;gap:0;}
	.ttp-filter-inner::-webkit-scrollbar{display:none;}
	.ttp-filter-btn{display:inline-flex;align-items:center;gap:7px;padding:18px 18px 16px;font-family:"DM Sans",sans-serif;font-size:13.5px;font-weight:500;color:#777;text-decoration:none!important;border-bottom:2.5px solid transparent;border-top:none;border-left:none;border-right:none;white-space:nowrap;transition:color .18s,border-color .18s;margin-bottom:-1.5px;background:none;}
	.ttp-filter-btn:hover{color:#1a1a2e;border-bottom-color:#ddd;text-decoration:none!important;}
	.ttp-filter-btn.active{color:#1a1a2e!important;font-weight:600;border-bottom-color:#f5c518!important;}
	.ttp-count{display:inline-flex;align-items:center;justify-content:center;background:#f2f2f2;color:#999;font-size:11px;font-weight:600;padding:2px 7px;border-radius:20px;min-width:22px;transition:background .18s,color .18s;}
	.ttp-filter-btn.active .ttp-count{background:#f5c518;color:#1a1a2e;}
	.ttp-search-wrap{margin-left:auto;padding:10px 0;flex-shrink:0;}
	.ttp-search-wrap .search-form{display:flex;gap:0;margin:0;}
	.ttp-search-wrap .search-field{background:#f8f8f8;border:1px solid #eaeaea!important;border-radius:8px 0 0 8px!important;padding:7px 14px!important;font-size:13px!important;color:#555;width:180px;outline:none;box-shadow:none!important;}
	.ttp-search-wrap .search-field:focus{border-color:#f5c518!important;}
	.ttp-search-wrap .search-submit{background:#1a1a2e;color:#fff;border:none;border-radius:0 8px 8px 0;padding:7px 14px;font-size:13px;cursor:pointer;font-weight:600;transition:background .18s;}
	.ttp-search-wrap .search-submit:hover{background:#f5c518;color:#1a1a2e;}
	@media(max-width:768px){.ttp-blog-hero-bar{padding:36px 20px 48px;}.ttp-filter-section{padding:0 16px;}.ttp-search-wrap{display:none;}}
	</style>';

	echo '<div class="ttp-blog-hero-bar">';
	echo '<span class="ttp-blog-eyebrow">The Top Percentile Blog</span>';
	echo '<h1 class="ttp-blog-hero-title">Insights &amp; Strategy for <span>MAH MBA CET 2026</span></h1>';
	echo '<p class="ttp-blog-hero-sub">Expert guides, mock analysis, and top college insights &mdash; all in one place</p>';
	echo '</div>';

	echo '<div class="ttp-filter-section"><div class="ttp-filter-inner">';
	foreach ( $categories as $cat ) {
		$active = is_category( $cat->term_id ) ? 'active' : '';
		echo '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" class="ttp-filter-btn ' . $active . '">'
			. esc_html( $cat->name )
			. '<span class="ttp-count">' . esc_html( $cat->count ) . '</span>'
			. '</a>';
	}
	echo '<div class="ttp-search-wrap">' . get_search_form( false ) . '</div>';
	echo '</div></div>';
}

function ttp_login_script() {
  if ( is_page('login') ) {
    wp_enqueue_script('ttp-login', get_template_directory_uri() . '/assets/js/ttp-login.js', array(), '1.2', true);
  }
}
add_action('wp_enqueue_scripts', 'ttp_login_script');

function hide_login_menu_for_logged_in_users( $classes, $item ) {
    if ( is_user_logged_in() && in_array( 'menu-login-link', $classes ) ) {
        $classes[] = 'hidden-menu-item';
    }
    return $classes;
}
add_filter( 'nav_menu_css_class', 'hide_login_menu_for_logged_in_users', 10, 2 );
add_action('wp_footer', function() {
    if (!is_single()) return;
    ?>
    <div class="ttp-progress-bar" id="ttpReadProgress"></div>
    <script>
    (function(){
        var bar = document.getElementById('ttpReadProgress');
        window.addEventListener('scroll', function(){
            var s = document.documentElement.scrollTop;
            var h = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            bar.style.width = (s/h*100) + '%';
        });
    })();
    </script>
    <?php
});
// Redirect "Return to Shop" to Enrol Now page
add_filter( 'woocommerce_return_to_shop_redirect', function() {
    return 'https://thetoppercentile.co.in/exam/';
});