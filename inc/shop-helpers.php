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
	static $cache = array();
	$pid = $product->get_id();
	if ( isset( $cache[ $pid ] ) ) {
		return $cache[ $pid ];
	}

	$fx_price = static function ( float $amount ): string {
		if ( function_exists( 'htoeau_child_fx_wc_price' ) ) {
			return htoeau_child_fx_wc_price( $amount );
		}
		return wc_price( $amount );
	};

	if ( $product->is_type( 'variable' ) ) {
		$lowest_per_can = null;
		/**
		 * Fast path: bulk variation prices (already display-adjusted) + one meta read per variation.
		 * Avoids get_available_variations(), wc_get_product() per variation, and wc_get_price_to_display()
		 * in a tight loop — those can still 503 on shared hosting when combined with sort/filter queries.
		 */
		/** @var WC_Product_Variable $product */
		$price_rows = $product->get_variation_prices( true );
		if ( ! empty( $price_rows['price'] ) && is_array( $price_rows['price'] ) ) {
			$attr_tax = function_exists( 'htoeau_child_can_count_attribute' )
				? htoeau_child_can_count_attribute()
				: 'pa_can-count';
			$meta_key = 'attribute_' . $attr_tax;

			foreach ( $price_rows['price'] as $variation_id => $price_string ) {
				$variation_id = (int) $variation_id;
				$display      = (float) $price_string;
				if ( $variation_id < 1 || $display <= 0 ) {
					continue;
				}
				$can_raw = get_post_meta( $variation_id, $meta_key, true );
				$cans    = (int) preg_replace( '/\D/', '', (string) $can_raw );
				if ( $cans < 1 ) {
					$cans = (int) htoeau_child_shop_infer_can_count_from_variation_meta( $variation_id );
				}
				if ( $cans < 1 ) {
					continue;
				}
				$per = $display / $cans;
				if ( null === $lowest_per_can || $per < $lowest_per_can ) {
					$lowest_per_can = $per;
				}
			}
		}

		if ( null !== $lowest_per_can ) {
			$cache[ $pid ] = sprintf(
				'<span class="htoeau-shop-card__from">%s</span> <strong class="htoeau-shop-card__per">%s %s</strong>',
				esc_html__( 'From', 'hello-elementor-child' ),
				wp_kses_post( $fx_price( $lowest_per_can ) ),
				esc_html__( 'per can', 'hello-elementor-child' )
			);
			return $cache[ $pid ];
		}
	}

	$price = (float) wc_get_price_to_display( $product );
	if ( $price > 0 ) {
		$cache[ $pid ] = sprintf(
			'<span class="htoeau-shop-card__from">%s</span> <strong class="htoeau-shop-card__per">%s</strong>',
			esc_html__( 'From', 'hello-elementor-child' ),
			wp_kses_post( $fx_price( $price ) )
		);
		return $cache[ $pid ];
	}

	$cache[ $pid ] = '';
	return '';
}

/**
 * Fallback can count from variation post meta when the primary attribute key is missing.
 *
 * @param int $variation_id Variation ID.
 * @return int
 */
function htoeau_child_shop_infer_can_count_from_variation_meta( $variation_id ): int {
	$variation_id = (int) $variation_id;
	if ( $variation_id < 1 ) {
		return 0;
	}
	$all = get_post_meta( $variation_id );
	if ( ! is_array( $all ) ) {
		return 0;
	}
	foreach ( $all as $key => $values ) {
		if ( ! is_string( $key ) ) {
			continue;
		}
		$lk = strtolower( $key );
		if ( false === strpos( $lk, 'attribute_' ) || false === strpos( $lk, 'can' ) ) {
			continue;
		}
		$val = isset( $values[0] ) ? $values[0] : '';
		$n   = (int) preg_replace( '/\D/', '', (string) $val );
		if ( $n > 0 ) {
			return $n;
		}
	}
	return 0;
}

/**
 * Product image HTML for shop/archive cards only (see `woocommerce/content-product.php`).
 *
 * Field `shop_catalog_image` is registered with the PDP fields in
 * `htoeau_child_register_acf_fields()` (local field group `group_htoeau_product`, tab
 * “Shop / catalog”). Return format: Image ID (array with `ID` is also accepted).
 *
 * When set and valid, that image is shown on archive/shop cards. Otherwise the usual
 * product thumbnail (featured image) is used. Single product / gallery are unchanged.
 *
 * @param WC_Product $product Product.
 * @return string HTML `<img>` from core helpers.
 */
function htoeau_child_shop_get_loop_product_thumbnail_html( WC_Product $product ): string {
	$catalog_id = 0;
	if ( function_exists( 'get_field' ) ) {
		$field = get_field( 'shop_catalog_image', $product->get_id() );
		if ( is_numeric( $field ) ) {
			$catalog_id = (int) $field;
		} elseif ( is_array( $field ) && ! empty( $field['ID'] ) ) {
			$catalog_id = (int) $field['ID'];
		}
	}

	$attrs = array(
		'class' => 'htoeau-shop-card__img',
		'alt'   => esc_attr( trim( wp_strip_all_tags( $product->get_name() ) ) ),
	);
	$size  = 'woocommerce_thumbnail';

	if ( $catalog_id > 0 && wp_attachment_is_image( $catalog_id ) ) {
		return wp_get_attachment_image( $catalog_id, $size, false, $attrs );
	}

	return woocommerce_get_product_thumbnail( $size, $attrs );
}
