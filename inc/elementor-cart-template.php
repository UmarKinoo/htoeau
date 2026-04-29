<?php
/**
 * Output a saved Elementor template below the WooCommerce cart.
 *
 * Configure under Appearance -> Customize -> HtoEAU Cart page.
 *
 * @package Hello_Elementor_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme mod key: Elementor library post ID (0 = off).
 */
function htoeau_child_cart_below_template_mod_key(): string {
	return 'htoeau_cart_below_elementor_template_id';
}

/**
 * Customizer: pick Elementor template slot on the cart page.
 *
 * @param \WP_Customize_Manager $wp_customize Manager.
 */
function htoeau_child_register_cart_elementor_customizer( $wp_customize ): void {
	$wp_customize->add_section(
		'htoeau_cart_elementor',
		array(
			'title'       => __( 'HtoEAU Cart page', 'hello-elementor-child' ),
			'description' => __( 'Choose a template saved in Templates -> Saved Templates (Elementor). It can contain any number of Elementor widgets in one layout.', 'hello-elementor-child' ),
			'priority'    => 161,
		)
	);

	$wp_customize->add_setting(
		htoeau_child_cart_below_template_mod_key(),
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'htoeau_cart_below_elementor_template_id',
		array(
			'label'       => __( 'Cart - Elementor template (below cart)', 'hello-elementor-child' ),
			'description' => __( 'Renders after the main cart content. Use this to stack any Elementor widgets/sections below the cart.', 'hello-elementor-child' ),
			'section'     => 'htoeau_cart_elementor',
			'settings'    => htoeau_child_cart_below_template_mod_key(),
			'type'        => 'select',
			'choices'     => function_exists( 'htoeau_child_get_elementor_library_choices' ) ? htoeau_child_get_elementor_library_choices() : array( 0 => __( '- None -', 'hello-elementor-child' ) ),
		)
	);
}
add_action( 'customize_register', 'htoeau_child_register_cart_elementor_customizer' );

/**
 * Front: output selected template below cart.
 */
function htoeau_child_output_cart_elementor_template(): void {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	$id = (int) get_theme_mod( htoeau_child_cart_below_template_mod_key(), 0 );
	if ( $id < 1 || ! function_exists( 'htoeau_child_render_elementor_template_html' ) ) {
		return;
	}

	$html = htoeau_child_render_elementor_template_html( $id );
	if ( '' === trim( $html ) ) {
		return;
	}

	echo '<div class="htoeau-cart-elementor-slot htoeau-cart-below-elementor">';
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor HTML/CSS for a designed template.
	echo $html;
	echo '</div>';
}
add_action( 'woocommerce_after_cart', 'htoeau_child_output_cart_elementor_template', 20 );
