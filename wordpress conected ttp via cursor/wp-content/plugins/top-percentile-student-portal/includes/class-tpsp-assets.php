<?php
/**
 * Asset loader.
 */

if (!defined('ABSPATH')) {
    exit;
}

class TPSP_Assets {
    /**
     * Register hooks.
     *
     * @return void
     */
    public function hooks() {
        add_action('init', [$this, 'prevent_page_cache_for_logged_in_users'], 0);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_filter('nav_menu_item_title', [$this, 'normalize_nav_menu_titles'], 5, 2);
        add_filter('nav_menu_item_title', [$this, 'filter_nav_login_to_my_account_title'], 10, 2);
        add_filter('nav_menu_css_class', [$this, 'add_nav_auth_menu_item_class'], 10, 4);
        add_filter('nav_menu_link_attributes', [$this, 'add_nav_auth_capsule_link_attrs'], 8, 2);
        add_filter('nav_menu_link_attributes', [$this, 'filter_nav_login_to_my_account_link'], 10, 2);
        add_action('wp_head', [$this, 'print_nav_auth_capsule_styles'], 25);
        add_action('wp_footer', [$this, 'print_nav_my_account_script'], 99);
        add_action('wp_footer', [$this, 'print_session_aware_nav_script'], 100);
        add_action('wp_head', [$this, 'inject_login_page_anti_flash'], 0);
        add_action('wp_head', [$this, 'inject_exam_popup_kill_switch'], 1);
        add_action('wp_head', [$this, 'inject_order_received_access_strip'], 999);
        add_action('wp_footer', [$this, 'inject_cart_checkout_navigation_fix'], 40);
        add_action('wp_footer', [$this, 'inject_order_received_access_strip_js'], 999);
        add_action('wp_footer', [$this, 'inject_strip_orphan_single_product_notices'], 999);
    }

    /**
     * Only strip TCY access UI when the order is not eligible (unpaid / wrong viewer).
     * Paid processing/completed orders must show TTP WooCommerce course access controls.
     *
     * @return bool
     */
    private function should_inject_order_received_access_strip() {
        $uri = isset($_SERVER['REQUEST_URI']) ? strtolower((string) wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (false === strpos($uri, '/checkout/order-received/')) {
            return false;
        }
        if (!function_exists('wc_get_order')) {
            return true;
        }
        $order = $this->get_order_from_order_received_request();
        if (!$order instanceof \WC_Order) {
            return true;
        }
        if (!$this->viewer_can_see_order_thankyou($order)) {
            return true;
        }
        if (function_exists('ttp_order_qualifies_for_tcy_actions') && ttp_order_qualifies_for_tcy_actions($order)) {
            return false;
        }

        return true;
    }

    /**
     * @return \WC_Order|null
     */
    private function get_order_from_order_received_request() {
        if (!function_exists('wc_get_order')) {
            return null;
        }
        $order_id = 0;
        if (function_exists('get_query_var')) {
            $order_id = absint(get_query_var('order-received'));
        }
        if (!$order_id && !empty($_SERVER['REQUEST_URI'])) {
            if (preg_match('#/checkout/order-received/(\d+)#i', (string) wp_unslash($_SERVER['REQUEST_URI']), $m)) {
                $order_id = absint($m[1]);
            }
        }
        if ($order_id <= 0) {
            return null;
        }
        $order = wc_get_order($order_id);

        return $order instanceof \WC_Order ? $order : null;
    }

    /**
     * @param \WC_Order $order Order.
     * @return bool
     */
    private function viewer_can_see_order_thankyou(\WC_Order $order) {
        $key = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
        if ($key !== '' && $order->key_is_valid($key)) {
            return true;
        }
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {
            $cid = (int) $order->get_user_id();
            if ($cid > 0 && $cid === (int) get_current_user_id()) {
                return true;
            }
        }

        return (bool) apply_filters('ttp_thankyou_redirect_allow_without_key', false, $order);
    }

    /**
     * Hard-hide leaked success/access UI on unsafe order-received views only.
     *
     * @return void
     */
    public function inject_order_received_access_strip() {
        if (!$this->should_inject_order_received_access_strip()) {
            return;
        }
        ?>
        <style id="tpsp-order-received-access-strip">
            .ttp-purchase-thankyou,
            .ttp-success-box,
            .tpsp-thankyou,
            .ttp-access-btn,
            a[href*="tcyonline"],
            a[href*="erp_request.php"],
            [class*="access-course"],
            [id*="access-course"] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        </style>
        <?php
    }

    /**
     * Remove leaked success/access nodes and block click-through links.
     *
     * @return void
     */
    public function inject_order_received_access_strip_js() {
        if (!$this->should_inject_order_received_access_strip()) {
            return;
        }
        ?>
        <script id="tpsp-order-received-access-strip-js">
        (function () {
            function removeUnsafeNodes() {
                var selectors = [
                    '.ttp-purchase-thankyou',
                    '.ttp-success-box',
                    '.tpsp-thankyou',
                    '.ttp-access-btn',
                    'a[href*="tcyonline"]',
                    'a[href*="erp_request.php"]',
                    '[class*="access-course"]',
                    '[id*="access-course"]'
                ];
                selectors.forEach(function (s) {
                    document.querySelectorAll(s).forEach(function (el) { el.remove(); });
                });

                document.querySelectorAll('h1,h2,h3,p,div,button,a,span').forEach(function (el) {
                    var t = (el.textContent || '').toLowerCase();
                    if (
                        t.indexOf('payment successful') !== -1 ||
                        t.indexOf('login & access') !== -1 ||
                        t.indexOf('study portal') !== -1 ||
                        t.indexOf('your course is now active') !== -1 ||
                        t.indexOf('enrollment confirmed') !== -1
                    ) {
                        el.remove();
                    }
                });
            }

            document.addEventListener('click', function (e) {
                var target = e.target && e.target.closest ? e.target.closest('.ttp-access-btn, a[href*="tcyonline"], a[href*="erp_request.php"], [class*="access-course"], [id*="access-course"]') : null;
                if (!target) return;
                e.preventDefault();
                e.stopPropagation();
                if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
                alert('Course access is available only after successful payment.');
            }, true);

            removeUnsafeNodes();
            setTimeout(removeUnsafeNodes, 300);
            setTimeout(removeUnsafeNodes, 1000);
            setTimeout(removeUnsafeNodes, 2200);
        })();
        </script>
        <?php
    }

    /**
     * Remove stray Woo notices above the summary card and keep WooCommerce URLs clickable.
     *
     * @return void
     */
    public function inject_strip_orphan_single_product_notices() {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }
        ?>
        <script id="tpsp-strip-stray-product-notices">
        (function () {
            function outsideCard(el) {
                return !el.closest(".ttp-course-summary-card");
            }
            function prune() {
                document.querySelectorAll(".woocommerce-notices-wrapper").forEach(function (wrap) {
                    if (!outsideCard(wrap)) return;
                    wrap.remove();
                });
                document.querySelectorAll(".woocommerce-error, .woocommerce-message, .woocommerce-info").forEach(function (el) {
                    if (!outsideCard(el)) return;
                    el.remove();
                });
            }
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", prune);
            } else {
                prune();
            }
            if (typeof jQuery !== "undefined") {
                jQuery(document.body).on("wc_fragment_refresh wc_fragments_loaded added_to_cart", prune);
            }
        })();
        </script>
        <?php
    }

    /**
     * Undo third-party handlers that kill checkout navigation (coupon plugins disabling .checkout-button).
     *
     * @return void
     */
    public function inject_cart_checkout_navigation_fix() {
        if (!function_exists('is_cart') || !is_cart()) {
            return;
        }
        if (function_exists('ttp_guest_checkout_url_with_cart')) {
            $checkout_url = ttp_guest_checkout_url_with_cart();
        } elseif (function_exists('ttp_guest_checkout_direct_url')) {
            $checkout_url = ttp_guest_checkout_direct_url();
        } else {
            $checkout_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('checkout') : home_url('/checkout/');
        }
        ?>
        <script id="tpsp-cart-force-leave">
        (function () {
            var checkoutUrl = <?php echo wp_json_encode($checkout_url); ?>;
            if (!checkoutUrl) {
                return;
            }
            document.addEventListener(
                "click",
                function (e) {
                    var t = e.target;
                    if (!t || !t.closest) {
                        return;
                    }
                    var el = t.closest(
                        "a.ttp-cart__checkout, #tpsp-proceed-checkout, a.tpsp-proceed-checkout, .wc-proceed-to-checkout a.checkout-button, a.checkout-button.wc-forward, a.wc-block-cart__submit-button, .wc-block-cart__submit-container a.checkout-button, button.tpsp-proceed-checkout-submit"
                    );
                    if (!el) {
                        return;
                    }
                    e.preventDefault();
                    if (typeof e.stopImmediatePropagation === "function") {
                        e.stopImmediatePropagation();
                    }
                    e.stopPropagation();
                    window.location.assign(el.getAttribute("href") || checkoutUrl);
                },
                true
            );
        })();
        </script>
        <style id="tpsp-cart-proceed-always-clickable">
            .woocommerce-cart .wc-proceed-to-checkout .checkout-button,
            .woocommerce-cart .wc-proceed-to-checkout a.checkout-button,
            .woocommerce-cart a.checkout-button.button.alt,
            .woocommerce-cart a.tpsp-proceed-checkout,
            .woocommerce-cart #tpsp-proceed-checkout,
            .woocommerce-cart a.ttp-cart__checkout {
                pointer-events: auto !important;
                opacity: 1 !important;
                cursor: pointer !important;
            }
            /* TTP custom cart: never show yellow floating fallback. */
            body.woocommerce-cart .ttp-cart-page ~ #tpsp-floating-checkout,
            body.woocommerce-cart #tpsp-floating-checkout { display: none !important; }
        </style>
        <script id="tpsp-unstick-checkout-navigation">
        (function ($) {
            var checkoutUrl = <?php echo wp_json_encode($checkout_url); ?>;
            var selectors = [
                "a.ttp-cart__checkout",
                "a.checkout-button",
                "button.checkout-button",
                ".checkout-button",
                ".woocommerce-checkout-review-order-table ~ .checkout .button",
                ".wc-proceed-to-checkout a",
                "a.tpsp-proceed-checkout",
                "#tpsp-proceed-checkout",
                ".wc-block-cart__submit-container a",
                ".wp-block-woocommerce-proceed-to-checkout-block a"
            ].join(", ");

            function fixNav() {
                $(selectors).each(function () {
                    var el = this;
                    el.removeAttribute("disabled");
                    el.removeAttribute("aria-disabled");
                    if (typeof $ !== "undefined" && $.fn.prop) {
                        $(el).prop("disabled", false).removeProp("disabled");
                    }
                    el.style.opacity = "";
                    el.style.pointerEvents = "";
                    el.style.cursor = "";
                    if (el.tagName && el.tagName.toLowerCase() === "a") {
                        var href = (el.getAttribute("href") || "").trim();
                        if ((!href || href === "#") && checkoutUrl) {
                            el.setAttribute("href", checkoutUrl);
                        }
                    }
                });
            }

            fixNav();

            if (typeof jQuery !== "undefined") {
                jQuery(document.body).on(
                    "updated_cart_totals updated_wc_div wc_fragments_loaded wc_fragment_refresh",
                    fixNav
                );
            }

            /* Smart Coupons and similar scripts can re-disable the button after AJAX; re-apply briefly after load. */
            var n = 0;
            var iv = setInterval(function () {
                fixNav();
                n++;
                if (n >= 25) {
                    clearInterval(iv);
                }
            }, 400);

            /* TTP custom cart page already has .ttp-cart__checkout — do not inject yellow floating button. */
            setTimeout(function () {
                if (document.querySelector(".ttp-cart-page a.ttp-cart__checkout")) {
                    var floater = document.getElementById("tpsp-floating-checkout");
                    if (floater && floater.parentNode) {
                        floater.parentNode.removeChild(floater);
                    }
                    return;
                }
                if (!checkoutUrl) {
                    return;
                }
                if (document.getElementById("tpsp-floating-checkout")) {
                    return;
                }
                var candidates = document.querySelectorAll(
                    "a.ttp-cart__checkout, a.checkout-button, a.tpsp-proceed-checkout, #tpsp-proceed-checkout, a.wc-block-cart__submit-button, .wc-block-cart__submit-container a"
                );
                var i, ok = false;
                for (i = 0; i < candidates.length; i++) {
                    var h = (candidates[i].getAttribute("href") || "").toLowerCase();
                    if (
                        h !== "#" &&
                        h.indexOf("javascript:") === -1 &&
                        h.indexOf("checkout") !== -1
                    ) {
                        ok = true;
                        break;
                    }
                }
                if (ok) {
                    return;
                }
                var a = document.createElement("a");
                a.id = "tpsp-floating-checkout";
                a.href = checkoutUrl;
                a.textContent = "Proceed to checkout";
                a.setAttribute("role", "button");
                a.style.cssText =
                    "position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:999999;" +
                    "padding:14px 28px;background:#f5c518;color:#111;font:800 15px system-ui,sans-serif;" +
                    "border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.4);text-decoration:none;";
                document.body.appendChild(a);
            }, 2200);
        })(typeof jQuery !== "undefined" ? jQuery : function () {});
        </script>
        <?php
    }

    /**
     * @return string
     */
    private function my_account_profile_url() {
        if (function_exists('tpsp_get_login_page_url')) {
            return tpsp_get_login_page_url();
        }

        return home_url('/login/');
    }

    /**
     * @param mixed $item Menu item.
     * @return bool
     */
    private function is_header_login_menu_item($item) {
        if (!is_object($item)) {
            return false;
        }

        $title = isset($item->title) ? strtolower(trim(wp_strip_all_tags($item->title))) : '';
        if (in_array($title, ['login', 'log in', 'logout', 'log out', 'my account'], true)) {
            return true;
        }

        $url = isset($item->url) ? strtolower((string) $item->url) : '';

        return '' !== $url && false !== strpos($url, 'action=logout');
    }

    /**
     * @param string $title Menu title.
     * @param mixed  $item  Menu item.
     * @return string
     */
    public function normalize_nav_menu_titles($title, $item) {
        unset($item);
        $plain = trim(wp_strip_all_tags((string) $title));
        if ('FREE RESOURCES' === strtoupper($plain)) {
            return 'Free Resources';
        }

        return $title;
    }

    /**
     * @param string $title Menu title.
     * @param mixed  $item  Menu item.
     * @return string
     */
    public function filter_nav_login_to_my_account_title($title, $item) {
        if (!is_user_logged_in() || !$this->is_header_login_menu_item($item)) {
            return $title;
        }

        return __('My Account', 'tpsp');
    }

    /**
     * @param array    $classes CSS classes.
     * @param WP_Post  $item    Menu item.
     * @param stdClass $args    Menu args.
     * @param int      $depth   Depth.
     * @return array
     */
    public function add_nav_auth_menu_item_class($classes, $item, $args, $depth) {
        if ((int) $depth > 0 || !$this->is_header_login_menu_item($item)) {
            return $classes;
        }

        $classes[] = 'tpsp-nav-auth-menu-item';

        return $classes;
    }

    /**
     * @param array $atts Link attributes.
     * @param mixed $item Menu item.
     * @return array
     */
    public function add_nav_auth_capsule_link_attrs($atts, $item) {
        if (!$this->is_header_login_menu_item($item)) {
            return $atts;
        }

        $existing       = isset($atts['class']) ? trim((string) $atts['class']) : '';
        $atts['class']  = trim($existing . ' tpsp-nav-auth-capsule');
        $atts['role']   = 'button';

        return $atts;
    }

    /**
     * @param array $atts Link attributes.
     * @param mixed $item Menu item.
     * @return array
     */
    public function filter_nav_login_to_my_account_link($atts, $item) {
        if (!is_user_logged_in() || !$this->is_header_login_menu_item($item)) {
            return $atts;
        }

        $atts['href']                  = $this->my_account_profile_url();
        $atts['title']                 = esc_attr__('Open your student account', 'tpsp');
        $atts['data-tpsp-profile-nav'] = '1';

        return $this->add_nav_auth_capsule_link_attrs($atts, $item);
    }

    /**
     * Header Login / My Account pill button (black capsule, white text).
     *
     * @return void
     */
    public function print_nav_auth_capsule_styles() {
        if (is_admin()) {
            return;
        }
        ?>
        <style id="tpsp-nav-auth-capsule-css">
            .main-header-menu .tpsp-nav-auth-menu-item > a,
            .ast-header-menu .tpsp-nav-auth-menu-item > a,
            a.tpsp-nav-auth-capsule,
            a[data-tpsp-profile-nav="1"] {
                background: #101010 !important;
                color: #ffffff !important;
                border-radius: 9999px !important;
                padding: 5px 14px !important;
                min-height: 0 !important;
                height: auto !important;
                font-weight: 700 !important;
                font-size: 14px !important;
                line-height: 1.25 !important;
                text-decoration: none !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                white-space: nowrap !important;
                border: 1px solid #101010 !important;
                box-shadow: none !important;
                letter-spacing: 0.01em;
            }
            .main-header-menu .tpsp-nav-auth-menu-item > a:hover,
            .main-header-menu .tpsp-nav-auth-menu-item > a:focus,
            a.tpsp-nav-auth-capsule:hover,
            a.tpsp-nav-auth-capsule:focus,
            a[data-tpsp-profile-nav="1"]:hover,
            a[data-tpsp-profile-nav="1"]:focus {
                background: #2a2a2a !important;
                color: #ffffff !important;
                border-color: #2a2a2a !important;
            }
            .main-header-menu .tpsp-nav-auth-menu-item,
            .ast-header-menu .tpsp-nav-auth-menu-item {
                margin-left: 6px;
            }
            .ast-mobile-popup-content a.tpsp-nav-auth-capsule,
            .ast-mobile-popup-content a[data-tpsp-profile-nav="1"],
            .elementor-nav-menu--dropdown a.tpsp-nav-auth-capsule {
                margin-top: 8px;
                text-align: center;
            }
        </style>
        <?php
    }

    /**
     * Stop full-page cache from serving guest HTML to logged-in members (Hostinger LiteSpeed).
     *
     * @return void
     */
    public function prevent_page_cache_for_logged_in_users() {
        if (!is_user_logged_in()) {
            return;
        }

        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        if (has_action('litespeed_control_set_nocache')) {
            do_action('litespeed_control_set_nocache', 'tpsp-logged-in-user');
        }
    }

    /**
     * Hide login form before paint when a WP auth cookie exists (stops 1s flash on /login/).
     *
     * @return void
     */
    public function inject_login_page_anti_flash() {
        if (!class_exists('TPSP_Dashboard', false) || !TPSP_Dashboard::is_login_page_request()) {
            return;
        }

        $ajax_url = admin_url('admin-ajax.php');
        ?>
        <style id="tpsp-login-anti-flash-css">
            html.tpsp-login-pending .user-registration-form-login,
            html.tpsp-login-pending .ur-frontend-form,
            html.tpsp-login-pending form.login,
            html.tpsp-login-pending .woocommerce-form-login,
            html.tpsp-login-pending .woocommerce-form-register,
            html.tpsp-login-pending #user-registration {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                max-height: 0 !important;
                overflow: hidden !important;
                opacity: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                pointer-events: none !important;
            }
            html.tpsp-login-pending h1.entry-title,
            html.tpsp-login-pending .entry-header {
                visibility: hidden !important;
                height: 0 !important;
                margin: 0 !important;
                overflow: hidden !important;
            }
            html.tpsp-login-pending .tpsp-login-loading-placeholder {
                display: block;
                text-align: center;
                padding: 3rem 1.5rem;
                color: #555;
                font-size: 1rem;
            }
            html.tpsp-panel-ready .tpsp-login-loading-placeholder {
                display: none !important;
            }
        </style>
        <script id="tpsp-login-anti-flash-js">
        (function () {
            var path = (location.pathname || '').toLowerCase();
            if (path.indexOf('/login') === -1) {
                return;
            }
            if (document.cookie.indexOf('wordpress_logged_in') === -1) {
                return;
            }
            document.documentElement.classList.add('tpsp-login-pending');
            var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
            window.tpspLoginPanelPromise = fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=tpsp_login_panel_html'
            }).then(function (r) {
                return r.json();
            });
        })();
        </script>
        <?php
    }

    /**
     * Fix header Login/My Account on cached pages using the real PHP session.
     *
     * @return void
     */
    public function print_session_aware_nav_script() {
        $login_url = function_exists('tpsp_get_login_page_url') ? tpsp_get_login_page_url() : home_url('/login/');
        $ajax_url  = admin_url('admin-ajax.php');
        ?>
        <script id="tpsp-session-nav-sync">
        (function () {
            var ajaxUrl = <?php echo wp_json_encode($ajax_url); ?>;
            var loginUrl = <?php echo wp_json_encode($login_url); ?>;

            function isAuthNavLink(a) {
                if (!a || a.getAttribute('data-tpsp-profile-nav') === '1') {
                    return true;
                }
                var txt = (a.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                if (txt !== 'login' && txt !== 'log in' && txt !== 'logout' && txt !== 'log out' && txt !== 'my account') {
                    return false;
                }
                var href = (a.getAttribute('href') || '').toLowerCase();
                if (href.indexOf('action=logout') !== -1 || href.indexOf('customer-logout') !== -1) {
                    return false;
                }
                return txt === 'login' || txt === 'log in' || txt === 'my account' || href.indexOf('/login') !== -1;
            }

            function hideLoginFormsOnPage() {
                var selectors = '.user-registration-form-login,form.login,.woocommerce-form-login,.woocommerce-form-register,#user-registration';
                document.querySelectorAll(selectors).forEach(function (el) {
                    el.style.setProperty('display', 'none', 'important');
                    el.style.setProperty('visibility', 'hidden', 'important');
                });
                document.querySelectorAll('h1,h2').forEach(function (h) {
                    var t = (h.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                    if (t === 'login' || t === 'log in') {
                        h.style.setProperty('display', 'none', 'important');
                    }
                });
            }

            function markLoginPanelReady() {
                document.documentElement.classList.remove('tpsp-login-pending');
                document.documentElement.classList.add('tpsp-panel-ready');
            }

            function injectStudentPanel(html) {
                if (!html || document.querySelector('.tpsp-login-dashboard')) {
                    hideLoginFormsOnPage();
                    markLoginPanelReady();
                    return;
                }
                if (document.getElementById('tpsp-ajax-login-panel')) {
                    hideLoginFormsOnPage();
                    markLoginPanelReady();
                    return;
                }
                var wrap = document.createElement('div');
                wrap.id = 'tpsp-ajax-login-panel';
                wrap.className = 'tpsp-login-page-panel-wrap';
                wrap.innerHTML = html;
                var main = document.querySelector('.site-content, .entry-content, main, .elementor-location-single, article.page');
                if (main) {
                    var placeholder = main.querySelector('.tpsp-login-loading-placeholder');
                    if (placeholder) {
                        main.insertBefore(wrap, placeholder);
                    } else {
                        main.insertBefore(wrap, main.firstChild);
                    }
                } else {
                    document.body.insertBefore(wrap, document.body.firstChild);
                }
                document.body.classList.add('tpsp-show-student-panel', 'logged-in');
                hideLoginFormsOnPage();
                markLoginPanelReady();
            }

            function handleLoginPanelResponse(res) {
                if (res && res.success && res.data && res.data.html) {
                    injectStudentPanel(res.data.html);
                } else {
                    document.documentElement.classList.remove('tpsp-login-pending');
                }
            }

            function loadStudentPanelOnLoginPage(loggedIn) {
                if (!loggedIn) {
                    document.documentElement.classList.remove('tpsp-login-pending');
                    return;
                }
                var path = (window.location.pathname || '').toLowerCase();
                if (path.indexOf('/login') === -1) {
                    return;
                }
                if (document.querySelector('.tpsp-login-dashboard')) {
                    document.body.classList.add('tpsp-show-student-panel', 'logged-in');
                    hideLoginFormsOnPage();
                    markLoginPanelReady();
                    return;
                }
                if (window.tpspLoginPanelPromise) {
                    window.tpspLoginPanelPromise.then(handleLoginPanelResponse).catch(function () {
                        document.documentElement.classList.remove('tpsp-login-pending');
                    });
                    return;
                }
                fetch(ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=tpsp_login_panel_html'
                })
                    .then(function (r) { return r.json(); })
                    .then(handleLoginPanelResponse)
                    .catch(function () {
                        document.documentElement.classList.remove('tpsp-login-pending');
                    });
            }

            function applyNavState(loggedIn, profileUrl) {
                if (loggedIn) {
                    document.body.classList.add('logged-in');
                } else {
                    document.body.classList.remove('logged-in');
                    document.body.classList.remove('tpsp-show-student-panel');
                }

                var label = loggedIn ? 'My Account' : 'Login';
                var target = loggedIn ? profileUrl : loginUrl;

                document.querySelectorAll('a').forEach(function (a) {
                    if (a.closest && (a.closest('#wpadminbar') || a.closest('.ttp-plan-card'))) {
                        return;
                    }
                    if (!isAuthNavLink(a)) {
                        return;
                    }
                    a.textContent = label;
                    a.href = target;
                    a.setAttribute('data-tpsp-profile-nav', loggedIn ? '1' : '0');
                    a.classList.add('tpsp-nav-auth-capsule');
                });

                window.tpspSessionLoggedIn = loggedIn;
                loadStudentPanelOnLoginPage(loggedIn);

                if (typeof window.jQuery !== 'undefined') {
                    window.jQuery(document.body).trigger('tpsp:session-synced', [{ loggedIn: loggedIn }]);
                }
            }

            var body = 'action=tpsp_session_status';
            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || !res.success || !res.data) {
                        return;
                    }
                    applyNavState(!!res.data.logged_in, res.data.profile_url || loginUrl);
                })
                .catch(function () {});
        })();
        </script>
        <?php
    }

    /**
     * Elementor header menus may ignore WP nav filters.
     *
     * @return void
     */
    public function print_nav_my_account_script() {
        if (!is_user_logged_in()) {
            return;
        }

        $profile_url = $this->my_account_profile_url();
        ?>
        <script id="tpsp-nav-my-account">
        (function () {
            var profileUrl = <?php echo wp_json_encode($profile_url); ?>;
            var label = "My Account";
            function fix() {
                document.querySelectorAll('a[data-tpsp-profile-nav="1"]').forEach(function (a) {
                    a.textContent = label;
                    a.href = profileUrl;
                });
                document.querySelectorAll('a').forEach(function (a) {
                    if (a.getAttribute('data-tpsp-profile-nav') === '1' || (a.closest && a.closest('#wpadminbar'))) {
                        return;
                    }
                    var txt = (a.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
                    if (txt !== 'login' && txt !== 'log in' && txt !== 'logout' && txt !== 'log out') {
                        return;
                    }
                    var href = (a.getAttribute('href') || '').toLowerCase();
                    if (href.indexOf('action=logout') !== -1 || href.indexOf('customer-logout') !== -1) {
                        return;
                    }
                    a.textContent = label;
                    a.href = profileUrl;
                    a.setAttribute('data-tpsp-profile-nav', '1');
                    a.classList.add('tpsp-nav-auth-capsule');
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fix);
            } else {
                fix();
            }
        })();
        </script>
        <?php
    }

    /**
     * Enqueue frontend CSS and JS.
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        if (is_admin()) {
            return;
        }

        wp_enqueue_style(
            'tpsp-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
            [],
            '6.5.2'
        );

        wp_enqueue_style(
            'tpsp-frontend',
            TPSP_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            TPSP_VERSION
        );

        wp_enqueue_script(
            'tpsp-frontend',
            TPSP_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            TPSP_VERSION,
            true
        );

        $wc_ajax_url = function_exists('WC') && class_exists('WC_AJAX')
            ? WC_AJAX::get_endpoint('%%endpoint%%')
            : '';
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('checkout') : home_url('/checkout/'));
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

        $current_product_id = 0;
        $product_in_cart    = 0;
        if (is_product()) {
            $current_product_id = (int) get_queried_object_id();
            if ($current_product_id > 0 && function_exists('WC') && WC()->cart && WC()->cart->get_cart_contents_count() >= 1) {
                $cart_uid = WC()->cart->generate_cart_id($current_product_id, 0, [], []);
                $product_in_cart = WC()->cart->find_product_in_cart($cart_uid) ? 1 : 0;
            }
        }

        $purchased_product_ids    = [];
        $purchased_course_names   = [];
        $current_product_name     = '';
        if (is_product()) {
            $current_product_name = get_the_title(get_the_ID());
        }
        if (is_user_logged_in() && function_exists('wc_get_orders')) {
            $orders = wc_get_orders([
                'customer_id' => get_current_user_id(),
                'limit'       => -1,
                'status'      => array_keys(wc_get_order_statuses()),
            ]);

            foreach ($orders as $order) {
                foreach ($order->get_items() as $item) {
                    $product_id = (int) $item->get_product_id();
                    if ($product_id > 0) {
                        $purchased_product_ids[] = $product_id;
                    }
                    $item_name = trim((string) $item->get_name());
                    if ($item_name !== '') {
                        $purchased_course_names[] = $item_name;
                    }
                }
            }
            $purchased_product_ids = array_values(array_unique($purchased_product_ids));
            $purchased_course_names = array_values(array_unique($purchased_course_names));
        }

        wp_localize_script(
            'tpsp-frontend',
            'tpspData',
            [
                'ajaxUrl'         => admin_url('admin-ajax.php'),
                'loginUrl'        => function_exists('tpsp_get_login_page_url') ? tpsp_get_login_page_url() : home_url('/login/'),
                'myCoursesUrl'    => function_exists('tpsp_get_account_endpoint_url') ? tpsp_get_account_endpoint_url('my-courses') : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/')),
                'checkoutUrl'     => $checkout_url,
                'cartUrl'        => $cart_url,
                'currentProductId' => $current_product_id,
                'productInCart'  => $product_in_cart,
                'isLoggedIn'      => is_user_logged_in() ? 1 : 0,
                'isSingleProduct' => is_product() ? 1 : 0,
                'isPurchasedCourse' => (is_user_logged_in() && is_product() && function_exists('wc_customer_bought_product')) ? (wc_customer_bought_product('', get_current_user_id(), get_the_ID()) ? 1 : 0) : 0,
                'purchasedProductIds' => $purchased_product_ids,
                'purchasedCourseNames' => $purchased_course_names,
                'currentProductName' => $current_product_name,
                'nonce'           => wp_create_nonce('tpsp_nonce'),
                'wcAjaxUrl'       => $wc_ajax_url,
                'spinnerText'     => __('Adding...', 'tpsp'),
                'successText'     => __('Added to cart successfully!', 'tpsp'),
                'verificationSent'=> __('Verification email sent.', 'tpsp'),
            ]
        );
    }

    /**
     * Hard-disable exam signup popup and force login/checkout redirects.
     *
     * @return void
     */
    public function inject_exam_popup_kill_switch() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (false === strpos($request_uri, '/exam/')) {
            return;
        }

        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
        $login_url    = function_exists('tpsp_get_login_page_url') ? tpsp_get_login_page_url() : home_url('/login/');
        ?>
        <style id="tpsp-exam-popup-kill">
            #ttpSignupOverlay,
            .ttp-signup-overlay,
            .ttp-signup-modal,
            .ttp-modal-backdrop {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
        </style>
        <script id="tpsp-exam-login-redirect-hardguard">
            (function () {
                var checkoutBase = <?php echo wp_json_encode($checkout_url); ?>;
                var loginBase = <?php echo wp_json_encode($login_url); ?>;
                var isLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;

                function isLoggedInNow() {
                    if (typeof window.tpspSessionLoggedIn !== 'undefined') {
                        return !!window.tpspSessionLoggedIn;
                    }
                    return !!isLoggedIn || document.body.classList.contains('logged-in');
                }

                function buildCheckoutUrl(pid) {
                    if (!pid) return checkoutBase;
                    var sep = checkoutBase.indexOf('?') === -1 ? '?' : '&';
                    return checkoutBase + sep + 'add-to-cart=' + encodeURIComponent(pid);
                }

                function buildLoginUrl(pid) {
                    var target = buildCheckoutUrl(pid);
                    var sep = loginBase.indexOf('?') === -1 ? '?' : '&';
                    return loginBase + sep + 'redirect_to=' + encodeURIComponent(target);
                }

                function enforce(e) {
                    var target = e.target && e.target.closest ? e.target.closest('.ttp-open-signup, .ttp-btn-enroll, .ttp-buy-now-btn') : null;
                    if (!target) return;
                    var pid = target.getAttribute('data-product-id') || '';
                    var dest = isLoggedInNow() ? buildCheckoutUrl(pid) : buildLoginUrl(pid);
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof e.stopImmediatePropagation === 'function') {
                        e.stopImmediatePropagation();
                    }
                    window.location.href = dest;
                }

                document.addEventListener('click', enforce, true);
            })();
        </script>
        <?php
    }
}
