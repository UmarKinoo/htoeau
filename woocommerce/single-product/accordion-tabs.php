<?php
/**
 * Product data as accordions (WC tab callbacks).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

$tabs = apply_filters( 'woocommerce_product_tabs', array() );

if ( empty( $tabs ) ) {
	return;
}

$img_base = htoeau_child_get_brand_images_base_url();
$index    = 0;
?>
<div class="htoeau-accordion" id="htoeau-product-accordion" data-htoeau-accordion>
	<?php foreach ( $tabs as $key => $tab ) : ?>
		<?php
		if ( ! isset( $tab['title'], $tab['callback'] ) || ! is_callable( $tab['callback'] ) ) {
			continue;
		}
		$is_open = ( 0 === $index );
		$panel_id = 'htoeau-acc-panel-' . sanitize_title( $key );
		$btn_id   = 'htoeau-acc-btn-' . sanitize_title( $key );
		?>
		<div class="htoeau-accordion__item<?php echo $is_open ? ' is-open' : ''; ?>" data-htoeau-acc-item>
			<button type="button" class="htoeau-accordion__trigger" id="<?php echo esc_attr( $btn_id ); ?>" data-htoeau-acc-trigger aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
				<span class="htoeau-accordion__title"><?php echo esc_html( $tab['title'] ); ?></span>
				<span class="htoeau-accordion__icon" aria-hidden="true">
					<img class="htoeau-accordion__icon-open" src="<?php echo esc_url( $img_base . 'accordion-open.svg' ); ?>" alt="" width="14" height="24" />
					<img class="htoeau-accordion__icon-closed" src="<?php echo esc_url( $img_base . 'accordion-closed.svg' ); ?>" alt="" width="14" height="24" />
				</span>
			</button>
			<div class="htoeau-accordion__panel" id="<?php echo esc_attr( $panel_id ); ?>" role="region" aria-labelledby="<?php echo esc_attr( $btn_id ); ?>" <?php echo $is_open ? '' : 'hidden'; ?>>
				<div class="htoeau-accordion__panel-inner woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr( $key ); ?>">
					<?php
					call_user_func( $tab['callback'], $key, $tab );
					?>
				</div>
			</div>
		</div>
		<?php
		++$index;
	endforeach;
	?>
</div>
