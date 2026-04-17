<?php
/**
 * Product card in shop loop (Figma 1:2729).
 *
 * @package Hello_Elementor_Child
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$excerpt   = htoeau_child_shop_get_card_excerpt( $product );
$price_line = htoeau_child_shop_get_per_can_line_html( $product );

$link = $product->get_permalink();
?>
<li <?php wc_product_class( 'htoeau-shop-card-wrap', $product ); ?>>
	<div class="htoeau-shop-card">
		<a class="htoeau-shop-card__media-link" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
			<div class="htoeau-shop-card__thumb">
				<?php
				// ACF `shop_catalog_image` (HtoEAU Product → Shop / catalog); else default thumbnail.
				echo htoeau_child_shop_get_loop_product_thumbnail_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</a>
		<div class="htoeau-shop-card__body">
			<h2 class="htoeau-shop-card__title">
				<a href="<?php echo esc_url( $link ); ?>"><?php echo wp_kses_post( $product->get_name() ); ?></a>
			</h2>
			<?php if ( '' !== $excerpt ) : ?>
				<p class="htoeau-shop-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $price_line ) : ?>
				<p class="htoeau-shop-card__price-line"><?php echo wp_kses_post( $price_line ); ?></p>
			<?php endif; ?>
			<a class="htoeau-shop-card__cta" href="<?php echo esc_url( $link ); ?>">
				<?php esc_html_e( 'Try HtoEAU Now', 'hello-elementor-child' ); ?>
			</a>
		</div>
	</div>
</li>
