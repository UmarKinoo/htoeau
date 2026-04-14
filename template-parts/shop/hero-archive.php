<?php
/**
 * Shop / taxonomy archive hero — Figma node 1:2706.
 *
 * Optiona white announcement bar, then ~45% copy / ~55% image (image bottom-right, flush to screen edge).
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

$hero_img_id   = htoeau_child_shop_hero_image_id();
$announcement  = htoeau_child_shop_hero_announcement();
?>
<div class="htoeau-shop-hero-wrap">
	<?php if ( '' !== $announcement ) : ?>
		<div class="htoeau-shop-hero-announcement" role="region" aria-label="<?php esc_attr_e( 'Promotional message', 'hello-elementor-child' ); ?>">
			<?php echo esc_html( $announcement ); ?>
		</div>
	<?php endif; ?>

	<section class="htoeau-shop-hero" aria-labelledby="htoeau-shop-hero-heading">
		<div class="htoeau-shop-hero__inner">
			<div class="htoeau-shop-hero__text">
				<h1 id="htoeau-shop-hero-heading" class="htoeau-shop-hero__title">
					<?php echo esc_html( htoeau_child_shop_hero_title() ); ?>
				</h1>
				<p class="htoeau-shop-hero__desc">
					<?php echo esc_html( htoeau_child_shop_hero_description() ); ?>
				</p>
			</div>
		</div>

		<div class="htoeau-shop-hero__visual" aria-hidden="true">
			<div class="htoeau-shop-hero__figure">
				<?php if ( $hero_img_id > 0 ) : ?>
					<?php
					echo wp_get_attachment_image(
						$hero_img_id,
						'full',
						false,
						array(
							'class'    => 'htoeau-shop-hero__img',
							'loading'  => 'eager',
							'decoding' => 'async',
						)
					);
					?>
				<?php else : ?>
					<img
						class="htoeau-shop-hero__img"
						src="<?php echo esc_url( htoeau_child_shop_hero_image_url() ); ?>"
						alt=""
						loading="eager"
						decoding="async"
					/>
				<?php endif; ?>
			</div>
		</div>
	</section>
</div>
