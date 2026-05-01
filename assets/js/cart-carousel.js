/**
 * Cart page JS — testimonial carousel + sidebar injection.
 *
 * The WooCommerce Cart Block handles qty updates natively via the Store API.
 * This file only manages the carousel and moves the help/testimonials panel
 * from its server-rendered holding div into the block cart's totals column.
 */
(function () {
	'use strict';

	/* ── Testimonial Carousel ───────────────────────────────── */

	function initCarousel(root) {
		if (!root || root._htoeauCarouselInit) return;
		root._htoeauCarouselInit = true;

		var track    = root.querySelector('.htoeau-cart-testimonials__track');
		var slides   = root.querySelectorAll('.htoeau-cart-testimonials__slide');
		var prev     = root.querySelector('.htoeau-cart-testimonials__nav--prev');
		var next     = root.querySelector('.htoeau-cart-testimonials__nav--next');
		var dotsWrap = root.querySelector('.htoeau-cart-testimonials__dots');

		if (!track || slides.length === 0) return;

		var n = slides.length;
		var i = 0;

		function getDots() {
			return dotsWrap ? Array.from(dotsWrap.querySelectorAll('.htoeau-cart-testimonials__dot')) : [];
		}

		function update() {
			track.style.transform = 'translateX(-' + i * 100 + '%)';
			slides.forEach(function (el, k) {
				el.setAttribute('aria-hidden', k === i ? 'false' : 'true');
			});
			getDots().forEach(function (d, k) {
				var active = k === i;
				d.setAttribute('aria-selected', active ? 'true' : 'false');
				d.tabIndex = active ? 0 : -1;
			});
			if (prev) prev.disabled = (n <= 1 || i <= 0);
			if (next) next.disabled = (n <= 1 || i >= n - 1);
		}

		function go(k) {
			i = Math.max(0, Math.min(n - 1, k));
			update();
		}

		if (prev) prev.addEventListener('click', function () { go(i - 1); });
		if (next) next.addEventListener('click', function () { go(i + 1); });

		getDots().forEach(function (dot, k) {
			dot.addEventListener('click', function () { go(k); });
		});

		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft')  go(i - 1);
			if (e.key === 'ArrowRight') go(i + 1);
		});

		update();
	}

	/* ── Sidebar injection ──────────────────────────────────── */
	// PHP renders help + testimonials in a hidden .htoeau-cart-sidebar-inject div.
	// We move its children into the block cart's totals column so they sit below
	// the order summary in the native right column.

	function injectSidebar() {
		var source  = document.querySelector('.htoeau-cart-sidebar-inject');
		var totals  = document.querySelector('.wp-block-woocommerce-cart-totals-block');

		if (!source || !totals) return;

		while (source.firstChild) {
			totals.appendChild(source.firstChild);
		}
		source.parentNode.removeChild(source);

		// Init carousel now that the element is in the DOM and visible.
		totals.querySelectorAll('[data-htoeau-cart-carousel]').forEach(initCarousel);
	}

	/* ── Init ───────────────────────────────────────────────── */

	injectSidebar();

})();
