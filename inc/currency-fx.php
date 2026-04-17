<?php
/**
 * GBP ↔ USD display conversion (store currency stays the WooCommerce setting; checkout unchanged).
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
	return apply_filters( 'htoeau_fx_supported_currencies', array( 'GBP', 'USD' ) );
}

/**
 * Whether FX module applies (Woo active, store currency is GBP or USD).
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
 * Pick GBP or USD for display from visitor country when the store supports FX.
 *
 * @return string
 */
function htoeau_child_fx_geo_guess_display_currency() {
	$store   = get_woocommerce_currency();
	$country = htoeau_child_fx_detect_country_code();
	$usd     = apply_filters(
		'htoeau_fx_usd_display_countries',
		array( 'US', 'PR', 'GU', 'VI', 'AS', 'MP' )
	);
	$gbp     = apply_filters(
		'htoeau_fx_gbp_display_countries',
		array( 'GB', 'GG', 'JE', 'IM' )
	);
	if ( 'GBP' === $store ) {
		if ( $country && in_array( $country, $usd, true ) ) {
			return 'USD';
		}
		return 'GBP';
	}
	if ( 'USD' === $store ) {
		if ( $country && in_array( $country, $gbp, true ) ) {
			return 'GBP';
		}
		return 'USD';
	}
	return $store;
}

/**
 * USD per 1 GBP (Customizer / filter). Used both directions.
 */
function htoeau_child_fx_usd_per_gbp() {
	$default = 1.27;
	if ( function_exists( 'get_theme_mod' ) ) {
		$mod = (float) get_theme_mod( 'htoeau_fx_usd_per_gbp', $default );
		if ( $mod > 0 ) {
			$default = $mod;
		}
	}
	return (float) apply_filters( 'htoeau_fx_usd_per_gbp', $default );
}

/**
 * Multiplier from $from to $to (ISO codes).
 *
 * @param string $from Store or source ISO code.
 * @param string $to   Target ISO code.
 */
function htoeau_child_fx_get_pair_multiplier( $from, $to ) {
	$from = strtoupper( (string) $from );
	$to   = strtoupper( (string) $to );
	if ( $from === $to ) {
		return 1.0;
	}
	$x = htoeau_child_fx_usd_per_gbp();
	if ( $x <= 0 ) {
		return 1.0;
	}
	if ( 'GBP' === $from && 'USD' === $to ) {
		return $x;
	}
	if ( 'USD' === $from && 'GBP' === $to ) {
		return 1 / $x;
	}
	return 1.0;
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
 *
 * @param float $amount Amount in shop (store) currency.
 */
function htoeau_child_fx_convert_amount( $amount ) {
	$amount = (float) $amount;
	if ( ! htoeau_child_fx_is_enabled() ) {
		return $amount;
	}
	$store   = get_woocommerce_currency();
	$display = htoeau_child_fx_get_display_currency();
	if ( $store === $display ) {
		return $amount;
	}
	return $amount * htoeau_child_fx_get_pair_multiplier( $store, $display );
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
		} elseif ( 'GBP' === $display_ccy || 'USD' === $display_ccy ) {
			$args['decimal_separator'] = '.';
		}
	}

	if ( ! htoeau_child_fx_is_enabled() ) {
		return wc_price( $amount, $args );
	}
	$store   = get_woocommerce_currency();
	$display = htoeau_child_fx_get_display_currency();
	if ( $store === $display ) {
		return wc_price( $amount, $args );
	}
	$args['currency'] = $display;
	return wc_price( htoeau_child_fx_convert_amount( $amount ), $args );
}

/**
 * Set display currency cookie from ?htoeau_ccy=GBP|USD and redirect (strip query arg).
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
 * - GBP / USD: dot (.)
 * - EUR: comma (,)
 *
 * @param string $separator Default separator.
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

	if ( 'GBP' === $currency || 'USD' === $currency ) {
		return '.';
	}

	return $separator;
}
add_filter( 'woocommerce_price_decimal_sep', 'htoeau_child_fx_price_decimal_separator', 50, 1 );

/**
 * Customizer: editable USD-per-GBP rate.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function htoeau_child_fx_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'htoeau_fx',
		array(
			'title'       => __( 'HtoEAU currency (GBP ↔ USD)', 'hello-elementor-child' ),
			'description' => __( 'Browsing prices follow visitor location (e.g. US → USD, UK → GBP when the store is GBP). Optional override: add ?htoeau_ccy=GBP or USD to set a cookie. Checkout still uses your store currency.', 'hello-elementor-child' ),
			'priority'    => 200,
		)
	);

	$wp_customize->add_setting(
		'htoeau_fx_usd_per_gbp',
		array(
			'default'           => 1.27,
			'sanitize_callback' => 'htoeau_child_fx_sanitize_rate',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'htoeau_fx_usd_per_gbp',
		array(
			'label'       => __( 'US dollars per 1 British pound', 'hello-elementor-child' ),
			'description' => __( 'Example: 1.27 means £1.00 ≈ $1.27.', 'hello-elementor-child' ),
			'section'     => 'htoeau_fx',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 0.01,
				'max'  => 999,
				'step' => 0.01,
			),
		)
	);
}
add_action( 'customize_register', 'htoeau_child_fx_customize_register' );

/**
 * @param mixed $value Raw.
 * @return float
 */
function htoeau_child_fx_sanitize_rate( $value ) {
	$f = is_numeric( $value ) ? (float) $value : 1.27;
	return $f > 0 ? $f : 1.27;
}
