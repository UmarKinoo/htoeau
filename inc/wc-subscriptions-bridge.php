<?php
/**
 * WooCommerce Subscriptions bridge — optional; loads when the extension is active.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether WooCommerce Subscriptions is available.
 *
 * @return bool
 */
function htoeau_child_wcs_is_active() {
	return class_exists( 'WC_Subscriptions_Product' );
}

/**
 * Product uses real subscription billing (WCS), not the theme's legacy “Subscribe & Save” discount UI.
 *
 * @param WC_Product|null $product Product.
 * @return bool
 */
function htoeau_child_product_is_wcs_subscription( $product ) {
	if ( ! $product || ! htoeau_child_wcs_is_active() ) {
		return false;
	}
	return (bool) WC_Subscriptions_Product::is_subscription( $product );
}

/**
 * Variable or variable-subscription — both use the variable PDP (cards + form).
 *
 * @param WC_Product|null $product Product.
 * @return bool
 */
function htoeau_child_product_is_variable_pdp( $product ) {
	if ( ! $product ) {
		return false;
	}
	return $product->is_type( 'variable' ) || $product->is_type( 'variable-subscription' );
}

/**
 * Output the correct variable add-to-cart template for the product type.
 */
function htoeau_child_template_variable_add_to_cart() {
	global $product;
	if ( ! $product ) {
		return;
	}
	if ( $product->is_type( 'variable-subscription' ) && function_exists( 'woocommerce_variable_subscription_add_to_cart' ) ) {
		woocommerce_variable_subscription_add_to_cart();
		return;
	}
	woocommerce_variable_add_to_cart();
}

/**
 * Use the theme’s variable form (hidden selects + cards) for variable subscriptions so the PDP CTA stays wired to #htoeau-variations-form.
 *
 * @param string $template      Full path to template.
 * @param string $template_name Template name.
 * @param string $template_path Path inside plugin.
 * @param string $default_path Default path.
 * @return string
 */
function htoeau_child_locate_variable_subscription_add_to_cart_template( $template, $template_name, $template_path, $default_path = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( 'single-product/add-to-cart/variable-subscription.php' !== $template_name ) {
		return $template;
	}
	$theme_var = HTOEAU_CHILD_DIR . '/woocommerce/single-product/add-to-cart/variable.php';
	if ( file_exists( $theme_var ) ) {
		return $theme_var;
	}
	return $template;
}
add_filter( 'woocommerce_locate_template', 'htoeau_child_locate_variable_subscription_add_to_cart_template', 10, 4 );
