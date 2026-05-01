<?php
/**
 * Force classic cart shortcode when the cart page post content is still rendered.
 *
 * Primary override is force-cart-template-include.php. This filter is a fallback
 * if another plugin lowers the template priority or the cart page runs through
 * the normal loop without our forced template.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Force classic [woocommerce_cart] output on the cart page.
 *
 * This helps child-theme overrides in `woocommerce/cart/*.php` apply when the
 * cart page content is blocks, Elementor, or other builders that hijack the_content.
 *
 * @param string $content Post content.
 * @return string
 */
function htoeau_child_force_classic_cart_shortcode( $content ) {
	if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return $content;
	}

	if ( ! apply_filters( 'htoeau_child_force_classic_cart', true ) ) {
		return $content;
	}

	return do_shortcode( '[woocommerce_cart]' );
}
add_filter( 'the_content', 'htoeau_child_force_classic_cart_shortcode', 1 );
