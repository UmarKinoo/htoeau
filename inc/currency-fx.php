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
 */
function htoeau_child_fx_store_supports_display_fx() {
	if ( ! function_exists( 'get_woocommerce_currency' ) ) {
		return false;
	}
	$store = get_woocommerce_currency();
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
function htoeau_child_fx_geo_guess_display_currency() {
	$store   = get_woocommerce_currency();
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
 * Cookie override (e.g. ?htoeau_ccy=) or geo: which currency to show prices in.
 */
function htoeau_child_fx_get_display_currency() {
	static $resolving = false;

	if ( ! htoeau_child_fx_is_enabled() ) {
		return get_woocommerce_currency();
	}

	if ( $resolving ) {
		return get_woocommerce_currency();
	}

	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) {
		$resolving = true;
		$store     = get_woocommerce_currency();
		$allowed   = htoeau_child_fx_supported_codes();

		if ( function_exists( 'htoeau_child_yay_htoeau_cookie_currency_code' ) ) {
			$cookie_code = htoeau_child_yay_htoeau_cookie_currency_code();
			if ( $cookie_code ) {
				$resolving = false;
				return $cookie_code;
			}
		}

		if ( ! function_exists( 'htoeau_child_yay_has_user_currency_cookie' ) || ! htoeau_child_yay_has_user_currency_cookie() ) {
			$guessed = htoeau_child_fx_geo_guess_display_currency();
			if ( $guessed && in_array( $guessed, $allowed, true ) ) {
				$resolving = false;
				return $guessed;
			}
		}

		$apply = \Yay_Currency\Helpers\YayCurrencyHelper::detect_current_currency();
		$resolving = false;
		if ( is_array( $apply ) && ! empty( $apply['currency'] ) ) {
			return strtoupper( (string) $apply['currency'] );
		}
		return $store;
	}

	$store   = get_woocommerce_currency();
	$allowed = htoeau_child_fx_supported_codes();
	$cookie  = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $cookie && in_array( $cookie, $allowed, true ) ) {
		return $cookie;
	}
	$guessed = htoeau_child_fx_geo_guess_display_currency();
	if ( $guessed && in_array( $guessed, $allowed, true ) ) {
		return $guessed;
	}
	return $store;
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
		$store   = get_woocommerce_currency();
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

	// Enforce separators by display currency (frontend consistency regardless of Woo global decimal option).
	if ( ! isset( $args['decimal_separator'] ) ) {
		if ( 'EUR' === $display_ccy ) {
			$args['decimal_separator'] = ',';
		} elseif ( 'GBP' === $display_ccy ) {
			$args['decimal_separator'] = '.';
		}
	}

	if ( function_exists( 'htoeau_child_yay_currency_is_active' ) && htoeau_child_yay_currency_is_active() ) {
		$store   = get_woocommerce_currency();
		$display = htoeau_child_fx_get_display_currency();
		if ( $display && $store && $display !== $store ) {
			$args['currency'] = $display;
			$formatted        = wc_price( $amount, $args );
			$symbol_map       = array(
				'GBP' => html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' ),
				'EUR' => html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' ),
			);
			$target_symbol = isset( $symbol_map[ $display ] ) ? $symbol_map[ $display ] : '';
			if ( $target_symbol ) {
				$known_symbols = array_values( $symbol_map );
				$formatted     = str_replace( $known_symbols, $target_symbol, $formatted );
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
 * Set display currency cookie from ?htoeau_ccy=GBP|EUR and redirect (strip query arg).
 */
function htoeau_child_fx_capture_query_currency() {
	if ( ! isset( $_GET['htoeau_ccy'] ) || ! htoeau_child_fx_is_enabled() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$code = strtoupper( sanitize_text_field( wp_unslash( $_GET['htoeau_ccy'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! in_array( $code, htoeau_child_fx_supported_codes(), true ) ) {
		return;
	}
	if ( headers_sent() ) {
		return;
	}
	setcookie( HTOEAU_FX_COOKIE, $code, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE[ HTOEAU_FX_COOKIE ] = $code; // Current request.
	if ( function_exists( 'htoeau_child_yay_sync_currency_cookie' ) ) {
		htoeau_child_yay_sync_currency_cookie( $code );
	}
	wp_safe_redirect( remove_query_arg( 'htoeau_ccy' ) );
	exit;
}
add_action( 'init', 'htoeau_child_fx_capture_query_currency', 2 );

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
	if ( ! htoeau_child_fx_legacy_symbol_swap_enabled() ) {
		return $separator;
	}

	$currency = function_exists( 'htoeau_child_fx_get_display_currency' )
		? htoeau_child_fx_get_display_currency()
		: ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '' );

	$currency = strtoupper( (string) $currency );
	if ( 'EUR' === $currency ) {
		return ',';
	}

	if ( 'GBP' === $currency ) {
		return '.';
	}

	return $separator;
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
	if ( ! htoeau_child_fx_legacy_symbol_swap_enabled() ) {
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
			'description' => __( 'With Yay Currency: geo rules (UK/Channel Islands/Isle of Man/MU → GBP, others → EUR). GBP and EUR use the same numeric price (1:1); only symbol/format differs. Test override: ?htoeau_ccy=GBP or EUR.', 'hello-elementor-child' ),
			'priority'    => 200,
		)
	);
}
add_action( 'customize_register', 'htoeau_child_fx_customize_register' );
