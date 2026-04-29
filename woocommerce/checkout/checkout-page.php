<?php
/**
 * Dedicated checkout page wrapper to guarantee classic checkout shortcode render.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="site-main" role="main">
	<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</main>
<?php
get_footer();
