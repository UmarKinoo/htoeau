<?php
/**
 * WooCommerce My Account tweaks — hide Downloads, account self-delete.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Downloads are unused (no digital products); remove from account nav.
 *
 * @param array $items Endpoint slug => label.
 * @return array
 */
function htoeau_child_remove_account_downloads_menu_item( $items ) {
	unset( $items['downloads'] );
	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'htoeau_child_remove_account_downloads_menu_item', 20 );

/**
 * Redirect direct /downloads/ visits to Account details.
 */
function htoeau_child_redirect_account_downloads_endpoint() {
	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_account_page() || ! is_wc_endpoint_url( 'downloads' ) ) {
		return;
	}

	wp_safe_redirect( wc_get_account_endpoint_url( 'edit-account' ) );
	exit;
}
add_action( 'template_redirect', 'htoeau_child_redirect_account_downloads_endpoint', 5 );

/**
 * Delete-account form below Account details (GDPR-style self-service).
 */
function htoeau_child_account_delete_form() {
	if ( ! is_user_logged_in() ) {
		return;
	}
	?>
	<section class="htoeau-account-delete" aria-labelledby="htoeau-account-delete-heading">
		<h3 id="htoeau-account-delete-heading"><?php esc_html_e( 'Delete account', 'hello-elementor-child' ); ?></h3>
		<p class="htoeau-account-delete__lead">
			<?php esc_html_e( 'Permanently remove your login and personal details. Your order history is kept for legal and accounting records.', 'hello-elementor-child' ); ?>
		</p>
		<form class="htoeau-account-delete__form" method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Delete your account permanently? This cannot be undone.', 'hello-elementor-child' ) ); ?>');">
			<?php wp_nonce_field( 'htoeau_delete_account', 'htoeau_delete_account_nonce' ); ?>
			<button type="submit" class="woocommerce-button button htoeau-account-delete__btn" name="htoeau_delete_account" value="1">
				<?php esc_html_e( 'Delete my account', 'hello-elementor-child' ); ?>
			</button>
		</form>
	</section>
	<?php
}
add_action( 'woocommerce_after_edit_account_form', 'htoeau_child_account_delete_form' );

/**
 * Process account deletion request.
 */
function htoeau_child_process_account_delete_request() {
	if ( ! isset( $_POST['htoeau_delete_account'] ) || ! is_user_logged_in() ) {
		return;
	}

	if ( empty( $_POST['htoeau_delete_account_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['htoeau_delete_account_nonce'] ) ), 'htoeau_delete_account' ) ) {
		wc_add_notice( __( 'Could not delete your account. Please try again.', 'hello-elementor-child' ), 'error' );
		return;
	}

	$user_id = get_current_user_id();
	if ( ! $user_id || user_can( $user_id, 'manage_options' ) ) {
		wc_add_notice( __( 'This account cannot be deleted from the storefront.', 'hello-elementor-child' ), 'error' );
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/user.php';

	$deleted = wp_delete_user( $user_id );
	if ( ! $deleted ) {
		wc_add_notice( __( 'Could not delete your account. Please contact support.', 'hello-elementor-child' ), 'error' );
		return;
	}

	wp_logout();
	wp_safe_redirect( add_query_arg( 'account_deleted', '1', wc_get_page_permalink( 'myaccount' ) ) );
	exit;
}
add_action( 'template_redirect', 'htoeau_child_process_account_delete_request', 9 );

/**
 * Notice after successful account deletion.
 */
function htoeau_child_account_deleted_notice() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! isset( $_GET['account_deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	wc_add_notice( __( 'Your account has been deleted.', 'hello-elementor-child' ), 'success' );
}
add_action( 'wp', 'htoeau_child_account_deleted_notice' );

/**
 * Minimal styles for delete-account block on My Account.
 */
function htoeau_child_enqueue_my_account_styles() {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}

	wp_add_inline_style(
		'woocommerce-general',
		'.htoeau-account-delete{margin-top:2.5rem;padding-top:2rem;border-top:1px solid rgba(0,0,0,.08)}.htoeau-account-delete__lead{margin:.5rem 0 1rem;color:#4b5563}.htoeau-account-delete__btn{background:#b42318!important;border-color:#b42318!important;color:#fff!important}'
	);
}
add_action( 'wp_enqueue_scripts', 'htoeau_child_enqueue_my_account_styles', 100 );
