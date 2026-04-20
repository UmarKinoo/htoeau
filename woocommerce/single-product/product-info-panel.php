<?php
/**
 * Rating, title, from-per-can line, short description.
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

$lowest_per_can = null;
if ( function_exists( 'htoeau_child_product_is_variable_pdp' ) && htoeau_child_product_is_variable_pdp( $product ) ) {
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
}

?>
<div class="htoeau-info-panel">
	<h1 class="htoeau-product-title"><?php echo wp_kses_post( $product->get_name() ); ?></h1>

	<?php if ( null !== $lowest_per_can ) : ?>
		<p class="htoeau-from-per-can">
			<?php esc_html_e( 'From', 'hello-elementor-child' ); ?>
			<strong data-htoeau-per-can><?php echo wp_kses_post( htoeau_child_fx_wc_price( $lowest_per_can ) ); ?> <?php esc_html_e( 'per can', 'hello-elementor-child' ); ?></strong>
		</p>
	<?php elseif ( $product->is_type( 'simple' ) && '' !== (string) $product->get_price() ) : ?>
		<p class="htoeau-simple-product-price woocommerce"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
	<?php endif; ?>

	<?php if ( $product->get_short_description() ) : ?>
		<div class="htoeau-short-desc">
			<?php echo apply_filters( 'woocommerce_short_description', $product->get_short_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</div>
