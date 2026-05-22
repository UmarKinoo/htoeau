<?php
/**
 * GBP ↔ EUR display conversion (store currency stays the WooCommerce setting; checkout unchanged).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

define( 'HTOEAU_FX_COOKIE', 'htoeau_display_ccy' );

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
 * @param string $code GBP|EUR.
 */
function htoeau_child_fx_set_display_currency_cookie( $code ) {
	$code = strtoupper( (string) $code );
	if ( ! in_array( $code, htoeau_child_fx_supported_codes(), true ) || headers_sent() ) {
		return;
	}
	setcookie( HTOEAU_FX_COOKIE, $code, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), false );
	$_COOKIE[ HTOEAU_FX_COOKIE ] = $code; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( function_exists( 'htoeau_child_yay_sync_currency_cookie' ) ) {
		htoeau_child_yay_sync_currency_cookie( $code );
	}
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
 * Two-letter country code for the visitor (Cloudflare header or WooCommerce geolocation).
 *
 * @return string Empty string if unknown.
 */
function htoeau_child_fx_detect_country_code() {
	if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$c = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( preg_match( '/^[A-Z]{2}$/', $c ) && 'XX' !== $c && 'T1' !== $c ) {
			return $c;
		}
	}

	// Avoid blocking external geolocation API calls on every request (can cause 503/timeouts).
	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) {
		return '';
	}

	if ( class_exists( 'WC_Geolocation' ) ) {
		$geo = WC_Geolocation::geolocate_ip( '', true );
		if ( ! empty( $geo['country'] ) ) {
			return strtoupper( (string) $geo['country'] );
		}
	}
	return '';
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
		array( 'GB', 'GG', 'JE', 'IM', 'MU' )
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

	// 2. Geo (only when user hasn't manually chosen via Yay switcher).
	$yay_active = function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active();
	$user_chose = function_exists( 'htoeau_child_yay_has_user_currency_cookie' ) && htoeau_child_yay_has_user_currency_cookie();

	if ( ! $user_chose ) {
		$guessed = htoeau_child_fx_geo_guess_display_currency();
		if ( $guessed && in_array( $guessed, $allowed, true ) ) {
			$cached = $guessed;
			return $cached;
		}
	}

	// 3. Yay's current selection.
	if ( $yay_active && class_exists( 'Yay_Currency\Helpers\YayCurrencyHelper' ) ) {
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
 * Temporary frontend debug output for FX/country detection.
 */
function htoeau_child_fx_debug_console_output() {
	if ( is_admin() || ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) ) {
		return;
	}

	$store_currency   = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
	$display_currency = function_exists( 'htoeau_child_fx_get_display_currency' ) ? htoeau_child_fx_get_display_currency() : $store_currency;
	$country          = function_exists( 'htoeau_child_fx_detect_country_code' ) ? htoeau_child_fx_detect_country_code() : '';
	$cookie_currency  = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$cf_country       = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
	$decimal_sep      = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '';
	$enabled          = function_exists( 'htoeau_child_fx_is_enabled' ) ? htoeau_child_fx_is_enabled() : false;
	$yay_active       = function_exists( 'htoeau_child_yay_currency_is_active' ) ? htoeau_child_yay_currency_is_active() : false;
	$yay_rate         = $yay_active ? (float) apply_filters( 'yay_currency_rate', 1 ) : null;
	$gbp_countries    = apply_filters( 'htoeau_fx_gbp_display_countries', array( 'GB', 'GG', 'JE', 'IM', 'MU' ) );
	$eur_countries    = apply_filters( 'htoeau_fx_eur_display_countries', array() );

	$payload = array(
		'fx_enabled'                 => (bool) $enabled,
		'yay_currency_active'        => (bool) $yay_active,
		'yay_currency_rate'          => $yay_rate,
		'detected_country'           => (string) $country,
		'cloudflare_country_header'  => (string) $cf_country,
		'cookie_currency'            => (string) $cookie_currency,
		'store_currency'             => (string) $store_currency,
		'display_currency'           => (string) $display_currency,
		'wc_decimal_separator'       => (string) $decimal_sep,
		'gbp_display_countries'      => array_values( (array) $gbp_countries ),
		'eur_display_countries'      => array_values( (array) $eur_countries ),
		'request_uri'                => isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		'sample_wc_price_store'      => function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( 2.36 ) ) : '',
		'sample_fx_wc_price'         => function_exists( 'htoeau_child_fx_wc_price' ) ? wp_strip_all_tags( htoeau_child_fx_wc_price( 2.36 ) ) : '',
		'symbol_for_gbp'             => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol( 'GBP' ), ENT_QUOTES, 'UTF-8' ) : '',
		'symbol_for_eur'             => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol( 'EUR' ), ENT_QUOTES, 'UTF-8' ) : '',
	);
	$json = wp_json_encode( $payload );
	if ( ! $json ) {
		return;
	}
	?>
	<script>
		console.groupCollapsed('HtoEAU FX Debug');
		console.log(<?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>);
		console.groupEnd();
	</script>
	<?php
}
add_action( 'wp_footer', 'htoeau_child_fx_debug_console_output', 999 );

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
			'description' => __( 'Geo: UK/Channel Islands/Isle of Man/MU → GBP, others → EUR. Same numeric price (1:1); EUR uses comma (34,13), GBP uses dot (34.13). Test GBP: visit /ccy-gbp/ then the PDP, or use the Yay switcher → Pound sterling.', 'hello-elementor-child' ),
			'priority'    => 200,
		)
	);
}
add_action( 'customize_register', 'htoeau_child_fx_customize_register' );
