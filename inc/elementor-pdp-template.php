<?php
/**
 * Output a saved Elementor template (library: multiple widgets in one design) on the PDP.
 *
 * Placed after the main `.htoeau-pdp` block (see `woocommerce/content-single-product.php`).
 * Configure under Appearance → Customize
 * → HtoEAU Product page.
 *
 * @package Hello_Elementor_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme mod key: Elementor library post ID (0 = off).
 */
function htoeau_child_pdp_below_template_mod_key(): string {
	return 'htoeau_pdp_below_elementor_template_id';
}

/**
 * @return array<int,string> ID => label
 */
function htoeau_child_get_elementor_library_choices(): array {
	$choices = array( 0 => __( '— None —', 'hello-elementor-child' ) );

	if ( ! post_type_exists( 'elementor_library' ) ) {
		return $choices;
	}

	$posts = get_posts(
		array(
			'post_type'      => 'elementor_library',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	foreach ( $posts as $post ) {
		if ( ! $post instanceof \WP_Post ) {
			continue;
		}
		$choices[ (int) $post->ID ] = sprintf(
			/* translators: 1: template title, 2: post ID */
			__( '%1$s (ID %2$d)', 'hello-elementor-child' ),
			$post->post_title !== '' ? $post->post_title : __( '(no title)', 'hello-elementor-child' ),
			(int) $post->ID
		);
	}

	return $choices;
}

/**
 * @param int $template_id elementor_library post ID.
 * @return string Markup or empty.
 */
function htoeau_child_render_elementor_template_html( int $template_id ): string {
	if ( $template_id < 1 ) {
		return '';
	}

	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return '';
	}

	$post = get_post( $template_id );
	if ( ! $post || 'elementor_library' !== $post->post_type || 'publish' !== $post->post_status ) {
		return '';
	}

	$html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, true );

	return is_string( $html ) ? $html : '';
}

/**
 * Customizer: pick Elementor template slot on the product page.
 *
 * @param \WP_Customize_Manager $wp_customize Manager.
 */
function htoeau_child_register_pdp_elementor_customizer( $wp_customize ): void {
	$wp_customize->add_section(
		'htoeau_pdp_elementor',
		array(
			'title'       => __( 'HtoEAU Product page', 'hello-elementor-child' ),
			'description' => __( 'Choose a template saved in Templates → Saved Templates (Elementor). It can contain any number of Elementor widgets in one layout.', 'hello-elementor-child' ),
			'priority'    => 160,
		)
	);

	$wp_customize->add_setting(
		htoeau_child_pdp_below_template_mod_key(),
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'htoeau_pdp_below_elementor_template_id',
		array(
			'label'       => __( 'PDP — Elementor template (below main product)', 'hello-elementor-child' ),
			'description' => __( 'Renders after the gallery + purchase block. Use for sections previously built in the theme (e.g. sample kit promo, transformation). Requires Elementor.', 'hello-elementor-child' ),
			'section'     => 'htoeau_pdp_elementor',
			'settings'    => htoeau_child_pdp_below_template_mod_key(),
			'type'        => 'select',
			'choices'     => htoeau_child_get_elementor_library_choices(),
		)
	);
}
add_action( 'customize_register', 'htoeau_child_register_pdp_elementor_customizer' );

/**
 * Front: output selected template (hooked from `content-single-product.php`).
 */
function htoeau_child_output_pdp_elementor_template(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$id = (int) get_theme_mod( htoeau_child_pdp_below_template_mod_key(), 0 );
	if ( $id < 1 ) {
		return;
	}

	$html = htoeau_child_render_elementor_template_html( $id );
	if ( '' === trim( $html ) ) {
		return;
	}

	echo '<div class="htoeau-pdp-elementor-slot htoeau-pdp-below-elementor">';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor HTML/CSS for a designed template.
	echo $html;
	echo '</div>';
}

add_action( 'htoeau_pdp_after_main_columns', 'htoeau_child_output_pdp_elementor_template', 10 );

/**
 * Shortcode: render any Elementor library template by ID (for use in widgets or content).
 *
 * Usage: [htoeau_elementor_template id="123"]
 *
 * @param array<string,string> $atts Shortcode attributes.
 * @return string
 */
function htoeau_child_shortcode_elementor_template( $atts ): string {
	$atts = shortcode_atts(
		array( 'id' => '0' ),
		$atts,
		'htoeau_elementor_template'
	);
	$id   = absint( $atts['id'] );
	$html = htoeau_child_render_elementor_template_html( $id );

	return $html !== '' ? '<div class="htoeau-elementor-template-shortcode">' . $html . '</div>' : '';
}
add_shortcode( 'htoeau_elementor_template', 'htoeau_child_shortcode_elementor_template' );
