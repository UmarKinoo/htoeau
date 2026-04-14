<?php
/**
 * Subscribe & Save vs one-time purchase panels.
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! function_exists( 'htoeau_child_product_is_variable_pdp' ) || ! htoeau_child_product_is_variable_pdp( $product ) ) {
	return;
}

if ( function_exists( 'htoeau_child_product_is_wcs_subscription' ) && htoeau_child_product_is_wcs_subscription( $product ) ) {
	return;
}

$img_base = htoeau_child_get_brand_images_base_url();

$bullets    = array();
$raw_bullets = function_exists( 'get_field' ) ? (string) get_field( 'subscribe_bullets_text', $product->get_id() ) : '';
if ( $raw_bullets ) {
	foreach ( explode( "\n", $raw_bullets ) as $line ) {
		$line = trim( $line );
		if ( '' !== $line ) {
			$bullets[] = $line;
		}
	}
}
if ( empty( $bullets ) ) {
	$bullets = array(
		__( 'Save 10% on every order', 'htoeau-child' ),
		__( 'Pause or cancel anytime', 'htoeau-child' ),
		__( 'Never run out of your routine', 'htoeau-child' ),
	);
}

$discount_pct = (float) apply_filters( 'htoeau_subscribe_discount_percent', 10 );
?>
<div class="htoeau-subscribe" data-htoeau-subscribe>
	<div class="htoeau-subscribe__option is-active" data-subscribe-panel="subscribe">
		<label class="htoeau-subscribe__label">
			<input type="radio" name="htoeau_purchase_type" value="subscribe" class="htoeau-subscribe__radio" checked="checked" />
			<span class="htoeau-subscribe__radio-visual" aria-hidden="true"></span>
			<span class="htoeau-subscribe__row">
				<span class="htoeau-subscribe__title"><?php esc_html_e( 'Subscribe & Save', 'htoeau-child' ); ?></span>
				<span class="htoeau-subscribe__price-wrap" data-subscribe-price-wrap>
					<span class="htoeau-subscribe__badge-save"><?php echo esc_html( sprintf( /* translators: %d: percent */ __( 'Save %d%%', 'htoeau-child' ), (int) $discount_pct ) ); ?></span>
					<span class="htoeau-subscribe__prices">
						<span class="htoeau-subscribe__was" data-subscribe-strike></span>
						<span class="htoeau-subscribe__now" data-subscribe-amount></span>
					</span>
				</span>
			</span>
		</label>
		<div class="htoeau-subscribe__dropdown-wrap">
			<label class="screen-reader-text" for="htoeau-delivery-interval"><?php esc_html_e( 'Delivery frequency', 'htoeau-child' ); ?></label>
			<div class="htoeau-subscribe__dropdown">
				<select id="htoeau-delivery-interval" name="htoeau_delivery_interval" data-htoeau-delivery form="htoeau-variations-form">
					<?php
					$intervals = htoeau_child_get_delivery_interval_options();
					foreach ( $intervals as $val => $lab ) {
						printf(
							'<option value="%1$s"%3$s>%2$s</option>',
							esc_attr( $val ),
							esc_html( $lab ),
							selected( $val, '8w', false )
						);
					}
					?>
				</select>
				<span class="htoeau-subscribe__chev" aria-hidden="true"><img src="<?php echo esc_url( $img_base . 'chevron-dropdown.svg' ); ?>" alt="" width="9" height="16" /></span>
			</div>
		</div>
		<ul class="htoeau-subscribe__bullets">
			<?php foreach ( $bullets as $b ) : ?>
				<li>
					<img src="<?php echo esc_url( $img_base . 'check-bullet.svg' ); ?>" alt="" width="12" height="10" loading="lazy" />
					<span><?php echo esc_html( $b ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="htoeau-subscribe__option htoeau-subscribe__option--simple" data-subscribe-panel="once">
		<label class="htoeau-subscribe__label htoeau-subscribe__label--simple">
			<input type="radio" name="htoeau_purchase_type" value="once" class="htoeau-subscribe__radio" />
			<span class="htoeau-subscribe__radio-visual" aria-hidden="true"></span>
			<span class="htoeau-subscribe__row">
				<span class="htoeau-subscribe__title"><?php esc_html_e( 'One-Time Purchase', 'htoeau-child' ); ?></span>
				<span class="htoeau-subscribe__once-price" data-onetime-amount></span>
			</span>
		</label>
	</div>
</div>
