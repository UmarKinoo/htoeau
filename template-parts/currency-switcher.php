<?php
/**
 * GBP / USD display toggle (browse only; checkout uses WooCommerce store currency).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'htoeau_child_fx_is_enabled' ) || ! htoeau_child_fx_is_enabled() ) {
	return;
}

$store   = get_woocommerce_currency();
$current = htoeau_child_fx_get_display_currency();
$codes   = htoeau_child_fx_supported_codes();

$base_url = get_permalink();
if ( ! $base_url ) {
	return;
}
?>
<div class="htoeau-currency-switcher" role="group" aria-label="<?php esc_attr_e( 'Display currency', 'htoeau-child' ); ?>">
	<span class="htoeau-currency-switcher__label"><?php esc_html_e( 'Prices in', 'htoeau-child' ); ?></span>
	<?php foreach ( $codes as $code ) : ?>
		<?php
		$url   = esc_url( add_query_arg( 'htoeau_ccy', $code, $base_url ) );
		$is_on = ( $code === $current );
		?>
		<a
			class="htoeau-currency-switcher__opt<?php echo $is_on ? ' is-active' : ''; ?>"
			href="<?php echo $url; ?>"
			<?php echo $is_on ? 'aria-current="true"' : ''; ?>
		><?php echo esc_html( $code ); ?></a>
	<?php endforeach; ?>
	<?php if ( $current !== $store ) : ?>
		<p class="htoeau-currency-switcher__note">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: WooCommerce store currency code */
					__( 'Checkout is charged in %s.', 'htoeau-child' ),
					$store
				)
			);
			?>
		</p>
	<?php endif; ?>
</div>
