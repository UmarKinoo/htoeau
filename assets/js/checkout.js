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

	// WC checkout.js (often deferred) hides .checkout_coupon on init and does not re-bind
	// submit after order_review AJAX refresh — keep our sidebar promo visible + working.
	function bindCheckoutCouponForm() {
		var $form = $('.htoeau-checkout-coupon form.checkout_coupon');
		if (!$form.length) {
			return;
		}

		$form.show();

		$form.off('submit.htoeau-coupon').on('submit.htoeau-coupon', function (evt) {
			if (typeof wc_checkout_coupons !== 'undefined' && wc_checkout_coupons.submit) {
				return wc_checkout_coupons.submit.call(wc_checkout_coupons, evt);
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
