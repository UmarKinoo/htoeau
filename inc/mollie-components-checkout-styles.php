<?php
/**
 * Align Mollie Components card fields with HtoEAU checkout: centered label text via padding + line-height.
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
 * Line height for Mollie iframe inputs — keep to one line box; vertical centering uses padding.
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function htoeau_mollie_components_line_height( $value ) {
	if ( false === $value || null === $value || '' === $value ) {
		return '16px';
	}
	// Unitless 1.2 or tall line boxes leave MM/YY visually stuck to the top.
	if ( is_string( $value ) && preg_match( '/^\s*1\.2\s*$/', $value ) ) {
		return '16px';
	}
	if ( is_numeric( $value ) && abs( (float) $value - 1.2 ) < 0.0001 ) {
		return '16px';
	}
	if ( is_string( $value ) && preg_match( '/^\s*46px\s*$/', $value ) ) {
		return '16px';
	}
	return $value;
}

/**
 * Symmetric vertical padding + horizontal padding ≈ 46px total field (15 + 16 + 15).
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function htoeau_mollie_components_padding( $value ) {
	if ( false === $value || null === $value || '' === $value ) {
		return '15px 14px';
	}
	if ( is_string( $value ) && preg_match( '/\.63\s*em/', $value ) ) {
		return '15px 14px';
	}
	if ( is_string( $value ) && preg_match( '/^\s*0\s+14px\s*$/', $value ) ) {
		return '15px 14px';
	}
	return $value;
}

/**
 * Slightly smaller than 16px so “MM / YY” matches checkout field scale.
 *
 * @param mixed $value Option value.
 * @return mixed
 */
function htoeau_mollie_components_font_size( $value ) {
	if ( false === $value || null === $value || '' === $value ) {
		return '15px';
	}
	if ( is_string( $value ) && '16px' === trim( $value ) ) {
		return '15px';
	}
	return $value;
}

add_filter( 'option_mollie_components_lineHeight', 'htoeau_mollie_components_line_height', 20 );
add_filter( 'option_mollie_components_padding', 'htoeau_mollie_components_padding', 20 );
add_filter( 'option_mollie_components_fontSize', 'htoeau_mollie_components_font_size', 20 );
