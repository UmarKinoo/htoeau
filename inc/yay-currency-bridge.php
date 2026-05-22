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
 * htoeau_display_ccy cookie override (?htoeau_ccy=GBP|EUR).
 *
 * @return string GBP|EUR or empty.
 */
function htoeau_child_yay_htoeau_cookie_currency_code() {
	if ( ! defined( 'HTOEAU_FX_COOKIE' ) || ! function_exists( 'htoeau_child_fx_supported_codes' ) ) {
		return '';
	}
	$cookie  = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$allowed = htoeau_child_fx_supported_codes();
	return ( $cookie && in_array( $cookie, $allowed, true ) ) ? $cookie : '';
}

/**
 * Visitor chose a currency in Yay’s switcher (persisted cookie).
 */
function htoeau_child_yay_has_user_currency_cookie() {
	return isset( $_COOKIE['yay_currency_widget'] ) && '' !== (string) $_COOKIE['yay_currency_widget']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * Build a Yay apply-currency row when GBP/EUR is not configured in Yay (e.g. EUR-only store).
 *
 * @param string $code GBP|EUR.
 * @return array|false
 */
function htoeau_child_yay_synthetic_apply_currency_row( $code ) {
	if ( ! htoeau_child_yay_currency_is_active() || ! class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		return false;
	}
	$code  = strtoupper( (string) $code );
	$store = function_exists( 'htoeau_child_fx_store_currency_code' )
		? htoeau_child_fx_store_currency_code()
		: strtoupper( (string) get_option( 'woocommerce_currency', 'GBP' ) );
	if ( ! $code || $code === $store || ! in_array( $code, htoeau_child_fx_supported_codes(), true ) ) {
		return false;
	}
	$base = \Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code( $store );
	if ( ! is_array( $base ) ) {
		$base = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
	}
	if ( ! is_array( $base ) ) {
		return false;
	}
	$row              = $base;
	$row['currency']  = $code;
	$row['rate']      = 1;
	$row['fee']       = array(
		'value' => '0',
		'type'  => 'fixed',
	);
	return htoeau_child_yay_apply_parity_to_currency_row( $row );
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

	$param = apply_filters( 'yay_currency_param_name', 'yay-currency' );
	if ( isset( $_REQUEST[ $param ] ) && '' !== sanitize_text_field( wp_unslash( $_REQUEST[ $param ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $apply_currency;
	}

	// ?htoeau_ccy= or geo — must beat a stale yay_currency_widget (e.g. EUR) cookie.
	$code = htoeau_child_fx_resolve_target_currency_code();
	if ( $code && class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		$custom = \Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code( $code );
		if ( is_array( $custom ) && ! empty( $custom['currency'] ) ) {
			return htoeau_child_yay_apply_parity_to_currency_row( $custom );
		}
		$synthetic = htoeau_child_yay_synthetic_apply_currency_row( $code );
		if ( is_array( $synthetic ) && ! empty( $synthetic['currency'] ) ) {
			return $synthetic;
		}
	}

	// No HtoEAU override: keep visitor’s Yay switcher choice.
	if ( htoeau_child_yay_has_user_currency_cookie() ) {
		return $apply_currency;
	}

	return $apply_currency;
}
add_filter( 'yay_currency_apply_currency', 'htoeau_child_yay_apply_geo_currency', 15, 1 );

/**
 * Align Yay’s widget cookie with htoeau_display_ccy (Yay reads this before apply_currency filters).
 */
function htoeau_child_yay_prime_widget_cookie_from_htoeau() {
	if ( ! htoeau_child_yay_currency_is_active() || headers_sent() ) {
		return;
	}
	$code = htoeau_child_yay_htoeau_cookie_currency_code();
	if ( ! $code || ! class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		return;
	}
	$apply = \Yay_Currency\Helpers\YayCurrencyHelper::get_currency_by_currency_code( $code );
	if ( ! is_array( $apply ) || empty( $apply['ID'] ) ) {
		$apply = htoeau_child_yay_synthetic_apply_currency_row( $code );
	}
	if ( ! is_array( $apply ) || empty( $apply['ID'] ) ) {
		return;
	}
	$_COOKIE['yay_currency_widget'] = (string) (int) $apply['ID']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( ! headers_sent() ) {
		\Yay_Currency\Helpers\YayCurrencyHelper::set_cookies( $apply );
	}
}
add_action( 'wp_loaded', 'htoeau_child_yay_prime_widget_cookie_from_htoeau', 5 );

/**
 * When store is EUR but we display GBP (cookie/geo), tell Woo/Yay the active code is GBP.
 *
 * @param string $currency WooCommerce currency code.
 * @return string
 */
function htoeau_child_yay_filter_woocommerce_currency( $currency ) {
	static $in_filter = false;

	if ( $in_filter || ( is_admin() && ! wp_doing_ajax() ) ) {
		return $currency;
	}
	if ( ! htoeau_child_yay_currency_is_active() ) {
		return $currency;
	}

	$in_filter = true;
	$override  = htoeau_child_yay_htoeau_cookie_currency_code();
	if ( $override ) {
		$in_filter = false;
		return $override;
	}
	if ( function_exists( 'htoeau_child_fx_resolve_target_currency_code' ) ) {
		$resolved = htoeau_child_fx_resolve_target_currency_code();
		if ( $resolved && in_array( $resolved, htoeau_child_fx_supported_codes(), true ) ) {
			$in_filter = false;
			return $resolved;
		}
	}
	if ( ! htoeau_child_yay_has_user_currency_cookie() ) {
		$guessed = htoeau_child_fx_geo_guess_display_currency();
		if ( $guessed && in_array( $guessed, htoeau_child_fx_supported_codes(), true ) ) {
			$in_filter = false;
			return $guessed;
		}
	}
	$in_filter = false;
	return $currency;
}
add_filter( 'woocommerce_currency', 'htoeau_child_yay_filter_woocommerce_currency', 99999 );

/**
 * GBP store + EUR browse: same numeric amount, different symbol/format (rate = 1).
 *
 * Yay’s converted-currency list stores rate as an array; get_rate_fee expects a number.
 * Force a numeric rate of 1 for non-store currencies to avoid PHP fatals and keep 1:1 prices.
 *
 * @param array $apply_currency Yay currency row.
 * @return array
 */
function htoeau_child_yay_apply_parity_to_currency_row( $apply_currency ) {
	if ( ! htoeau_child_yay_currency_is_active() || ! is_array( $apply_currency ) ) {
		return $apply_currency;
	}

	$store = function_exists( 'htoeau_child_fx_store_currency_code' )
		? htoeau_child_fx_store_currency_code()
		: strtoupper( (string) get_option( 'woocommerce_currency', 'GBP' ) );
	$code  = isset( $apply_currency['currency'] ) ? strtoupper( (string) $apply_currency['currency'] ) : '';

	if ( ! $code || $code === $store || ! in_array( $code, htoeau_child_fx_supported_codes(), true ) ) {
		return $apply_currency;
	}

	$apply_currency['rate'] = 1;

	if ( isset( $apply_currency['fee'] ) && is_array( $apply_currency['fee'] ) ) {
		$apply_currency['fee']['value'] = 0;
		$apply_currency['fee']['type']  = 'fixed';
	}

	return $apply_currency;
}
add_filter( 'yay_currency_apply_currency', 'htoeau_child_yay_apply_parity_to_currency_row', 25, 1 );

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
		$apply = htoeau_child_yay_apply_parity_to_currency_row( $apply );
		\Yay_Currency\Helpers\YayCurrencyHelper::set_cookie_currency_switcher( (int) $apply['ID'], true );
		\Yay_Currency\Helpers\YayCurrencyHelper::set_cookies( $apply );
		$_COOKIE['yay_currency_widget'] = (string) (int) $apply['ID']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
}
