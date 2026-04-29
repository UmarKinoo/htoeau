<?php
/**
 * Dedicated cart page wrapper to guarantee classic cart shortcode render.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main" role="main">
	<?php echo do_shortcode( '[woocommerce_cart]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php
get_footer();
