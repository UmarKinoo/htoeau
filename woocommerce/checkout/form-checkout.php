<?php
/**
 * Custom checkout form layout for HtoEAU.
 *
 * Two-column grid: customer details (left) + sticky order summary
 * with payment + place-order CTA (right). All WC hooks are preserved.
 *
 * @package Hello_Elementor_Child
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

/* Inline SVG icons for trust bar — match cart page. */
$icon_lock   = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>';
$icon_truck  = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
$icon_shield = '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>';
?>

<section class="htoeau-checkout-page">

	<header class="htoeau-checkout-page__head">
		<h1 class="htoeau-checkout-page__title"><?php esc_html_e( 'Checkout', 'hello-elementor-child' ); ?></h1>
		<div class="htoeau-checkout-page__trust" role="list">
			<span class="htoeau-checkout-trust-item" role="listitem">
				<?php echo $icon_lock; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Secure encrypted payment', 'hello-elementor-child' ); ?>
			</span>
			<span class="htoeau-checkout-trust-item" role="listitem">
				<?php echo $icon_truck; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Free return within 14 days', 'hello-elementor-child' ); ?>
			</span>
			<span class="htoeau-checkout-trust-item" role="listitem">
				<?php echo $icon_shield; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php esc_html_e( 'Tested by sports &amp; scientists', 'hello-elementor-child' ); ?>
			</span>
		</div>
	</header>

	<form name="checkout" method="post" class="checkout woocommerce-checkout htoeau-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

		<div class="htoeau-checkout-form__grid">

			<!-- ── Main column: customer details ─────────────── -->
			<div class="htoeau-checkout-form__main">
				<?php if ( $checkout->get_checkout_fields() ) : ?>

					<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

					<div id="customer_details">
						<div class="htoeau-checkout-section htoeau-checkout-section--billing">
							<?php do_action( 'woocommerce_checkout_billing' ); ?>
						</div>

						<div class="htoeau-checkout-section htoeau-checkout-section--shipping">
							<?php do_action( 'woocommerce_checkout_shipping' ); ?>
						</div>
					</div>

					<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

				<?php endif; ?>
			</div>

			<!-- ── Sidebar: order review + payment + CTA ─────── -->
			<aside class="htoeau-checkout-form__side">
				<div class="htoeau-checkout-review">
					<h3 id="order_review_heading" class="htoeau-checkout-review__title">
						<?php esc_html_e( 'Order summary', 'hello-elementor-child' ); ?>
					</h3>

					<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php do_action( 'woocommerce_checkout_order_review' ); ?>
					</div>

					<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				</div>
			</aside>

		</div>

	</form>

	<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

</section>
