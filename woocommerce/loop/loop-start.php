<?php
/**
 * Product loop start — HtoEAU grid.
 *
 * @package Hello_Elementor_Child
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="products htoeau-shop-grid columns-<?php echo esc_attr( wc_get_loop_prop( 'columns' ) ); ?>">
