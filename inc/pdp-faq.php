<?php
/**
 * PDP FAQ from ACF (flat fields, ACF Free–compatible).
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the product has at least one FAQ question (required to show the block).
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function htoeau_child_product_has_acf_pdp_faq( $product_id ): bool {
	$product_id = (int) $product_id;
	if ( $product_id < 1 || ! function_exists( 'get_field' ) ) {
		return false;
	}
	for ( $i = 1; $i <= 5; $i++ ) {
		$q = get_field( 'pdp_faq_' . $i . '_question', $product_id );
		if ( is_string( $q ) && '' !== trim( $q ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Heading, subheading, and Q&A rows for the PDP FAQ template.
 *
 * @param int $product_id Product ID.
 * @return array{heading: string, subheading: string, items: array<int, array{q: string, a: string}>}
 */
function htoeau_child_get_pdp_faq_data( $product_id ): array {
	$product_id = (int) $product_id;
	$default_heading = __( 'Frequently Asked Questions', 'hello-elementor-child' );

	$heading    = '';
	$subheading = '';
	if ( function_exists( 'get_field' ) ) {
		$h = get_field( 'pdp_faq_heading', $product_id );
		$s = get_field( 'pdp_faq_subheading', $product_id );
		$heading    = is_string( $h ) ? trim( $h ) : '';
		$subheading = is_string( $s ) ? trim( $s ) : '';
	}
	if ( '' === $heading ) {
		$heading = $default_heading;
	}

	$items = array();
	for ( $i = 1; $i <= 5; $i++ ) {
		if ( ! function_exists( 'get_field' ) ) {
			break;
		}
		$q = get_field( 'pdp_faq_' . $i . '_question', $product_id );
		$a = get_field( 'pdp_faq_' . $i . '_answer', $product_id );
		$q = is_string( $q ) ? trim( $q ) : '';
		$a = is_string( $a ) ? trim( $a ) : '';
		if ( '' === $q ) {
			continue;
		}
		$items[] = array(
			'q' => $q,
			'a' => $a,
		);
	}

	return array(
		'heading'    => $heading,
		'subheading' => $subheading,
		'items'      => $items,
	);
}

/**
 * Enqueue HtoEAU Elementor widgets stylesheet when this product renders an ACF FAQ (same .htoeau-faq styles).
 */
function htoeau_child_enqueue_pdp_acf_faq_styles(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$pid = get_queried_object_id();
	if ( $pid < 1 || ! htoeau_child_product_has_acf_pdp_faq( $pid ) ) {
		return;
	}
	if ( ! defined( 'HTOEAU_WIDGETS_URL' ) || ! defined( 'HTOEAU_WIDGETS_VERSION' ) ) {
		return;
	}
	$deps = array();
	if ( wp_style_is( 'elementor-frontend', 'registered' ) ) {
		$deps[] = 'elementor-frontend';
	}
	wp_enqueue_style(
		'htoeau-widgets-frontend',
		HTOEAU_WIDGETS_URL . 'assets/css/frontend.css',
		$deps,
		HTOEAU_WIDGETS_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_pdp_acf_faq_styles', 25 );
