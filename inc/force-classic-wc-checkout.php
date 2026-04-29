<?php
/**
 * Force classic [woocommerce_checkout] shortcode so child theme overrides
 * in `woocommerce/checkout/*.php` are used (Woo Blocks ignore PHP overrides).
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replace page content with the classic checkout shortcode on the checkout page.
 * Skips order-received / order-pay / other WC endpoints so receipts stay intact.
 *
 * @param string $content Post content.
 * @return string
 */
function htoeau_child_force_classic_checkout_shortcode( $content ) {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return $content;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
		return $content;
	}

	if ( ! apply_filters( 'htoeau_child_force_classic_checkout', true ) ) {
		return $content;
	}

	return do_shortcode( '[woocommerce_checkout]' );
}
add_filter( 'the_content', 'htoeau_child_force_classic_checkout_shortcode', 1 );
