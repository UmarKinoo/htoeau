/**
 * Cart page — testimonial carousel (sidebar).
 */
(function () {
	'use strict';

	function init(root) {
		if (!root || root._htoeauCartCarouselInit) {
			return;
		}
		root._htoeauCartCarouselInit = true;

		var track = root.querySelector('.htoeau-cart-testimonials__track');
		var slides = root.querySelectorAll('.htoeau-cart-testimonials__slide');
		var prev = root.querySelector('.htoeau-cart-testimonials__nav--prev');
		var next = root.querySelector('.htoeau-cart-testimonials__nav--next');
		var dotsWrap = root.querySelector('.htoeau-cart-testimonials__dots');

		if (!track || slides.length === 0) {
			return;
		}

		var n = slides.length;
		var i = 0;

		function dots() {
			return dotsWrap ? dotsWrap.querySelectorAll('.htoeau-cart-testimonials__dot') : [];
		}

		function setTransform() {
			track.style.transform = 'translateX(-' + i * 100 + '%)';
			slides.forEach(function (el, k) {
				var on = k === i;
				el.setAttribute('aria-hidden', on ? 'false' : 'true');
			});
			dots().forEach(function (d, k) {
				d.setAttribute('aria-selected', k === i ? 'true' : 'false');
				d.tabIndex = k === i ? 0 : -1;
			});
			if (prev) {
				prev.disabled = n <= 1 || i <= 0;
			}
			if (next) {
				next.disabled = n <= 1 || i >= n - 1;
			}
		}

		function go(k) {
			i = Math.max(0, Math.min(n - 1, k));
			setTransform();
		}

		if (prev) {
			prev.addEventListener('click', function () {
				go(i - 1);
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				go(i + 1);
			});
		}

		dots().forEach(function (dot, k) {
			dot.addEventListener('click', function () {
				go(k);
			});
		});

		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') {
				go(i - 1);
			} else if (e.key === 'ArrowRight') {
				go(i + 1);
			}
		});

		setTransform();
	}

	document.querySelectorAll('[data-htoeau-cart-carousel]').forEach(init);
})();
