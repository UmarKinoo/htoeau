<?php
/**
 * Rating, title, from-per-can line, short description.
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

$img_base = htoeau_child_get_brand_images_base_url();

$lowest_per_can = null;
if ( $product->is_type( 'variable' ) ) {
	foreach ( $product->get_available_variations() as $v ) {
		$cans = htoeau_child_get_can_count_from_variation_attrs( $v['attributes'] );
		if ( $cans < 1 || empty( $v['display_price'] ) ) {
			continue;
		}
		$per = (float) $v['display_price'] / $cans;
		if ( null === $lowest_per_can || $per < $lowest_per_can ) {
			$lowest_per_can = $per;
		}
	}
}

$review_count = $product->get_review_count();
$rating       = (float) $product->get_average_rating();
if ( $rating <= 0 ) {
	$rating = 4.8; // Design default when no reviews yet.
}
if ( $review_count <= 0 ) {
	$review_count = 72;
}
?>
<div class="htoeau-info-panel">
	<?php get_template_part( 'template-parts/currency', 'switcher' ); ?>
	<div class="htoeau-rating-row">
		<img class="htoeau-rating-row__stars" src="<?php echo esc_url( $img_base . 'stars-rating.svg' ); ?>" alt="" width="73" height="12" loading="lazy" />
		<p class="htoeau-rating-row__text">
			<strong class="htoeau-rating-row__score"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></strong>
			<span class="htoeau-rating-row__paren"> (</span>
			<a class="htoeau-rating-row__link" href="<?php echo esc_url( apply_filters( 'htoeau_reviews_link', get_permalink( $product->get_id() ) . '#reviews' ) ); ?>"><?php echo esc_html( sprintf( /* translators: %d: review count */ __( 'see what %d people said', 'hello-elementor-child' ), $review_count ) ); ?></a>
			<span class="htoeau-rating-row__paren">)</span>
		</p>
	</div>

	<h1 class="htoeau-product-title"><?php echo wp_kses_post( $product->get_name() ); ?></h1>

	<?php if ( null !== $lowest_per_can ) : ?>
		<p class="htoeau-from-per-can">
			<?php esc_html_e( 'From', 'hello-elementor-child' ); ?>
			<strong><?php echo wp_kses_post( htoeau_child_fx_wc_price( $lowest_per_can ) ); ?> <?php esc_html_e( 'per can', 'hello-elementor-child' ); ?></strong>
		</p>
	<?php endif; ?>

	<?php if ( $product->get_short_description() ) : ?>
		<div class="htoeau-short-desc">
			<?php echo apply_filters( 'woocommerce_short_description', $product->get_short_description() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	<?php endif; ?>
</div>
