<?php
/**
 * Show every enabled Mollie gateway at checkout.
 *
 * The Mollie plugin hides methods when its API says they don't apply to the
 * current billing country + store currency. This re-includes enabled gateways
 * and puts Card first so Mollie Components mount on load.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param WC_Payment_Gateway $gateway Gateway instance.
 * @return bool
 */
function htoeau_child_is_mollie_wc_gateway( $gateway ) {
	if ( ! is_object( $gateway ) || empty( $gateway->id ) ) {
		return false;
	}

	return 0 === strpos( (string) $gateway->id, 'mollie_wc_gateway_' );
}

/**
 * @return bool
 */
function htoeau_child_force_all_mollie_gateways_enabled() {
	return (bool) apply_filters( 'htoeau_child_force_all_mollie_gateways', true );
}

/**
 * Turn on Apple Pay express button in Mollie settings (classic checkout).
 *
 * @param mixed $value Gateway settings option.
 * @return mixed
 */
function htoeau_child_enable_mollie_applepay_express_checkout( $value ) {
	if ( ! is_array( $value ) ) {
		$value = array();
	}
	$value['mollie_apple_pay_button_enabled_express_checkout'] = 'yes';
	return $value;
}
add_filter( 'option_woocommerce_mollie_wc_gateway_applepay_settings', 'htoeau_child_enable_mollie_applepay_express_checkout', 20 );

/**
 * Apple Pay express slot (Mollie injects the button when express is enabled + Safari).
 */
function htoeau_child_checkout_apple_pay_express_slot() {
	if ( ! function_exists( 'mollieWooCommerceIsApplePayDirectEnabled' ) ) {
		return;
	}
	if ( ! mollieWooCommerceIsApplePayDirectEnabled( 'express_checkout' ) ) {
		return;
	}
	echo '<div class="htoeau-checkout-apple-pay-express" id="mollie-applepayDirect-button"></div>';
}
add_action( 'woocommerce_review_order_before_payment', 'htoeau_child_checkout_apple_pay_express_slot', 5 );

/**
 * Re-add enabled Mollie gateways that is_available() removed; Card first.
 *
 * @param WC_Payment_Gateway[] $gateways Available gateways.
 * @return WC_Payment_Gateway[]
 */
function htoeau_child_force_all_mollie_payment_gateways( $gateways ) {
	if ( ! htoeau_child_force_all_mollie_gateways_enabled() ) {
		return $gateways;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return $gateways;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return $gateways;
	}

	if ( ! is_array( $gateways ) ) {
		$gateways = array();
	}

	$mollie = array();

	foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
		if ( ! htoeau_child_is_mollie_wc_gateway( $gateway ) ) {
			continue;
		}
		if ( 'yes' !== $gateway->enabled ) {
			continue;
		}
		// Mollie hides direct debit from checkout on purpose.
		if ( 'mollie_wc_gateway_directdebit' === $gateway->id ) {
			continue;
		}
		$mollie[ $gateway->id ] = $gateway;
	}

	if ( empty( $mollie ) ) {
		return $gateways;
	}

	// Card first so Mollie Components load and the form is visible by default.
	if ( isset( $mollie['mollie_wc_gateway_creditcard'] ) ) {
		$card = $mollie['mollie_wc_gateway_creditcard'];
		unset( $mollie['mollie_wc_gateway_creditcard'] );
		$mollie = array_merge( array( 'mollie_wc_gateway_creditcard' => $card ), $mollie );
	}

	foreach ( $mollie as $id => $gateway ) {
		$gateways[ $id ] = $gateway;
	}

	return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'htoeau_child_force_all_mollie_payment_gateways', 9999 );
