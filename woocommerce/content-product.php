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

$rating     = htoeau_child_shop_get_rating_display( $product );
$stars_base = htoeau_child_get_brand_images_base_url();

$excerpt   = htoeau_child_shop_get_card_excerpt( $product );
$price_line = htoeau_child_shop_get_per_can_line_html( $product );

$link = $product->get_permalink();
?>
<li <?php wc_product_class( 'htoeau-shop-card-wrap', $product ); ?>>
	<div class="htoeau-shop-card">
		<a class="htoeau-shop-card__media-link" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
			<div class="htoeau-shop-card__thumb">
				<?php
				echo woocommerce_get_product_thumbnail(
					'woocommerce_thumbnail',
					array(
						'class' => 'htoeau-shop-card__img',
						'alt'   => esc_attr( trim( wp_strip_all_tags( $product->get_name() ) ) ),
					)
				);
				?>
			</div>
		</a>
		<div class="htoeau-shop-card__body">
			<div class="htoeau-shop-card__rating">
				<img
					class="htoeau-shop-card__star"
					src="<?php echo esc_url( $stars_base . 'stars-rating.svg' ); ?>"
					alt=""
					width="13"
					height="12"
					loading="lazy"
				/>
				<p class="htoeau-shop-card__rating-text">
					<strong class="htoeau-shop-card__rating-score"><?php echo esc_html( number_format_i18n( $rating['rating'], 1 ) ); ?></strong>
					<span class="htoeau-shop-card__rating-count">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: review count */
								__( ' (%d reviews)', 'hello-elementor-child' ),
								$rating['count']
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
