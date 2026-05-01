/**
 * Cart page JS — testimonial carousel + quantity stepper.
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

	/* ── Quantity Stepper ───────────────────────────────────── */

	function initQuantitySteppers() {
		document.querySelectorAll('td.product-quantity .quantity').forEach(function (wrap) {
			if (wrap._htoeauStepperInit) return;
			wrap._htoeauStepperInit = true;

			var input = wrap.querySelector('input.qty');
			if (!input) return;

			// Remove any WooCommerce-injected native +/- buttons to avoid duplicates.
			wrap.querySelectorAll('button.plus, button.minus, .qty_button').forEach(function (btn) {
				btn.parentNode.removeChild(btn);
			});

			var dec = document.createElement('button');
			dec.type = 'button';
			dec.className = 'htoeau-qty-btn htoeau-qty-btn--dec';
			dec.setAttribute('aria-label', 'Decrease quantity');
			dec.textContent = '−';

			var inc = document.createElement('button');
			inc.type = 'button';
			inc.className = 'htoeau-qty-btn htoeau-qty-btn--inc';
			inc.setAttribute('aria-label', 'Increase quantity');
			inc.textContent = '+';

			wrap.insertBefore(dec, input);
			wrap.appendChild(inc);

			dec.addEventListener('click', function () {
				var v   = parseInt(input.value, 10) || 1;
				var min = parseInt(input.getAttribute('min'), 10);
				min = isNaN(min) ? 0 : min;
				if (v - 1 >= min) {
					input.value = v - 1;
					input.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});

			inc.addEventListener('click', function () {
				var v   = parseInt(input.value, 10) || 0;
				var max = parseInt(input.getAttribute('max'), 10);
				if (isNaN(max) || v + 1 <= max) {
					input.value = v + 1;
					input.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});
		});
	}

	/* ── Init ───────────────────────────────────────────────── */

	document.querySelectorAll('[data-htoeau-cart-carousel]').forEach(initCarousel);
	initQuantitySteppers();

	// Re-init after WC AJAX cart updates (e.g. Update cart button).
	document.body.addEventListener('updated_cart_totals', initQuantitySteppers);
	document.body.addEventListener('updated_wc_div', initQuantitySteppers);

})();
