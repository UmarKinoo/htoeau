<?php
/**
 * Force classic cart template so child theme override `woocommerce/cart/cart.php` is used.
 *
 * WooCommerce defaults the Cart page to the Cart block; blocks ignore PHP template overrides.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Force classic [woocommerce_cart] output on the cart page.
 *
 * This guarantees child-theme template overrides in `woocommerce/cart/*.php` are used
 * even when the cart page content is built with Woo blocks or Elementor content.
 *
 * @param string $content Post content.
 * @return string
 */
function htoeau_child_force_classic_cart_shortcode( $content ) {
	if ( is_admin() || wp_doing_ajax() || ! is_cart() ) {
		return $content;
	}

	if ( ! apply_filters( 'htoeau_child_force_classic_cart', true ) ) {
		return $content;
	}

	return do_shortcode( '[woocommerce_cart]' );
}
add_filter( 'the_content', 'htoeau_child_force_classic_cart_shortcode', 1 );
