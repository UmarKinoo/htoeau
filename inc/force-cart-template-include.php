<?php
/**
 * Hard-force a dedicated cart page template to bypass page-builder content.
 *
 * Elementor (and the default Cart block body) replace the cart page’s post content.
 * Loading this minimal template runs [woocommerce_cart] so child overrides in
 * woocommerce/cart/cart.php are used.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Use custom cart wrapper template on cart requests.
 *
 * @param string $template Resolved template path.
 * @return string
 */
function htoeau_child_force_cart_template_include( $template ) {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return $template;
	}

	if ( ! apply_filters( 'htoeau_child_force_cart_template_include', true ) ) {
		return $template;
	}

	$forced = HTOEAU_CHILD_DIR . '/woocommerce/cart/cart-page.php';
	if ( file_exists( $forced ) ) {
		return $forced;
	}

	return $template;
}
add_filter( 'template_include', 'htoeau_child_force_cart_template_include', 99999 );
