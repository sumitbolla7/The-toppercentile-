<?php
/**
 * Plugin Name: TTP Exam Page — White background + course cards
 * Description: Forces /exam/ to show [ttp_enroll_page] on white background with visible products.
 * Version: 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return bool
 */
function ttp_exam_clean_is_exam_page() {
	if ( function_exists( 'is_page' ) && is_page( [ 'exam', 'enrol-now', 'enroll-now' ] ) ) {
		return true;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return (bool) preg_match( '#/(exam|enrol-now|enroll-now)(/|$|\?)#i', $uri );
}

/**
 * Build enroll markup once per request.
 *
 * @return string
 */
function ttp_exam_clean_build_markup() {
	static $html = null;
	if ( null !== $html ) {
		return $html;
	}
	$html = do_shortcode( '[ttp_enroll_page show_hero="0"]' );
	return $html;
}

/**
 * Output markup only once (avoids duplicate headers/cards).
 *
 * @return string
 */
function ttp_exam_clean_get_markup_once() {
	static $used = false;
	if ( $used ) {
		return '';
	}
	$used = true;
	return ttp_exam_clean_build_markup();
}

/**
 * @param string $content Post content.
 * @return string
 */
function ttp_exam_clean_replace_content( $content ) {
	if ( ! ttp_exam_clean_is_exam_page() ) {
		return $content;
	}
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}
	if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
		return $content;
	}
	$markup = ttp_exam_clean_get_markup_once();
	return $markup !== '' ? $markup : $content;
}

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( ttp_exam_clean_is_exam_page() ) {
			$classes[] = 'ttp-enroll-landing';
			$classes[] = 'ttp-enroll-premium';
			$classes[] = 'ttp-exam-clean';
		}
		return $classes;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		if ( ! ttp_exam_clean_is_exam_page() ) {
			return;
		}
		if ( class_exists( 'TTP_Enroll_Page' ) ) {
			TTP_Enroll_Page::enqueue_enroll_styles();
		}
	},
	5
);

add_filter( 'the_content', 'ttp_exam_clean_replace_content', 1 );
add_filter( 'elementor/frontend/the_content', 'ttp_exam_clean_replace_content', 1 );

add_action(
	'wp_head',
	static function () {
		if ( ! ttp_exam_clean_is_exam_page() ) {
			return;
		}
		$ver = defined( 'TTP_VERSION' ) ? TTP_VERSION : '2.9.5';
		?>
		<!-- TTP exam UI <?php echo esc_attr( $ver ); ?> -->
		<style id="ttp-exam-clean-ui">
			html,
			body.ttp-exam-clean,
			body.ttp-exam-clean #page,
			body.ttp-exam-clean .site,
			body.ttp-exam-clean .site-content,
			body.ttp-exam-clean #content,
			body.ttp-exam-clean main,
			body.ttp-exam-clean article,
			body.ttp-exam-clean .entry-content,
			body.ttp-exam-clean .elementor,
			body.ttp-exam-clean .elementor-section,
			body.ttp-exam-clean .elementor-top-section,
			body.ttp-exam-clean .e-con,
			body.ttp-exam-clean .e-con-inner,
			body.ttp-exam-clean .elementor-column,
			body.ttp-exam-clean .elementor-widget-wrap,
			body.ttp-exam-clean .elementor-widget-container,
			body.ttp-exam-clean .elementor-widget-shortcode,
			body.ttp-exam-clean .elementor-widget-heading {
				background: #ffffff !important;
				background-color: #ffffff !important;
				background-image: none !important;
				color: #111827 !important;
			}
			body.ttp-exam-clean .entry-title,
			body.ttp-exam-clean .page-title,
			body.ttp-exam-clean h1.entry-title {
				display: none !important;
			}
			body.ttp-exam-clean .elementor-widget-shortcode,
			body.ttp-exam-clean .elementor-widget-shortcode .elementor-widget-container {
				overflow: visible !important;
				max-height: none !important;
				height: auto !important;
			}
			body.ttp-exam-clean .ttp-enroll-page {
				display: block !important;
				visibility: visible !important;
				opacity: 1 !important;
				background: #ffffff !important;
			}
			body.ttp-exam-clean .ttp-course-card--premium {
				display: flex !important;
				flex-direction: column !important;
				visibility: visible !important;
				opacity: 1 !important;
				background: #ffffff !important;
			}
			body.ttp-exam-clean .ttp-plans-layout,
			body.ttp-exam-clean .ttp-plans-row {
				display: grid !important;
				visibility: visible !important;
				opacity: 1 !important;
			}
		</style>
		<?php
	},
	999
);

/**
 * If Elementor still shows black + no cards, inject markup once at footer.
 */
add_action(
	'wp_footer',
	static function () {
		if ( ! ttp_exam_clean_is_exam_page() ) {
			return;
		}
		$ver = defined( 'TTP_VERSION' ) ? TTP_VERSION : '2.9.5';
		?>
		<div id="ttp-exam-rescue" hidden aria-hidden="true" data-ver="<?php echo esc_attr( $ver ); ?>">
			<?php echo ttp_exam_clean_build_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<script id="ttp-exam-rescue-js">
		(function () {
			/* Force white — Elementor often sets inline black backgrounds */
			document.querySelectorAll('.elementor-section, .e-con, .e-con-inner, #page, main, article').forEach(function (el) {
				el.style.setProperty('background', '#ffffff', 'important');
				el.style.setProperty('background-color', '#ffffff', 'important');
			});

			var cards = document.querySelectorAll('.ttp-course-card--premium');
			if (cards.length >= 2) {
				var rescue = document.getElementById('ttp-exam-rescue');
				if (rescue) { rescue.remove(); }
				/* Keep the enroll block with the most cards if duplicated */
				var pages = document.querySelectorAll('.ttp-enroll-page');
				if (pages.length > 1) {
					var best = pages[0], bestN = best.querySelectorAll('.ttp-course-card--premium').length;
					for (var i = 1; i < pages.length; i++) {
						var n = pages[i].querySelectorAll('.ttp-course-card--premium').length;
						if (n > bestN) {
							if (best.parentNode) { best.parentNode.removeChild(best); }
							best = pages[i];
							bestN = n;
						} else if (pages[i].parentNode) {
							pages[i].parentNode.removeChild(pages[i]);
						}
					}
				}
				return;
			}

			var rescue = document.getElementById('ttp-exam-rescue');
			if (!rescue) { return; }
			rescue.removeAttribute('hidden');
			rescue.style.display = 'block';
			rescue.setAttribute('aria-hidden', 'false');
			var target = document.querySelector('.entry-content') || document.querySelector('main') || document.querySelector('#content') || document.body;
			target.insertBefore(rescue, target.firstChild);
		})();
		</script>
		<?php
	},
	50
);
