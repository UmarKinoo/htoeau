<?php
/**
 * Sample kit promo — Figma node 86:500 (Hydrate Smarter).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

$img_base = htoeau_child_get_brand_images_base_url();

$cta_url = apply_filters( 'htoeau_sample_kit_hero_cta_url', '#htoeau-pdp-purchase' );

$features = apply_filters(
	'htoeau_sample_kit_hero_features',
	array(
		__( 'Three cans in every Try-Out Box', 'hello-elementor-child' ),
		__( 'Four sample kits to explore', 'hello-elementor-child' ),
		__( 'Find the right fit for your routine', 'hello-elementor-child' ),
	)
);
?>
<section class="htoeau-sample-hero" aria-labelledby="htoeau-sample-hero-heading">
	<div class="htoeau-sample-hero__inner">
		<div class="htoeau-sample-hero__content">
			<h2 id="htoeau-sample-hero-heading" class="htoeau-sample-hero__title">
				<?php esc_html_e( 'Hydrate Smarter', 'hello-elementor-child' ); ?>
			</h2>
			<div class="htoeau-sample-hero__body">
				<p>
					<?php esc_html_e( 'Discover the HtoEAU® Try-Out Box and explore our advanced hydration systems. Choose from four sample kit options, including Hydrogen Water, Deuterium-Depleted Water, and Hydrogen-Infused Deuterium-Depleted Water.', 'hello-elementor-child' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Each box includes three cans — a simple way to experience HtoEAU® and find the right fit for your routine.', 'hello-elementor-child' ); ?>
				</p>
			</div>
			<a class="htoeau-sample-hero__cta" href="<?php echo esc_url( $cta_url ); ?>">
				<?php esc_html_e( 'Get your sample kit now', 'hello-elementor-child' ); ?>
			</a>
			<?php if ( ! empty( $features ) && is_array( $features ) ) : ?>
				<ul class="htoeau-sample-hero__features">
					<?php foreach ( $features as $label ) : ?>
						<?php if ( ! is_string( $label ) || '' === trim( $label ) ) { continue; } ?>
						<li class="htoeau-sample-hero__feature">
							<img src="<?php echo esc_url( $img_base . 'sample-kit-hero-check.svg' ); ?>" alt="" class="htoeau-sample-hero__feature-icon" width="30" height="30" />
							<span class="htoeau-sample-hero__feature-text"><?php echo esc_html( $label ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<div class="htoeau-sample-hero__visual">
			<img
				class="htoeau-sample-hero__frame"
				src="<?php echo esc_url( $img_base . 'sample-kit-hero-frame.png' ); ?>"
				alt=""
				width="502"
				height="502"
				loading="lazy"
				decoding="async"
			/>
			<img
				class="htoeau-sample-hero__product"
				src="<?php echo esc_url( $img_base . 'sample-kit-hero-product.png' ); ?>"
				alt="<?php echo esc_attr__( 'Open Try-Out Box with three HtoEAU cans and water splash', 'hello-elementor-child' ); ?>"
				width="485"
				height="493"
				loading="lazy"
				decoding="async"
			/>
		</div>
	</div>
</section>
