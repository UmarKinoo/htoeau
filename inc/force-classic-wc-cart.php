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
 * Replace Cart block output with the classic [woocommerce_cart] shortcode on the cart page.
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

	if ( ! function_exists( 'has_block' ) || ! has_block( 'woocommerce/cart', $content ) ) {
		return $content;
	}

	return do_shortcode( '[woocommerce_cart]' );
}
add_filter( 'the_content', 'htoeau_child_force_classic_cart_shortcode', 5 );
