<?php
/**
 * Custom cart layout for HtoEAU.
 *
 * Keeps WooCommerce cart/totals logic but applies project design structure.
 * Totals live in the sticky sidebar; cart items stay in the main column.
 *
 * @package Hello_Elementor_Child
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$img_base      = function_exists( 'htoeau_child_get_brand_images_base_url' ) ? htoeau_child_get_brand_images_base_url() : '';
$support_email = (string) apply_filters( 'htoeau_support_email', 'hello@htoeau.com' );
$shop_url      = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

$testimonials = array(
	array(
		'quote' => __( 'As a professional football player I pay attention to recovery, and HtoEAU has become part of my daily routine.', 'hello-elementor-child' ),
		'name'  => __( 'Angus', 'hello-elementor-child' ),
		'role'  => __( 'Professional football player', 'hello-elementor-child' ),
	),
	array(
		'quote' => __( 'As an MSc sports therapist and strength coach, hydration quality matters. HtoEAU is now in my routine.', 'hello-elementor-child' ),
		'name'  => __( 'Heather', 'hello-elementor-child' ),
		'role'  => __( 'MSc Sports Therapist & Strength Coach', 'hello-elementor-child' ),
	),
	array(
		'quote' => __( 'I use HtoEAU to stay sharp and recover better. It fits naturally into my day.', 'hello-elementor-child' ),
		'name'  => __( 'Inge', 'hello-elementor-child' ),
		'role'  => __( 'Health & performance client', 'hello-elementor-child' ),
	),
);

/* Inline SVG icons for trust bar — portable, no image request. */
$icon_truck  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
$icon_check  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>';
$icon_shield = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>';

do_action( 'woocommerce_before_cart' );
?>

<section class="htoeau-cart-page">

	<header class="htoeau-cart-page__head">
		<h1 class="htoeau-cart-page__title"><?php esc_html_e( 'Your Cart', 'hello-elementor-child' ); ?></h1>
		<div class="htoeau-cart-page__trust" role="list">
			<span class="htoeau-cart-trust-item" role="listitem">
				<?php echo $icon_truck; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Free return within 14 days', 'hello-elementor-child' ); ?>
			</span>
			<span class="htoeau-cart-trust-item" role="listitem">
				<?php echo $icon_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Fast customer service', 'hello-elementor-child' ); ?>
			</span>
			<span class="htoeau-cart-trust-item" role="listitem">
				<?php echo $icon_shield; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Tested by sports &amp; scientists', 'hello-elementor-child' ); ?>
			</span>
		</div>
	</header>

	<div class="htoeau-cart-page__grid">

		<!-- ── Main column: cart items ───────────────────── -->
		<div class="htoeau-cart-page__main">
			<form class="woocommerce-cart-form htoeau-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
				<?php do_action( 'woocommerce_before_cart_table' ); ?>
				<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents" cellspacing="0">
					<thead>
						<tr>
							<th class="product-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove item', 'woocommerce' ); ?></span></th>
							<th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></span></th>
							<th scope="col" class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
							<th scope="col" class="product-price"><?php esc_html_e( 'Price', 'woocommerce' ); ?></th>
							<th scope="col" class="product-quantity"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></th>
							<th scope="col" class="product-subtotal"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php do_action( 'woocommerce_before_cart_contents' ); ?>

						<?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : ?>
							<?php
							$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
							$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
							if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
								continue;
							}
							$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
							$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
							?>
							<tr class="woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

								<td class="product-remove">
									<?php
									echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										'woocommerce_cart_item_remove_link',
										sprintf(
											'<a role="button" href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
											esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
											esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( is_string( $product_name ) ? $product_name : '' ) ) ),
											esc_attr( $product_id ),
											esc_attr( $_product->get_sku() )
										),
										$cart_item_key
									);
									?>
								</td>

								<td class="product-thumbnail">
									<?php
									$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
									echo $product_permalink
										? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										: $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</td>

								<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
									<?php
									echo $product_permalink
										? wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) )
										: wp_kses_post( is_string( $product_name ) ? $product_name . '&nbsp;' : '' );
									do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
									echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</td>

								<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
									<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</td>

								<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
									<?php
									$product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity(),
											'min_value'    => $_product->is_sold_individually() ? 1 : 0,
											'product_name' => is_string( $product_name ) ? $product_name : '',
										),
										$_product,
										false
									);
									echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</td>

								<td class="product-subtotal" data-title="<?php esc_attr_e( 'Subtotal', 'woocommerce' ); ?>">
									<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</td>

							</tr>
						<?php endforeach; ?>

						<?php do_action( 'woocommerce_cart_contents' ); ?>

						<tr>
							<td colspan="6" class="actions">

								<button
									type="submit"
									class="button"
									name="update_cart"
									value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"
									style="display:none"
									aria-hidden="true"
									tabindex="-1"
								><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>

								<div class="actions-right">
									<a href="<?php echo esc_url( $shop_url ); ?>" class="htoeau-btn-ghost">
										&#8592; <?php esc_html_e( 'Continue shopping', 'hello-elementor-child' ); ?>
									</a>
								</div>


								<?php do_action( 'woocommerce_cart_actions' ); ?>
								<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

							</td>
						</tr>

						<?php do_action( 'woocommerce_after_cart_contents' ); ?>
					</tbody>
				</table>
				<?php do_action( 'woocommerce_after_cart_table' ); ?>
			</form>
		</div>

		<!-- ── Sidebar: totals → help → testimonials ──────── -->
		<aside class="htoeau-cart-page__side">

			<?php woocommerce_cart_totals(); ?>

			<div class="htoeau-cart-help">
				<h3 class="htoeau-cart-help__title"><?php esc_html_e( 'Need help?', 'hello-elementor-child' ); ?></h3>
				<p><?php esc_html_e( 'Our customer service is ready to answer any questions about your order.', 'hello-elementor-child' ); ?></p>
				<p><a href="mailto:<?php echo esc_attr( $support_email ); ?>"><?php echo esc_html( $support_email ); ?></a></p>
			</div>

			<div class="htoeau-cart-testimonials" data-htoeau-cart-carousel>
				<div class="htoeau-cart-testimonials__viewport">
					<div class="htoeau-cart-testimonials__track">
						<?php foreach ( $testimonials as $idx => $t ) : ?>
							<article
								class="htoeau-testimonial htoeau-cart-testimonials__slide"
								aria-hidden="<?php echo 0 === $idx ? 'false' : 'true'; ?>"
							>
								<p class="htoeau-testimonial__quote"><?php echo esc_html( $t['quote'] ); ?></p>
								<div class="htoeau-testimonial__meta">
									<?php if ( $img_base ) : ?>
										<img
											class="htoeau-testimonial__stars"
											src="<?php echo esc_url( $img_base . 'stars-testimonial.svg' ); ?>"
											alt="<?php esc_attr_e( '5 stars', 'hello-elementor-child' ); ?>"
											width="68"
											height="12"
											loading="lazy"
										/>
									<?php endif; ?>
									<p class="htoeau-testimonial__byline">
										<span class="htoeau-testimonial__name"><?php echo esc_html( $t['name'] ); ?></span>
										<span class="htoeau-testimonial__role"><?php echo esc_html( $t['role'] ); ?></span>
									</p>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</div>

				<button
					type="button"
					class="htoeau-cart-testimonials__nav htoeau-cart-testimonials__nav--prev"
					aria-label="<?php esc_attr_e( 'Previous testimonial', 'hello-elementor-child' ); ?>"
				>&#10094;</button>
				<button
					type="button"
					class="htoeau-cart-testimonials__nav htoeau-cart-testimonials__nav--next"
					aria-label="<?php esc_attr_e( 'Next testimonial', 'hello-elementor-child' ); ?>"
				>&#10095;</button>

				<div class="htoeau-cart-testimonials__dots" role="tablist" aria-label="<?php esc_attr_e( 'Testimonials', 'hello-elementor-child' ); ?>">
					<?php foreach ( $testimonials as $idx => $t ) : ?>
						<button
							type="button"
							class="htoeau-cart-testimonials__dot"
							role="tab"
							aria-selected="<?php echo 0 === $idx ? 'true' : 'false'; ?>"
							tabindex="<?php echo 0 === $idx ? '0' : '-1'; ?>"
							aria-label="<?php echo esc_attr( sprintf( __( 'Testimonial %d', 'hello-elementor-child' ), $idx + 1 ) ); ?>"
						></button>
					<?php endforeach; ?>
				</div>
			</div>

		</aside>
	</div>

</section>

<?php do_action( 'woocommerce_after_cart' ); ?>
