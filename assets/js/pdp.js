/**
 * HtoEAU PDP — variation cards, subscribe toggle, CTA label, accordion, gallery, transformation.
 */
(function ($) {
	'use strict';

	function parseJsonScript(id) {
		var el = document.getElementById(id);
		if (!el || !el.textContent) {
			return null;
		}
		try {
			return JSON.parse(el.textContent);
		} catch (e) {
			return null;
		}
	}

	function formatMoney(amount, symbol, decimals) {
		var n = Number(amount);
		if (isNaN(n)) {
			n = 0;
		}
		var fixed = n.toFixed(decimals);
		return symbol + fixed;
	}

	function getPdpVariation(data, variationId) {
		if (!data || !data.variations) {
			return null;
		}
		var id = parseInt(variationId, 10);
		for (var i = 0; i < data.variations.length; i++) {
			if (parseInt(data.variations[i].variationId, 10) === id) {
				return data.variations[i];
			}
		}
		return null;
	}

	function setVariationFromCard($form, variation) {
		if (!variation || !variation.attributes) {
			return;
		}
		var $last = null;
		$.each(variation.attributes, function (key, val) {
			var $sel = $form.find('select[name="' + key + '"]');
			if ($sel.length && val) {
				$sel.val(val);
				$last = $sel;
			}
		});
		if ($last) {
			$last.trigger('change');
		}
	}

	$(function () {
		var pdpData = parseJsonScript('htoeau-pdp-data');
		var $form = $('form.variations_form');
		var sym = (window.htoeauPdp && htoeauPdp.currencySymbol) || '£';
		var dec = pdpData && typeof pdpData.decimals === 'number' ? pdpData.decimals : 2;
		var subscribeMode = true;

		/* ---------- Gallery ---------- */
		var $gal = $('[data-htoeau-gallery]');
		if ($gal.length) {
			var $slides = $gal.find('[data-gallery-slide]');
			var $thumbs = $gal.find('[data-gallery-thumb]');
			function showSlide(idx) {
				idx = parseInt(idx, 10);
				$slides.removeClass('is-active').filter('[data-gallery-slide="' + idx + '"]').addClass('is-active');
				$thumbs.removeClass('is-active').attr('aria-selected', 'false');
				$thumbs.filter('[data-gallery-thumb="' + idx + '"]').addClass('is-active').attr('aria-selected', 'true');
			}
			$gal.on('click', '[data-gallery-thumb]', function () {
				showSlide($(this).attr('data-gallery-thumb'));
			});
			var cur = 0;
			$gal.on('click', '[data-gallery-next]', function () {
				cur = (cur + 1) % $slides.length;
				showSlide(cur);
			});
			$gal.on('click', '[data-gallery-prev]', function () {
				cur = (cur - 1 + $slides.length) % $slides.length;
				showSlide(cur);
			});
		}

		/* ---------- Accordion (scoped per root — PDP tabs + FAQ can coexist) ---------- */
		$(document).on('click', '[data-htoeau-accordion] [data-htoeau-acc-trigger]', function () {
			var $btn = $(this);
			var $root = $btn.closest('[data-htoeau-accordion]');
			var $item = $btn.closest('[data-htoeau-acc-item]');
			var $panel = $item.find('.htoeau-accordion__panel');
			var open = $item.hasClass('is-open');
			$root.find('[data-htoeau-acc-item]').removeClass('is-open');
			$root.find('[data-htoeau-acc-trigger]').attr('aria-expanded', 'false');
			$root.find('.htoeau-accordion__panel').attr('hidden', true);
			if (!open) {
				$item.addClass('is-open');
				$btn.attr('aria-expanded', 'true');
				$panel.removeAttr('hidden');
			}
		});

		/* ---------- Transformation ---------- */
		var $tf = $('[data-htoeau-transform]');
		if ($tf.length) {
			$tf.on('click', '[data-transform-panel]', function () {
				var $p = $(this);
				$tf.find('[data-transform-panel]').removeClass('is-active').attr('aria-expanded', 'false');
				$p.addClass('is-active').attr('aria-expanded', 'true');
			});
		}

		if (!$form.length || !pdpData) {
			return;
		}

		var $cards = $('[data-htoeau-qty-card]');
		var $intent = $('[data-htoeau-purchase-intent]');
		var $btn = $('[data-htoeau-add-btn]');
		var $btnLabel = $('[data-htoeau-add-btn-label]');
		var $subStrike = $('[data-subscribe-strike]');
		var $subAmt = $('[data-subscribe-amount]');
		var $onceAmt = $('[data-onetime-amount]');

		function updatePurchasePanels(v) {
			if (!v) {
				return;
			}
			var one = parseFloat(v.oneTime);
			var reg = parseFloat(v.oneTimeRegular);
			var sub = parseFloat(v.subscribe);
			$subStrike.text(reg > one ? formatMoney(reg, sym, dec) : '');
			$subAmt.text(formatMoney(sub, sym, dec));
			$onceAmt.text(formatMoney(one, sym, dec));
		}

		function updateCta(v) {
			if (!v) {
				return;
			}
			var amt = subscribeMode ? parseFloat(v.subscribe) : parseFloat(v.oneTime);
			var label = (window.htoeauPdp && htoeauPdp.i18n && htoeauPdp.i18n.addToCart) || 'Add to Cart';
			$btnLabel.text(label + ' – ' + formatMoney(amt, sym, dec));
			$btn.prop('disabled', false);
		}

		function selectCardByVariationId(vid) {
			$cards.removeClass('is-selected').attr('aria-pressed', 'false');
			$cards.filter('[data-variation-id="' + vid + '"]').addClass('is-selected').attr('aria-pressed', 'true');
		}

		function onVariationResolved(variationId) {
			var vid = parseInt(variationId, 10);
			var v = getPdpVariation(pdpData, vid);
			updatePurchasePanels(v);
			updateCta(v);
			selectCardByVariationId(vid);
		}

		$form.on('found_variation', function (event, variation) {
			if (variation && variation.variation_id) {
				onVariationResolved(variation.variation_id);
			}
		});

		$form.on('reset_data', function () {
			$btn.prop('disabled', true);
		});

		$cards.on('click', function () {
			var vid = parseInt($(this).attr('data-variation-id'), 10);
			var v = getPdpVariation(pdpData, vid);
			if (v) {
				setVariationFromCard($form, v);
			}
		});

		function syncSubscribePanelsAndRadios() {
			var $wrap = $('[data-htoeau-subscribe]');
			$wrap.find('.htoeau-subscribe__option').removeClass('is-active');
			$wrap.find('.htoeau-subscribe__option').each(function () {
				var $opt = $(this);
				var $inp = $opt.find('input[type="radio"]');
				if ($inp.prop('checked')) {
					$opt.addClass('is-active');
				}
			});
		}

		var $delivery = $('[data-htoeau-delivery]');

		function syncDeliveryFieldEnabled() {
			if (!$delivery.length) {
				return;
			}
			var sub = $('[data-htoeau-subscribe] input[name="htoeau_purchase_type"]:checked').val() === 'subscribe';
			$delivery.prop('disabled', !sub);
		}

		$('[data-htoeau-subscribe] input[name="htoeau_purchase_type"]').on('change', function () {
			subscribeMode = $(this).val() === 'subscribe';
			$intent.val(subscribeMode ? 'subscribe' : 'once');
			syncSubscribePanelsAndRadios();
			syncDeliveryFieldEnabled();
			var $vid = $form.find('input.variation_id');
			if ($vid.length && $vid.val() && parseInt($vid.val(), 10) > 0) {
				var pv = getPdpVariation(pdpData, parseInt($vid.val(), 10));
				updatePurchasePanels(pv);
				updateCta(pv);
			}
		});

		syncSubscribePanelsAndRadios();
		syncDeliveryFieldEnabled();

		$form.on('submit', function () {
			var sub = $('[data-htoeau-subscribe] input[name="htoeau_purchase_type"]:checked').val() === 'subscribe';
			$intent.val(sub ? 'subscribe' : 'once');
			syncDeliveryFieldEnabled();
		});

		/* Initial default variation */
		if (pdpData.defaultVariationId) {
			var init = getPdpVariation(pdpData, pdpData.defaultVariationId);
			if (init) {
				setVariationFromCard($form, init);
			}
		}

		/* If WC already found variation before our handlers */
		setTimeout(function () {
			var vid = $form.find('input.variation_id').val();
			if (vid && parseInt(vid, 10) > 0) {
				onVariationResolved(vid);
			}
		}, 250);
	});
})(jQuery);
