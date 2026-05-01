<?php
/**
 * Hello Elementor child — HtoEAU WooCommerce PDP + ACF fields.
 *
 * @package Hello_Elementor_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HTOEAU_CHILD_VERSION', '1.6.0' );
define( 'HTOEAU_CHILD_DIR', get_stylesheet_directory() );
define( 'HTOEAU_CHILD_URI', get_stylesheet_directory_uri() );

require_once HTOEAU_CHILD_DIR . '/inc/currency-fx.php';
require_once HTOEAU_CHILD_DIR . '/inc/wc-subscriptions-bridge.php';
require_once HTOEAU_CHILD_DIR . '/inc/elementor-pdp-template.php';
require_once HTOEAU_CHILD_DIR . '/inc/elementor-cart-template.php';
require_once HTOEAU_CHILD_DIR . '/inc/shop-helpers.php';
require_once HTOEAU_CHILD_DIR . '/inc/elementor-shop-template.php';
require_once HTOEAU_CHILD_DIR . '/inc/shop-hero-customizer.php';
require_once HTOEAU_CHILD_DIR . '/inc/pdp-faq.php';
require_once HTOEAU_CHILD_DIR . '/inc/force-classic-wc-cart.php';
require_once HTOEAU_CHILD_DIR . '/inc/force-cart-template-include.php';
require_once HTOEAU_CHILD_DIR . '/inc/cart-block-wrapper.php';
require_once HTOEAU_CHILD_DIR . '/inc/force-classic-wc-checkout.php';
require_once HTOEAU_CHILD_DIR . '/inc/force-checkout-template-include.php';
require_once HTOEAU_CHILD_DIR . '/inc/mollie-components-checkout-styles.php';

/**
 * Bump this string to re-copy `/assets/images/*` into `wp-content/uploads/htoeau-brand-assets/`.
 * Serves icons from uploads (same origin as media) so they load reliably with Local/nginx.
 */
define( 'HTOEAU_BRAND_ASSETS_SYNC_VER', '1.1.2' );

/**
 * Copy theme brand images into uploads/htoeau-brand-assets/.
 */
function htoeau_child_sync_brand_assets_to_uploads() {
	if ( get_option( 'htoeau_brand_assets_sync_ver', '' ) === HTOEAU_BRAND_ASSETS_SYNC_VER ) {
		return;
	}

	$src_dir = HTOEAU_CHILD_DIR . '/assets/images';
	if ( ! is_dir( $src_dir ) ) {
		return;
	}

	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		return;
	}

	$dest_dir = trailingslashit( $upload['basedir'] ) . 'htoeau-brand-assets';
	wp_mkdir_p( $dest_dir );

	$patterns = array( '*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg' );
	foreach ( $patterns as $pattern ) {
		foreach ( (array) glob( $src_dir . '/' . $pattern ) as $file ) {
			if ( ! is_string( $file ) || ! is_readable( $file ) ) {
				continue;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- one-time sync from theme to uploads.
			copy( $file, $dest_dir . '/' . basename( $file ) );
		}
	}

	update_option( 'htoeau_brand_assets_sync_ver', HTOEAU_BRAND_ASSETS_SYNC_VER, false );
}
add_action( 'after_setup_theme', 'htoeau_child_sync_brand_assets_to_uploads', 1 );

/**
 * Base URL for PDP / brand images: uploads copy first, theme fallback.
 *
 * @return string Trailing slash.
 */
function htoeau_child_get_brand_images_base_url() {
	htoeau_child_sync_brand_assets_to_uploads();

	$upload = wp_upload_dir();
	$marker = trailingslashit( $upload['basedir'] ) . 'htoeau-brand-assets/stars-testimonial.svg';

	if ( file_exists( $marker ) ) {
		return trailingslashit( $upload['baseurl'] ) . 'htoeau-brand-assets/';
	}

	return trailingslashit( HTOEAU_CHILD_URI ) . 'assets/images/';
}

/**
 * Enqueue parent + child styles and scripts.
 */
function htoeau_child_enqueue_assets() {
	/*
	 * Hello Elementor loads real CSS as hello-elementor + hello-elementor-theme-style (not root style.css).
	 */
	$parent_style_deps = array();
	if ( wp_style_is( 'hello-elementor-theme-style', 'registered' ) || wp_style_is( 'hello-elementor-theme-style', 'enqueued' ) ) {
		$parent_style_deps[] = 'hello-elementor-theme-style';
	} elseif ( wp_style_is( 'hello-elementor', 'registered' ) || wp_style_is( 'hello-elementor', 'enqueued' ) ) {
		$parent_style_deps[] = 'hello-elementor';
	}

	wp_enqueue_style(
		'htoeau-google-fonts',
		'https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Roboto:wght@400;500;700&display=swap',
		array(),
		null
	);

	$child_style_deps = array_merge( $parent_style_deps, array( 'htoeau-google-fonts' ) );

	wp_enqueue_style(
		'htoeau-child-style',
		get_stylesheet_uri(),
		$child_style_deps,
		HTOEAU_CHILD_VERSION
	);

	$icon_script_deps = array( 'jquery' );
	if ( wp_script_is( 'elementor-frontend', 'registered' ) ) {
		$icon_script_deps[] = 'elementor-frontend';
	}
	wp_enqueue_script(
		'htoeau-elementor-check-icons',
		HTOEAU_CHILD_URI . '/assets/js/elementor-check-icons.js',
		$icon_script_deps,
		HTOEAU_CHILD_VERSION,
		true
	);

	if ( is_product() ) {
		$pdp_deps = array( 'jquery' );
		if ( wp_script_is( 'wc-add-to-cart-variation', 'registered' ) ) {
			$pdp_deps[] = 'wc-add-to-cart-variation';
		}
		wp_enqueue_script(
			'htoeau-pdp',
			HTOEAU_CHILD_URI . '/assets/js/pdp.js',
			$pdp_deps,
			HTOEAU_CHILD_VERSION,
			true
		);

		$fx_display = function_exists( 'htoeau_child_fx_get_display_currency' ) ? htoeau_child_fx_get_display_currency() : '';
		$fx_store   = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
		$sym_ccy    = ( function_exists( 'htoeau_child_fx_is_enabled' ) && htoeau_child_fx_is_enabled() && $fx_display )
			? $fx_display
			: $fx_store;
		$symbol_map = array(
			'GBP' => html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' ),
			'EUR' => html_entity_decode( '&euro;', ENT_QUOTES, 'UTF-8' ),
		);
		$sym_value  = isset( $symbol_map[ $sym_ccy ] )
			? $symbol_map[ $sym_ccy ]
			: ( function_exists( 'get_woocommerce_currency_symbol' )
				? html_entity_decode( get_woocommerce_currency_symbol( $sym_ccy ), ENT_QUOTES, 'UTF-8' )
				: html_entity_decode( '&pound;', ENT_QUOTES, 'UTF-8' ) );
		wp_localize_script(
			'htoeau-pdp',
			'htoeauPdp',
			array(
				'i18n' => array(
					'addToCart' => __( 'Add to Cart', 'hello-elementor-child' ),
				),
				'currencySymbol' => $sym_value,
				'storeCurrency'   => $fx_store,
				'displayCurrency' => $fx_display,
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_assets', 200 );

/**
 * PDP CSS after Elementor (kit / post / frontend) so gallery buttons beat global button styles.
 */
function htoeau_child_enqueue_pdp_css_after_elementor() {
	$deps = array( 'htoeau-child-style' );
	foreach ( array( 'elementor-frontend', 'elementor-gf-local-roboto', 'elementor-gf-local-robotoslab' ) as $h ) {
		if ( wp_style_is( $h, 'registered' ) ) {
			$deps[] = $h;
		}
	}
	if ( is_product() ) {
		$pid = get_queried_object_id();
		if ( $pid && wp_style_is( 'elementor-post-' . $pid, 'registered' ) ) {
			$deps[] = 'elementor-post-' . $pid;
		}
	}
	wp_enqueue_style(
		'htoeau-pdp',
		HTOEAU_CHILD_URI . '/assets/css/pdp.css',
		$deps,
		HTOEAU_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_pdp_css_after_elementor', 999 );

/**
 * Remove WooCommerce core breadcrumb on single product (Elementor breadcrumb = CSS below).
 */
function htoeau_child_remove_wc_breadcrumb_on_product() {
	if ( ! is_product() ) {
		return;
	}
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 10 );
}
add_action( 'wp', 'htoeau_child_remove_wc_breadcrumb_on_product', 5 );

/**
 * WooCommerce support + nav menu.
 */
function htoeau_child_woocommerce_setup() {
	add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'htoeau_child_woocommerce_setup' );

/**
 * Shop / category archives: layout, filters, no sidebar, custom toolbar (see archive-product.php).
 */
function htoeau_child_shop_archive_setup() {
	if ( ! function_exists( 'is_shop' ) ) {
		return;
	}
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 10 );
}
add_action( 'wp', 'htoeau_child_shop_archive_setup', 15 );

/**
 * Hide WooCommerce archive page title (custom hero + toolbar; no duplicate H1).
 *
 * @param bool $show Whether to show the title.
 * @return bool
 */
function htoeau_child_shop_hide_page_title( $show ) {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		return false;
	}
	return $show;
}
add_filter( 'woocommerce_show_page_title', 'htoeau_child_shop_hide_page_title' );

/**
 * Category / stock filters on the main shop query only.
 *
 * @param WP_Query $q Query.
 */
function htoeau_child_shop_product_query( $q ) {
	if ( ! $q->is_main_query() ) {
		return;
	}
	if ( is_shop() && isset( $_GET['htoeau_shop_cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slug = sanitize_title( wp_unslash( $_GET['htoeau_shop_cat'] ) );
		if ( $slug ) {
			$tax_query = $q->get( 'tax_query' );
			if ( ! is_array( $tax_query ) ) {
				$tax_query = array();
			}
			$tax_query[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $slug,
			);
			$q->set( 'tax_query', $tax_query );
		}
	}

	if ( isset( $_GET['htoeau_stock'] ) && 'instock' === $_GET['htoeau_stock'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$meta_query = $q->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}
		$meta_query[] = array(
			'key'     => '_stock_status',
			'value'   => 'instock',
			'compare' => '=',
		);
		$q->set( 'meta_query', $meta_query );
	}
}
add_action( 'woocommerce_product_query', 'htoeau_child_shop_product_query', 5 );

/**
 * Three-column product grid on archives.
 *
 * @return int
 */
function htoeau_child_loop_shop_columns() {
	return 3;
}
add_filter( 'loop_shop_columns', 'htoeau_child_loop_shop_columns', 20 );

/**
 * Body class for shop archive CSS.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function htoeau_child_shop_body_class( $classes ) {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) ) {
		$classes[] = 'htoeau-shop-archive';
	}
	return $classes;
}
add_filter( 'body_class', 'htoeau_child_shop_body_class' );

/**
 * Shop archive CSS after Elementor so theme rules win.
 */
function htoeau_child_enqueue_shop_archive_css_after_elementor() {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_taxonomy() ) ) {
		return;
	}
	$deps = array( 'htoeau-child-style' );
	foreach ( array( 'elementor-frontend', 'elementor-gf-local-roboto', 'elementor-gf-local-robotoslab' ) as $h ) {
		if ( wp_style_is( $h, 'registered' ) ) {
			$deps[] = $h;
		}
	}
	wp_enqueue_style(
		'htoeau-shop-archive',
		HTOEAU_CHILD_URI . '/assets/css/shop-archive.css',
		$deps,
		HTOEAU_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_shop_archive_css_after_elementor', 999 );

/**
 * Shop toolbar: auto-submit form on select change (GET filters/sort).
 */
function htoeau_child_enqueue_shop_archive_toolbar_js() {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_taxonomy() ) ) {
		return;
	}
	wp_enqueue_script(
		'htoeau-shop-archive-toolbar',
		HTOEAU_CHILD_URI . '/assets/js/shop-archive-toolbar.js',
		array(),
		HTOEAU_CHILD_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_shop_archive_toolbar_js', 1000 );

/**
 * Cart page CSS after Elementor so branded layout overrides default WooCommerce styles.
 */
function htoeau_child_enqueue_cart_css_after_elementor() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	$deps = array( 'htoeau-child-style' );
	foreach ( array( 'elementor-frontend', 'elementor-gf-local-roboto', 'elementor-gf-local-robotoslab' ) as $h ) {
		if ( wp_style_is( $h, 'registered' ) ) {
			$deps[] = $h;
		}
	}

	wp_enqueue_style(
		'htoeau-cart',
		HTOEAU_CHILD_URI . '/assets/css/cart.css',
		$deps,
		HTOEAU_CHILD_VERSION
	);

	wp_enqueue_script(
		'htoeau-cart-carousel',
		HTOEAU_CHILD_URI . '/assets/js/cart-carousel.js',
		array(),
		HTOEAU_CHILD_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_cart_css_after_elementor', 999 );

/**
 * Checkout page CSS after Elementor so branded layout overrides default WC styles.
 * Skips order-received / order-pay endpoints (those use the standard receipt flow).
 */
function htoeau_child_enqueue_checkout_css_after_elementor() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
		return;
	}

	$deps = array( 'htoeau-child-style' );
	foreach ( array( 'elementor-frontend', 'elementor-gf-local-roboto', 'elementor-gf-local-robotoslab' ) as $h ) {
		if ( wp_style_is( $h, 'registered' ) ) {
			$deps[] = $h;
		}
	}

	wp_enqueue_style(
		'htoeau-checkout',
		HTOEAU_CHILD_URI . '/assets/css/checkout.css',
		$deps,
		HTOEAU_CHILD_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_checkout_css_after_elementor', 999 );

/**
 * Inject branded inline coupon form into checkout sidebar (above payment methods).
 * Uses <details>/<summary> to avoid WooCommerce button CSS inheritance.
 */
function htoeau_checkout_inline_coupon() {
	if ( ! wc_coupons_enabled() ) {
		return;
	}
	?>
	<details class="htoeau-checkout-coupon">
		<summary class="htoeau-checkout-coupon__summary">
			<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
			<span><?php esc_html_e( 'Have a promo code?', 'hello-elementor-child' ); ?></span>
			<svg class="htoeau-checkout-coupon__chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
		</summary>
		<div class="htoeau-checkout-coupon__body">
			<form class="checkout_coupon woocommerce-form-coupon" method="post">
				<div class="htoeau-checkout-coupon__row">
					<input
						type="text"
						name="coupon_code"
						class="input-text htoeau-checkout-coupon__input"
						id="coupon_code_checkout"
						value=""
						placeholder="<?php esc_attr_e( 'Promo code', 'hello-elementor-child' ); ?>"
					/>
					<button
						type="submit"
						class="htoeau-checkout-coupon__btn"
						name="apply_coupon"
						value="apply_coupon"
					><?php esc_html_e( 'Apply', 'hello-elementor-child' ); ?></button>
				</div>
			</form>
		</div>
	</details>
	<?php
}
add_action( 'woocommerce_checkout_order_review', 'htoeau_checkout_inline_coupon', 15 );

/**
 * Remove default single product layout hooks (custom templates replace them).
 */
function htoeau_child_remove_single_product_hooks() {
	if ( ! is_product() ) {
		return;
	}

	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

	if ( function_exists( 'WC' ) && WC()->structured_data ) {
		remove_action( 'woocommerce_single_product_summary', array( WC()->structured_data, 'generate_product_data' ), 60 );
	}

	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
}
add_action( 'wp', 'htoeau_child_remove_single_product_hooks', 99 );

/**
 * Re-output structured data after custom template.
 */
function htoeau_child_output_product_structured_data() {
	if ( ! is_product() || ! function_exists( 'WC' ) || ! WC()->structured_data ) {
		return;
	}
	WC()->structured_data->generate_product_data();
}
add_action( 'woocommerce_after_single_product', 'htoeau_child_output_product_structured_data', 5 );

/**
 * Hide default variation add to cart button (custom CTA used).
 */
function htoeau_child_remove_variation_cart_button() {
	remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
}
add_action( 'init', 'htoeau_child_remove_variation_cart_button' );

/**
 * Register ACF local field groups (ACF Free-compatible — no repeaters).
 */
function htoeau_child_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_htoeau_product',
			'title'                 => __( 'HtoEAU Product', 'hello-elementor-child' ),
			'fields'                => array(

				/* ── Tab: Feature Blurbs ── */
				array(
					'key'   => 'field_htoeau_tab_features',
					'label' => __( 'Feature Blurbs', 'hello-elementor-child' ),
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_htoeau_fb_1_title',
					'label'        => __( 'Blurb 1 — Title', 'hello-elementor-child' ),
					'name'         => 'feature_blurb_1_title',
					'type'         => 'text',
					'placeholder'  => 'Cellular Hydration:',
				),
				array(
					'key'         => 'field_htoeau_fb_1_desc',
					'label'       => __( 'Blurb 1 — Description', 'hello-elementor-child' ),
					'name'        => 'feature_blurb_1_desc',
					'type'        => 'textarea',
					'rows'        => 2,
				),
				array(
					'key'          => 'field_htoeau_fb_2_title',
					'label'        => __( 'Blurb 2 — Title', 'hello-elementor-child' ),
					'name'         => 'feature_blurb_2_title',
					'type'         => 'text',
					'placeholder'  => 'Precision Hydrogen Infusion:',
				),
				array(
					'key'         => 'field_htoeau_fb_2_desc',
					'label'       => __( 'Blurb 2 — Description', 'hello-elementor-child' ),
					'name'        => 'feature_blurb_2_desc',
					'type'        => 'textarea',
					'rows'        => 2,
				),
				array(
					'key'          => 'field_htoeau_fb_3_title',
					'label'        => __( 'Blurb 3 — Title', 'hello-elementor-child' ),
					'name'         => 'feature_blurb_3_title',
					'type'         => 'text',
					'placeholder'  => 'Cognitive Clarity:',
				),
				array(
					'key'         => 'field_htoeau_fb_3_desc',
					'label'       => __( 'Blurb 3 — Description', 'hello-elementor-child' ),
					'name'        => 'feature_blurb_3_desc',
					'type'        => 'textarea',
					'rows'        => 2,
				),

				/* ── Tab: Shop / catalog ── */
				array(
					'key'   => 'field_htoeau_tab_shop_catalog',
					'label' => __( 'Shop / catalog', 'hello-elementor-child' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_htoeau_shop_catalog_image',
					'label'         => __( 'Shop card image', 'hello-elementor-child' ),
					'name'          => 'shop_catalog_image',
					'type'          => 'image',
					'return_format' => 'id',
					'instructions'  => __( 'Optional. Used on shop and archive product cards only. The product image / gallery still controls the PDP.', 'hello-elementor-child' ),
				),

				/* ── Tab: PDP quantity cards ── */
				array(
					'key'   => 'field_htoeau_tab_pdp_qty_cards',
					'label' => __( 'PDP quantity cards', 'hello-elementor-child' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_htoeau_pdp_can_image',
					'label'         => __( 'Can graphic', 'hello-elementor-child' ),
					'name'          => 'pdp_can_image',
					'type'          => 'image',
					'return_format' => 'array',
					'instructions'  => __( 'Optional. Tall narrow can art for the pack cards (12 / 48 / 96). Leave empty to use the default theme can. Prefer a PNG with similar proportions to a slim can, not the square main product photo.', 'hello-elementor-child' ),
				),

				/* ── Tab: Subscribe Bullets ── */
				array(
					'key'   => 'field_htoeau_tab_subscribe',
					'label' => __( 'Subscribe Bullets', 'hello-elementor-child' ),
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_htoeau_subscribe_bullets_text',
					'label'        => __( 'Bullet points (one per line)', 'hello-elementor-child' ),
					'name'         => 'subscribe_bullets_text',
					'type'         => 'textarea',
					'rows'         => 4,
					'instructions' => __( 'Enter one bullet per line. Leave blank for defaults.', 'hello-elementor-child' ),
				),

				/* ── Tab: Whats Inside Every Can ── */
				array(
					'key'   => 'field_htoeau_tab_whats_inside_can',
					'label' => __( "What's Inside Every Can", 'hello-elementor-child' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_htoeau_inside_hydrogen_water',
					'label'         => __( 'Hydrogen Water text', 'hello-elementor-child' ),
					'name'          => 'inside_can_hydrogen_water_text',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => 'filtered/purified still water, infused with minimum 5mg/l molecular hydrogen gas via proprietary pressure technology - described as a market leader',
					'instructions'  => __( "Optional override for the PDP What's Inside Every Can tab.", 'hello-elementor-child' ),
				),
				array(
					'key'           => 'field_htoeau_inside_ddw',
					'label'         => __( 'Deuterium Depleted Water text', 'hello-elementor-child' ),
					'name'          => 'inside_can_ddw_text',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => 'purified still water, redistilled over 50 times to deplete Deuterium to 50ppm, called "pure Light Water"',
				),
				array(
					'key'           => 'field_htoeau_inside_h2_ddw',
					'label'         => __( 'H2 DDW text', 'hello-elementor-child' ),
					'name'          => 'inside_can_h2_ddw_text',
					'type'          => 'textarea',
					'rows'          => 4,
					'default_value' => 'purified still DDW at 50ppm, infused with minimum 5mg/l molecular hydrogen, described as "the ultimate H2 DD fusion - unrivalled anywhere"',
				),

				/* ── Tab: Transformation Steps ── */
				array(
					'key'   => 'field_htoeau_tab_transform',
					'label' => __( 'Transformation Steps', 'hello-elementor-child' ),
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_htoeau_ts_1_title',
					'label'        => __( 'Step 1 — Title', 'hello-elementor-child' ),
					'name'         => 'transform_step_1_title',
					'type'         => 'text',
					'placeholder'  => 'Drink HtoEAU',
				),
				array(
					'key'         => 'field_htoeau_ts_1_desc',
					'label'       => __( 'Step 1 — Description', 'hello-elementor-child' ),
					'name'        => 'transform_step_1_desc',
					'type'        => 'textarea',
					'rows'        => 2,
				),
				array(
					'key'           => 'field_htoeau_ts_1_image',
					'label'         => __( 'Step 1 — Image', 'hello-elementor-child' ),
					'name'          => 'transform_step_1_image',
					'type'          => 'image',
					'return_format' => 'array',
				),
				array(
					'key'          => 'field_htoeau_ts_2_title',
					'label'        => __( 'Step 2 — Title', 'hello-elementor-child' ),
					'name'         => 'transform_step_2_title',
					'type'         => 'text',
					'placeholder'  => 'Rapid Hydration',
				),
				array(
					'key'         => 'field_htoeau_ts_2_desc',
					'label'       => __( 'Step 2 — Description', 'hello-elementor-child' ),
					'name'        => 'transform_step_2_desc',
					'type'        => 'textarea',
					'rows'        => 2,
				),
				array(
					'key'           => 'field_htoeau_ts_2_image',
					'label'         => __( 'Step 2 — Image', 'hello-elementor-child' ),
					'name'          => 'transform_step_2_image',
					'type'          => 'image',
					'return_format' => 'array',
				),
				array(
					'key'          => 'field_htoeau_ts_3_title',
					'label'        => __( 'Step 3 — Title', 'hello-elementor-child' ),
					'name'         => 'transform_step_3_title',
					'type'         => 'text',
					'placeholder'  => 'Stay Ready for Your Day',
				),
				array(
					'key'         => 'field_htoeau_ts_3_desc',
					'label'       => __( 'Step 3 — Description', 'hello-elementor-child' ),
					'name'        => 'transform_step_3_desc',
					'type'        => 'textarea',
					'rows'        => 2,
				),
				array(
					'key'           => 'field_htoeau_ts_3_image',
					'label'         => __( 'Step 3 — Image', 'hello-elementor-child' ),
					'name'          => 'transform_step_3_image',
					'type'          => 'image',
					'return_format' => 'array',
				),

				/* ── Tab: PDP FAQ ── */
				array(
					'key'   => 'field_htoeau_tab_pdp_faq',
					'label' => __( 'PDP FAQ', 'hello-elementor-child' ),
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_htoeau_pdp_faq_heading',
					'label'         => __( 'FAQ — heading', 'hello-elementor-child' ),
					'name'          => 'pdp_faq_heading',
					'type'          => 'text',
					'placeholder'   => __( 'Frequently Asked Questions', 'hello-elementor-child' ),
					'instructions'  => __( 'Optional. Leave empty to use the default heading. At least one question below must be filled for the FAQ block to appear.', 'hello-elementor-child' ),
				),
				array(
					'key'           => 'field_htoeau_pdp_faq_subheading',
					'label'         => __( 'FAQ — subheading', 'hello-elementor-child' ),
					'name'          => 'pdp_faq_subheading',
					'type'          => 'textarea',
					'rows'          => 3,
					'instructions'  => __( 'Optional intro text under the heading.', 'hello-elementor-child' ),
				),
				array(
					'key'          => 'field_htoeau_pdp_faq_1_q',
					'label'        => __( 'Question 1', 'hello-elementor-child' ),
					'name'         => 'pdp_faq_1_question',
					'type'         => 'text',
				),
				array(
					'key'         => 'field_htoeau_pdp_faq_1_a',
					'label'       => __( 'Answer 1', 'hello-elementor-child' ),
					'name'        => 'pdp_faq_1_answer',
					'type'        => 'textarea',
					'rows'        => 4,
				),
				array(
					'key'          => 'field_htoeau_pdp_faq_2_q',
					'label'        => __( 'Question 2', 'hello-elementor-child' ),
					'name'         => 'pdp_faq_2_question',
					'type'         => 'text',
				),
				array(
					'key'         => 'field_htoeau_pdp_faq_2_a',
					'label'       => __( 'Answer 2', 'hello-elementor-child' ),
					'name'        => 'pdp_faq_2_answer',
					'type'        => 'textarea',
					'rows'        => 4,
				),
				array(
					'key'          => 'field_htoeau_pdp_faq_3_q',
					'label'        => __( 'Question 3', 'hello-elementor-child' ),
					'name'         => 'pdp_faq_3_question',
					'type'         => 'text',
				),
				array(
					'key'         => 'field_htoeau_pdp_faq_3_a',
					'label'       => __( 'Answer 3', 'hello-elementor-child' ),
					'name'        => 'pdp_faq_3_answer',
					'type'        => 'textarea',
					'rows'        => 4,
				),
				array(
					'key'          => 'field_htoeau_pdp_faq_4_q',
					'label'        => __( 'Question 4', 'hello-elementor-child' ),
					'name'         => 'pdp_faq_4_question',
					'type'         => 'text',
				),
				array(
					'key'         => 'field_htoeau_pdp_faq_4_a',
					'label'       => __( 'Answer 4', 'hello-elementor-child' ),
					'name'        => 'pdp_faq_4_answer',
					'type'        => 'textarea',
					'rows'        => 4,
				),
				array(
					'key'          => 'field_htoeau_pdp_faq_5_q',
					'label'        => __( 'Question 5', 'hello-elementor-child' ),
					'name'         => 'pdp_faq_5_question',
					'type'         => 'text',
				),
				array(
					'key'         => 'field_htoeau_pdp_faq_5_a',
					'label'       => __( 'Answer 5', 'hello-elementor-child' ),
					'name'        => 'pdp_faq_5_answer',
					'type'        => 'textarea',
					'rows'        => 4,
					'instructions' => __( 'Up to five pairs (ACF Free). Uses the same layout as the HtoEAU FAQ Accordion Elementor widget.', 'hello-elementor-child' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'product',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
		)
	);

	acf_add_local_field_group(
		array(
			'key'                   => 'group_htoeau_variation',
			'title'                 => __( 'HtoEAU Variation', 'hello-elementor-child' ),
			'fields'                => array(
				array(
					'key'   => 'field_htoeau_variation_badge',
					'label' => __( 'Variation badge', 'hello-elementor-child' ),
					'name'  => 'variation_badge',
					'type'  => 'text',
					'instructions' => __( 'e.g. Most Popular, Best Value', 'hello-elementor-child' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'product_variation',
					),
				),
			),
		)
	);
}
add_action( 'acf/init', 'htoeau_child_register_acf_fields' );

/**
 * Variation badge (ACF-backed): WooCommerce’s variation editor does not render ACF meta boxes.
 * Mirror the same data on the Product → Variations row so it is visible without opening the variation post.
 *
 * @param int     $loop           Variation index in the form.
 * @param array   $variation_data Row data.
 * @param WP_Post $variation      Variation post.
 */
function htoeau_child_render_variation_badge_wc_field( $loop, $variation_data, $variation ) {
	$variation_id = isset( $variation->ID ) ? (int) $variation->ID : 0;
	if ( ! $variation_id ) {
		return;
	}
	$value = function_exists( 'get_field' )
		? (string) get_field( 'variation_badge', $variation_id )
		: (string) get_post_meta( $variation_id, 'variation_badge', true );

	woocommerce_wp_text_input(
		array(
			'id'            => 'htoeau_variation_badge_' . (int) $loop,
			'name'          => 'htoeau_variation_badge[' . (int) $loop . ']',
			'value'         => $value,
			'label'         => __( 'Variation badge', 'hello-elementor-child' ),
			'description'   => __( 'Shown on the PDP quantity card (e.g. Most Popular, Best Value).', 'hello-elementor-child' ),
			'desc_tip'      => true,
			'wrapper_class' => 'form-row form-row-full',
		)
	);
}
add_action( 'woocommerce_product_after_variable_attributes', 'htoeau_child_render_variation_badge_wc_field', 10, 3 );

/**
 * Persist variation badge from the WooCommerce variation form (same meta as ACF field `variation_badge`).
 *
 * @param int $variation_id Variation post ID.
 * @param int $i            Loop index.
 */
function htoeau_child_save_variation_badge_wc_field( $variation_id, $i ) {
	if ( ! isset( $_POST['htoeau_variation_badge'][ $i ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	$val = sanitize_text_field( wp_unslash( $_POST['htoeau_variation_badge'][ $i ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( function_exists( 'update_field' ) ) {
		update_field( 'variation_badge', $val, $variation_id );
	} else {
		update_post_meta( $variation_id, 'variation_badge', $val );
	}
}
add_action( 'woocommerce_save_product_variation', 'htoeau_child_save_variation_badge_wc_field', 10, 2 );

/**
 * Helper: canonical can-count attribute slug (filterable).
 */
function htoeau_child_can_count_attribute() {
	return apply_filters( 'htoeau_can_count_attribute', 'pa_can-count' );
}

/**
 * Delivery interval options for PDP + cart validation (filterable).
 *
 * @return array<string, string> slug => label.
 */
function htoeau_child_get_delivery_interval_options() {
	return apply_filters(
		'htoeau_delivery_interval_options',
		array(
			'4w' => __( 'Deliver every 4 weeks', 'hello-elementor-child' ),
			'6w' => __( 'Deliver every 6 weeks', 'hello-elementor-child' ),
			'8w' => __( 'Deliver every 8 weeks', 'hello-elementor-child' ),
		)
	);
}

/**
 * @param string $slug Interval key e.g. 8w.
 * @return string
 */
function htoeau_child_delivery_interval_label( $slug ) {
	$opts = htoeau_child_get_delivery_interval_options();
	return isset( $opts[ $slug ] ) ? $opts[ $slug ] : (string) $slug;
}

/**
 * Attach PDP choices to the cart line (POST from custom single-product layout).
 *
 * @param array $cart_item_data Existing data.
 * @param int   $product_id     Product ID.
 * @param int   $variation_id   Variation ID.
 * @param int   $quantity       Qty.
 * @return array
 */
function htoeau_child_add_pdp_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
	$parent = wc_get_product( $product_id );
	if ( $parent && function_exists( 'htoeau_child_product_is_wcs_subscription' ) && htoeau_child_product_is_wcs_subscription( $parent ) ) {
		return $cart_item_data;
	}
	if ( ! isset( $_POST['htoeau_purchase_intent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WC validated add-to-cart.
		return $cart_item_data;
	}

	$intent = sanitize_text_field( wp_unslash( $_POST['htoeau_purchase_intent'] ) );
	if ( ! in_array( $intent, array( 'subscribe', 'once' ), true ) ) {
		$intent = 'once';
	}
	$cart_item_data['htoeau_purchase_intent'] = $intent;

	if ( 'subscribe' === $intent && isset( $_POST['htoeau_delivery_interval'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$raw     = sanitize_text_field( wp_unslash( $_POST['htoeau_delivery_interval'] ) );
		$allowed = array_keys( htoeau_child_get_delivery_interval_options() );
		if ( in_array( $raw, $allowed, true ) ) {
			$cart_item_data['htoeau_delivery_interval'] = $raw;
		}
	}

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'htoeau_child_add_pdp_cart_item_data', 10, 4 );

/**
 * Show PDP meta on cart / checkout line items.
 *
 * @param array $item_data Rows.
 * @param array $cart_item Cart row.
 * @return array
 */
function htoeau_child_get_item_data( $item_data, $cart_item ) {
	if ( ! empty( $cart_item['data'] ) && function_exists( 'htoeau_child_product_is_wcs_subscription' ) && htoeau_child_product_is_wcs_subscription( $cart_item['data'] ) ) {
		return $item_data;
	}
	if ( empty( $cart_item['htoeau_purchase_intent'] ) ) {
		return $item_data;
	}

	if ( 'subscribe' === $cart_item['htoeau_purchase_intent'] ) {
		$item_data[] = array(
			'name'  => __( 'Purchase type', 'hello-elementor-child' ),
			'value' => __( 'Subscribe & Save', 'hello-elementor-child' ),
		);
		if ( ! empty( $cart_item['htoeau_delivery_interval'] ) ) {
			$item_data[] = array(
				'name'  => __( 'Delivery frequency', 'hello-elementor-child' ),
				'value' => htoeau_child_delivery_interval_label( $cart_item['htoeau_delivery_interval'] ),
			);
		}
	} else {
		$item_data[] = array(
			'name'  => __( 'Purchase type', 'hello-elementor-child' ),
			'value' => __( 'One-time purchase', 'hello-elementor-child' ),
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'htoeau_child_get_item_data', 10, 2 );

/**
 * Persist PDP meta on order items.
 *
 * @param WC_Order_Item_Product $item          Order item.
 * @param string                $cart_item_key Key.
 * @param array                 $values        Cart row.
 * @param WC_Order              $order         Order.
 */
function htoeau_child_checkout_order_line_item_meta( $item, $cart_item_key, $values, $order ) {
	if ( ! empty( $values['data'] ) && function_exists( 'htoeau_child_product_is_wcs_subscription' ) && htoeau_child_product_is_wcs_subscription( $values['data'] ) ) {
		return;
	}
	if ( empty( $values['htoeau_purchase_intent'] ) ) {
		return;
	}

	$type_label = 'subscribe' === $values['htoeau_purchase_intent']
		? __( 'Subscribe & Save', 'hello-elementor-child' )
		: __( 'One-time purchase', 'hello-elementor-child' );

	$item->add_meta_data( __( 'Purchase type', 'hello-elementor-child' ), $type_label, true );

	if ( ! empty( $values['htoeau_delivery_interval'] ) && 'subscribe' === $values['htoeau_purchase_intent'] ) {
		$item->add_meta_data(
			__( 'Delivery frequency', 'hello-elementor-child' ),
			htoeau_child_delivery_interval_label( $values['htoeau_delivery_interval'] ),
			true
		);
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'htoeau_child_checkout_order_line_item_meta', 10, 4 );

/**
 * Match PDP pricing: subscribe lines use catalog variation price minus discount %.
 *
 * @param WC_Cart $cart Cart.
 */
function htoeau_child_apply_subscribe_cart_prices( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	$discount_pct = (float) apply_filters( 'htoeau_subscribe_discount_percent', 10 );

	foreach ( $cart->get_cart() as $cart_item ) {
		if ( ! empty( $cart_item['data'] ) && function_exists( 'htoeau_child_wcs_is_active' ) && htoeau_child_wcs_is_active() && class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
			continue;
		}
		if ( empty( $cart_item['htoeau_purchase_intent'] ) || 'subscribe' !== $cart_item['htoeau_purchase_intent'] ) {
			continue;
		}
		if ( empty( $cart_item['variation_id'] ) ) {
			continue;
		}

		$variation = wc_get_product( (int) $cart_item['variation_id'] );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			continue;
		}

		$base = (float) $variation->get_price( 'edit' );
		$sub  = max( 0, $base * ( 1 - $discount_pct / 100 ) );

		$cart_item['data']->set_price( wc_format_decimal( $sub ) );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'htoeau_child_apply_subscribe_cart_prices', 20, 1 );

/**
 * PDP-style line for cart: optional ACF variation badge + pack size (e.g. "Best Value, 96 Cans").
 *
 * @param array $cart_item Cart row.
 * @return string
 */
function htoeau_child_get_cart_variation_display_line( $cart_item ) {
	if ( empty( $cart_item['data'] ) || ! $cart_item['data']->is_type( 'variation' ) ) {
		return '';
	}
	/** @var WC_Product_Variation $product */
	$product = $cart_item['data'];
	$parts   = array();

	$vid = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
	if ( $vid && function_exists( 'get_field' ) ) {
		$badge = trim( (string) get_field( 'variation_badge', $vid ) );
		if ( '' !== $badge ) {
			$parts[] = $badge;
		}
	}

	$attrs = isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? $cart_item['variation'] : array();
	$cans  = htoeau_child_get_can_count_from_variation_attrs( $attrs );
	if ( $cans > 0 ) {
		$parts[] = sprintf(
			/* translators: %d: number of cans in the pack */
			__( '%d Cans', 'hello-elementor-child' ),
			$cans
		);
	}

	if ( ! empty( $parts ) ) {
		return apply_filters( 'htoeau_cart_variation_display_line', implode( ', ', $parts ), $parts, $cart_item );
	}

	$line = wc_get_formatted_variation( $product, true, true, false );
	return apply_filters(
		'htoeau_cart_variation_display_line',
		trim( wp_strip_all_tags( $line ) ),
		array(),
		$cart_item
	);
}

/**
 * Append variation choice to the product name on cart, checkout review, and mini-cart.
 *
 * @param string $name          Product name or <a>…</a> HTML (cart table applies this filter twice).
 * @param array  $cart_item     Cart row.
 * @param string $cart_item_key Key.
 * @return string
 */
function htoeau_child_cart_item_name_append_variation( $name, $cart_item, $cart_item_key ) {
	if ( empty( $cart_item['data'] ) || ! $cart_item['data']->is_type( 'variation' ) ) {
		return $name;
	}
	$line = htoeau_child_get_cart_variation_display_line( $cart_item );
	if ( '' === $line ) {
		return $name;
	}
	$block = '<span class="htoeau-cart-item-variation">' . esc_html( $line ) . '</span>';
	// Cart product-name column: second pass wraps the title in a link.
	if ( false !== strpos( $name, '</a>' ) ) {
		return preg_replace( '/<\/a>/i', '</a><div class="htoeau-cart-item-variation-wrap">' . $block . '</div>', $name, 1 );
	}
	return $name . '<div class="htoeau-cart-item-variation-wrap">' . $block . '</div>';
}
add_filter( 'woocommerce_cart_item_name', 'htoeau_child_cart_item_name_append_variation', 20, 3 );

/**
 * Customize WooCommerce product tabs for accordion labels and delivery tab.
 *
 * @param array $tabs Tabs.
 * @return array
 */
function htoeau_child_product_tabs( $tabs ) {
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['title'] = __( 'Product Description', 'hello-elementor-child' );
	}
	if ( isset( $tabs['additional_information'] ) ) {
		$tabs['additional_information']['title']    = __( "What's Inside Every Can", 'hello-elementor-child' );
		$tabs['additional_information']['callback'] = 'htoeau_child_whats_inside_every_can_tab_content';
	} else {
		$tabs['additional_information'] = array(
			'title'    => __( "What's Inside Every Can", 'hello-elementor-child' ),
			'priority' => 25,
			'callback' => 'htoeau_child_whats_inside_every_can_tab_content',
		);
	}
	unset( $tabs['reviews'] );

	$tabs['delivery_returns'] = array(
		'title'    => __( 'Delivery & Returns', 'hello-elementor-child' ),
		'priority' => 35,
		'callback' => 'htoeau_child_delivery_returns_tab_content',
	);

	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'htoeau_child_product_tabs', 50 );

/**
 * What's Inside Every Can tab body (ACF-backed with defaults).
 */
function htoeau_child_whats_inside_every_can_tab_content() {
	$defaults = array(
		'hydrogen' => 'filtered/purified still water, infused with minimum 5mg/l molecular hydrogen gas via proprietary pressure technology - described as a market leader',
		'ddw'      => 'purified still water, redistilled over 50 times to deplete Deuterium to 50ppm, called "pure Light Water"',
		'h2_ddw'   => 'purified still DDW at 50ppm, infused with minimum 5mg/l molecular hydrogen, described as "the ultimate H2 DD fusion - unrivalled anywhere"',
	);

	$product_id = get_the_ID();
	$hydrogen   = '';
	$ddw        = '';
	$h2_ddw     = '';

	if ( function_exists( 'get_field' ) && $product_id ) {
		$hydrogen = trim( (string) get_field( 'inside_can_hydrogen_water_text', $product_id ) );
		$ddw      = trim( (string) get_field( 'inside_can_ddw_text', $product_id ) );
		$h2_ddw   = trim( (string) get_field( 'inside_can_h2_ddw_text', $product_id ) );
	}

	if ( '' === $hydrogen ) {
		$hydrogen = $defaults['hydrogen'];
	}
	if ( '' === $ddw ) {
		$ddw = $defaults['ddw'];
	}
	if ( '' === $h2_ddw ) {
		$h2_ddw = $defaults['h2_ddw'];
	}

	echo '<p><strong>' . esc_html__( 'Hydrogen Water:', 'hello-elementor-child' ) . '</strong> ' . esc_html( $hydrogen ) . '</p>';
	echo '<p><strong>' . esc_html__( 'Deuterium Depleted Water:', 'hello-elementor-child' ) . '</strong> ' . esc_html( $ddw ) . '</p>';
	echo '<p><strong>' . esc_html__( 'H2 DDW:', 'hello-elementor-child' ) . '</strong> ' . esc_html( $h2_ddw ) . '</p>';
}

/**
 * Delivery & Returns tab body (filterable).
 */
function htoeau_child_delivery_returns_tab_content() {
	$content = apply_filters(
		'htoeau_delivery_returns_content',
		'<p>' . esc_html__( 'Standard delivery and return policy information can be added in the theme or via this filter: htoeau_delivery_returns_content.', 'hello-elementor-child' ) . '</p>'
	);
	echo wp_kses_post( $content );
}

/**
 * Extract numeric can count from variation attributes.
 *
 * @param array $variation_attrs Variation attributes array (keys like attribute_pa_can-count).
 * @return int
 */
function htoeau_child_get_can_count_from_variation_attrs( $variation_attrs ) {
	$slug = htoeau_child_can_count_attribute();
	$key  = 'attribute_' . sanitize_title( $slug );
	if ( isset( $variation_attrs[ $key ] ) ) {
		return (int) preg_replace( '/\D/', '', (string) $variation_attrs[ $key ] );
	}
	foreach ( $variation_attrs as $k => $v ) {
		if ( false !== strpos( $k, 'can' ) && is_string( $v ) ) {
			return (int) preg_replace( '/\D/', '', $v );
		}
	}
	return 0;
}
