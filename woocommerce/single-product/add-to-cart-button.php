<?php
/**
 * Primary add to cart CTA (variable products — submits variations form).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! function_exists( 'htoeau_child_product_is_variable_pdp' ) || ! htoeau_child_product_is_variable_pdp( $product ) ) {
	return;
}
?>
<div class="htoeau-pdp__cta-wrap">
	<?php if ( ! function_exists( 'htoeau_child_product_is_wcs_subscription' ) || ! htoeau_child_product_is_wcs_subscription( $product ) ) : ?>
	<input type="hidden" name="htoeau_purchase_intent" value="subscribe" form="htoeau-variations-form" data-htoeau-purchase-intent />
	<?php endif; ?>
	<button
		type="submit"
		form="htoeau-variations-form"
		class="single_add_to_cart_button button alt htoeau-pdp__add-btn"
		data-htoeau-add-btn
		disabled
	>
		<span data-htoeau-add-btn-label><?php esc_html_e( 'Add to Cart', 'htoeau-child' ); ?></span>
	</button>
</div>
