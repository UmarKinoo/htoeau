<?php
/**
 * Shop toolbar: filters + result count + sort (Figma 1:2729).
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$base = htoeau_child_get_shop_filter_base_url();
if ( '' === $base ) {
	return;
}

$total = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
$total_label = $total < 100 ? str_pad( (string) $total, 2, '0', STR_PAD_LEFT ) : (string) $total;

$orderby_default = apply_filters( 'woocommerce_default_catalog_orderby', 'menu_order' );
$orderby_cur     = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( '' === $orderby_cur ) {
	$orderby_cur = $orderby_default;
}

$price_order_cur = isset( $_GET['htoeau_price_order'] ) ? sanitize_text_field( wp_unslash( $_GET['htoeau_price_order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$stock_cur = isset( $_GET['htoeau_stock'] ) ? sanitize_text_field( wp_unslash( $_GET['htoeau_stock'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$cat_cur = isset( $_GET['htoeau_shop_cat'] ) ? sanitize_title( wp_unslash( $_GET['htoeau_shop_cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( is_product_category() ) {
	$t = get_queried_object();
	if ( $t instanceof WP_Term ) {
		$cat_cur = $t->slug;
	}
}

$sort_options = array(
	'menu_order' => __( 'Recommended', 'hello-elementor-child' ),
	'popularity' => __( 'Popularity', 'hello-elementor-child' ),
	'rating'     => __( 'Average rating', 'hello-elementor-child' ),
	'date'       => __( 'Latest', 'hello-elementor-child' ),
);

$price_options = array(
	''           => __( 'Price', 'hello-elementor-child' ),
	'price'      => __( 'Price: low to high', 'hello-elementor-child' ),
	'price-desc' => __( 'Price: high to low', 'hello-elementor-child' ),
);

$cats = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'orderby'    => 'name',
		'number'     => 80,
	)
);

$img_base = function_exists( 'htoeau_child_get_brand_images_base_url' ) ? htoeau_child_get_brand_images_base_url() : '';
$chevron  = $img_base
	? '<img class="htoeau-shop-toolbar__chev" src="' . esc_url( $img_base . 'accordion-closed.svg' ) . '" alt="" width="14" height="24" aria-hidden="true" />'
	: '<svg class="htoeau-shop-toolbar__chev" width="12" height="6" viewBox="0 0 12 6" aria-hidden="true"><path fill="currentColor" d="M6 6 0 0h12z"/></svg>';
?>
<div class="htoeau-shop-toolbar">
	<form class="htoeau-shop-toolbar__form" method="get" action="<?php echo esc_url( $base ); ?>">
		<div class="htoeau-shop-toolbar__row">
			<div class="htoeau-shop-toolbar__filters">
				<label class="htoeau-shop-toolbar__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Sort by price', 'hello-elementor-child' ); ?></span>
					<select class="htoeau-shop-toolbar__select" name="htoeau_price_order">
						<?php foreach ( $price_options as $val => $lab ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $price_order_cur, $val ); ?>><?php echo esc_html( $lab ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="htoeau-shop-toolbar__chev-wrap"><?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</label>

				<?php if ( is_shop() ) : ?>
				<label class="htoeau-shop-toolbar__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Filter by category', 'hello-elementor-child' ); ?></span>
					<select class="htoeau-shop-toolbar__select" name="htoeau_shop_cat">
						<option value=""><?php esc_html_e( 'Category', 'hello-elementor-child' ); ?></option>
						<?php if ( ! is_wp_error( $cats ) && is_array( $cats ) ) : ?>
							<?php foreach ( $cats as $term ) : ?>
								<?php
								if ( ! $term instanceof WP_Term ) {
									continue;
								}
								?>
								<option value="<?php echo esc_attr( $term->slug ); ?>"<?php selected( $cat_cur, $term->slug ); ?>>
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<span class="htoeau-shop-toolbar__chev-wrap"><?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</label>
				<?php endif; ?>

				<label class="htoeau-shop-toolbar__field">
					<span class="screen-reader-text"><?php esc_html_e( 'Availability', 'hello-elementor-child' ); ?></span>
					<select class="htoeau-shop-toolbar__select" name="htoeau_stock">
						<option value=""><?php esc_html_e( 'Availability', 'hello-elementor-child' ); ?></option>
						<option value="instock"<?php selected( $stock_cur, 'instock' ); ?>><?php esc_html_e( 'In stock', 'hello-elementor-child' ); ?></option>
					</select>
					<span class="htoeau-shop-toolbar__chev-wrap"><?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</label>

				<a class="htoeau-shop-toolbar__all-filters" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : (string) get_permalink( wc_get_page_id( 'shop' ) ) ); ?>">
					<?php esc_html_e( 'All filters', 'hello-elementor-child' ); ?>
				</a>
			</div>

			<div class="htoeau-shop-toolbar__meta">
				<p class="htoeau-shop-toolbar__count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: zero-padded or total product count */
							__( '%s products', 'hello-elementor-child' ),
							$total_label
						)
					);
					?>
				</p>
				<span class="htoeau-shop-toolbar__sort-label"><?php esc_html_e( 'Sort by', 'hello-elementor-child' ); ?></span>
				<label class="htoeau-shop-toolbar__field htoeau-shop-toolbar__field--sort">
					<span class="screen-reader-text"><?php esc_html_e( 'Sort products', 'hello-elementor-child' ); ?></span>
					<select class="htoeau-shop-toolbar__select" name="orderby">
						<?php foreach ( $sort_options as $val => $lab ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $orderby_cur, $val ); ?>><?php echo esc_html( $lab ); ?></option>
						<?php endforeach; ?>
					</select>
					<span class="htoeau-shop-toolbar__chev-wrap"><?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</label>
			</div>
		</div>
	</form>
</div>
