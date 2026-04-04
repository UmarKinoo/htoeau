<?php
/**
 * Primary add to cart CTA (variable products — submits variations form).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_type( 'variable' ) ) {
	return;
}
?>
<div class="htoeau-pdp__cta-wrap">
	<input type="hidden" name="htoeau_purchase_intent" value="subscribe" form="htoeau-variations-form" data-htoeau-purchase-intent />
	<button
		type="submit"
		form="htoeau-variations-form"
		class="single_add_to_cart_button button alt htoeau-pdp__add-btn"
		data-htoeau-add-btn
		disabled
	>
		<span data-htoeau-add-btn-label><?php esc_html_e( 'Add to Cart', 'hello-elementor-child' ); ?></span>
	</button>
</div>
