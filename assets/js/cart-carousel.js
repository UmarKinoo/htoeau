/**
 * Cart page JS — testimonial carousel, block sidebar injection, qty stepper, auto-update cart.
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
	// Move its children into the block cart's totals column so they appear below
	// the order summary in the right sidebar.

	function injectSidebar() {
		var source = document.querySelector('.htoeau-cart-sidebar-inject');
		var totals = document.querySelector('.wp-block-woocommerce-cart-totals-block') ||
		             document.querySelector('.wc-block-components-sidebar');
		if (!source || !totals) return;

		while (source.firstChild) {
			totals.appendChild(source.firstChild);
		}
		source.parentNode.removeChild(source);

		totals.querySelectorAll('[data-htoeau-cart-carousel]').forEach(initCarousel);
	}

	/* ── Classic cart quantity stepper (− / +) ─────────────── */

	function initCartQtySteppers() {
		document.querySelectorAll('.woocommerce-cart-form .quantity').forEach(function (wrap) {
			if (wrap.dataset.htoeauQtyStepper === '1') {
				return;
			}
			var input = wrap.querySelector('input.qty');
			var minus = wrap.querySelector('button.minus');
			var plus  = wrap.querySelector('button.plus');
			if (!input || !minus || !plus) {
				return;
			}
			wrap.dataset.htoeauQtyStepper = '1';

			function parseQty() {
				var v = parseFloat(input.value);
				return isNaN(v) ? 0 : v;
			}
			function getStep() {
				var s = parseFloat(input.step);
				return !isNaN(s) && s > 0 ? s : 1;
			}
			function getMin() {
				var m = parseFloat(input.getAttribute('min'));
				return isNaN(m) ? 0 : m;
			}
			function getMax() {
				var attr = input.getAttribute('max');
				if (attr === null || attr === '') {
					return null;
				}
				var m = parseFloat(attr);
				return isNaN(m) ? null : m;
			}
			function applyDelta(direction) {
				var step = getStep();
				var min = getMin();
				var max = getMax();
				var next = parseQty() + direction * step;
				if (next < min) {
					next = min;
				}
				if (max !== null && next > max) {
					next = max;
				}
				input.value = next;
				input.dispatchEvent(new Event('input', { bubbles: true }));
				input.dispatchEvent(new Event('change', { bubbles: true }));
			}

			minus.addEventListener('click', function (e) {
				e.preventDefault();
				applyDelta(-1);
			});
			plus.addEventListener('click', function (e) {
				e.preventDefault();
				applyDelta(1);
			});
		});
	}

	/* ── Qty change → debounced Update cart (WC AJAX) ─────── */
	function initQtyAutoUpdate() {
		var form = document.querySelector('form.woocommerce-cart-form');
		if (!form) return;

		var timer = null;

		form.querySelectorAll('input.qty').forEach(function (input) {
			if (input.dataset.htoeauAutoQty === '1') return;
			input.dataset.htoeauAutoQty = '1';

			input.addEventListener('change', function () {
				clearTimeout(timer);
				timer = setTimeout(function () {
					var btn = form.querySelector('button[name="update_cart"]');
					if (btn) {
						btn.disabled = false;
						btn.click();
					} else {
						form.submit();
					}
				}, 500);
			});
		});
	}

	function refreshClassicCartUi() {
		initCartQtySteppers();
		initQtyAutoUpdate();
	}

	/* ── Init ───────────────────────────────────────────────── */

	document.querySelectorAll('[data-htoeau-cart-carousel]').forEach(initCarousel);
	injectSidebar();
	refreshClassicCartUi();

	if (typeof window.jQuery !== 'undefined') {
		window.jQuery(document.body).on('updated_wc_div', function () {
			refreshClassicCartUi();
		});
	}

})();
