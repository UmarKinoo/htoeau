<?php
/**
 * Custom cart layout for HtoEAU.
 *
 * Keeps WooCommerce cart/totals logic but applies project design structure.
 *
 * @package Hello_Elementor_Child
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$img_base = function_exists( 'htoeau_child_get_brand_images_base_url' ) ? htoeau_child_get_brand_images_base_url() : '';

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

do_action( 'woocommerce_before_cart' );
?>

<section class="htoeau-cart-page">
	<header class="htoeau-cart-page__head">
		<h1 class="htoeau-cart-page__title"><?php esc_html_e( 'Your Cart', 'hello-elementor-child' ); ?></h1>
		<div class="htoeau-cart-page__trust">
			<span><?php esc_html_e( 'Not satisfied? Free return within 14 days', 'hello-elementor-child' ); ?></span>
			<span aria-hidden="true">•</span>
			<span><?php esc_html_e( 'Questions? Fast customer service', 'hello-elementor-child' ); ?></span>
			<span aria-hidden="true">•</span>
			<span><?php esc_html_e( 'Tested by sports and scientists', 'hello-elementor-child' ); ?></span>
		</div>
	</header>

	<div class="htoeau-cart-page__grid">
		<div class="htoeau-cart-page__main">
			<form class="woocommerce-cart-form htoeau-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
				<?php do_action( 'woocommerce_before_cart_table' ); ?>
				<table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents htoeau-cart-table" cellspacing="0">
					<thead>
						<tr>
							<th class="product-remove"><span class="screen-reader-text"><?php esc_html_e( 'Remove item', 'woocommerce' ); ?></span></th>
							<th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e( 'Thumbnail image', 'woocommerce' ); ?></span></th>
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
											esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
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
									echo $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ) : $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</td>
								<td class="product-name" data-title="<?php esc_attr_e( 'Product', 'woocommerce' ); ?>">
									<?php
									echo $product_permalink
										? wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) )
										: wp_kses_post( $product_name . '&nbsp;' );
									do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
									echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</td>
								<td class="product-price" data-title="<?php esc_attr_e( 'Price', 'woocommerce' ); ?>">
									<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</td>
								<td class="product-quantity" data-title="<?php esc_attr_e( 'Quantity', 'woocommerce' ); ?>">
									<?php
									$min_quantity = $_product->is_sold_individually() ? 1 : 0;
									$max_quantity = $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity();
									$product_quantity = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $max_quantity,
											'min_value'    => $min_quantity,
											'product_name' => $product_name,
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
								<?php if ( wc_coupons_enabled() ) : ?>
									<div class="coupon">
										<label for="coupon_code" class="screen-reader-text"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
										<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
										<button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
										<?php do_action( 'woocommerce_cart_coupon' ); ?>
									</div>
								<?php endif; ?>
								<button type="submit" class="button" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>"><?php esc_html_e( 'Update cart', 'woocommerce' ); ?></button>
								<?php do_action( 'woocommerce_cart_actions' ); ?>
								<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
							</td>
						</tr>
						<?php do_action( 'woocommerce_after_cart_contents' ); ?>
					</tbody>
				</table>
				<?php do_action( 'woocommerce_after_cart_table' ); ?>
			</form>

			<div class="htoeau-cart-page__totals">
				<?php woocommerce_cart_totals(); ?>
			</div>
		</div>

		<aside class="htoeau-cart-page__side">
			<div class="htoeau-cart-help">
				<h3><?php esc_html_e( 'Questions?', 'hello-elementor-child' ); ?></h3>
				<p><?php esc_html_e( 'Feel free to contact us. Our customer service is willing to help you with the questions you have regarding your order.', 'hello-elementor-child' ); ?></p>
				<p><strong>hello@staging.htoeau.com</strong></p>
				<?php if ( $img_base ) : ?>
					<img src="<?php echo esc_url( $img_base . 'payment-cards.svg' ); ?>" alt="" loading="lazy" />
				<?php endif; ?>
			</div>
			<div class="htoeau-cart-testimonials">
				<?php foreach ( $testimonials as $t ) : ?>
					<article class="htoeau-testimonial">
						<p class="htoeau-testimonial__quote"><?php echo esc_html( $t['quote'] ); ?></p>
						<div class="htoeau-testimonial__meta">
							<?php if ( $img_base ) : ?>
								<img class="htoeau-testimonial__stars" src="<?php echo esc_url( $img_base . 'stars-testimonial.svg' ); ?>" alt="" width="63" height="10" loading="lazy" />
							<?php endif; ?>
							<p class="htoeau-testimonial__byline">
								<span class="htoeau-testimonial__name"><?php echo esc_html( $t['name'] ); ?></span>
								<span class="htoeau-testimonial__role"><?php echo esc_html( $t['role'] ); ?></span>
							</p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</aside>
	</div>

	<section class="htoeau-cart-faq" aria-label="<?php esc_attr_e( 'Frequently asked questions', 'hello-elementor-child' ); ?>">
		<h2 class="htoeau-cart-faq__title"><?php esc_html_e( 'Frequently Asked Questions', 'hello-elementor-child' ); ?></h2>
		<details class="htoeau-cart-faq__item" open>
			<summary><?php esc_html_e( 'How long will my order take to be delivered?', 'hello-elementor-child' ); ?></summary>
			<p><?php esc_html_e( 'Shipping within Europe takes 1 to 3 days. Orders within the Netherlands are delivered the next day.', 'hello-elementor-child' ); ?></p>
		</details>
		<details class="htoeau-cart-faq__item">
			<summary><?php esc_html_e( 'How will my order be delivered?', 'hello-elementor-child' ); ?></summary>
			<p><?php esc_html_e( 'We deliver through our trusted shipping partners with tracked delivery options.', 'hello-elementor-child' ); ?></p>
		</details>
		<details class="htoeau-cart-faq__item">
			<summary><?php esc_html_e( 'What if I do not like the water?', 'hello-elementor-child' ); ?></summary>
			<p><?php esc_html_e( 'You can use our return policy window; contact support and we will help immediately.', 'hello-elementor-child' ); ?></p>
		</details>
		<details class="htoeau-cart-faq__item">
			<summary><?php esc_html_e( 'How can I reach HtoEAU?', 'hello-elementor-child' ); ?></summary>
			<p><?php esc_html_e( 'Email us at hello@staging.htoeau.com and our team will respond quickly.', 'hello-elementor-child' ); ?></p>
		</details>
	</section>
</section>

<?php do_action( 'woocommerce_after_cart' ); ?>
