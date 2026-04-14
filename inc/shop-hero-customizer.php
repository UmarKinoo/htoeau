<?php
/**
 * Shop archive hero — editable via Appearance → Customize → HtoEAU Shop.
 *
 * @package Hello_Elementor_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default hero headline (Customizer default + fallback).
 *
 * @return string
 */
function htoeau_child_shop_hero_default_title(): string {
	return __( 'Shop Hydrogen Enriched Performance Water', 'hello-elementor-child' );
}

/**
 * Default hero body copy.
 *
 * @return string
 */
function htoeau_child_shop_hero_default_description(): string {
	return __( 'Explore our full range of clinically tested hydrogen and deuterium-depleted water designed to support performance, recovery, and cellular health.', 'hello-elementor-child' );
}

/**
 * Hero title from Customizer.
 *
 * @return string
 */
function htoeau_child_shop_hero_title(): string {
	$v = (string) get_theme_mod( 'htoeau_shop_hero_title', htoeau_child_shop_hero_default_title() );
	if ( '' === trim( $v ) ) {
		return htoeau_child_shop_hero_default_title();
	}
	return $v;
}

/**
 * Hero description from Customizer.
 *
 * @return string
 */
function htoeau_child_shop_hero_description(): string {
	$v = (string) get_theme_mod( 'htoeau_shop_hero_description', htoeau_child_shop_hero_default_description() );
	if ( '' === trim( $v ) ) {
		return htoeau_child_shop_hero_default_description();
	}
	return $v;
}

/**
 * Theme mod key: attachment ID for grouped hero artwork (0 = use bundled PNG).
 *
 * @return string
 */
function htoeau_child_shop_hero_image_mod_key(): string {
	return 'htoeau_shop_hero_image';
}

/**
 * Attachment ID for hero image, or 0 to use bundled fallback.
 *
 * @return int
 */
function htoeau_child_shop_hero_image_id(): int {
	return (int) get_theme_mod( htoeau_child_shop_hero_image_mod_key(), 0 );
}

/**
 * Bundled hero image URL when no Customizer image is set.
 * Prefers hero-composite.png (grouped artwork); falls back to hero-box.png if missing.
 *
 * @return string
 */
function htoeau_child_shop_hero_image_url(): string {
	$dir = trailingslashit( get_stylesheet_directory() );
	$uri = trailingslashit( get_stylesheet_directory_uri() );
	foreach ( array( 'hero-composite.png', 'hero-box.png' ) as $file ) {
		if ( file_exists( $dir . 'assets/images/shop/' . $file ) ) {
			return $uri . 'assets/images/shop/' . $file;
		}
	}
	return $uri . 'assets/images/shop/hero-composite.png';
}

/**
 * Optional strip above the shop hero (white bar). Empty = hidden.
 *
 * @return string
 */
function htoeau_child_shop_hero_announcement(): string {
	$v = (string) get_theme_mod( 'htoeau_shop_hero_announcement', '' );
	return trim( $v );
}

/**
 * Register Customizer settings (section `htoeau_shop_elementor` from elementor-shop-template.php).
 *
 * @param \WP_Customize_Manager $wp_customize Manager.
 */
function htoeau_child_register_shop_hero_customizer( $wp_customize ): void {
	$wp_customize->add_setting(
		'htoeau_shop_hero_announcement',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'htoeau_shop_hero_announcement',
		array(
			'label'       => __( 'Shop — announcement bar (above hero)', 'hello-elementor-child' ),
			'description' => __( 'Optional single line (e.g. bullets with •). Leave empty to hide the white bar.', 'hello-elementor-child' ),
			'section'     => 'htoeau_shop_elementor',
			'type'        => 'textarea',
			'priority'    => 7,
		)
	);

	$wp_customize->add_setting(
		'htoeau_shop_hero_title',
		array(
			'default'           => htoeau_child_shop_hero_default_title(),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'htoeau_shop_hero_title',
		array(
			'label'    => __( 'Shop hero — headline', 'hello-elementor-child' ),
			'section'  => 'htoeau_shop_elementor',
			'type'     => 'text',
			'priority' => 8,
		)
	);

	$wp_customize->add_setting(
		'htoeau_shop_hero_description',
		array(
			'default'           => htoeau_child_shop_hero_default_description(),
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'htoeau_shop_hero_description',
		array(
			'label'    => __( 'Shop hero — description', 'hello-elementor-child' ),
			'section'  => 'htoeau_shop_elementor',
			'type'     => 'textarea',
			'priority' => 9,
		)
	);

	$wp_customize->add_setting(
		htoeau_child_shop_hero_image_mod_key(),
		array(
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		new WP_Customize_Media_Control(
			$wp_customize,
			htoeau_child_shop_hero_image_mod_key(),
			array(
				'label'       => __( 'Shop hero — image', 'hello-elementor-child' ),
				'description' => __( 'Single grouped artwork (splash + product + frame in one image). Leave empty to use the theme file assets/images/shop/hero-composite.png.', 'hello-elementor-child' ),
				'section'     => 'htoeau_shop_elementor',
				'mime_type'   => 'image',
				'priority'    => 12,
			)
		)
	);
}
add_action( 'customize_register', 'htoeau_child_register_shop_hero_customizer', 11 );
