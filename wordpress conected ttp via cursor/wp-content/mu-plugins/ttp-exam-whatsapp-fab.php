<?php
/**
 * Plugin Name: TTP Exam — WhatsApp floating button
 * Description: Shows a WhatsApp contact FAB on /exam/ with "Not sure? DM us" label.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return bool
 */
function ttp_exam_wa_fab_is_exam_request() {
	if ( function_exists( 'is_page' ) && is_page( [ 'exam', 'enrol-now', 'enroll-now' ] ) ) {
		return true;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return (bool) preg_match( '#/(exam|enrol-now|enroll-now)(/|$|\?)#i', $uri );
}

add_action(
	'wp_footer',
	static function () {
		if ( ! ttp_exam_wa_fab_is_exam_request() ) {
			return;
		}
		?>
		<style>
		/* Hide legacy Elementor-embedded FAB (role on .ttp-wa-fab itself). */
		.ttp-wa-fab[role="region"] {
			display: none !important;
		}
		.ttp-wa-fab-wrap {
			position: fixed;
			bottom: 24px;
			right: 20px;
			z-index: 99999;
			display: flex;
			align-items: center;
			gap: 10px;
			flex-direction: row-reverse;
		}
		.ttp-wa-label {
			background: #fff;
			border: 2px solid #0a0a0a;
			box-shadow: 2px 2px 0 #0a0a0a;
			padding: 8px 14px;
			border-radius: 20px;
			font-size: 13px;
			font-weight: 600;
			color: #0a0a0a;
			white-space: nowrap;
			line-height: 1.2;
		}
		.ttp-wa-fab {
			position: relative;
			flex-shrink: 0;
		}
		.ttp-wa-fab a {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 52px;
			height: 52px;
			background: #25D366;
			border-radius: 50%;
			border: 2.5px solid #0a0a0a;
			box-shadow: 3px 3px 0 #0a0a0a;
			text-decoration: none;
			-webkit-tap-highlight-color: transparent;
			touch-action: manipulation;
			-webkit-transform: translateZ(0);
			transform: translateZ(0);
			transition: transform 0.15s ease, box-shadow 0.15s ease;
		}
		.ttp-wa-fab a:active {
			transform: translate(2px, 2px) translateZ(0);
			box-shadow: 1px 1px 0 #0a0a0a;
		}
		.ttp-wa-fab svg {
			width: 28px;
			height: 28px;
		}
		.ttp-wa-pulse {
			position: absolute;
			inset: -4px;
			border-radius: 50%;
			border: 2px solid #25D366;
			animation: ttpWaPulse 2.4s ease-out infinite;
			pointer-events: none;
		}
		@keyframes ttpWaPulse {
			0%   { transform: scale(1);    opacity: 0.7; }
			100% { transform: scale(1.45); opacity: 0;   }
		}
		@media (max-width: 480px) {
			.ttp-wa-fab-wrap { bottom: 18px; right: 14px; gap: 8px; }
			.ttp-wa-label { font-size: 12px; padding: 6px 10px; }
			.ttp-wa-fab a { width: 48px; height: 48px; }
			.ttp-wa-fab svg { width: 26px; height: 26px; }
		}
		</style>
		<div class="ttp-wa-fab-wrap" role="region" aria-label="<?php esc_attr_e( 'WhatsApp contact', 'ttp-woocommerce' ); ?>">
			<div class="ttp-wa-fab">
				<div style="position: relative; display: inline-block;">
					<div class="ttp-wa-pulse"></div>
					<a href="https://wa.me/918169531832"
					   target="_blank"
					   rel="noopener noreferrer"
					   aria-label="<?php esc_attr_e( 'Chat on WhatsApp — Not sure? DM us', 'ttp-woocommerce' ); ?>"
					   onclick="this.blur()">
						<svg viewBox="0 0 32 32" fill="none" aria-hidden="true">
							<path d="M16 3C8.82 3 3 8.82 3 16c0 2.3.62 4.46 1.7 6.32L3 29l6.84-1.67A13 13 0 0016 29c7.18 0 13-5.82 13-13S23.18 3 16 3z" fill="#fff"/>
							<path d="M21.5 18.9c-.3-.15-1.76-.87-2.03-.97-.28-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.27-.47-2.42-1.5-.9-.8-1.5-1.79-1.68-2.09-.17-.3-.02-.46.13-.61.14-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.03 1-1.03 2.46s1.05 2.85 1.2 3.05c.15.2 2.07 3.16 5.02 4.43.7.3 1.25.48 1.67.62.7.22 1.34.19 1.85.11.56-.09 1.73-.71 1.98-1.4.24-.68.24-1.27.17-1.4-.07-.12-.27-.2-.57-.34z" fill="#25D366"/>
						</svg>
					</a>
				</div>
			</div>
			<span class="ttp-wa-label"><?php esc_html_e( 'Not sure? DM us', 'ttp-woocommerce' ); ?></span>
		</div>
		<?php
	},
	50
);
