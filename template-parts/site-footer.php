<?php
/**
 * Site footer — PDP / Figma “Navbar A/Desktop” (newsletter, company, nav, legal).
 *
 * @package HtoEAU_Child
 */

defined( 'ABSPATH' ) || exit;

$shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );

$footer_nav = apply_filters(
	'htoeau_footer_nav_links',
	array(
		array(
			'label' => __( 'Shop', 'htoeau-child' ),
			'url'   => $shop_url,
		),
		array(
			'label' => __( 'My Account', 'htoeau-child' ),
			'url'   => $account_url,
		),
		array(
			'label' => __( 'About', 'htoeau-child' ),
			'url'   => home_url( '/about/' ),
		),
		array(
			'label' => __( 'Science', 'htoeau-child' ),
			'url'   => home_url( '/science/' ),
		),
		array(
			'label' => __( 'Interview', 'htoeau-child' ),
			'url'   => home_url( '/interview/' ),
		),
		array(
			'label' => __( 'Contact', 'htoeau-child' ),
			'url'   => home_url( '/contact/' ),
		),
		array(
			'label' => __( 'Become an Affiliate', 'htoeau-child' ),
			'url'   => home_url( '/become-an-affiliate/' ),
		),
	)
);

$refund_id  = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'refund_returns' ) : 0;
$refund_url = ( $refund_id > 0 ) ? get_permalink( $refund_id ) : home_url( '/refund-policy/' );

$privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
if ( ! is_string( $privacy_url ) || '' === $privacy_url ) {
	$privacy_url = home_url( '/privacy-policy/' );
}

$terms_id  = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'terms' ) : 0;
$terms_url = ( $terms_id > 0 ) ? get_permalink( $terms_id ) : home_url( '/terms-of-service/' );

$legal_links = apply_filters(
	'htoeau_footer_legal_links',
	array(
		array(
			'label' => __( 'Refund Policy', 'htoeau-child' ),
			'url'   => $refund_url,
		),
		array(
			'label' => __( 'Privacy Policy', 'htoeau-child' ),
			'url'   => $privacy_url,
		),
		array(
			'label' => __( 'Terms of Service', 'htoeau-child' ),
			'url'   => $terms_url,
		),
		array(
			'label' => __( 'Accessibility', 'htoeau-child' ),
			'url'   => home_url( '/accessibility/' ),
		),
	)
);

$newsletter_action = apply_filters( 'htoeau_footer_newsletter_form_action', '' );
$newsletter_stub   = '' === $newsletter_action;
$form_action       = $newsletter_stub ? '#' : $newsletter_action;
$copyright_text    = apply_filters(
	'htoeau_footer_copyright_text',
	/* translators: 1: year, 2: site name */
	sprintf( __( '© %1$s %2$s. All rights reserved', 'htoeau-child' ), gmdate( 'Y' ), 'HtoEAU' )
);
?>
<div class="htoeau-site-footer">
	<div class="htoeau-site-footer__inner">
		<section class="htoeau-site-footer__newsletter" aria-labelledby="htoeau-footer-newsletter-heading">
			<div class="htoeau-site-footer__newsletter-copy">
				<h2 id="htoeau-footer-newsletter-heading" class="htoeau-site-footer__newsletter-title">
					<?php esc_html_e( 'Stay Informed', 'htoeau-child' ); ?>
				</h2>
				<p class="htoeau-site-footer__newsletter-desc">
					<?php esc_html_e( 'Performance insights, hydration research, and product updates from HtoEAU.', 'htoeau-child' ); ?>
				</p>
			</div>
			<form
				class="htoeau-site-footer__newsletter-form"
				method="post"
				action="<?php echo esc_url( $form_action ); ?>"
				<?php echo $newsletter_stub ? ' data-htoeau-newsletter-stub="1"' : ''; ?>
			>
				<?php if ( ! $newsletter_stub ) : ?>
					<?php wp_nonce_field( 'htoeau_footer_newsletter', 'htoeau_footer_newsletter_nonce' ); ?>
				<?php endif; ?>
				<div class="htoeau-site-footer__newsletter-cta">
					<label class="screen-reader-text" for="htoeau-footer-email"><?php esc_html_e( 'Email address', 'htoeau-child' ); ?></label>
					<input
						id="htoeau-footer-email"
						class="htoeau-site-footer__email-input"
						type="email"
						name="htoeau_footer_email"
						autocomplete="email"
						placeholder="<?php echo esc_attr__( 'Enter email address', 'htoeau-child' ); ?>"
					/>
					<button type="submit" class="htoeau-site-footer__signup-btn">
						<?php esc_html_e( 'Sign up now', 'htoeau-child' ); ?>
					</button>
				</div>
			</form>
		</section>

		<hr class="htoeau-site-footer__rule" />

		<div class="htoeau-site-footer__main">
			<div class="htoeau-site-footer__brand-block">
				<a class="htoeau-site-footer__logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="screen-reader-text"><?php bloginfo( 'name' ); ?></span>
					<svg class="htoeau-site-footer__logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 136 28" aria-hidden="true" focusable="false">
						<text x="0" y="21" fill="currentColor" font-size="21" font-weight="600" font-family="Figtree, system-ui, sans-serif">Ht</text>
						<g transform="translate(28, 3)" fill="none" stroke="currentColor" stroke-width="1.75">
							<circle cx="10" cy="10" r="8.5" />
							<line x1="3" y1="10" x2="17" y2="10" stroke-linecap="round" />
						</g>
						<text x="51" y="21" fill="currentColor" font-size="21" font-weight="600" font-family="Figtree, system-ui, sans-serif">EAU</text>
					</svg>
				</a>
				<address class="htoeau-site-footer__address">
					<?php echo wp_kses_post( __( 'The Hydrogen Innovation Company B.V.,<br />Keizersgracht 62, 1015 CS Amsterdam,<br />The Netherlands', 'htoeau-child' ) ); ?>
				</address>
				<p class="htoeau-site-footer__contact-line">
					<a href="mailto:hello@HtoEAU.com">hello@HtoEAU.com</a>
				</p>
				<p class="htoeau-site-footer__contact-line"><?php esc_html_e( 'KvK: 92794076', 'htoeau-child' ); ?></p>
				<p class="htoeau-site-footer__contact-line"><?php esc_html_e( 'VAT: NL862936330B01', 'htoeau-child' ); ?></p>
			</div>
			<nav class="htoeau-site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'htoeau-child' ); ?>">
				<ul class="htoeau-site-footer__nav-list">
					<?php foreach ( $footer_nav as $item ) : ?>
						<?php
						if ( empty( $item['url'] ) || empty( $item['label'] ) ) {
							continue;
						}
						?>
						<li>
							<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</div>

		<div class="htoeau-site-footer__bottom-wrap">
			<hr class="htoeau-site-footer__rule" />
			<div class="htoeau-site-footer__bottom">
				<p class="htoeau-site-footer__copyright"><?php echo esc_html( $copyright_text ); ?></p>
				<ul class="htoeau-site-footer__legal">
					<?php foreach ( $legal_links as $item ) : ?>
						<?php
						if ( empty( $item['url'] ) || empty( $item['label'] ) ) {
							continue;
						}
						?>
						<li>
							<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</div>
