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
})(jQuery);
