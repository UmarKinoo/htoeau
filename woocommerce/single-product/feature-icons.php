<?php
/**
 * Feature blurbs (ACF Free flat fields: feature_blurb_N_title / _desc, max 3).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

$pid      = $product->get_id();
$img_base = htoeau_child_get_brand_images_base_url();
$defaults = array(
	array(
		'title' => __( 'Cellular Hydration: ', 'hello-elementor-child' ),
		'desc'  => __( "Supports the body\xE2\x80\x99s natural hydration processes and fluid balance.", 'hello-elementor-child' ),
	),
	array(
		'title' => __( 'Precision Hydrogen Infusion: ', 'hello-elementor-child' ),
		'desc'  => __( 'Infused to a minimum level of 5 mg/L dissolved hydrogen at the point of filling.', 'hello-elementor-child' ),
	),
	array(
		'title' => __( 'Cognitive Clarity: ', 'hello-elementor-child' ),
		'desc'  => __( 'Proper hydration supports normal focus, alertness, and mental performance.', 'hello-elementor-child' ),
	),
);

$items = array();
for ( $n = 1; $n <= 3; $n++ ) {
	$title = function_exists( 'get_field' ) ? (string) get_field( 'feature_blurb_' . $n . '_title', $pid ) : '';
	$desc  = function_exists( 'get_field' ) ? (string) get_field( 'feature_blurb_' . $n . '_desc', $pid ) : '';
	if ( $title || $desc ) {
		$items[] = array(
			'icon'  => $img_base . 'feature-check.svg',
			'title' => $title,
			'desc'  => $desc,
		);
	}
}

if ( empty( $items ) ) {
	foreach ( $defaults as $d ) {
		$items[] = array(
			'icon'  => $img_base . 'feature-check.svg',
			'title' => $d['title'],
			'desc'  => $d['desc'],
		);
	}
}
?>
<div class="htoeau-features">
	<?php foreach ( $items as $item ) : ?>
		<div class="htoeau-features__col">
			<?php if ( ! empty( $item['icon'] ) ) : ?>
				<img class="htoeau-features__icon" src="<?php echo esc_url( $item['icon'] ); ?>" alt="" width="30" height="30" loading="lazy" />
			<?php endif; ?>
			<p class="htoeau-features__text">
				<?php if ( $item['title'] ) : ?>
					<strong class="htoeau-features__title"><?php echo esc_html( $item['title'] ); ?></strong>
				<?php endif; ?>
				<?php echo esc_html( $item['desc'] ); ?>
			</p>
		</div>
	<?php endforeach; ?>
</div>
