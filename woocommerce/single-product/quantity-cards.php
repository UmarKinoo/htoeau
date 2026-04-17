<?php
/**
 * Dynamic variation cards powered by WooCommerce variation data.
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! function_exists( 'htoeau_child_product_is_variable_pdp' ) || ! htoeau_child_product_is_variable_pdp( $product ) ) {
	return;
}

$variations = $product->get_available_variations();
if ( empty( $variations ) ) {
	return;
}

$img_base = htoeau_child_get_brand_images_base_url();

$product_attributes = $product->get_attributes();

$pdp_can_image = '';
if ( function_exists( 'get_field' ) ) {
	$acf_img = get_field( 'pdp_can_image', $product->get_id() );
	if ( is_array( $acf_img ) && ! empty( $acf_img['url'] ) ) {
		$pdp_can_image = $acf_img['url'];
	} elseif ( is_string( $acf_img ) && '' !== $acf_img ) {
		$pdp_can_image = $acf_img;
	}
}
$can_image_url = $pdp_can_image ? $pdp_can_image : $img_base . 'can-label.png';

$format_variation_card_labels = function ( $attributes, $current_product, $product_attrs_meta ) {
	$parts = array();

	foreach ( $attributes as $key => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}

		$taxonomy_or_name = str_replace( 'attribute_', '', (string) $key );
		$label            = wc_attribute_label( $taxonomy_or_name, $current_product );
		$display_value    = (string) $value;

		if ( taxonomy_exists( $taxonomy_or_name ) ) {
			$term = get_term_by( 'slug', (string) $value, $taxonomy_or_name );
			if ( $term && ! is_wp_error( $term ) ) {
				$display_value = $term->name;
			}
		} else {
			$lookup_name = $taxonomy_or_name;
			if ( 0 === strpos( $lookup_name, 'pa_' ) ) {
				$lookup_name = substr( $lookup_name, 3 );
			}
			if ( isset( $product_attrs_meta[ $lookup_name ] ) && $product_attrs_meta[ $lookup_name ]->is_taxonomy() ) {
				$tax  = $product_attrs_meta[ $lookup_name ]->get_name();
				$term = get_term_by( 'slug', (string) $value, $tax );
				if ( $term && ! is_wp_error( $term ) ) {
					$display_value = $term->name;
				}
			} else {
				$display_value = wc_clean( $display_value );
			}
		}

		$parts[] = array(
			'label' => (string) $label,
			'value' => (string) $display_value,
		);
	}

	if ( empty( $parts ) ) {
		return array(
			'title'   => __( 'Option', 'htoeau-child' ),
			'details' => '',
		);
	}

	$title_part = array_shift( $parts );
	$title      = trim( $title_part['value'] );
	$details    = array();
	if ( '' !== trim( $title_part['label'] ) ) {
		$details[] = trim( $title_part['label'] );
	}
	foreach ( $parts as $part ) {
		$details[] = trim( $part['label'] . ': ' . $part['value'] );
	}

	return array(
		'title'   => $title,
		'details' => implode( ' - ', array_filter( $details ) ),
	);
};

$rows = array();
foreach ( $variations as $v ) {
	$vid = (int) $v['variation_id'];
	if ( empty( $v['is_purchasable'] ) || empty( $v['is_in_stock'] ) ) {
		continue;
	}
	$price    = (float) $v['display_price'];
	$regular  = (float) $v['display_regular_price'];
	$savings  = 0;
	if ( $regular > $price && $regular > 0 ) {
		$savings = round( 100 * ( 1 - ( $price / $regular ) ) );
	}
	$cans = htoeau_child_get_can_count_from_variation_attrs( $v['attributes'] );
	$card = $format_variation_card_labels( $v['attributes'], $product, $product_attributes );

	$badge = '';
	if ( function_exists( 'get_field' ) ) {
		$badge = (string) get_field( 'variation_badge', $vid );
	}
	$label = $card['title'];
	if ( $cans > 0 && is_numeric( $label ) ) {
		/* translators: %s: number of cans */
		$label = sprintf( _n( '%s Can', '%s Cans', (int) $label, 'hello-elementor-child' ), $label );
	}
	$rows[] = array(
		'variation_id' => $vid,
		'attributes'   => $v['attributes'],
		'cans'         => $cans,
		'label'        => $label,
		'details'      => $card['details'],
		'price'        => $price,
		'regular'      => $regular,
		'savings'      => $savings,
		'badge'        => $badge,
	);
}

if ( empty( $rows ) ) {
	return;
}

$discount_pct        = (float) apply_filters( 'htoeau_subscribe_discount_percent', 10 );
$legacy_subscribe_ui = ! ( function_exists( 'htoeau_child_product_is_wcs_subscription' ) && htoeau_child_product_is_wcs_subscription( $product ) );

$pdp_variations = array();
foreach ( $rows as $r ) {
	$one_time = function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $r['price'] ) : $r['price'];
	$regular  = function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $r['regular'] ) : $r['regular'];
	if ( $legacy_subscribe_ui ) {
		$sub_price = $r['price'] * ( 1 - $discount_pct / 100 );
		$sub_conv  = function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $sub_price ) : $sub_price;
	} else {
		$sub_conv = $one_time;
	}
	$pdp_variations[] = array(
		'variationId'    => $r['variation_id'],
		'attributes'     => $r['attributes'],
		'canCount'       => $r['cans'],
		'oneTime'        => $one_time,
		'oneTimeRegular' => $regular,
		'subscribe'      => $sub_conv,
		'perCan'         => ( $r['cans'] > 0 ) ? ( function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $r['price'] / $r['cans'] ) : ( $r['price'] / $r['cans'] ) ) : 0,
		'savingsPct'     => $r['savings'],
	);
}

$default_id = (int) apply_filters( 'htoeau_default_variation_id', 0, $pdp_variations );
if ( ! $default_id && ! empty( $pdp_variations ) ) {
	$default_attrs = $product->get_default_attributes();
	if ( ! empty( $default_attrs ) ) {
		foreach ( $rows as $row ) {
			$matches = true;
			foreach ( $default_attrs as $attr_name => $attr_value ) {
				$variation_key = 'attribute_' . sanitize_title( $attr_name );
				if ( ! isset( $row['attributes'][ $variation_key ] ) || (string) $row['attributes'][ $variation_key ] !== (string) $attr_value ) {
					$matches = false;
					break;
				}
			}
			if ( $matches ) {
				$default_id = (int) $row['variation_id'];
				break;
			}
		}
	}
	if ( ! $default_id ) {
		$default_id = (int) $rows[0]['variation_id'];
	}
}

$fx_display_ccy  = function_exists( 'htoeau_child_fx_get_display_currency' ) ? htoeau_child_fx_get_display_currency() : get_woocommerce_currency();
$symbol_map      = array(
	'GBP' => html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' ),
	'EUR' => html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' ),
);
$currency_symbol = isset( $symbol_map[ $fx_display_ccy ] )
	? $symbol_map[ $fx_display_ccy ]
	: html_entity_decode( get_woocommerce_currency_symbol( $fx_display_ccy ), ENT_QUOTES, 'UTF-8' );
$decimals        = wc_get_price_decimals();
?>
<div class="htoeau-qty-cards" data-htoeau-qty-cards>
	<?php foreach ( $rows as $r ) : ?>
		<?php
		$is_selected = ( $r['variation_id'] === $default_id );
		$pack        = (int) $r['cans'];
		$pack_mod    = in_array( $pack, array( 12, 48, 96 ), true ) ? $pack : 0;
		$card_class  = 'htoeau-qty-card htoeau-qty-card--pack-' . ( $pack_mod ? (string) $pack_mod : 'default' ) . ( $is_selected ? ' is-selected' : '' );
		?>
		<button type="button" class="<?php echo esc_attr( $card_class ); ?>" data-htoeau-qty-card data-variation-id="<?php echo esc_attr( (string) $r['variation_id'] ); ?>" data-can-count="<?php echo esc_attr( (string) $pack ); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">
			<?php if ( $r['badge'] ) : ?>
				<span class="htoeau-qty-card__badge htoeau-qty-card__badge--label"><?php echo esc_html( $r['badge'] ); ?></span>
			<?php endif; ?>
			<span class="htoeau-qty-card__cans-visual" aria-hidden="true">
				<?php
				if ( $r['cans'] > 0 ) {
					$can_map = array( 12 => 1, 24 => 2, 48 => 4, 96 => 8 );
					$show    = isset( $can_map[ $r['cans'] ] ) ? $can_map[ $r['cans'] ] : min( 8, max( 1, (int) round( $r['cans'] / 12 ) ) );
					for ( $i = 0; $i < $show; $i++ ) {
						echo '<span class="htoeau-qty-card__can"><img src="' . esc_url( $can_image_url ) . '" alt="" width="30" height="80" loading="lazy" /></span>';
					}
				}
				?>
			</span>
			<span class="htoeau-qty-card__body">
				<span class="htoeau-qty-card__label"><?php echo esc_html( $r['label'] ); ?></span>
				<span class="htoeau-qty-card__prices">
					<?php if ( $r['regular'] > $r['price'] ) : ?>
						<span class="htoeau-qty-card__was"><?php echo wp_kses_post( htoeau_child_fx_wc_price( $r['regular'] ) ); ?></span>
					<?php endif; ?>
					<span class="htoeau-qty-card__now"><?php echo wp_kses_post( htoeau_child_fx_wc_price( $r['price'] ) ); ?></span>
				</span>
				<?php if ( $r['cans'] > 0 ) : ?>
					<span class="htoeau-qty-card__unit"><?php echo wp_kses_post( htoeau_child_fx_wc_price( $r['price'] / $r['cans'] ) ); ?> <?php esc_html_e( 'per can', 'hello-elementor-child' ); ?></span>
				<?php endif; ?>
			</span>
			<?php if ( $r['savings'] > 0 ) : ?>
				<span class="htoeau-qty-card__badge htoeau-qty-card__badge--save"><?php echo esc_html( sprintf( /* translators: %d: percent */ __( 'Save %d%%', 'hello-elementor-child' ), $r['savings'] ) ); ?></span>
			<?php endif; ?>
		</button>
	<?php endforeach; ?>
</div>

<script type="application/json" id="htoeau-pdp-data"><?php echo wp_json_encode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'defaultVariationId'  => $default_id,
		'subscribeDiscount'   => $discount_pct,
		'legacySubscribeUi'   => $legacy_subscribe_ui,
		'currencySymbol'      => html_entity_decode( $currency_symbol, ENT_QUOTES, 'UTF-8' ),
		'decimals'            => $decimals,
		'variations'          => $pdp_variations,
	),
	JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
); ?></script>
