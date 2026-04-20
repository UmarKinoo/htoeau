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
					<svg class="htoeau-pdp-chevron" width="14" height="24" viewBox="0 0 14 24" focusable="false">
						<path d="M3.5 9.5 7 14l3.5-4.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
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
