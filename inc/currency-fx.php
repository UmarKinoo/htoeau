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
 * Whether FX module applies (Woo active, store currency is GBP or EUR).
 */
function htoeau_child_fx_is_enabled() {
	if ( ! function_exists( 'get_woocommerce_currency' ) ) {
		return false;
	}
	$store = get_woocommerce_currency();
	return in_array( $store, htoeau_child_fx_supported_codes(), true );
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
		array( 'GB', 'GG', 'JE', 'IM' )
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
	if ( ! htoeau_child_fx_is_enabled() ) {
		return get_woocommerce_currency();
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
 * For GBP/EUR mode we keep the same numeric amount and only swap symbol/format.
 *
 * @param float $amount Amount in shop (store) currency.
 */
function htoeau_child_fx_convert_amount( $amount ) {
	$amount = (float) $amount;
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

	if ( ! htoeau_child_fx_is_enabled() ) {
		return wc_price( $amount, $args );
	}
	$store   = get_woocommerce_currency();
	$display = htoeau_child_fx_get_display_currency();
	$target_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol( $display ) : '';
	if ( $store === $display ) {
		$formatted = wc_price( $amount, $args );
		if ( $target_symbol ) {
			$formatted = str_replace(
				array(
					get_woocommerce_currency_symbol( 'GBP' ),
					get_woocommerce_currency_symbol( 'EUR' ),
				),
				$target_symbol,
				$formatted
			);
		}
		return $formatted;
	}
	$args['currency'] = $display;
	$formatted        = wc_price( htoeau_child_fx_convert_amount( $amount ), $args );
	if ( $target_symbol ) {
		$formatted = str_replace(
			array(
				get_woocommerce_currency_symbol( 'GBP' ),
				get_woocommerce_currency_symbol( 'EUR' ),
			),
			$target_symbol,
			$formatted
		);
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
	if ( ! htoeau_child_fx_is_enabled() ) {
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
 * Temporary frontend debug output for FX/country detection.
 */
function htoeau_child_fx_debug_console_output() {
	if ( is_admin() ) {
		return;
	}

	$store_currency   = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
	$display_currency = function_exists( 'htoeau_child_fx_get_display_currency' ) ? htoeau_child_fx_get_display_currency() : $store_currency;
	$country          = function_exists( 'htoeau_child_fx_detect_country_code' ) ? htoeau_child_fx_detect_country_code() : '';
	$cookie_currency  = isset( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ? strtoupper( sanitize_text_field( wp_unslash( $_COOKIE[ HTOEAU_FX_COOKIE ] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$cf_country       = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.NonceVerification.Recommended
	$decimal_sep      = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '';
	$enabled          = function_exists( 'htoeau_child_fx_is_enabled' ) ? htoeau_child_fx_is_enabled() : false;
	$gbp_countries    = apply_filters( 'htoeau_fx_gbp_display_countries', array( 'GB', 'GG', 'JE', 'IM' ) );
	$eur_countries    = apply_filters( 'htoeau_fx_eur_display_countries', array() );

	$payload = array(
		'fx_enabled'                 => (bool) $enabled,
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
			'description' => __( 'Browsing prices follow visitor location (UK/Channel Islands/Isle of Man → GBP, all other countries → EUR). Optional override: add ?htoeau_ccy=GBP or EUR to set a cookie. Numeric price stays the same; symbol/format changes only. Checkout still uses your store currency.', 'hello-elementor-child' ),
			'priority'    => 200,
		)
	);
}
add_action( 'customize_register', 'htoeau_child_fx_customize_register' );
