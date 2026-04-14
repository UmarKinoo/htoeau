<?php
/**
 * Elementor library template slot below the shop product grid.
 *
 * Configure under Appearance → Customize → HtoEAU Shop.
 *
 * @package Hello_Elementor_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme mod key: Elementor library post ID (0 = off).
 */
function htoeau_child_shop_below_template_mod_key(): string {
	return 'htoeau_shop_below_elementor_template_id';
}

/**
 * Customizer: pick Elementor template below the shop grid.
 *
 * @param \WP_Customize_Manager $wp_customize Manager.
 */
function htoeau_child_register_shop_elementor_customizer( $wp_customize ): void {
	$wp_customize->add_section(
		'htoeau_shop_elementor',
		array(
			'title'       => __( 'HtoEAU Shop', 'hello-elementor-child' ),
			'description' => __( 'Edit the shop hero headline, description, and images. Optionally add an Elementor template below the product grid on shop and category pages.', 'hello-elementor-child' ),
			'priority'    => 161,
		)
	);

	$wp_customize->add_setting(
		htoeau_child_shop_below_template_mod_key(),
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'htoeau_shop_below_elementor_template_id',
		array(
			'label'       => __( 'Shop — Elementor template (below product grid)', 'hello-elementor-child' ),
			'description' => __( 'Choose a template from Templates → Saved Templates. Renders after pagination. Requires Elementor.', 'hello-elementor-child' ),
			'section'     => 'htoeau_shop_elementor',
			'settings'    => htoeau_child_shop_below_template_mod_key(),
			'type'        => 'select',
			'choices'     => function_exists( 'htoeau_child_get_elementor_library_choices' )
				? htoeau_child_get_elementor_library_choices()
				: array( 0 => __( '— None —', 'hello-elementor-child' ) ),
			'priority'    => 100,
		)
	);
}
add_action( 'customize_register', 'htoeau_child_register_shop_elementor_customizer' );

/**
 * Fire the shop “after loop” hook only after WooCommerce closes </main> and #primary.
 * Priority 20 runs after woocommerce_output_content_wrapper_end (10). Do not call
 * do_action( 'htoeau_shop_after_main_loop' ) from archive-product.php — that places
 * output inside .site-main while the shell is still open.
 */
function htoeau_child_fire_shop_after_main_loop_hook(): void {
	if ( ! function_exists( 'is_shop' ) ) {
		return;
	}
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}
	do_action( 'htoeau_shop_after_main_loop' );
}
add_action( 'woocommerce_after_main_content', 'htoeau_child_fire_shop_after_main_loop_hook', 20 );

/**
 * Front: output selected template after the main shop loop.
 */
function htoeau_child_output_shop_elementor_template(): void {
	if ( ! function_exists( 'is_shop' ) ) {
		return;
	}
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}

	$id = (int) get_theme_mod( htoeau_child_shop_below_template_mod_key(), 0 );
	if ( $id < 1 ) {
		return;
	}

	$html = htoeau_child_render_elementor_template_html( $id );
	if ( '' === trim( $html ) ) {
		return;
	}

	echo '<div class="htoeau-shop-elementor-slot-wrap">';
	echo '<div class="htoeau-shop-elementor-slot">';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor HTML for a saved template.
	echo $html;
	echo '</div></div>';
}

add_action( 'htoeau_shop_after_main_loop', 'htoeau_child_output_shop_elementor_template', 10 );
