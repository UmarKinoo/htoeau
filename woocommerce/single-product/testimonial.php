<?php
/**
 * Testimonial block below gallery (filterable defaults match design).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

$defaults = array(
	'quote'       => __( '"I\'ve reached a level of physical fitness where I can ascertain if any supplement I take can give me an extra edge. HtoEau is something I drink before every workout now, and no matter how tired I may feel before training I can now push through with seemingly an extra gear and end up having a great session. If you\'re serious about fitness and recovery it\'s a must have pre-workout! I couldn\'t recommend it enough!"', 'htoeau-child' ),
	'name'        => __( 'Daniel Ventura, ', 'htoeau-child' ),
	'credentials' => __( 'WBFF Pro Fitness Model, Actor, Advanced Personal Trainer', 'htoeau-child' ),
);

$data = apply_filters( 'htoeau_testimonial', $defaults );

$img_base = htoeau_child_get_brand_images_base_url();
?>
<aside class="htoeau-testimonial" aria-label="<?php esc_attr_e( 'Customer testimonial', 'htoeau-child' ); ?>">
	<p class="htoeau-testimonial__quote"><?php echo esc_html( $data['quote'] ); ?></p>
	<div class="htoeau-testimonial__meta">
		<img class="htoeau-testimonial__stars" src="<?php echo esc_url( $img_base . 'stars-testimonial.svg' ); ?>" alt="" width="63" height="10" loading="lazy" />
		<p class="htoeau-testimonial__byline">
			<span class="htoeau-testimonial__name"><?php echo esc_html( $data['name'] ); ?></span>
			<span class="htoeau-testimonial__role"><?php echo esc_html( $data['credentials'] ); ?></span>
		</p>
	</div>
</aside>
