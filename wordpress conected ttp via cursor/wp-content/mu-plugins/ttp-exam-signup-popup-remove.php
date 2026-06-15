<?php
/**
 * Plugin Name: TTP Exam — Remove signup popup
 * Description: Strips the embedded /exam/ signup modal; guests go to /login/, logged-in users to checkout.
 *
 * Must-use: loads on every request; does not depend on other plugins being updated on the server.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return bool
 */
function ttp_exam_popup_is_exam_request() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return ( false !== strpos( $uri, '/exam' ) );
}

/**
 * @return string
 */
function ttp_exam_popup_login_base_url() {
	$login_page = get_page_by_path( 'login' );
	if ( $login_page instanceof WP_Post ) {
		$url = get_permalink( $login_page );
		if ( ! empty( $url ) ) {
			return $url;
		}
	}

	return home_url( '/login/' );
}

/**
 * CSS: beat page-local .ttp-signup-overlay.open { display:flex } and any z-index tricks.
 */
add_action(
	'wp_head',
	static function () {
		if ( ! ttp_exam_popup_is_exam_request() ) {
			return;
		}
		echo '<style id="ttp-exam-popup-nuke-css">'
			. 'html body #ttpSignupOverlay,html body #ttpSignupOverlay.open,'
			. 'html body .ttp-signup-overlay,html body .ttp-signup-overlay.open,'
			. 'html body .ttp-signup-modal{'
			. 'display:none!important;visibility:hidden!important;opacity:0!important;'
			. 'pointer-events:none!important;max-height:0!important;overflow:hidden!important;'
			. 'position:fixed!important;left:-9999px!important;top:-9999px!important;'
			. 'z-index:-1!important;}'
			. '</style>' . "\n";

		$checkout = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
		$login    = ttp_exam_popup_login_base_url();

		// Register capture listener before any inline /exam/ scripts in the body (Elementor).
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$is_logged_in = is_user_logged_in();
		echo '<script id="ttp-exam-enroll-capture-head">'
			. '(function(){'
			. 'var checkoutBase=' . wp_json_encode( $checkout ) . ';'
			. 'var loginBase=' . wp_json_encode( $login ) . ';'
			. 'var isLoggedIn=' . ( $is_logged_in ? 'true' : 'false' ) . ';'
			. 'function isLoggedInNow(){'
			. 'if(typeof window.tpspSessionLoggedIn!=="undefined")return !!window.tpspSessionLoggedIn;'
			. 'return !!isLoggedIn||document.body.classList.contains("logged-in");'
			. '}'
			. 'function checkoutUrl(pid){'
			. 'if(!pid)return checkoutBase;'
			. 'var s=checkoutBase.indexOf("?")===-1?"?":"&";'
			. 'return checkoutBase+s+"add-to-cart="+encodeURIComponent(pid);'
			. '}'
			. 'function loginUrl(pid){'
			. 'var target=checkoutUrl(pid);'
			. 'var sep=loginBase.indexOf("?")===-1?"?":"&";'
			. 'return loginBase+sep+"redirect_to="+encodeURIComponent(target);'
			. '}'
			. 'function go(e){'
			. 'var t=e.target&&e.target.closest&&e.target.closest(".ttp-open-signup,.ttp-btn-enroll,.ttp-buy-now-btn");'
			. 'if(!t)return;'
			. 'var pid=t.getAttribute("data-product-id")||"";'
			. 'var dest=isLoggedInNow()?checkoutUrl(pid):loginUrl(pid);'
			. 'e.preventDefault();e.stopPropagation();'
			. 'if(typeof e.stopImmediatePropagation==="function")e.stopImmediatePropagation();'
			. 'window.location.href=dest;'
			. '}'
			. 'document.addEventListener("click",go,true);'
			. 'document.addEventListener("keydown",function(e){if(e.key==="Enter")go(e);},true);'
			. '})();'
			. '</script>' . "\n";
	},
	99999
);

/**
 * Shared inline script: strip modal nodes + capture-phase enroll → login/checkout.
 *
 * @return void
 */
function ttp_exam_popup_print_inline_script() {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON literals for inline script.
	echo '<script class="ttp-exam-popup-nuke-inline">'
		. '(function(){'
		. 'function strip(){'
		. 'var o=document.getElementById("ttpSignupOverlay");if(o&&o.parentNode)o.parentNode.removeChild(o);'
		. 'document.querySelectorAll(".ttp-signup-overlay").forEach(function(n){if(n.parentNode)n.parentNode.removeChild(n);});'
		. 'document.querySelectorAll(".ttp-signup-modal").forEach(function(n){if(n.parentNode)n.parentNode.removeChild(n);});'
		. '}'
		. 'strip();'
		. 'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",strip);'
		. 'setTimeout(strip,0);setTimeout(strip,300);setTimeout(strip,1000);setTimeout(strip,2500);'
		. 'if(typeof MutationObserver!=="undefined"&&document.documentElement){'
		. 'try{new MutationObserver(function(){strip();}).observe(document.documentElement,{childList:true,subtree:true});}'
		. 'catch(e){}'
		. '}'
		. '})();'
		. '</script>' . "\n";
}

/**
 * Run early in footer: main column HTML (Elementor modal) is already in the document above.
 */
add_action(
	'wp_footer',
	static function () {
		if ( ! ttp_exam_popup_is_exam_request() ) {
			return;
		}
		ttp_exam_popup_print_inline_script();
	},
	1
);
