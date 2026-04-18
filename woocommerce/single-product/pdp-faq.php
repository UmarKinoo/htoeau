<?php
/**
 * Product FAQ from ACF (HtoEAU Product → PDP FAQ tab).
 *
 * Markup matches the HtoEAU FAQ Accordion Elementor widget for shared CSS.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$pid = $product->get_id();
if ( ! function_exists( 'htoeau_child_product_has_acf_pdp_faq' ) || ! htoeau_child_product_has_acf_pdp_faq( $pid ) ) {
	return;
}

$data = htoeau_child_get_pdp_faq_data( $pid );
if ( empty( $data['items'] ) ) {
	return;
}

$heading    = $data['heading'];
$subheading = $data['subheading'];
$items      = $data['items'];
$group_name = 'htoeau-faq-pdp';
?>
<div class="htoeau-pdp-faq-wrap">
	<section class="htoeau-faq" aria-label="<?php echo esc_attr( $heading ); ?>">
		<header class="htoeau-faq__head">
			<?php if ( '' !== $heading ) : ?>
				<h2 class="htoeau-faq__title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== $subheading ) : ?>
				<p class="htoeau-faq__sub"><?php echo esc_html( $subheading ); ?></p>
			<?php endif; ?>
		</header>
		<div class="htoeau-faq__list">
			<?php foreach ( $items as $k => $item ) : ?>
				<?php
				$q = isset( $item['q'] ) ? (string) $item['q'] : '';
				$a = isset( $item['a'] ) ? (string) $item['a'] : '';
				if ( '' === trim( $q ) ) {
					continue;
				}
				$open = ( 0 === (int) $k );
				?>
				<details class="htoeau-faq__item"<?php echo $open ? ' open' : ''; ?> name="<?php echo esc_attr( $group_name ); ?>">
					<summary class="htoeau-faq__summary">
						<span class="htoeau-faq__summary-main">
							<span class="htoeau-faq__q"><?php echo esc_html( $q ); ?></span>
							<?php if ( '' !== trim( $a ) ) : ?>
								<div class="htoeau-faq__answer"><?php echo wp_kses_post( wpautop( $a ) ); ?></div>
							<?php endif; ?>
						</span>
						<span class="htoeau-faq__chev" aria-hidden="true">
							<svg class="htoeau-faq__chev-svg" width="14" height="24" viewBox="0 0 14 24" focusable="false">
								<path d="M3.5 9.5 7 14l3.5-4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
							</svg>
						</span>
					</summary>
				</details>
			<?php endforeach; ?>
		</div>
	</section>
</div>
