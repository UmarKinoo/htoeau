<?php
/**
 * Align Mollie Components card fields with HtoEAU checkout inputs (46px, vertically balanced text).
 *
 * Field text and placeholders render inside Mollie’s cross-origin iframes, so theme CSS cannot
 * set line-height or padding there. The plugin serializes styles from options `mollie_components_*`.
 *
 * @package Hello_Elementor_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Line height for Mollie iframe inputs — match .htoeau-checkout-form text inputs (46px tall).
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function htoeau_mollie_components_line_height( $value ) {
	if ( false === $value || null === $value || '' === $value ) {
		return '46px';
	}
	// Plugin default leaves placeholder/text visually top-heavy in the field.
	if ( is_string( $value ) && preg_match( '/^\s*1\.2\s*$/', $value ) ) {
		return '46px';
	}
	return $value;
}

/**
 * Horizontal padding only so vertical centering follows lineHeight (matches checkout inputs 0 14px).
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function htoeau_mollie_components_padding( $value ) {
	if ( false === $value || null === $value || '' === $value ) {
		return '0 14px';
	}
	if ( is_string( $value ) && preg_match( '/\.63\s*em/', $value ) ) {
		return '0 14px';
	}
	return $value;
}

add_filter( 'option_mollie_components_lineHeight', 'htoeau_mollie_components_line_height', 20 );
add_filter( 'option_mollie_components_padding', 'htoeau_mollie_components_padding', 20 );
