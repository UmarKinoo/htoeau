<?php
/**
 * Custom single product content — HtoEAU PDP layout.
 *
 * @package HtoEAU_Child
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$use_custom_variable_ui = false;
if ( $product && function_exists( 'htoeau_child_product_is_variable_pdp' ) && htoeau_child_product_is_variable_pdp( $product ) ) {
	$available_variations = $product->get_available_variations();
	foreach ( $available_variations as $variation ) {
		if ( ! empty( $variation['is_purchasable'] ) && ! empty( $variation['is_in_stock'] ) ) {
			$use_custom_variable_ui = true;
			break;
		}
	}
}

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
		</div>
		<div class="htoeau-pdp__info-col">
			<?php wc_get_template( 'single-product/product-info-panel.php' ); ?>

			<?php if ( function_exists( 'htoeau_child_product_is_variable_pdp' ) && htoeau_child_product_is_variable_pdp( $product ) && $use_custom_variable_ui ) : ?>
				<?php wc_get_template( 'single-product/quantity-cards.php' ); ?>
				<?php wc_get_template( 'single-product/subscribe-toggle.php' ); ?>
				<div class="htoeau-pdp__wc-form" id="htoeau-pdp-purchase">
					<?php
					if ( function_exists( 'htoeau_child_template_variable_add_to_cart' ) ) {
						htoeau_child_template_variable_add_to_cart();
					} else {
						woocommerce_variable_add_to_cart();
					}
					?>
					<?php wc_get_template( 'single-product/add-to-cart-button.php' ); ?>
				</div>
			<?php elseif ( function_exists( 'htoeau_child_product_is_variable_pdp' ) && htoeau_child_product_is_variable_pdp( $product ) ) : ?>
				<div class="htoeau-pdp__wc-form htoeau-pdp__wc-form--native-variable" id="htoeau-pdp-purchase">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>
			<?php else : ?>
				<div class="htoeau-pdp__wc-form htoeau-pdp__wc-form--simple" id="htoeau-pdp-purchase">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>
			<?php endif; ?>

			<?php wc_get_template( 'single-product/feature-icons.php' ); ?>
			<?php wc_get_template( 'single-product/testimonial.php' ); ?>
			<?php wc_get_template( 'single-product/accordion-tabs.php' ); ?>
		</div>
		</div>

		<?php wc_get_template( 'single-product/pdp-faq.php' ); ?>
	</div>

	<?php
	/**
	 * Elementor template slot (Customizer): extra PDP sections (e.g. former sample kit + transformation content).
	 *
	 * @see htoeau_child_output_pdp_elementor_template() in inc/elementor-pdp-template.php
	 */
	do_action( 'htoeau_pdp_after_main_columns' );
	?>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
