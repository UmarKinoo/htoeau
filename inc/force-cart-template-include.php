<?php
/**
 * Hard-force a dedicated cart page template to bypass page-builder content.
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

	$forced = HTOEAU_CHILD_DIR . '/woocommerce/cart/cart-page.php';
	if ( file_exists( $forced ) ) {
		return $forced;
	}

	return $template;
}
add_filter( 'template_include', 'htoeau_child_force_cart_template_include', 9999 );
