<?php
/**
 * Yay Currency (free) integration: geo GBP/EUR rules + legacy test overrides.
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Yay Currency is loaded and the store currency supports our GBP/EUR setup.
 */
function htoeau_child_yay_currency_is_active() {
	if ( ! class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		return false;
	}
	if ( ! function_exists( 'htoeau_child_fx_store_supports_display_fx' ) ) {
		return false;
	}
	return htoeau_child_fx_store_supports_display_fx();
}

/**
 * Visitor chose a currency in Yay’s switcher (persisted cookie).
 */
function htoeau_child_yay_has_user_currency_cookie() {
	return isset( $_COOKIE['yay_currency_widget'] ) && '' !== (string) $_COOKIE['yay_currency_widget']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * Target currency code from htoeau test cookie or geo rules.
 *
 * @return string GBP|EUR|empty
 */
function htoeau_child_fx_resolve_target_currency_code() {
	if ( ! function_exists( 'htoeau_child_fx_supported_codes' ) ) {
		return '';
	}
	$allowed = htoeau_child_fx_supported_codes();
	$cookie  = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $cookie && in_array( $cookie, $allowed, true ) ) {
		return $cookie;
	}
	if ( function_exists( 'htoeau_child_fx_geo_guess_display_currency' ) ) {
		$guessed = htoeau_child_fx_geo_guess_display_currency();
		if ( $guessed && in_array( $guessed, $allowed, true ) ) {
			return $guessed;
		}
	}
	return '';
}

/**
 * Apply HtoEAU geo / test-currency rules to Yay’s active currency object.
 *
 * @param array $apply_currency Yay currency row.
 * @return array
 */
function htoeau_child_yay_apply_geo_currency( $apply_currency ) {
	if ( ! htoeau_child_yay_currency_is_active() ) {
		return $apply_currency;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $apply_currency;
	}

	// Respect explicit Yay switcher or ?yay-currency= selection.
	if ( htoeau_child_yay_has_user_currency_cookie() ) {
		return $apply_currency;
	}
	$param = apply_filters( 'yay_currency_param_name', 'yay-currency' );
	if ( isset( $_REQUEST[ $param ] ) && '' !== sanitize_text_field( wp_unslash( $_REQUEST[ $param ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $apply_currency;
	}

	$code = htoeau_child_fx_resolve_target_currency_code();
	if ( ! $code || ! class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		return $apply_currency;
	}

	$custom = \Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code( $code );
	if ( is_array( $custom ) && ! empty( $custom['currency'] ) ) {
		return $custom;
	}

	return $apply_currency;
}
add_filter( 'yay_currency_apply_currency', 'htoeau_child_yay_apply_geo_currency', 15, 1 );

/**
 * Sync ?htoeau_ccy= overrides into Yay’s switcher cookie.
 *
 * @param string $code GBP|EUR.
 */
function htoeau_child_yay_sync_currency_cookie( $code ) {
	if ( ! htoeau_child_yay_currency_is_active() || ! class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		return;
	}
	$apply = \Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code( strtoupper( $code ) );
	if ( is_array( $apply ) && ! empty( $apply['ID'] ) ) {
		\Yay_Currency\Helpers\YayCurrencyHelper::set_cookie_currency_switcher( (int) $apply['ID'], true );
	}
}
