<?php
/**
 * Cart / checkout shipping display — free shipping only when available.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param array $rates   Package rates.
 * @param array $package Package.
 * @return array
 */
function htoeau_child_package_rates_free_shipping_only( $rates, $package ) {
	unset( $package );
	if ( ! is_array( $rates ) || empty( $rates ) ) {
		return $rates;
	}

	$free = array();
	foreach ( $rates as $rate_id => $rate ) {
		if ( is_object( $rate ) && isset( $rate->method_id ) && 'free_shipping' === $rate->method_id ) {
			$free[ $rate_id ] = $rate;
		}
	}

	return ! empty( $free ) ? $free : $rates;
}
add_filter( 'woocommerce_package_rates', 'htoeau_child_package_rates_free_shipping_only', 100, 2 );
