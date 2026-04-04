<?php
/**
 * Transformation B — three-step horizontal panels (desktop).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

global $product;

$pid      = $product && is_a( $product, 'WC_Product' ) ? $product->get_id() : 0;
$img_base = htoeau_child_get_brand_images_base_url();

$steps = array();
if ( $pid && function_exists( 'get_field' ) ) {
	for ( $n = 1; $n <= 3; $n++ ) {
		$title = (string) get_field( 'transform_step_' . $n . '_title', $pid );
		$desc  = (string) get_field( 'transform_step_' . $n . '_desc', $pid );
		$img   = get_field( 'transform_step_' . $n . '_image', $pid );
		$url   = is_array( $img ) && ! empty( $img['url'] ) ? $img['url'] : '';
		if ( $title || $url ) {
			$steps[] = array(
				'num'   => (string) $n,
				'title' => $title,
				'desc'  => $desc,
				'image' => $url,
			);
		}
	}
}

if ( empty( $steps ) ) {
	$steps = array(
		array(
			'num'   => '1',
			'title' => __( 'Drink HtoEAU', 'hello-elementor-child' ),
			'desc'  => __( 'Begin with clean, precision-engineered hydration developed using advanced purification and infusion technologies.', 'hello-elementor-child' ),
			'image' => $img_base . 'transform-bg-1.png',
		),
		array(
			'num'   => '2',
			'title' => __( 'Rapid Hydration', 'hello-elementor-child' ),
			'desc'  => '',
			'image' => $img_base . 'transform-bg-2.jpg',
		),
		array(
			'num'   => '3',
			'title' => __( 'Stay Ready for Your Day', 'hello-elementor-child' ),
			'desc'  => '',
			'image' => $img_base . 'transform-bg-3.jpg',
		),
	);
}
?>
<section class="htoeau-transform" aria-labelledby="htoeau-transform-heading" data-htoeau-transform>
	<div class="htoeau-transform__inner">
		<header class="htoeau-transform__header">
			<h2 id="htoeau-transform-heading" class="htoeau-transform__title"><?php esc_html_e( 'From Molecular Hydrogen to Next-Level Hydration', 'hello-elementor-child' ); ?></h2>
			<p class="htoeau-transform__subtitle"><?php esc_html_e( 'What happens when advanced infusion technology meets pure hydration?', 'hello-elementor-child' ); ?></p>
		</header>
		<div class="htoeau-transform__panels" data-transform-panels>
			<?php foreach ( $steps as $i => $step ) : ?>
				<?php
				$is_active = ( 0 === $i );
				$panel_cls = 'htoeau-transform__panel' . ( $is_active ? ' is-active' : '' );
				$circle    = $is_active ? 'step-circle-lg.svg' : 'step-circle-sm.svg';
				?>
				<button type="button" class="<?php echo esc_attr( $panel_cls ); ?>" data-transform-panel="<?php echo esc_attr( (string) $i ); ?>" aria-expanded="<?php echo $is_active ? 'true' : 'false'; ?>">
					<span class="htoeau-transform__panel-bg" style="background-image: url('<?php echo esc_url( $step['image'] ); ?>');"></span>
					<span class="htoeau-transform__overlay" aria-hidden="true"></span>
					<span class="htoeau-transform__panel-content">
						<span class="htoeau-transform__num-wrap">
							<img src="<?php echo esc_url( $img_base . $circle ); ?>" alt="" class="htoeau-transform__num-ring" width="<?php echo $is_active ? 72 : 56; ?>" height="<?php echo $is_active ? 72 : 56; ?>" />
							<span class="htoeau-transform__num"><?php echo esc_html( $step['num'] ); ?></span>
						</span>
						<span class="htoeau-transform__panel-title"><?php echo esc_html( $step['title'] ); ?></span>
						<?php if ( ! empty( $step['desc'] ) ) : ?>
							<span class="htoeau-transform__panel-desc"><?php echo esc_html( $step['desc'] ); ?></span>
						<?php endif; ?>
					</span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
