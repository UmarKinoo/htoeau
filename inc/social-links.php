<?php
/**
 * Footer / site social profile URLs.
 *
 * @package Hello_Elementor_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Instagram profile URL (@htoeau_official).
 */
function htoeau_child_social_instagram_url() {
	return apply_filters( 'htoeau_footer_instagram_url', 'https://www.instagram.com/htoeau_official/' );
}

/**
 * Facebook page URL.
 */
function htoeau_child_social_facebook_url() {
	return apply_filters( 'htoeau_footer_facebook_url', 'https://www.facebook.com/share/1BBYeraWaP/?mibextid=wwXIfr' );
}

/**
 * LinkedIn company page URL.
 */
function htoeau_child_social_linkedin_url() {
	return apply_filters( 'htoeau_footer_linkedin_url', 'https://www.linkedin.com/company/the-hydrogen-innovation-company' );
}

// Site-wide canonical URLs (override Elementor widget saved values).
add_filter( 'htoeau_footer_instagram_url', static function () {
	return 'https://www.instagram.com/htoeau_official/';
}, 100 );

add_filter( 'htoeau_footer_linkedin_url', static function () {
	return 'https://www.linkedin.com/company/the-hydrogen-innovation-company';
}, 100 );
