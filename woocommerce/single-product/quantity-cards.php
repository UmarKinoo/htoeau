<?php
/**
 * Quantity / pack variation cards (12 / 48 / 96 cans).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_type( 'variable' ) ) {
	return;
}

$variations = $product->get_available_variations();
if ( empty( $variations ) ) {
	return;
}

$img_base = htoeau_child_get_brand_images_base_url();

$rows = array();
foreach ( $variations as $v ) {
	$vid   = (int) $v['variation_id'];
	$cans  = htoeau_child_get_can_count_from_variation_attrs( $v['attributes'] );
	if ( $cans < 1 || ! $v['is_purchasable'] || ! $v['is_in_stock'] ) {
		continue;
	}
	$price    = (float) $v['display_price'];
	$regular  = (float) $v['display_regular_price'];
	$per_can  = $price / $cans;
	$savings  = 0;
	if ( $regular > $price && $regular > 0 ) {
		$savings = round( 100 * ( 1 - ( $price / $regular ) ) );
	}
	$badge = '';
	if ( function_exists( 'get_field' ) ) {
		$badge = (string) get_field( 'variation_badge', $vid );
	}
	$rows[] = array(
		'variation_id' => $vid,
		'attributes'   => $v['attributes'],
		'cans'         => $cans,
		'price'        => $price,
		'regular'      => $regular,
		'per_can'      => $per_can,
		'savings'      => $savings,
		'badge'        => $badge,
	);
}

if ( empty( $rows ) ) {
	return;
}

usort(
	$rows,
	function ( $a, $b ) {
		return $a['cans'] - $b['cans'];
	}
);

$discount_pct = (float) apply_filters( 'htoeau_subscribe_discount_percent', 10 );

$pdp_variations = array();
foreach ( $rows as $r ) {
	$sub_price = $r['price'] * ( 1 - $discount_pct / 100 );
	$pdp_variations[] = array(
		'variationId'    => $r['variation_id'],
		'attributes'     => $r['attributes'],
		'canCount'       => $r['cans'],
		'oneTime'        => function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $r['price'] ) : $r['price'],
		'oneTimeRegular' => function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $r['regular'] ) : $r['regular'],
		'subscribe'      => function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $sub_price ) : $sub_price,
		'perCan'         => function_exists( 'htoeau_child_fx_convert_amount' ) ? htoeau_child_fx_convert_amount( $r['per_can'] ) : $r['per_can'],
		'savingsPct'     => $r['savings'],
	);
}

$default_id = (int) apply_filters( 'htoeau_default_variation_id', 0, $pdp_variations );
if ( ! $default_id && ! empty( $pdp_variations ) ) {
	$max_cans = 0;
	foreach ( $pdp_variations as $pv ) {
		if ( (int) $pv['canCount'] >= $max_cans ) {
			$max_cans    = (int) $pv['canCount'];
			$default_id = (int) $pv['variationId'];
		}
	}
}

$fx_display_ccy  = function_exists( 'htoeau_child_fx_get_display_currency' ) ? htoeau_child_fx_get_display_currency() : get_woocommerce_currency();
$currency_symbol = get_woocommerce_currency_symbol( $fx_display_ccy );
$decimals        = wc_get_price_decimals();
?>
<div class="htoeau-qty-cards" data-htoeau-qty-cards>
	<?php foreach ( $rows as $r ) : ?>
		<?php
		$is_selected = ( $r['variation_id'] === $default_id );
		$card_class  = 'htoeau-qty-card' . ( $is_selected ? ' is-selected' : '' );
		?>
		<button type="button" class="<?php echo esc_attr( $card_class ); ?>" data-htoeau-qty-card data-variation-id="<?php echo esc_attr( (string) $r['variation_id'] ); ?>" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">
			<?php if ( $r['badge'] ) : ?>
				<span class="htoeau-qty-card__badge htoeau-qty-card__badge--label"><?php echo esc_html( $r['badge'] ); ?></span>
			<?php endif; ?>
			<span class="htoeau-qty-card__cans-visual" aria-hidden="true">
				<?php
				$can_map = array( 12 => 1, 24 => 2, 48 => 4, 96 => 8 );
				$show    = isset( $can_map[ $r['cans'] ] ) ? $can_map[ $r['cans'] ] : min( 8, max( 1, (int) round( $r['cans'] / 12 ) ) );
				for ( $i = 0; $i < $show; $i++ ) {
					echo '<span class="htoeau-qty-card__can"><img src="' . esc_url( $img_base . 'can-label.png' ) . '" alt="" width="30" height="80" loading="lazy" /></span>';
				}
				?>
			</span>
			<span class="htoeau-qty-card__label"><?php echo esc_html( sprintf( /* translators: %d: can count */ __( '%d Cans', 'hello-elementor-child' ), $r['cans'] ) ); ?></span>
			<span class="htoeau-qty-card__prices">
				<?php if ( $r['regular'] > $r['price'] ) : ?>
					<span class="htoeau-qty-card__was"><?php echo wp_kses_post( htoeau_child_fx_wc_price( $r['regular'] ) ); ?></span>
				<?php endif; ?>
				<span class="htoeau-qty-card__now"><?php echo wp_kses_post( htoeau_child_fx_wc_price( $r['price'] ) ); ?></span>
			</span>
			<span class="htoeau-qty-card__per"><?php echo wp_kses_post( htoeau_child_fx_wc_price( $r['per_can'] ) ); ?> <?php esc_html_e( 'per can', 'hello-elementor-child' ); ?></span>
			<?php if ( $r['savings'] > 0 ) : ?>
				<span class="htoeau-qty-card__badge htoeau-qty-card__badge--save"><?php echo esc_html( sprintf( /* translators: %d: percent */ __( 'Save %d%%', 'hello-elementor-child' ), $r['savings'] ) ); ?></span>
			<?php endif; ?>
		</button>
	<?php endforeach; ?>
</div>

<script type="application/json" id="htoeau-pdp-data"><?php echo wp_json_encode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'defaultVariationId' => $default_id,
		'subscribeDiscount'  => $discount_pct,
		'currencySymbol'     => html_entity_decode( $currency_symbol, ENT_QUOTES, 'UTF-8' ),
		'decimals'           => $decimals,
		'variations'         => $pdp_variations,
	),
	JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
); ?></script>
