/**
 * Checkout payment UI — ensure the selected gateway’s .payment_box is visible.
 * (Theme layout uses flex on .wc_payment_method; this mirrors Woo’s default toggle.)
 */
(function ($) {
	'use strict';

	function syncPaymentBoxes() {
		var $payment = $('#payment .wc_payment_methods');
		if (!$payment.length) {
			return;
		}
		$payment.find('.payment_box').hide();
		$payment
			.find('input[name="payment_method"]:checked')
			.closest('li.wc_payment_method')
			.find('.payment_box')
			.show();
	}

	$(document.body).on('updated_checkout payment_method_selected init_checkout', syncPaymentBoxes);
	$(syncPaymentBoxes);

	// Promo block lives inside form.checkout — must not use a nested <form> or Apply submits checkout (Mollie card validation).
	function applyCheckoutCoupon(evt) {
		if (evt) {
			evt.preventDefault();
			evt.stopPropagation();
			if (typeof evt.stopImmediatePropagation === 'function') {
				evt.stopImmediatePropagation();
			}
		}

		var $block = $(evt.currentTarget).closest('.checkout_coupon');
		if (!$block.length) {
			return false;
		}

		if (typeof wc_checkout_coupons !== 'undefined' && wc_checkout_coupons.submit) {
			return wc_checkout_coupons.submit.call(wc_checkout_coupons, $.extend({}, evt, { currentTarget: $block[0] }));
		}

		return false;
	}

	function bindCheckoutCouponForm() {
		var $block = $('.htoeau-checkout-coupon .checkout_coupon');
		if (!$block.length) {
			return;
		}

		$block.show();

		$block
			.find('.htoeau-checkout-coupon__btn')
			.off('click.htoeau-coupon')
			.on('click.htoeau-coupon', applyCheckoutCoupon);

		$block
			.find('#coupon_code, input[name="coupon_code"]')
			.off('keydown.htoeau-coupon')
			.on('keydown.htoeau-coupon', function (evt) {
				if (evt.key === 'Enter' || evt.keyCode === 13) {
					applyCheckoutCoupon(evt);
				}
			});
	}

	function scheduleCheckoutCouponBind() {
		bindCheckoutCouponForm();
		// Deferred wc-checkout can hide the form after init_checkout; run again on next tick.
		window.setTimeout(bindCheckoutCouponForm, 0);
		window.setTimeout(bindCheckoutCouponForm, 300);
	}

	$(document.body).on('init_checkout updated_checkout', scheduleCheckoutCouponBind);
	$(scheduleCheckoutCouponBind);
})(jQuery);
