/**
 * Checkout payment UI — ensure the selected gateway’s .payment_box is visible.
 * Promo apply: WC’s wc_checkout_coupons is not global; we call apply_coupon AJAX directly.
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

	function clearCouponError($block) {
		$block.find('.coupon-error-notice').remove();
		$block
			.find('input[name="coupon_code"]')
			.removeClass('has-error')
			.removeAttr('aria-invalid')
			.removeAttr('aria-describedby');
	}

	function showCouponError(html, $target) {
		clearCouponError($target.closest('.checkout_coupon'));
		var msg = $('<div>').html(html).text().trim();
		if (!msg || !$target.length) {
			return;
		}

		var $input = $target.find('input[name="coupon_code"]');
		$input
			.addClass('has-error')
			.attr('aria-invalid', 'true')
			.attr('aria-describedby', 'coupon-error-notice')
			.trigger('focus');

		$('<span>', {
			class: 'coupon-error-notice',
			id: 'coupon-error-notice',
			role: 'alert',
			text: msg,
		}).appendTo($target);
	}

	function applyCheckoutCoupon(evt) {
		if (evt) {
			evt.preventDefault();
			evt.stopPropagation();
			if (typeof evt.stopImmediatePropagation === 'function') {
				evt.stopImmediatePropagation();
			}
		}

		if (typeof wc_checkout_params === 'undefined') {
			return false;
		}

		var $block = $(evt.currentTarget).closest('.checkout_coupon');
		if (!$block.length) {
			return false;
		}

		var $row = $block.find('.htoeau-checkout-coupon__row');
		var $input = $block.find('input[name="coupon_code"]');
		var code = $.trim($input.val() || '');

		clearCouponError($block);
		$block.siblings('.woocommerce-error, .woocommerce-message').remove();
		$block.find('.woocommerce-error, .woocommerce-message').remove();

		if ($block.hasClass('processing')) {
			return false;
		}

		$block.addClass('processing').block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6,
			},
		});

		$.ajax({
			type: 'POST',
			url: wc_checkout_params.wc_ajax_url
				.toString()
				.replace('%%endpoint%%', 'apply_coupon'),
			data: {
				security: wc_checkout_params.apply_coupon_nonce,
				coupon_code: code,
				billing_email: $('form.checkout').find('input[name="billing_email"]').val() || '',
			},
			dataType: 'html',
			success: function (response) {
				$block.removeClass('processing').unblock();

				if (!response) {
					return;
				}

				var isError =
					response.indexOf('woocommerce-error') !== -1 ||
					response.indexOf('is-error') !== -1;

				if (isError) {
					showCouponError(response, $row);
				} else {
					$input.val('');
					clearCouponError($block);
					$row.before(response);
				}

				$(document.body).trigger('applied_coupon_in_checkout', [code]);
				$(document.body).trigger('update_checkout', {
					update_shipping_method: false,
				});
			},
			error: function (jqXHR) {
				$block.removeClass('processing').unblock();
				if (wc_checkout_params.debug_mode && window.console) {
					window.console.log(jqXHR.responseText);
				}
			},
		});

		return false;
	}

	// Promo sits inside form.checkout (no nested <form>). Delegation survives order_review AJAX refresh.
	$(document.body).on(
		'click.htoeau-coupon',
		'.htoeau-checkout-coupon .htoeau-checkout-coupon__btn',
		applyCheckoutCoupon
	);

	$(document.body).on(
		'keydown.htoeau-coupon',
		'.htoeau-checkout-coupon input[name="coupon_code"]',
		function (evt) {
			if (evt.key === 'Enter' || evt.keyCode === 13) {
				applyCheckoutCoupon(evt);
			}
		}
	);

	function keepPromoBlockVisible() {
		$('.htoeau-checkout-coupon .checkout_coupon').show();
	}

	$(document.body).on('init_checkout updated_checkout', keepPromoBlockVisible);
	$(keepPromoBlockVisible);
})(jQuery);
