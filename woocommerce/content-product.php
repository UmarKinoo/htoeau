<?php
/**
 * Product card in shop loop (Figma 1:2729 grid; 1:2265 desktop card).
 *
 * @package Hello_Elementor_Child
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$excerpt    = htoeau_child_shop_get_card_excerpt( $product );
$price_line = htoeau_child_shop_get_per_can_line_html( $product );
$rating     = function_exists( 'htoeau_child_shop_get_rating_display' ) ? htoeau_child_shop_get_rating_display( $product ) : array( 'rating' => 4.8, 'count' => 248 );
$rating_num = function_exists( 'wc_format_decimal' ) ? wc_format_decimal( $rating['rating'], 1 ) : number_format( (float) $rating['rating'], 1, '.', '' );

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
			<div class="htoeau-shop-card__rating">
				<svg class="htoeau-shop-card__star" width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M6.5 0L8.04 4.15H12.5L9 6.72L10.54 10.87L6.5 8.3L2.46 10.87L4 6.72L0.5 4.15H4.96L6.5 0Z" fill="#FFC107"/>
				</svg>
				<p class="htoeau-shop-card__rating-text">
					<span class="htoeau-shop-card__rating-score"><?php echo esc_html( $rating_num ); ?></span>
					<span class="htoeau-shop-card__rating-count">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: review count */
								__( ' (%d reviews)', 'hello-elementor-child' ),
								(int) $rating['count']
							)
						);
						?>
					</span>
				</p>
			</div>
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
