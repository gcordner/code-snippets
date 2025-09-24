<?php
/*
* GeneratePress: Move Featured Image Above Title on Archives
*
* Purpose:
* Reorder the featured image placement in GeneratePress archive-style listings so that the image
* displays before the post title, improving visual hierarchy and card-style layouts.
*
* What it does:
* 1) On archive views (category/tag/tax/date/author), the blog posts index (is_home), and search results,
*    unhooks GeneratePress’s default `generate_post_image` from `generate_after_entry_header`.
* 2) Re-hooks `generate_post_image` earlier at `generate_before_entry_title` so images show above titles.
* 3) Leaves single posts/pages unaffected.
*
* Why needed:
* Many designs look more natural and scannable when featured images appear above titles in lists of posts.
* Using hooks instead of template overrides keeps the change portable and update-safe.
*
* Client benefit:
* Cleaner archive cards with a consistent visual emphasis on imagery, achieved without copying template files.
*
* Hook timing:
* Uses the `wp` action so conditional tags (`is_archive()`, `is_home()`, `is_search()`) are reliable
* and theme hooks are already registered before modifying them.
*
* Usage:
* Copy this snippet into your child theme’s `functions.php` or into a small functionality plugin.
* Do not remove `do_action( 'generate_after_entry_header' )` or `do_action( 'generate_before_entry_title' )`
* calls in the theme templates—this snippet simply repositions the callback on targeted views.
*
* Adjustments:
* - To exclude the blog index, remove `is_home()` from the condition.
* - To also apply on single posts, add `|| is_single()` to the condition.
*/

/**
 * Move featured image above title on archives (category/tag/tax/date/author),
 * the blog posts index, and search results.
 *
 * @return void
 */
add_action(
	'wp',
	function () {
		if ( is_archive() || is_home() || is_search() ) {
			remove_action( 'generate_after_entry_header', 'generate_post_image', 10 );
			add_action( 'generate_before_entry_title', 'generate_post_image', 5 );
		}
	}
);
