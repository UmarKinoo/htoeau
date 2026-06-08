<?php
/**
 * Checkout payment helpers for Mollie (layout only — do not bypass Mollie availability).
 *
 * Mollie hides methods that do not apply to billing country + currency; forcing them
 * into the list caused "error processing your order" when Place order called the API.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Apple Pay express slot above payment radios (Mollie injects when enabled in admin + Safari).
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
 * Put Mollie Card first when available so Components mount on load.
 *
 * @param WC_Payment_Gateway[] $gateways Available gateways.
 * @return WC_Payment_Gateway[]
 */
function htoeau_child_reorder_mollie_creditcard_first( $gateways ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $gateways;
	}
	if ( ! is_array( $gateways ) || empty( $gateways['mollie_wc_gateway_creditcard'] ) ) {
		return $gateways;
	}

	$card = $gateways['mollie_wc_gateway_creditcard'];
	unset( $gateways['mollie_wc_gateway_creditcard'] );

	return array_merge( array( 'mollie_wc_gateway_creditcard' => $card ), $gateways );
}
add_filter( 'woocommerce_available_payment_gateways', 'htoeau_child_reorder_mollie_creditcard_first', 100 );
