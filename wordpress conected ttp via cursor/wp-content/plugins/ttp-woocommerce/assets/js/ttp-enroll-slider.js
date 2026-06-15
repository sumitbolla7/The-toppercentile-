/**
 * Horizontal product slider — Enrol Now / exam page.
 */
(function () {
	'use strict';

	function initSlider(wrap) {
		var track = wrap.querySelector('.ttp-plans-slider__track');
		var viewport = wrap.querySelector('.ttp-plans-slider');
		var prev = wrap.querySelector('.ttp-plans-slider__nav--prev');
		var next = wrap.querySelector('.ttp-plans-slider__nav--next');
		if (!track || !viewport) {
			return;
		}

		var scrollAmount = function () {
			var slide = track.querySelector('.ttp-plans-slider__slide');
			if (!slide) {
				return 320;
			}
			var gap = 20;
			try {
				gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap) || 20;
			} catch (e) {
				gap = 20;
			}
			return slide.offsetWidth + gap;
		};

		var updateNav = function () {
			var max = track.scrollWidth - viewport.clientWidth - 2;
			if (prev) {
				prev.disabled = viewport.scrollLeft <= 2;
			}
			if (next) {
				next.disabled = viewport.scrollLeft >= max;
			}
		};

		if (prev) {
			prev.addEventListener('click', function () {
				viewport.scrollBy({ left: -scrollAmount(), behavior: 'smooth' });
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				viewport.scrollBy({ left: scrollAmount(), behavior: 'smooth' });
			});
		}

		viewport.addEventListener('scroll', updateNav, { passive: true });
		window.addEventListener('resize', updateNav);
		updateNav();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-ttp-slider]').forEach(initSlider);
	});
})();
