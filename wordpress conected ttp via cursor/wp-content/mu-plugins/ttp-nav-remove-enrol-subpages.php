<?php
/**
 * Plugin Name: TTP Nav — Remove Enrol Now sub-pages
 * Description: Removes OMETs / CET 2027 (and similar) under Enrol Now. Enrol Now goes straight to /exam/ with no mobile dropdown.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $title Menu item title.
 * @return string
 */
function ttp_nav_normalize_menu_title( $title ) {
	return strtolower( trim( wp_strip_all_tags( (string) $title ) ) );
}

/**
 * @param string $title Normalized title.
 * @return bool
 */
function ttp_nav_is_enrol_now_parent_title( $title ) {
	return false !== strpos( $title, 'enrol now' ) || false !== strpos( $title, 'enroll now' );
}

/**
 * @param object $item Menu item.
 * @return bool
 */
function ttp_nav_is_enrol_subpage_item( $item ) {
	$title = ttp_nav_normalize_menu_title( $item->title ?? '' );
	$url   = strtolower( (string) ( $item->url ?? '' ) );

	$title_hits = array(
		'omets',
		'omet',
		'cet 2027',
		'cet 2026',
		'cet2027',
		'cet2026',
		'mah cet',
		'mba cet',
	);

	foreach ( $title_hits as $hit ) {
		if ( $title === $hit || false !== strpos( $title, $hit ) ) {
			return true;
		}
	}

	$url_hits = array( '/omets', '/omet', 'cet-2027', 'cet-2026', 'cet2027', 'cet2026' );
	foreach ( $url_hits as $hit ) {
		if ( false !== strpos( $url, $hit ) ) {
			return true;
		}
	}

	return false;
}

/**
 * @param array  $items Menu items.
 * @param object $args  Menu args.
 * @return array
 */
function ttp_nav_filter_remove_enrol_subpages( $items, $args ) {
	if ( ! is_array( $items ) || empty( $items ) ) {
		return $items;
	}

	$enrol_parent_ids = array();
	foreach ( $items as $item ) {
		if ( ! is_object( $item ) ) {
			continue;
		}
		$title = ttp_nav_normalize_menu_title( $item->title ?? '' );
		if ( 0 === (int) ( $item->menu_item_parent ?? 0 ) && ttp_nav_is_enrol_now_parent_title( $title ) ) {
			$enrol_parent_ids[] = (int) $item->ID;
		}
	}

	if ( empty( $enrol_parent_ids ) ) {
		return $items;
	}

	$exam_url = home_url( '/exam/' );
	$filtered = array();

	foreach ( $items as $item ) {
		if ( ! is_object( $item ) ) {
			continue;
		}

		$parent_id = (int) ( $item->menu_item_parent ?? 0 );
		$title     = ttp_nav_normalize_menu_title( $item->title ?? '' );

		if ( in_array( $parent_id, $enrol_parent_ids, true ) || ttp_nav_is_enrol_subpage_item( $item ) ) {
			continue;
		}

		if ( in_array( (int) $item->ID, $enrol_parent_ids, true ) ) {
			$item->url = $exam_url;
			if ( is_array( $item->classes ) ) {
				$item->classes = array_diff( $item->classes, array( 'menu-item-has-children' ) );
			}
			$item->classes[] = 'ttp-enrol-direct-link';
		}

		$filtered[] = $item;
	}

	return $filtered;
}
add_filter( 'wp_nav_menu_objects', 'ttp_nav_filter_remove_enrol_subpages', 20, 2 );

/**
 * Elementor / Astra mobile menus: hide any leftover sub-links.
 *
 * @return void
 */
function ttp_nav_enrol_subpages_hide_css() {
	?>
	<style id="ttp-hide-enrol-subpages">
		/* Remove Enrol Now dropdown children (OMETs, CET 2027) — especially on mobile */
		.menu-item.ttp-enrol-direct-link > .sub-menu,
		.menu-item.ttp-enrol-direct-link .sub-menu,
		li.menu-item.ttp-enrol-direct-link .children {
			display: none !important;
		}
		@media (max-width: 921px) {
			.ast-header-break-point .main-header-menu .sub-menu a[href*="omet"],
			.ast-header-break-point .main-header-menu .sub-menu a[href*="cet-2027"],
			.ast-header-break-point .main-header-menu .sub-menu a[href*="cet-2026"],
			.ast-mobile-popup-content a[href*="omet"],
			.ast-mobile-popup-content a[href*="cet-2027"],
			.ast-mobile-popup-content a[href*="cet-2026"],
			.elementor-nav-menu--dropdown a[href*="omet"],
			.elementor-nav-menu--dropdown a[href*="cet-2027"],
			.elementor-nav-menu--dropdown a[href*="cet-2026"] {
				display: none !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'ttp_nav_enrol_subpages_hide_css', 50 );

/**
 * Strip orphan OMET / CET links from mobile DOM (Elementor cache).
 *
 * @return void
 */
function ttp_nav_enrol_subpages_footer_js() {
	?>
	<script id="ttp-remove-enrol-subpages">
	(function () {
		function norm(t) {
			return (t || '').replace(/\s+/g, ' ').trim().toLowerCase();
		}
		function isSubpage(a) {
			var txt = norm(a.textContent);
			var href = (a.getAttribute('href') || '').toLowerCase();
			if (txt === 'omets' || txt === 'omet' || txt.indexOf('cet 2027') !== -1 || txt.indexOf('cet 2026') !== -1) {
				return true;
			}
			return href.indexOf('omet') !== -1 || href.indexOf('cet-2027') !== -1 || href.indexOf('cet-2026') !== -1;
		}
		function fix() {
			document.querySelectorAll('a').forEach(function (a) {
				if (!isSubpage(a)) {
					return;
				}
				var li = a.closest('li');
				if (li) {
					li.parentNode.removeChild(li);
				}
			});
            document.querySelectorAll('a').forEach(function (a) {
                var txt = norm(a.textContent);
                if (txt === 'free resources') {
                    a.textContent = 'Free Resources';
                }
                if (txt === 'enrol now' || txt === 'enroll now') {
					a.setAttribute('href', <?php echo wp_json_encode( home_url( '/exam/' ) ); ?>);
					var li = a.closest('li');
					if (li) {
						li.classList.remove('menu-item-has-children');
					}
				}
			});
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fix);
		} else {
			fix();
		}
		setTimeout(fix, 400);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'ttp_nav_enrol_subpages_footer_js', 50 );
