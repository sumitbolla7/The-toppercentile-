<?php
/**
 * Plugin Name: TTP Exam — Replace "Choose Your Plan" subtitle
 * Description: Replaces the subtitle under "Choose Your Plan" on /exam/ with new copy.
 *              PHP-side replacement via the_content; JS fallback for Elementor widgets
 *              that render outside the_content (e.g. Heading widgets).
 * Version: 1.1.0
 *
 * Must-use: loads on every request; no admin UI required.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for the rewrite mapping.
 *
 * @return array<string,string>
 */
function ttp_exam_subtitle_map() {
	$new = "Find the MBA prep plan that's right for you.";

	return array(
		'All plans include full access to our TCY learning portal.' => $new,
		'From crash courses to full mentorship — find the prep that gets you in.' => $new,
		'From crash courses to full mentorship - find the prep that gets you in.' => $new,
	);
}

/**
 * @return bool
 */
function ttp_exam_subtitle_is_exam_request() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return ( false !== strpos( $uri, '/exam' ) );
}

/**
 * Replace the subtitle in any post content that flows through the_content
 * (covers most Elementor renderings).
 */
add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_string( $content ) || '' === $content ) {
			return $content;
		}
		if ( ! ttp_exam_subtitle_is_exam_request() ) {
			return $content;
		}
		foreach ( ttp_exam_subtitle_map() as $from => $to ) {
			if ( false !== strpos( $content, $from ) ) {
				$content = str_replace( $from, $to, $content );
			}
		}
		return $content;
	},
	9999
);

/**
 * JS fallback: rewrite the visible DOM in case the text is rendered by an
 * Elementor widget (Heading/Text) that doesn't pass through the_content.
 */
add_action(
	'wp_footer',
	static function () {
		if ( ! ttp_exam_subtitle_is_exam_request() ) {
			return;
		}
		$map = ttp_exam_subtitle_map();
		?>
		<script id="ttp-exam-subtitle-rewrite">
		(function () {
			var MAP = <?php echo wp_json_encode( $map ); ?>;
			if (!MAP || typeof MAP !== 'object') { return; }
			var keys = Object.keys(MAP);
			if (!keys.length) { return; }

			function normalize(s) { return (s || '').replace(/\s+/g, ' ').trim(); }

			function rewriteIn(root) {
				if (!root || !root.querySelectorAll) { return; }
				var nodes = root.querySelectorAll('h1,h2,h3,h4,h5,h6,p,span,div');
				for (var i = 0; i < nodes.length; i++) {
					var n = nodes[i];
					if (!n || n.children.length > 0) { continue; }
					var txt = normalize(n.textContent);
					if (!txt) { continue; }
					for (var k = 0; k < keys.length; k++) {
						if (txt === normalize(keys[k])) {
							n.textContent = MAP[keys[k]];
							break;
						}
					}
				}
			}

			function run() {
				try { rewriteIn(document); } catch (e) { /* ignore */ }
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', run);
			} else {
				run();
			}
			setTimeout(run, 200);
			setTimeout(run, 800);

			if (typeof MutationObserver !== 'undefined' && document.body) {
				try {
					new MutationObserver(function (muts) {
						for (var i = 0; i < muts.length; i++) {
							var added = muts[i].addedNodes || [];
							for (var j = 0; j < added.length; j++) {
								if (added[j].nodeType === 1) { rewriteIn(added[j]); }
							}
						}
					}).observe(document.body, { childList: true, subtree: true });
				} catch (e) { /* ignore */ }
			}
		})();
		</script>
		<?php
	},
	99
);
