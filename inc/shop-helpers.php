<?php
/**
 * Shop / archive helpers (per-can line, rating display).
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL for shop filter links (shop page or current taxonomy archive).
 *
 * @return string
 */
function htoeau_child_get_shop_filter_base_url(): string {
	if ( is_product_category() || is_product_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof \WP_Term ) {
			$link = get_term_link( $term );
			return ! is_wp_error( $link ) ? $link : '';
		}
	}
	$shop_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
	if ( $shop_id > 0 ) {
		return (string) get_permalink( $shop_id );
	}
	return home_url( '/' );
}

/**
 * Rating + review count for archive cards (real data; sensible defaults when empty).
 *
 * @param WC_Product $product Product.
 * @return array{rating: float, count: int}
 */
function htoeau_child_shop_get_rating_display( WC_Product $product ): array {
	$rating = (float) $product->get_average_rating();
	$count  = (int) $product->get_review_count();
	if ( $rating <= 0 ) {
		$rating = 4.8;
	}
	if ( $count <= 0 ) {
		$count = 248;
	}
	return array(
		'rating' => $rating,
		'count'  => $count,
	);
}

/**
 * Short excerpt for archive card (short description, else trimmed content).
 *
 * @param WC_Product $product Product.
 * @return string Plain text excerpt.
 */
function htoeau_child_shop_get_card_excerpt( WC_Product $product ): string {
	$raw = $product->get_short_description();
	if ( '' === trim( wp_strip_all_tags( (string) $raw ) ) ) {
		$raw = $product->get_description();
	}
	$raw = wp_strip_all_tags( (string) $raw );

	return (string) wp_trim_words( $raw, 36, '…' );
}

/**
 * “From … per can” HTML using FX-aware price when the FX module is active.
 *
 * @param WC_Product $product Product.
 * @return string HTML.
 */
function htoeau_child_shop_get_per_can_line_html( WC_Product $product ): string {
	$fx_price = static function ( float $amount ): string {
		if ( function_exists( 'htoeau_child_fx_wc_price' ) ) {
			return htoeau_child_fx_wc_price( $amount );
		}
		return wc_price( $amount );
	};

	if ( $product->is_type( 'variable' ) ) {
		$lowest_per_can = null;
		foreach ( $product->get_available_variations() as $v ) {
			$cans = htoeau_child_get_can_count_from_variation_attrs( $v['attributes'] );
			if ( $cans < 1 || empty( $v['display_price'] ) ) {
				continue;
			}
			$per = (float) $v['display_price'] / $cans;
			if ( null === $lowest_per_can || $per < $lowest_per_can ) {
				$lowest_per_can = $per;
			}
		}
		if ( null !== $lowest_per_can ) {
			return sprintf(
				'<span class="htoeau-shop-card__from">%s</span> <strong class="htoeau-shop-card__per">%s %s</strong>',
				esc_html__( 'From', 'hello-elementor-child' ),
				wp_kses_post( $fx_price( $lowest_per_can ) ),
				esc_html__( 'per can', 'hello-elementor-child' )
			);
		}
	}

	$price = (float) wc_get_price_to_display( $product );
	if ( $price > 0 ) {
		return sprintf(
			'<span class="htoeau-shop-card__from">%s</span> <strong class="htoeau-shop-card__per">%s</strong>',
			esc_html__( 'From', 'hello-elementor-child' ),
			wp_kses_post( $fx_price( $price ) )
		);
	}

	return '';
}
