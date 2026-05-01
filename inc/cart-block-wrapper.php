<?php
/**
 * Inject trust bar, help, and testimonial carousel around the WooCommerce Cart Block.
 *
 * The Cart Block renders via React / Store API — PHP template overrides are ignored.
 * We use render_block_woocommerce/cart to wrap the block output with our design chrome.
 *
 * Layout strategy:
 *   - Trust bar header sits above the block cart.
 *   - Help + testimonials are rendered as a hidden div and moved by cart-carousel.js
 *     into .wp-block-woocommerce-cart-totals-block so they sit below the order summary
 *     in the block cart's native right column.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'render_block_woocommerce/cart', 'htoeau_child_wrap_cart_block' );

/**
 * @param string $content Rendered block HTML.
 * @return string
 */
function htoeau_child_wrap_cart_block( string $content ): string {
	if ( is_admin() || wp_doing_ajax() ) {
		return $content;
	}
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return $content;
	}

	$img_base      = function_exists( 'htoeau_child_get_brand_images_base_url' ) ? htoeau_child_get_brand_images_base_url() : '';
	$support_email = (string) apply_filters( 'htoeau_support_email', 'hello@htoeau.com' );

	$icon_truck  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
	$icon_check  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>';
	$icon_shield = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>';

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

	ob_start();
	?>
	<div class="htoeau-cart-page">

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

		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — WC block output ?>

		<?php /*
		 * Sidebar injection source.
		 * cart-carousel.js moves the children of this div into
		 * .wp-block-woocommerce-cart-totals-block so they appear below
		 * the order summary in the block cart's right column.
		 */ ?>
		<div class="htoeau-cart-sidebar-inject" hidden aria-hidden="true">

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

		</div><!-- .htoeau-cart-sidebar-inject -->

	</div><!-- .htoeau-cart-page -->
	<?php
	return ob_get_clean();
}
