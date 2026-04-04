<?php
/**
 * Custom single product content — HtoEAU PDP layout.
 *
 * @package HtoEAU_Child
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'htoeau-pdp-product', $product ); ?>>

	<div class="htoeau-pdp">
		<div class="htoeau-pdp__inner">
			<div class="htoeau-pdp__gallery-col">
				<?php wc_get_template( 'single-product/product-image-gallery.php' ); ?>
				<?php wc_get_template( 'single-product/testimonial.php' ); ?>
			</div>
			<div class="htoeau-pdp__info-col">
				<?php wc_get_template( 'single-product/product-info-panel.php' ); ?>

				<?php if ( $product->is_type( 'variable' ) ) : ?>
					<?php wc_get_template( 'single-product/quantity-cards.php' ); ?>
					<?php wc_get_template( 'single-product/subscribe-toggle.php' ); ?>
					<div class="htoeau-pdp__wc-form" id="htoeau-pdp-purchase">
						<?php woocommerce_variable_add_to_cart(); ?>
						<?php wc_get_template( 'single-product/add-to-cart-button.php' ); ?>
					</div>
				<?php else : ?>
					<div class="htoeau-pdp__wc-form htoeau-pdp__wc-form--simple" id="htoeau-pdp-purchase">
						<?php woocommerce_template_single_add_to_cart(); ?>
					</div>
				<?php endif; ?>

				<?php wc_get_template( 'single-product/feature-icons.php' ); ?>
				<?php wc_get_template( 'single-product/accordion-tabs.php' ); ?>
			</div>
		</div>
	</div>

	<?php get_template_part( 'template-parts/sample-kit', 'hero' ); ?>

	<?php get_template_part( 'template-parts/transformation', 'section' ); ?>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
