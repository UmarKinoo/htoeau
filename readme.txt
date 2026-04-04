Hello Elementor Child (HtoEAU PDP)
==================================

Requires: WordPress 6.0+, WooCommerce, Hello Elementor parent theme, Advanced Custom Fields (Free or Pro).

Setup:
1. Install and activate Hello Elementor (parent), WooCommerce, and ACF.
2. Upload this folder to wp-content/themes/hello-elementor-child/ and activate this child theme.
3. Variable products: use a can-count attribute (filter htoeau_can_count_attribute defaults to pa_can-count).
4. Optional ACF on products: feature blurbs, subscribe bullets, transformation steps; per-variation badges.

See DEPLOY.txt for hosting / Elementor Theme Builder notes.

Brand icons sync from assets/images/ to uploads/htoeau-brand-assets/ on first load; bump HTOEAU_BRAND_ASSETS_SYNC_VER in functions.php to force re-copy.
