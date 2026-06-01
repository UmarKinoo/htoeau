<?php
/**
 * Sell only to GB, IE, GI — WooCommerce checkout enforcement + PDP enquire CTA by geo.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Countries where online purchase is allowed (billing / shipping at checkout).
 *
 * @return string[] ISO 3166-1 alpha-2 codes.
 */
function htoeau_child_get_sellable_country_codes() {
	return apply_filters(
		'htoeau_sellable_country_codes',
		array( 'GB', 'IE', 'GI' )
	);
}

/**
 * Visitor country — same resolver as currency (Cloudflare, then Woo GeoIP).
 *
 * @return string Empty when unknown.
 */
function htoeau_child_get_visitor_country_code() {
	return function_exists( 'htoeau_child_fx_detect_country_code' )
		? htoeau_child_fx_detect_country_code()
		: '';
}

/**
 * Whether the visitor's geo suggests they can buy online (PDP UI + purchasability).
 *
 * @return bool
 */
function htoeau_child_visitor_can_purchase_online() {
	$country = htoeau_child_get_visitor_country_code();
	if ( ! $country ) {
		return true;
	}
	return in_array( $country, htoeau_child_get_sellable_country_codes(), true );
}

/**
 * Mark product not purchasable when the visitor is outside sellable countries.
 *
 * @param bool       $purchasable Whether purchasable.
 * @param WC_Product $product     Product.
 * @return bool
 */
function htoeau_child_filter_product_is_purchasable( $purchasable, $product ) {
	if ( ! $purchasable || ! $product instanceof WC_Product ) {
		return $purchasable;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $purchasable;
	}
	if ( htoeau_child_product_is_out_of_stock_for_display( $product ) ) {
		return $purchasable;
	}
	if ( ! htoeau_child_visitor_can_purchase_online() ) {
		return false;
	}
	return $purchasable;
}
add_filter( 'woocommerce_is_purchasable', 'htoeau_child_filter_product_is_purchasable', 10, 2 );

/**
 * @param bool               $purchasable Whether purchasable.
 * @param WC_Product_Variation $variation Variation.
 * @return bool
 */
function htoeau_child_filter_variation_is_purchasable( $purchasable, $variation ) {
	if ( ! $purchasable || ! $variation instanceof WC_Product ) {
		return $purchasable;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $purchasable;
	}
	if ( ! htoeau_child_visitor_can_purchase_online() ) {
		return false;
	}
	return $purchasable;
}
add_filter( 'woocommerce_variation_is_purchasable', 'htoeau_child_filter_variation_is_purchasable', 10, 2 );

/**
 * Block add-to-cart POST when selling is restricted for this visitor.
 *
 * @param bool $passed      Validation result.
 * @param int  $product_id  Product ID.
 * @param int  $quantity    Quantity.
 * @return bool
 */
function htoeau_child_block_add_to_cart_when_restricted( $passed, $product_id, $quantity ) {
	unset( $product_id, $quantity );
	if ( ! $passed || htoeau_child_visitor_can_purchase_online() ) {
		return $passed;
	}
	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice(
			__( 'Online ordering is not available in your region. Please enquire by email.', 'htoeau-child' ),
			'error'
		);
	}
	return false;
}
add_filter( 'woocommerce_add_to_cart_validation', 'htoeau_child_block_add_to_cart_when_restricted', 10, 3 );

/**
 * Body class when PDP is enquire-only (CSS hides any stray buy UI).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function htoeau_child_pdp_restricted_body_class( $classes ) {
	if ( function_exists( 'is_product' ) && is_product() && htoeau_child_product_should_show_enquire() ) {
		$classes[] = 'htoeau-pdp-restricted';
	}
	return $classes;
}
add_filter( 'body_class', 'htoeau_child_pdp_restricted_body_class' );

/**
 * PDP: show enquire CTA instead of add-to-cart (never when out of stock).
 *
 * @param WC_Product|null $product Product.
 * @return bool
 */
function htoeau_child_product_should_show_enquire( $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		global $product;
	}
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	if ( htoeau_child_product_is_out_of_stock_for_display( $product ) ) {
		return false;
	}
	return ! htoeau_child_visitor_can_purchase_online();
}

/**
 * Enquire-by-email CTA (gradient pill, same as Add to Cart).
 *
 * @return string HTML.
 */
function htoeau_child_get_enquire_cta_html() {
	$email = apply_filters( 'htoeau_enquire_email', 'hello@HtoEAU.com' );
	$href  = 'mailto:' . sanitize_email( $email );

	return sprintf(
		'<div class="htoeau-pdp__cta-wrap htoeau-pdp__cta-wrap--enquire"><a class="htoeau-pdp__add-btn htoeau-pdp__enquire-btn" href="%1$s">%2$s</a></div>',
		esc_url( $href ),
		esc_html__( 'Enquire by email', 'htoeau-child' )
	);
}

/**
 * Small enquire badge for the price block (same label; link styled separately on PDP).
 *
 * @return string HTML.
 */
function htoeau_child_get_enquire_badge_html() {
	return sprintf(
		'<span class="htoeau-stock-badge htoeau-stock-badge--enquire">%s</span>',
		esc_html__( 'Enquire by email', 'htoeau-child' )
	);
}

/**
 * WooCommerce: limit billing country dropdown to sellable countries.
 *
 * @param array<string, string> $countries All countries.
 * @return array<string, string>
 */
function htoeau_child_filter_allowed_countries( $countries ) {
	if ( ! is_array( $countries ) ) {
		return $countries;
	}
	$allowed = htoeau_child_get_sellable_country_codes();
	return array_intersect_key( $countries, array_flip( $allowed ) );
}
add_filter( 'woocommerce_countries_allowed_countries', 'htoeau_child_filter_allowed_countries' );
add_filter( 'woocommerce_countries_shipping_countries', 'htoeau_child_filter_allowed_countries' );

/**
 * Force “sell to specific countries” so WC core validation matches our list.
 */
function htoeau_child_force_sell_to_specific_countries() {
	return 'specific';
}
add_filter( 'pre_option_woocommerce_allowed_countries', 'htoeau_child_force_sell_to_specific_countries' );
add_filter( 'pre_option_woocommerce_ship_to_countries', 'htoeau_child_force_sell_to_specific_countries' );

/**
 * @return string[]
 */
function htoeau_child_force_specific_allowed_country_list() {
	return htoeau_child_get_sellable_country_codes();
}
add_filter( 'pre_option_woocommerce_specific_allowed_countries', 'htoeau_child_force_specific_allowed_country_list' );
add_filter( 'pre_option_woocommerce_specific_ship_to_countries', 'htoeau_child_force_specific_allowed_country_list' );

/**
 * Checkout validation: billing country must be in sellable list.
 *
 * @param array    $data   Posted checkout data.
 * @param WP_Error $errors Errors.
 */
function htoeau_child_validate_checkout_country( $data, $errors ) {
	$allowed  = htoeau_child_get_sellable_country_codes();
	$billing  = isset( $data['billing_country'] ) ? strtoupper( (string) $data['billing_country'] ) : '';
	$shipping = isset( $data['shipping_country'] ) ? strtoupper( (string) $data['shipping_country'] ) : '';

	if ( $billing && ! in_array( $billing, $allowed, true ) ) {
		$errors->add(
			'billing_country',
			__( 'We currently only sell to the United Kingdom, Ireland, and Gibraltar.', 'htoeau-child' )
		);
	}

	if ( ! empty( $data['ship_to_different_address'] ) && $shipping && ! in_array( $shipping, $allowed, true ) ) {
		$errors->add(
			'shipping_country',
			__( 'We currently only ship to the United Kingdom, Ireland, and Gibraltar.', 'htoeau-child' )
		);
	}
}
add_action( 'woocommerce_after_checkout_validation', 'htoeau_child_validate_checkout_country', 10, 2 );

/**
 * Cart / checkout: show only free shipping when available.
 *
 * @param array $rates   Package rates.
 * @param array $package Package.
 * @return array
 */
function htoeau_child_package_rates_free_shipping_only( $rates, $package ) {
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
