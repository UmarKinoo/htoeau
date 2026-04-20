<?php
/**
 * Product gallery — main image, thumbnails, arrows.
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

$thumb_id   = $product->get_image_id();
$gallery_ids = $product->get_gallery_image_ids();
$all_ids    = array_values( array_filter( array_merge( array( $thumb_id ), $gallery_ids ) ) );

if ( empty( $all_ids ) ) {
	$all_ids = array( 0 );
}

/*
 * Figma node 1:1840 uses a 5-thumb strip under the main 608×608 media frame.
 * Keep parity by capping gallery items to 5 in this custom PDP gallery.
 */
if ( count( $all_ids ) > 5 ) {
	$all_ids = array_slice( $all_ids, 0, 5 );
}

?>
<div class="htoeau-gallery" data-htoeau-gallery>
	<div class="htoeau-gallery__main-wrap">
		<button type="button" class="htoeau-gallery__arrow htoeau-gallery__arrow--prev" data-gallery-prev aria-label="<?php esc_attr_e( 'Previous image', 'htoeau-child' ); ?>">
			<svg class="htoeau-pdp-chevron" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M9 6L15 12L9 18" fill="none" stroke="#016b9f" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</button>
		<div class="htoeau-gallery__main">
			<?php foreach ( $all_ids as $index => $aid ) : ?>
				<?php
				if ( $aid ) {
					$full = wp_get_attachment_image_url( $aid, 'woocommerce_single' );
					$alt  = get_post_meta( $aid, '_wp_attachment_image_alt', true );
				} else {
					$full = wc_placeholder_img_src( 'woocommerce_single' );
					$alt  = '';
				}
				?>
				<div class="htoeau-gallery__slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-gallery-slide="<?php echo esc_attr( (string) $index ); ?>">
					<img src="<?php echo esc_url( $full ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="608" height="608" loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>" />
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" class="htoeau-gallery__arrow htoeau-gallery__arrow--next" data-gallery-next aria-label="<?php esc_attr_e( 'Next image', 'htoeau-child' ); ?>">
			<svg class="htoeau-pdp-chevron" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M9 6L15 12L9 18" fill="none" stroke="#016b9f" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</button>
	</div>
	<?php if ( count( $all_ids ) > 1 ) : ?>
		<div class="htoeau-gallery__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Product images', 'htoeau-child' ); ?>">
			<?php foreach ( $all_ids as $index => $aid ) : ?>
				<?php
				if ( $aid ) {
					$thumb = wp_get_attachment_image_url( $aid, 'woocommerce_single' );
					$talt  = get_post_meta( $aid, '_wp_attachment_image_alt', true );
				} else {
					$thumb = wc_placeholder_img_src( 'woocommerce_single' );
					$talt  = '';
				}
				?>
				<button type="button" class="htoeau-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-gallery-thumb="<?php echo esc_attr( (string) $index ); ?>" role="tab" aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $talt ); ?>" width="115" height="115" loading="lazy" />
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
