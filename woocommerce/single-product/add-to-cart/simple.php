<?php
/**
 * Simple product add to cart — HtoEAU PDP layout (quantity + CTA row).
 *
 * @package Hello_Elementor_Child
 * @version 10.2.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( ! $product->is_in_stock() ) {
	return;
}

$min_q = $product->get_min_purchase_quantity();
$max_q = $product->get_max_purchase_quantity();
$fixed = $product->is_sold_individually() || ( (int) $min_q === 1 && (int) $max_q === 1 );

$qty_id = 'htoeau-simple-qty-' . (int) $product->get_id();

do_action( 'woocommerce_before_add_to_cart_form' );
?>
<form class="cart htoeau-simple-cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<div class="htoeau-simple-cart__actions">
		<div class="htoeau-simple-cart__qty">
			<?php if ( $fixed ) : ?>
				<input type="hidden" name="quantity" value="<?php echo esc_attr( (string) wc_stock_amount( $min_q ) ); ?>" />
			<?php else : ?>
				<label class="htoeau-simple-cart__qty-label" for="<?php echo esc_attr( $qty_id ); ?>"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></label>
				<?php
				do_action( 'woocommerce_before_add_to_cart_quantity' );
				woocommerce_quantity_input(
					array(
						'input_id'    => $qty_id,
						'min_value'   => $min_q,
						'max_value'   => $max_q,
						'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $min_q, // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						'classes'     => array( 'input-text', 'qty', 'text', 'htoeau-simple-cart__input' ),
					)
				);
				do_action( 'woocommerce_after_add_to_cart_quantity' );
				?>
			<?php endif; ?>
		</div>

		<button
			type="submit"
			name="add-to-cart"
			value="<?php echo esc_attr( $product->get_id() ); ?>"
			class="single_add_to_cart_button button alt htoeau-pdp__add-btn<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"
		><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
	</div>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
</form>
<?php
do_action( 'woocommerce_after_add_to_cart_form' );
