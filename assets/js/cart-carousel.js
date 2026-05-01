/**
 * Cart page JS — testimonial carousel + qty auto-update.
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

	/* ── Qty auto-update ────────────────────────────────────── */
	// WooCommerce's own wc-cart.js intercepts clicks on button[name="update_cart"]
	// and performs an AJAX update (no page reload). We keep that button hidden in
	// the DOM and programmatically click it after a short debounce, so WC handles
	// the AJAX call and fires updated_cart_totals to refresh the totals panel.

	function initQtyAutoUpdate() {
		var form = document.querySelector('form.woocommerce-cart-form');
		if (!form) return;

		// Strip any WC-injected +/- buttons — we only want the plain number input.
		form.querySelectorAll('.quantity button.plus, .quantity button.minus, .quantity .qty_button').forEach(function (btn) {
			btn.parentNode.removeChild(btn);
		});

		var timer = null;

		form.querySelectorAll('input.qty').forEach(function (input) {
			if (input._htoeauAutoUpdate) return;
			input._htoeauAutoUpdate = true;

			input.addEventListener('change', function () {
				clearTimeout(timer);
				timer = setTimeout(function () {
					var btn = form.querySelector('button[name="update_cart"]');
					if (btn) {
						// WC marks the button disabled by default; enable it so WC's
						// AJAX handler accepts the click.
						btn.disabled = false;
						btn.click();
					} else {
						form.submit();
					}
				}, 500);
			});
		});
	}

	/* ── Init ───────────────────────────────────────────────── */

	document.querySelectorAll('[data-htoeau-cart-carousel]').forEach(initCarousel);
	initQtyAutoUpdate();

})();
