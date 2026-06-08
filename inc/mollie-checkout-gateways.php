<?php
/**
 * Show every enabled Mollie gateway at checkout.
 *
 * The Mollie plugin hides methods when its API says they don't apply to the
 * current billing country + store currency (e.g. iDEAL for NL/EUR only).
 * This re-includes all enabled Mollie gateways in the checkout payment list.
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
 * Re-add enabled Mollie gateways that is_available() removed.
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

	foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
		if ( ! htoeau_child_is_mollie_wc_gateway( $gateway ) ) {
			continue;
		}
		if ( 'yes' !== $gateway->enabled ) {
			continue;
		}
		// Mollie direct debit is intentionally hidden from checkout by the plugin.
		if ( 'mollie_wc_gateway_directdebit' === $gateway->id ) {
			continue;
		}
		$gateways[ $gateway->id ] = $gateway;
	}

	return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'htoeau_child_force_all_mollie_payment_gateways', 9999 );
