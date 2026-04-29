<?php
/**
 * Force a dedicated checkout page template to bypass page-builder content.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Use custom checkout wrapper template on /checkout/.
 * Skips order-received / order-pay so receipts use the standard flow.
 *
 * @param string $template Resolved template path.
 * @return string
 */
function htoeau_child_force_checkout_template_include( $template ) {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return $template;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
		return $template;
	}

	$forced = HTOEAU_CHILD_DIR . '/woocommerce/checkout/checkout-page.php';
	if ( file_exists( $forced ) ) {
		return $forced;
	}

	return $template;
}
add_filter( 'template_include', 'htoeau_child_force_checkout_template_include', 9999 );
