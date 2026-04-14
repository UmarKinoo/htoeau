Hello Elementor Child (HtoEAU PDP)
==================================

Requires: WordPress 6.0+, WooCommerce, Hello Elementor parent theme, Advanced Custom Fields (Free or Pro).

Setup:
1. Install and activate Hello Elementor (parent), WooCommerce, and ACF.
2. Upload this folder to wp-content/themes/hello-elementor-child/ and activate this child theme.
3. Variable products: use a can-count attribute (filter htoeau_can_count_attribute defaults to pa_can-count).
4. Optional ACF on products: feature blurbs, subscribe bullets, transformation steps; per-variation badges.

See DEPLOY.txt for hosting / Elementor Theme Builder notes.

Currency (GBP/USD store): browsing prices are shown in USD or GBP from visitor location (US territories → USD when store is GBP; UK → GBP when store is USD). No on-page switcher; optional ?htoeau_ccy=GBP|USD sets a cookie for testing.

Brand icons sync from assets/images/ to uploads/htoeau-brand-assets/ on first load; bump HTOEAU_BRAND_ASSETS_SYNC_VER in functions.php to force re-copy.

Git (this folder is its own repo)
---------------------------------
- Work here, commit often:  git add -A && git commit -m "Describe change"
- Undo uncommitted edits to one file:  git checkout -- path/to/file.php
- See history:  git log --oneline
- Restore last good commit (discard ALL local changes):  git reset --hard HEAD
- Restore a specific old commit (detached — then branch or cherry-pick):  git checkout <commit-hash>

GitHub (backup + history off your machine)
------------------------------------------
1. Create a new empty repo on GitHub (no README if you already have commits).
2. In this folder:
   git remote add origin https://github.com/YOU/REPO.git
   git push -u origin main
3. Later:  git push  after each commit.

FTP deploy stays separate: push to GitHub does not update the server — upload files or use a deploy action when you are ready.
