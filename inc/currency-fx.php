<?php
/**
 * GBP ↔ EUR display conversion (store currency stays the WooCommerce setting; checkout unchanged).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

define( 'HTOEAU_FX_COOKIE', 'htoeau_display_ccy' );
define( 'HTOEAU_FX_MANUAL_COOKIE', 'htoeau_display_ccy_manual' );

/**
 * Tell LiteSpeed Cache to vary by our currency cookie so GBP/EUR get separate cached pages.
 */
function htoeau_child_fx_litespeed_vary_cookie( $cookies ) {
	$cookies[] = HTOEAU_FX_COOKIE;
	return $cookies;
}
add_filter( 'litespeed_vary_cookies', 'htoeau_child_fx_litespeed_vary_cookie' );

/**
 * Also set the Vary cookie via LiteSpeed's API if available.
 */
function htoeau_child_fx_litespeed_vary_init() {
	if ( ! method_exists( 'LiteSpeed\Vary', 'add' ) ) {
		return;
	}
	$code = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $code && in_array( $code, array( 'GBP', 'EUR' ), true ) ) {
		\LiteSpeed\Vary::add( HTOEAU_FX_COOKIE, $code );
	}
}
add_action( 'litespeed_vary', 'htoeau_child_fx_litespeed_vary_init' );

/**
 * Separate LiteSpeed cache entries per Cloudflare country (geo currency without a prior cookie).
 */
function htoeau_child_fx_litespeed_vary_cf_country() {
	if ( ! method_exists( 'LiteSpeed\Vary', 'add' ) ) {
		return;
	}
	if ( empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return;
	}
	$c = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( preg_match( '/^[A-Z]{2}$/', $c ) && 'XX' !== $c && 'T1' !== $c ) {
		\LiteSpeed\Vary::add( 'cf_ipcountry', $c );
	}
}
add_action( 'litespeed_vary', 'htoeau_child_fx_litespeed_vary_cf_country', 5 );

/**
 * Mark htoeau_display_ccy as a vary cookie in LiteSpeed via constant (fallback for older versions).
 */
function htoeau_child_fx_litespeed_conf_vary( $vary ) {
	if ( is_string( $vary ) ) {
		$cookies = array_filter( array_map( 'trim', explode( ',', $vary ) ) );
	} else {
		$cookies = is_array( $vary ) ? $vary : array();
	}
	if ( ! in_array( HTOEAU_FX_COOKIE, $cookies, true ) ) {
		$cookies[] = HTOEAU_FX_COOKIE;
	}
	return implode( ',', $cookies );
}
add_filter( 'litespeed_conf_vary__cookies', 'htoeau_child_fx_litespeed_conf_vary' );

/**
 * Currencies we can convert between (store must be one of these for FX UI to show).
 *
 * @return string[]
 */
function htoeau_child_fx_supported_codes() {
	return apply_filters( 'htoeau_fx_supported_currencies', array( 'GBP', 'EUR' ) );
}

/**
 * Store currency is GBP or EUR (required for browse FX).
 * Uses get_option directly to avoid triggering woocommerce_currency filter (recursion risk).
 */
function htoeau_child_fx_store_supports_display_fx() {
	if ( ! function_exists( 'get_woocommerce_currency' ) ) {
		return false;
	}
	$store = strtoupper( (string) get_option( 'woocommerce_currency', '' ) );
	return in_array( $store, htoeau_child_fx_supported_codes(), true );
}

/**
 * Legacy symbol-swap FX (disabled when Yay Currency handles conversion).
 */
function htoeau_child_fx_legacy_symbol_swap_enabled() {
	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) {
		return false;
	}
	return htoeau_child_fx_store_supports_display_fx();
}

/**
 * Whether FX module applies (Woo active, store currency is GBP or EUR).
 */
function htoeau_child_fx_is_enabled() {
	return htoeau_child_fx_store_supports_display_fx();
}

/**
 * Decimal separator for a currency code (EUR → comma, GBP → dot).
 *
 * @param string $code GBP|EUR.
 * @return string
 */
function htoeau_child_fx_decimal_separator_for_code( $code ) {
	return 'EUR' === strtoupper( (string) $code ) ? ',' : '.';
}

/**
 * Persist display-currency override cookie (readable by JS and PHP).
 *
 * @param string $code   GBP|EUR.
 * @param bool   $manual True when set via /ccy-gbp/, hash, or ?htoeau_ccy= (testing).
 */
function htoeau_child_fx_set_display_currency_cookie( $code, $manual = false ) {
	$code = strtoupper( (string) $code );
	if ( ! in_array( $code, htoeau_child_fx_supported_codes(), true ) || headers_sent() ) {
		return;
	}
	$path   = COOKIEPATH ? COOKIEPATH : '/';
	$domain = COOKIE_DOMAIN;
	$secure = is_ssl();
	$expiry = time() + YEAR_IN_SECONDS;

	setcookie( HTOEAU_FX_COOKIE, $code, $expiry, $path, $domain, $secure, false );
	$_COOKIE[ HTOEAU_FX_COOKIE ] = $code; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( $manual ) {
		setcookie( HTOEAU_FX_MANUAL_COOKIE, '1', $expiry, $path, $domain, $secure, false );
		$_COOKIE[ HTOEAU_FX_MANUAL_COOKIE ] = '1'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	} else {
		setcookie( HTOEAU_FX_MANUAL_COOKIE, '', time() - HOUR_IN_SECONDS, $path, $domain, $secure, false );
		unset( $_COOKIE[ HTOEAU_FX_MANUAL_COOKIE ] );
	}

	if ( function_exists( 'htoeau_child_yay_sync_currency_cookie' ) ) {
		htoeau_child_yay_sync_currency_cookie( $code );
	}
}

/**
 * Whether the visitor explicitly chose a test currency (not geo-derived).
 *
 * @return bool
 */
function htoeau_child_fx_is_manual_currency_override() {
	return isset( $_COOKIE[ HTOEAU_FX_MANUAL_COOKIE ] ) && '1' === (string) $_COOKIE[ HTOEAU_FX_MANUAL_COOKIE ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}

/**
 * Whether browse formatting (symbol + decimal separator) should follow display currency.
 */
function htoeau_child_fx_applies_display_formatting() {
	if ( ! htoeau_child_fx_is_enabled() ) {
		return false;
	}
	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) {
		return true;
	}
	return htoeau_child_fx_legacy_symbol_swap_enabled();
}

/**
 * Staging / local host (GeoIP API fallback enabled by default).
 *
 * @return bool
 */
function htoeau_child_fx_is_staging_or_local_host() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) $_SERVER['HTTP_HOST'] ) : '';
	if ( false !== strpos( $host, 'staging.' ) || false !== strpos( $host, '.local' ) ) {
		return true;
	}
	return defined( 'WP_ENVIRONMENT_TYPE' ) && in_array( WP_ENVIRONMENT_TYPE, array( 'staging', 'local', 'development' ), true );
}

/**
 * Valid Cloudflare CF-IPCountry from the current request, if any.
 *
 * @return string ISO code or empty.
 */
function htoeau_child_fx_cloudflare_country_from_request() {
	if ( empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return '';
	}
	$c = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( preg_match( '/^[A-Z]{2}$/', $c ) && 'XX' !== $c && 'T1' !== $c ) {
		return $c;
	}
	return '';
}

/**
 * Use WooCommerce ip-api.com when MaxMind DB is missing.
 * Default: on whenever Cloudflare did not send a country (staging and production).
 *
 * @return bool
 */
function htoeau_child_fx_geolocation_api_fallback_enabled() {
	$default = '' === htoeau_child_fx_cloudflare_country_from_request();
	return (bool) apply_filters( 'htoeau_fx_geolocation_api_fallback', $default );
}

/**
 * Allow ?htoeau_country=MU test override (default on staging/local).
 *
 * @return bool
 */
function htoeau_child_fx_allow_test_country_override() {
	return (bool) apply_filters( 'htoeau_fx_allow_test_country_override', htoeau_child_fx_is_staging_or_local_host() );
}

/**
 * Test country from ?htoeau_country= or htoeau_test_country cookie (staging).
 *
 * @return string ISO code or empty.
 */
function htoeau_child_fx_get_test_country_override() {
	if ( ! htoeau_child_fx_allow_test_country_override() ) {
		return '';
	}
	if ( isset( $_GET['htoeau_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$c = strtoupper( sanitize_text_field( wp_unslash( $_GET['htoeau_country'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( preg_match( '/^[A-Z]{2}$/', $c ) ) {
			return $c;
		}
	}
	if ( isset( $_COOKIE['htoeau_test_country'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$c = strtoupper( sanitize_text_field( wp_unslash( $_COOKIE['htoeau_test_country'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( preg_match( '/^[A-Z]{2}$/', $c ) ) {
			return $c;
		}
	}
	return '';
}

/**
 * Persist ?htoeau_country= for geo testing on staging.
 */
function htoeau_child_fx_capture_test_country_query() {
	if ( ! htoeau_child_fx_allow_test_country_override() || ! isset( $_GET['htoeau_country'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$c = strtoupper( sanitize_text_field( wp_unslash( $_GET['htoeau_country'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! preg_match( '/^[A-Z]{2}$/', $c ) || headers_sent() ) {
		return;
	}
	setcookie( 'htoeau_test_country', $c, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
	$_COOKIE['htoeau_test_country'] = $c; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
}
add_action( 'init', 'htoeau_child_fx_capture_test_country_query', 1 );

/**
 * WooCommerce GeoIP lookup (MaxMind DB, then optional cached API on staging).
 *
 * @return string Country code or empty.
 */
function htoeau_child_fx_wc_geolocate_country_code() {
	if ( ! class_exists( 'WC_Geolocation' ) ) {
		return '';
	}

	$ip = WC_Geolocation::get_ip_address();
	if ( ! $ip ) {
		return '';
	}

	$api     = htoeau_child_fx_geolocation_api_fallback_enabled();
	$use_api = $api;
	$cache_key = 'htoeau_wc_geo_' . md5( $ip . ( $use_api ? 'api' : 'db' ) );

	if ( $use_api ) {
		$cached = get_transient( $cache_key );
		if ( is_string( $cached ) && preg_match( '/^[A-Z]{2}$/', $cached ) ) {
			return $cached;
		}
	}

	$geo = WC_Geolocation::geolocate_ip( $ip, true, $use_api );
	$code = '';
	if ( ! empty( $geo['country'] ) ) {
		$c = strtoupper( (string) $geo['country'] );
		if ( preg_match( '/^[A-Z]{2}$/', $c ) && 'XX' !== $c ) {
			$code = $c;
		}
	}

	if ( $use_api ) {
		set_transient( $cache_key, $code, DAY_IN_SECONDS );
	}

	return $code;
}

/**
 * Resolve visitor country once (Cloudflare → test override → WooCommerce GeoIP).
 *
 * @return array{code: string, source: string} source = cloudflare|test_override|woocommerce|woocommerce_api|none.
 */
function htoeau_child_fx_resolve_visitor_country() {
	static $resolved = null;

	if ( null !== $resolved ) {
		return $resolved;
	}

	$resolved = array(
		'code'   => '',
		'source' => 'none',
	);

	$cf = htoeau_child_fx_cloudflare_country_from_request();
	if ( $cf ) {
		$resolved['code']   = $cf;
		$resolved['source'] = 'cloudflare';
		return $resolved;
	}

	$test = htoeau_child_fx_get_test_country_override();
	if ( $test ) {
		$resolved['code']   = $test;
		$resolved['source'] = 'test_override';
		return $resolved;
	}

	$wc_code = htoeau_child_fx_wc_geolocate_country_code();
	if ( $wc_code ) {
		$resolved['code']   = $wc_code;
		$resolved['source'] = htoeau_child_fx_geolocation_api_fallback_enabled() ? 'woocommerce_api' : 'woocommerce';
		return $resolved;
	}

	return $resolved;
}

/**
 * Two-letter country code for the visitor.
 *
 * @return string Empty string if unknown.
 */
function htoeau_child_fx_detect_country_code() {
	$r = htoeau_child_fx_resolve_visitor_country();
	return $r['code'];
}

/**
 * How the visitor country was resolved (for debug).
 *
 * @return string cloudflare|woocommerce|none
 */
function htoeau_child_fx_get_country_detection_source() {
	$r = htoeau_child_fx_resolve_visitor_country();
	return $r['source'];
}

/**
 * Pick GBP or EUR for display from visitor country when the store supports FX.
 *
 * @return string
 */
function htoeau_child_fx_store_currency_code() {
	return strtoupper( (string) get_option( 'woocommerce_currency', 'GBP' ) );
}

function htoeau_child_fx_geo_guess_display_currency() {
	$store   = htoeau_child_fx_store_currency_code();
	$country = htoeau_child_fx_detect_country_code();
	$eur     = apply_filters(
		'htoeau_fx_eur_display_countries',
		array()
	);
	$gbp     = apply_filters(
		'htoeau_fx_gbp_display_countries',
		array( 'GB', 'GG', 'JE', 'IM', 'GI', 'MU', 'CA' )
	);
	if ( 'GBP' === $store ) {
		if ( $country && in_array( $country, $gbp, true ) ) {
			return 'GBP';
		}
		return 'EUR';
	}
	if ( 'EUR' === $store ) {
		if ( $country && in_array( $country, $gbp, true ) ) {
			return 'GBP';
		}
		return 'EUR';
	}
	return $store;
}

/**
 * Cookie override or geo: which currency to show prices in.
 * Priority: htoeau_display_ccy cookie → geo → Yay detect → store default.
 */
function htoeau_child_fx_get_display_currency() {
	static $cached = null;

	if ( null !== $cached ) {
		return $cached;
	}

	$store = htoeau_child_fx_store_currency_code();

	if ( ! htoeau_child_fx_is_enabled() ) {
		$cached = $store;
		return $cached;
	}

	$allowed = htoeau_child_fx_supported_codes();

	// 1. Our cookie wins (set via hash override or Yay switcher sync).
	$cookie = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $cookie && in_array( $cookie, $allowed, true ) ) {
		$cached = $cookie;
		return $cached;
	}

	// 2. Geo (automatic; no on-page currency switcher — see htoeau_child_yay_hide_switcher_ui).
	$guessed = htoeau_child_fx_geo_guess_display_currency();
	if ( $guessed && in_array( $guessed, $allowed, true ) ) {
		$cached = $guessed;
		return $cached;
	}

	// 3. Yay fallback (internal default row, not a visitor switcher choice).
	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active()
		&& class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		$apply = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		if ( is_array( $apply ) && ! empty( $apply['currency'] ) ) {
			$cached = strtoupper( (string) $apply['currency'] );
			return $cached;
		}
	}

	$cached = $store;
	return $cached;
}

/**
 * Convert a store-currency amount to the current display currency.
 *
 * @param float $amount Amount in shop (store) currency.
 */
function htoeau_child_fx_convert_amount( $amount ) {
	static $converting = false;

	$amount = (float) $amount;
	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) {
		$store   = htoeau_child_fx_store_currency_code();
		$display = htoeau_child_fx_get_display_currency();
		if ( $display && $store && $display !== $store ) {
			return $amount;
		}
		if ( $converting ) {
			return $amount;
		}
		$converting = true;
		$amount     = (float) apply_filters( 'yay_currency_convert_price', $amount );
		$converting = false;
	}
	return $amount;
}

/**
 * Format a store-currency amount using wc_price in the active display currency when needed.
 *
 * @param float $amount Store-currency amount.
 * @param array $args   Extra wc_price args.
 */
function htoeau_child_fx_wc_price( $amount, $args = array() ) {
	$amount = (float) $amount;
	if ( ! is_array( $args ) ) {
		$args = array();
	}

	$display_ccy = function_exists( 'htoeau_child_fx_get_display_currency' )
		? htoeau_child_fx_get_display_currency()
		: ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '' );
	$display_ccy = strtoupper( (string) $display_ccy );

	if ( ! isset( $args['decimal_separator'] ) && $display_ccy ) {
		$args['decimal_separator'] = htoeau_child_fx_decimal_separator_for_code( $display_ccy );
	}

	$symbol_map = array(
		'GBP' => html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' ),
		'EUR' => html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' ),
	);

	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) {
		$store   = htoeau_child_fx_store_currency_code();
		$display = htoeau_child_fx_get_display_currency();
		if ( $display && in_array( $display, htoeau_child_fx_supported_codes(), true ) ) {
			$args['currency'] = $display;
			$converted        = ( $store !== $display ) ? $amount : htoeau_child_fx_convert_amount( $amount );
			$formatted        = wc_price( $converted, $args );
			$target_symbol    = isset( $symbol_map[ $display ] ) ? $symbol_map[ $display ] : '';
			if ( $target_symbol ) {
				$formatted = str_replace( array_values( $symbol_map ), $target_symbol, $formatted );
			}
			return $formatted;
		}
		return wc_price( htoeau_child_fx_convert_amount( $amount ), $args );
	}

	if ( ! htoeau_child_fx_legacy_symbol_swap_enabled() ) {
		return wc_price( $amount, $args );
	}
	$store   = get_woocommerce_currency();
	$display = htoeau_child_fx_get_display_currency();
	$symbol_map    = array(
		'GBP' => html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' ),
		'EUR' => html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' ),
	);
	$target_symbol = isset( $symbol_map[ $display ] )
		? $symbol_map[ $display ]
		: ( function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol( $display ), ENT_QUOTES, 'UTF-8' ) : '' );
	$known_symbols = array_values( $symbol_map );
	$known_symbols[] = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol( 'GBP' ), ENT_QUOTES, 'UTF-8' ) : html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' );
	$known_symbols[] = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol( 'EUR' ), ENT_QUOTES, 'UTF-8' ) : html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' );
	$known_symbols   = array_unique( array_filter( $known_symbols ) );
	if ( $store === $display ) {
		$formatted = wc_price( $amount, $args );
		if ( $target_symbol ) {
			$formatted = str_replace( $known_symbols, $target_symbol, $formatted );
		}
		return $formatted;
	}
	$args['currency'] = $display;
	$formatted        = wc_price( htoeau_child_fx_convert_amount( $amount ), $args );
	if ( $target_symbol ) {
		$formatted = str_replace( $known_symbols, $target_symbol, $formatted );
	}
	return $formatted;
}

/**
 * URL to set the display-currency test cookie (hash; avoids hosts that 503 on query strings).
 *
 * @param string $code     GBP|EUR.
 * @param string $base_url Page URL without hash; defaults to current request path.
 * @return string
 */
function htoeau_child_fx_currency_override_url( $code, $base_url = '' ) {
	$code = strtoupper( (string) $code );
	if ( ! in_array( $code, htoeau_child_fx_supported_codes(), true ) ) {
		return '';
	}
	if ( ! $base_url ) {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = strtok( $path, '?' );
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$base_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $path;
	}
	$base_url = remove_query_arg( 'htoeau_ccy', $base_url );
	return $base_url . '#htoeau_ccy=' . rawurlencode( $code );
}

/**
 * Set display currency cookie from ?htoeau_ccy=GBP|EUR and redirect (strip query arg).
 * Some hosts return 503 before PHP for any ?query=; use #htoeau_ccy= via htoeau_child_fx_currency_override_url().
 */
function htoeau_child_fx_capture_query_currency() {
	if ( ! isset( $_GET['htoeau_ccy'] ) || ! htoeau_child_fx_is_enabled() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$code = strtoupper( sanitize_text_field( wp_unslash( $_GET['htoeau_ccy'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! in_array( $code, htoeau_child_fx_supported_codes(), true ) ) {
		return;
	}
	htoeau_child_fx_set_display_currency_cookie( $code );
	wp_safe_redirect( remove_query_arg( 'htoeau_ccy' ) );
	exit;
}
add_action( 'init', 'htoeau_child_fx_capture_query_currency', 2 );

/**
 * /ccy-gbp/ or /ccy-eur/ — set cookie server-side (no query string needed).
 * Runs late (template_redirect) so WooCommerce is fully loaded.
 */
function htoeau_child_fx_capture_ccy_slug_request() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path = strtok( $uri, '?' );
	if ( ! $path || ! preg_match( '#^/ccy-(gbp|eur)/?$#i', $path, $matches ) ) {
		return;
	}
	$code = strtoupper( sanitize_text_field( $matches[1] ) );
	if ( ! in_array( $code, htoeau_child_fx_supported_codes(), true ) ) {
		return;
	}
	htoeau_child_fx_set_display_currency_cookie( $code );
	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = home_url( '/product/htoeau-hydrogen-water-2/' );
	}
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'template_redirect', 'htoeau_child_fx_capture_ccy_slug_request', 1 );

/**
 * Load hash currency override script (footer; works when page cache omits wp_enqueue output).
 */
function htoeau_child_fx_print_hash_override_script() {
	if ( ! htoeau_child_fx_is_enabled() ) {
		return;
	}
	$src = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/currency-override.js';
	$ver = defined( 'HTOEAU_CHILD_VERSION' ) ? HTOEAU_CHILD_VERSION : '1.6.8';
	printf(
		'<script id="htoeau-currency-override-js" src="%s"></script>' . "\n",
		esc_url( $src . '?ver=' . rawurlencode( $ver ) )
	);
}
add_action( 'wp_footer', 'htoeau_child_fx_print_hash_override_script', 1 );

/**
 * Inline hash handler on PDP (loads even when footer assets are cached out of date).
 */
function htoeau_child_fx_print_inline_hash_override_script() {
	if ( ! htoeau_child_fx_is_enabled() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
	<script id="htoeau-currency-override-inline">
	(function(){var m=(location.hash||'').match(/^#htoeau_ccy=(GBP|EUR)$/i);if(!m)return;var c=m[1].toUpperCase(),n='htoeau_display_ccy',s=document.cookie.split(';').map(function(p){return p.trim()}).filter(function(p){return p.indexOf(n+'=')===0})[0];if(s&&s.split('=')[1]===c&&history.replaceState){history.replaceState(null,'',location.pathname+location.search);return;}var x='; path=/; max-age=0; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');document.cookie='yay_currency_widget='+x;document.cookie='yay_currency='+x;document.cookie=n+'='+c+'; path=/; max-age=31536000; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');if(history.replaceState){history.replaceState(null,'',location.pathname+location.search);}location.reload();})();
	</script>
	<?php
}
add_action( 'wp_footer', 'htoeau_child_fx_print_inline_hash_override_script', 0 );

/**
 * Rebuild price HTML in the visitor’s display currency.
 *
 * @param string     $html    Default HTML.
 * @param WC_Product $product Product.
 */
function htoeau_child_fx_filter_price_html( $html, $product ) {
	if ( ! htoeau_child_fx_legacy_symbol_swap_enabled() ) {
		return $html;
	}
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $html;
	}
	$store   = get_woocommerce_currency();
	$display = htoeau_child_fx_get_display_currency();
	if ( $store === $display ) {
		return $html;
	}
	$args = array( 'currency' => $display );

	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		if ( empty( $prices['price'] ) ) {
			return $html;
		}
		$min_price     = (float) current( $prices['price'] );
		$max_price     = (float) end( $prices['price'] );
		$min_reg_price = (float) current( $prices['regular_price'] );
		$max_reg_price = (float) end( $prices['regular_price'] );

		if ( $min_price !== $max_price ) {
			$price = sprintf(
				/* translators: 1: low price 2: high price */
				_x( '%1$s <span aria-hidden="true">&ndash;</span> %2$s', 'Price range: from-to', 'woocommerce' ),
				wc_price( htoeau_child_fx_convert_amount( $min_price ), $args ),
				wc_price( htoeau_child_fx_convert_amount( $max_price ), $args )
			);
		} elseif ( $product->is_on_sale() && $min_reg_price === $max_reg_price ) {
			$price = wc_format_sale_price(
				wc_price( htoeau_child_fx_convert_amount( $max_reg_price ), $args ),
				wc_price( htoeau_child_fx_convert_amount( $min_price ), $args )
			);
		} else {
			$price = wc_price( htoeau_child_fx_convert_amount( $min_price ), $args );
		}
		return $price . $product->get_price_suffix();
	}

	if ( '' === $product->get_price() ) {
		return $html;
	}

	if ( $product->is_on_sale() ) {
		$reg = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
		$cur = (float) wc_get_price_to_display( $product );
		return wc_format_sale_price(
			wc_price( htoeau_child_fx_convert_amount( $reg ), $args ),
			wc_price( htoeau_child_fx_convert_amount( $cur ), $args )
		) . $product->get_price_suffix();
	}

	$cur = (float) wc_get_price_to_display( $product );
	return wc_price( htoeau_child_fx_convert_amount( $cur ), $args ) . $product->get_price_suffix();
}
add_filter( 'woocommerce_get_price_html', 'htoeau_child_fx_filter_price_html', 999, 2 );

/**
 * Decimal separator by displayed currency:
 * - GBP: dot (.)
 * - EUR: comma (,)
 *
 * Hooks Woo’s `wc_get_price_decimal_separator` filter (not the option id `woocommerce_price_decimal_sep`).
 *
 * @param string $separator Separator from option (filter first argument).
 * @return string
 */
function htoeau_child_fx_price_decimal_separator( $separator ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $separator;
	}
	if ( ! htoeau_child_fx_applies_display_formatting() ) {
		return $separator;
	}

	$currency = function_exists( 'htoeau_child_fx_get_display_currency' )
		? htoeau_child_fx_get_display_currency()
		: ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '' );

	$currency = strtoupper( (string) $currency );
	return htoeau_child_fx_decimal_separator_for_code( $currency );
}
add_filter( 'wc_get_price_decimal_separator', 'htoeau_child_fx_price_decimal_separator', 50, 1 );

/**
 * Frontend currency symbol by display mode (GBP/EUR), including cart contexts.
 *
 * @param string $currency_symbol Symbol resolved by WooCommerce.
 * @param string $currency        Requested currency code.
 * @return string
 */
function htoeau_child_fx_currency_symbol( $currency_symbol, $currency ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $currency_symbol;
	}
	if ( ! htoeau_child_fx_applies_display_formatting() ) {
		return $currency_symbol;
	}

	$display = function_exists( 'htoeau_child_fx_get_display_currency' )
		? strtoupper( (string) htoeau_child_fx_get_display_currency() )
		: strtoupper( (string) $currency );

	if ( 'EUR' === $display ) {
		return html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' );
	}

	if ( 'GBP' === $display ) {
		return html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' );
	}

	return $currency_symbol;
}
add_filter( 'woocommerce_currency_symbol', 'htoeau_child_fx_currency_symbol', 999, 2 );

/**
 * Whether to print FX / geo debug info in the browser console (frontend only).
 *
 * Disable in production: add_filter( 'htoeau_fx_console_debug_enabled', '__return_false' );
 *
 * @return bool
 */
function htoeau_child_fx_console_debug_enabled() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}
	// ?htoeau_fx_debug=1 on any URL forces the panel on.
	if ( isset( $_GET['htoeau_fx_debug'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return true;
	}
	// Off on production by default; on for staging/local unless filtered.
	$default = htoeau_child_fx_is_staging_or_local_host();
	return (bool) apply_filters( 'htoeau_fx_console_debug_enabled', $default );
}

/**
 * Avoid serving a cached page without the debug payload (LiteSpeed full-page cache).
 */
function htoeau_child_fx_bypass_page_cache_for_console_debug() {
	if ( ! htoeau_child_fx_console_debug_enabled() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
}
add_action( 'init', 'htoeau_child_fx_bypass_page_cache_for_console_debug', 0 );

/**
 * Enqueue standalone debug script (cache-busted by theme version).
 */
function htoeau_child_fx_enqueue_console_debug_script() {
	if ( ! htoeau_child_fx_console_debug_enabled() ) {
		return;
	}
	wp_enqueue_script(
		'htoeau-fx-console-debug',
		trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/fx-console-debug.js',
		array(),
		defined( 'HTOEAU_CHILD_VERSION' ) ? HTOEAU_CHILD_VERSION : '1.7.5',
		true
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_fx_enqueue_console_debug_script', 9999 );

/**
 * Debug payload for console (currency + geo + selling).
 *
 * @return array<string, mixed>
 */
function htoeau_child_fx_get_console_debug_payload() {
	$store_currency = function_exists( 'htoeau_child_fx_store_currency_code' )
		? htoeau_child_fx_store_currency_code()
		: strtoupper( (string) get_option( 'woocommerce_currency', '' ) );
	$display_currency = function_exists( 'htoeau_child_fx_get_display_currency' )
		? htoeau_child_fx_get_display_currency()
		: $store_currency;
	$country          = function_exists( 'htoeau_child_fx_detect_country_code' ) ? htoeau_child_fx_detect_country_code() : '';
	$country_source   = function_exists( 'htoeau_child_fx_get_country_detection_source' ) ? htoeau_child_fx_get_country_detection_source() : 'none';
	$cf_country       = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
	$cookie_currency  = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$manual_override  = function_exists( 'htoeau_child_fx_is_manual_currency_override' ) && htoeau_child_fx_is_manual_currency_override();
	$geo_guess        = function_exists( 'htoeau_child_fx_geo_guess_display_currency' ) ? htoeau_child_fx_geo_guess_display_currency() : '';
	$resolve_target   = function_exists( 'htoeau_child_fx_resolve_target_currency_code' ) ? htoeau_child_fx_resolve_target_currency_code() : '';
	$gbp_countries    = apply_filters( 'htoeau_fx_gbp_display_countries', array( 'GB', 'GG', 'JE', 'IM', 'GI', 'MU', 'CA' ) );
	$enabled          = function_exists( 'htoeau_child_fx_is_enabled' ) ? htoeau_child_fx_is_enabled() : false;
	$yay_active       = function_exists( 'htoeau_child_yay_currency_is_active' ) ? htoeau_child_yay_currency_is_active() : false;
	$yay_rate         = $yay_active ? (float) apply_filters( 'yay_currency_rate', 1 ) : null;
	$yay_widget_id    = isset( $_COOKIE['yay_currency_widget'] ) ? (string) $_COOKIE['yay_currency_widget'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$yay_detect_code  = '';
	$yay_apply_code   = '';

	if ( $yay_active && class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
		$detect_row = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		if ( is_array( $detect_row ) && ! empty( $detect_row['currency'] ) ) {
			$yay_detect_code = strtoupper( (string) $detect_row['currency'] );
		}
		$apply_row = apply_filters( 'yay_currency_apply_currency', $detect_row );
		if ( is_array( $apply_row ) && ! empty( $apply_row['currency'] ) ) {
			$yay_apply_code = strtoupper( (string) $apply_row['currency'] );
		}
	}

	$country_in_gbp_list = $country && in_array( $country, (array) $gbp_countries, true );
	$cf_in_gbp_list      = $cf_country && in_array( $cf_country, (array) $gbp_countries, true );

	$display_source = 'store_default';
	$allowed        = function_exists( 'htoeau_child_fx_supported_codes' ) ? htoeau_child_fx_supported_codes() : array();
	if ( $cookie_currency && in_array( $cookie_currency, $allowed, true ) ) {
		$display_source = $manual_override ? 'manual_test_cookie' : 'htoeau_display_ccy_cookie';
	} elseif ( $geo_guess && in_array( $geo_guess, $allowed, true ) ) {
		$display_source = 'geo_rules';
	} elseif ( $yay_detect_code ) {
		$display_source = 'yay_detect_fallback';
	}

	$hints = array();
	if ( ! $cf_country && ! $country ) {
		$hints[] = 'No country: use Cloudflare proxy on prod, or on staging try ?htoeau_country=MU or install Woo MaxMind GeoIP.';
	}
	if ( ! $cf_country && $country && in_array( $country_source, array( 'woocommerce', 'woocommerce_api' ), true ) ) {
		$hints[] = 'CF-IPCountry missing — country ' . $country . ' from WooCommerce (' . $country_source . ').';
	}
	if ( 'test_override' === $country_source ) {
		$hints[] = 'Country from test override (?htoeau_country= or htoeau_test_country cookie).';
	}
	if ( $cookie_currency && $geo_guess && $cookie_currency !== $geo_guess ) {
		$hints[] = sprintf(
			'Cookie %s=%s overrides geo guess %s (clear cookies or use /ccy-gbp/ to test).',
			HTOEAU_FX_COOKIE,
			$cookie_currency,
			$geo_guess
		);
	}
	if ( 'MU' === $country && 'EUR' === $display_currency && ! $cookie_currency ) {
		$hints[] = 'MU should get GBP via geo list — check LiteSpeed serving a cached EUR page (purge cache).';
	}
	if ( 'MU' === $cf_country && ! $country ) {
		$hints[] = 'CF says MU but detect_country_code is empty — unexpected; report this.';
	}

	$selling = array(
		'sellable_countries'       => function_exists( 'htoeau_child_get_sellable_country_codes' ) ? htoeau_child_get_sellable_country_codes() : array(),
		'visitor_can_buy_online'   => function_exists( 'htoeau_child_visitor_can_purchase_online' ) ? htoeau_child_visitor_can_purchase_online() : null,
		'pdp_show_enquire'         => function_exists( 'is_product' ) && is_product() && function_exists( 'htoeau_child_product_should_show_enquire' ) && htoeau_child_product_should_show_enquire(),
	);

	return array(
		'display_currency'          => (string) $display_currency,
		'display_currency_source'   => $display_source,
		'store_currency'            => (string) $store_currency,
		'geo_guess_from_country'    => (string) $geo_guess,
		'resolve_target_for_yay'    => (string) $resolve_target,
		'cloudflare_CF_IPCOUNTRY'   => (string) $cf_country,
		'detected_country_code'     => (string) $country,
		'country_detection_source'  => (string) $country_source,
		'country_in_gbp_list'       => (bool) $country_in_gbp_list,
		'cf_country_in_gbp_list'    => (bool) $cf_in_gbp_list,
		'gbp_display_countries'     => array_values( (array) $gbp_countries ),
		'cookies'                   => array(
			HTOEAU_FX_COOKIE           => (string) $cookie_currency,
			HTOEAU_FX_MANUAL_COOKIE    => $manual_override ? '1' : '',
			'yay_currency_widget'      => $yay_widget_id,
			'yay_currency_do_change_switcher' => isset( $_COOKIE['yay_currency_do_change_switcher'] ) ? (string) $_COOKIE['yay_currency_do_change_switcher'] : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		),
		'yay_currency_active'       => (bool) $yay_active,
		'yay_detect_currency'       => (string) $yay_detect_code,
		'yay_apply_currency'        => (string) $yay_apply_code,
		'yay_currency_rate'         => $yay_rate,
		'fx_module_enabled'         => (bool) $enabled,
		'wc_decimal_separator'      => function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '',
		'sample_fx_price_34_13'     => function_exists( 'htoeau_child_fx_wc_price' ) ? wp_strip_all_tags( htoeau_child_fx_wc_price( 34.13 ) ) : '',
		'selling'                   => $selling,
		'litespeed_vary_value'      => isset( $_SERVER['LSCACHE_VARY_VALUE'] ) ? (string) $_SERVER['LSCACHE_VARY_VALUE'] : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		'visitor_ip'                => class_exists( 'WC_Geolocation' ) ? (string) WC_Geolocation::get_ip_address() : '',
		'geolocation_api_fallback'  => function_exists( 'htoeau_child_fx_geolocation_api_fallback_enabled' ) && htoeau_child_fx_geolocation_api_fallback_enabled(),
		'is_staging_host'           => function_exists( 'htoeau_child_fx_is_staging_or_local_host' ) && htoeau_child_fx_is_staging_or_local_host(),
		'test_country_override'     => function_exists( 'htoeau_child_fx_get_test_country_override' ) ? htoeau_child_fx_get_test_country_override() : '',
		'hints'                     => $hints,
		'priority_order'            => '1) manual/cookie htoeau_display_ccy → 2) geo (CF country + GBP list) → 3) Yay detect → 4) store',
		'request_uri'               => isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	);
}

/**
 * Print JSON payload for fx-console-debug.js (footer + head so Elementor/cache miss one hook).
 */
function htoeau_child_fx_print_console_debug_data() {
	if ( ! htoeau_child_fx_console_debug_enabled() ) {
		return;
	}

	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;

	$payload = htoeau_child_fx_get_console_debug_payload();
	$json    = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
	if ( ! $json ) {
		return;
	}

	printf(
		'<script type="application/json" id="htoeau-fx-debug-data">%s</script>' . "\n",
		$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode
	);
}
add_action( 'wp_footer', 'htoeau_child_fx_print_console_debug_data', 99999 );
add_action( 'wp_body_open', 'htoeau_child_fx_print_console_debug_data', 99999 );

/**
 * Customizer section for FX behavior notes.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function htoeau_child_fx_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'htoeau_fx',
		array(
			'title'       => __( 'HtoEAU currency (GBP ↔ EUR)', 'hello-elementor-child' ),
			'description' => __( 'Geo only (no currency switcher on site): UK/Channel Islands/Isle of Man/MU → GBP, others → EUR. 1:1 prices; EUR comma, GBP dot. Test: /ccy-gbp/ or #htoeau_ccy=GBP on PDP.', 'hello-elementor-child' ),
			'priority'    => 200,
		)
	);
}
add_action( 'customize_register', 'htoeau_child_fx_customize_register' );
